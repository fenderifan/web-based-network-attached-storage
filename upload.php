<?php
// --- CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 600); // Increased execution time
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logging.php';

$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

// --- PATH SETUP ---
$baseDir = __DIR__ . '/files';
$tempDir = __DIR__ . '/files/.tmp';

if (!file_exists($tempDir)) {
    @mkdir($tempDir, 0777, true);
}

// --- VALIDATE REQUEST ---
if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMessage = 'Upload error. Code: ' . ($_FILES['fileToUpload']['error'] ?? 'Unknown');
    write_log($errorMessage);
    echo $errorMessage;
    exit;
}

// --- GET METADATA ---
$chunkNumber = isset($_POST['chunkNumber']) ? (int)$_POST['chunkNumber'] : 0;
$totalChunks = isset($_POST['totalChunks']) ? (int)$_POST['totalChunks'] : 0;
$originalName = isset($_POST['fileName']) ? $_POST['fileName'] : $_FILES['fileToUpload']['name'];
$targetFolder = isset($_POST['targetFolder']) ? trim($_POST['targetFolder'], '/') : '';
$fileSize = isset($_POST['fileSize']) ? (int)$_POST['fileSize'] : 0;

// --- SANITIZE AND PREPARE PATHS ---
$safeFileName = preg_replace("/([^a-zA-Z0-9\._\s-]+)/", "", basename($originalName));
// Make the identifier more unique to avoid collisions with same-name files
$uploadIdentifier = md5($safeFileName . $fileSize . ($targetFolder ?? ''));

$finalDirectory = $baseDir . ($targetFolder ? "/$targetFolder" : '');
if (!file_exists($finalDirectory)) {
    @mkdir($finalDirectory, 0777, true);
}

// --- LOGGING PROGRESS (ONCE PER SECOND) ---
$logTimeFile = $tempDir . '/' . $uploadIdentifier . '.logtime';
$startTimeFile = $tempDir . '/' . $uploadIdentifier . '.starttime';
$statsFile = $tempDir . '/' . $uploadIdentifier . '.stats'; // For storing performance stats

// On the first chunk, record the start time of the entire upload.
if ($chunkNumber === 0) {
    file_put_contents($startTimeFile, microtime(true));
    file_put_contents($logTimeFile, '0'); // Initialize log time tracker
    file_put_contents($statsFile, serialize([])); // Initialize stats file
}

$lastLogTime = file_exists($logTimeFile) ? (float)file_get_contents($logTimeFile) : 0;
$currentTime = microtime(true);

// Check if at least 1 second has passed since the last log write.
if ($currentTime - $lastLogTime >= 1) {
    $ram = get_ram_usage();
    $cpu = get_cpu_usage();

    $uploadStartTime = file_exists($startTimeFile) ? (float)file_get_contents($startTimeFile) : $currentTime;
    $timeElapsed = $currentTime - $uploadStartTime;
    // Estimate bytes uploaded based on chunk progress
    $bytesUploaded = ($chunkNumber / $totalChunks) * $fileSize;
    $speed = $timeElapsed > 0.1 ? ($bytesUploaded / $timeElapsed) / (1024 * 1024) : 0; // MB/s

    // Read existing stats, add current, and write back
    $statsLog = file_exists($statsFile) ? unserialize(file_get_contents($statsFile)) : [];
    $statsLog[] = [
       'cpu' => $cpu,
       'ram_kb' => $ram['used_kb'],
       'ram_pct' => $ram['percent'],
       'speed_mbps' => $speed
    ];
    file_put_contents($statsFile, serialize($statsLog));

    write_log(sprintf(
        'Uploading "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s)',
        $safeFileName,
        $cpu,
        format_ram($ram['used_kb']),
        $ram['percent'],
        $speed
    ));

    // Update the last log time.
    file_put_contents($logTimeFile, $currentTime);
}

// --- PROCESS THE CHUNK ---
$chunkPath = $tempDir . '/' . $uploadIdentifier . '.part' . $chunkNumber;
if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $chunkPath)) {
    http_response_code(500);
    $errorMessage = "Failed to move uploaded chunk to temporary directory.";
    write_log($errorMessage);
    echo $errorMessage;
    // Clean up tracking files on failure
    @unlink($logTimeFile);
    @unlink($startTimeFile);
    @unlink($statsFile);
    exit;
}

// --- ASSEMBLE THE FILE (if it's the last chunk) ---
$isLastChunk = ($chunkNumber === $totalChunks - 1);

if ($isLastChunk) {
    $processingStartTime = microtime(true);
    write_log('Processing "' . $safeFileName . '"');

    $finalDestinationPath = $finalDirectory . '/' . $safeFileName;
    $finalName = $safeFileName;
    $counter = 1;
    // Check for name conflicts and rename if necessary
    while (file_exists($finalDestinationPath)) {
        $nameWithoutExt = pathinfo($safeFileName, PATHINFO_FILENAME);
        $extension = pathinfo($safeFileName, PATHINFO_EXTENSION);
        $finalName = $nameWithoutExt . " ($counter)" . ($extension ? '.' . $extension : '');
        $finalDestinationPath = $finalDirectory . '/' . $finalName;
        $counter++;
    }

    // Log if the file was renamed
    if ($finalName !== $safeFileName) {
        write_log('File name conflict. Renamed "' . $safeFileName . '" to "' . $finalName . '"');
    }

    $finalFile = @fopen($finalDestinationPath, 'wb');
    if (!$finalFile) {
        http_response_code(500);
        write_log("Failed to open final file for writing: " . $finalDestinationPath);
        exit;
    }

    // Combine all chunks
    for ($i = 0; $i < $totalChunks; $i++) {
        $partPath = $tempDir . '/' . $uploadIdentifier . '.part' . $i;
        $chunkStream = fopen($partPath, 'rb');
        if ($chunkStream === false) {
            fclose($finalFile);
            unlink($finalDestinationPath); // Clean up partially created file
            http_response_code(500);
            write_log("Failed to read chunk #$i.");
            exit;
        }
        stream_copy_to_stream($chunkStream, $finalFile);
        fclose($chunkStream);
        unlink($partPath); // Clean up chunk
    }
    fclose($finalFile);

    // --- FINAL LOG WITH CORRECT TIMING ---
    $uploadStartTime = (float)file_get_contents($startTimeFile);
    $totalTime = microtime(true) - $uploadStartTime;
    $processingTime = microtime(true) - $processingStartTime;
    // Uploading time is total time minus the final processing/assembly time
    $uploadTime = max(0, $totalTime - $processingTime);

    write_log(sprintf(
        'Uploaded "%s" (%s) in %.1f sec (Uploading: %.1f sec, Processing: %.1f sec)',
        $finalName,
        format_bytes($fileSize),
        $totalTime,
        $uploadTime,
        $processingTime
    ));

    // --- LOG PEAK/AVERAGE STATS ---
    $statsLog = file_exists($statsFile) ? unserialize(file_get_contents($statsFile)) : [];
    $firstTenStatsLog = array_slice($statsLog, 0, 10);

    // Log Peak/Average for the first 10 seconds
    if (!empty($firstTenStatsLog)) {
        $cpuStats = calculate_stats(array_column($firstTenStatsLog, 'cpu'));
        $ramPctStats = calculate_stats(array_column($firstTenStatsLog, 'ram_pct'));
        $ramSizeStats = calculate_stats(array_column($firstTenStatsLog, 'ram_kb'));
        $speedStats = calculate_stats(array_column($firstTenStatsLog, 'speed_mbps'));
        write_log(sprintf(
            'Upload Stats (first 10s Peak/Avg): CPU (%.1f%%/%.1f%%), RAM (%s/%s | %d%%/%d%%), Speed (%.1f MBps/%.1f MBps)',
            $cpuStats['peak'], $cpuStats['avg'],
            format_ram($ramSizeStats['peak']), format_ram($ramSizeStats['avg']),
            $ramPctStats['peak'], $ramPctStats['avg'],
            $speedStats['peak'], $speedStats['avg']
        ));
    }
    
    // Log Peak/Average for the entire duration
    if (!empty($statsLog)) {
        $cpuStats = calculate_stats(array_column($statsLog, 'cpu'));
        $ramPctStats = calculate_stats(array_column($statsLog, 'ram_pct'));
        $ramSizeStats = calculate_stats(array_column($statsLog, 'ram_kb'));
        $speedStats = calculate_stats(array_column($statsLog, 'speed_mbps'));
        write_log(sprintf(
            'Upload Stats (Total Peak/Avg): CPU (%.1f%%/%.1f%%), RAM (%s/%s | %d%%/%d%%), Speed (%.1f MBps/%.1f MBps)',
            $cpuStats['peak'], $cpuStats['avg'],
            format_ram($ramSizeStats['peak']), format_ram($ramSizeStats['avg']),
            $ramPctStats['peak'], $ramPctStats['avg'],
            $speedStats['peak'], $speedStats['avg']
        ));
    }


    // Clean up tracking files
    @unlink($logTimeFile);
    @unlink($startTimeFile);
    @unlink($statsFile);

    echo "Upload complete: " . $finalName;
} else {
    http_response_code(200);
    echo "Chunk #$chunkNumber of $totalChunks received.";
}