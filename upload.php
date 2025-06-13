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
    mkdir($tempDir, 0777, true);
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
$clientStartTime = isset($_POST['startTime']) ? (float)$_POST['startTime'] : microtime(true);
$fileSize = isset($_POST['fileSize']) ? (int)$_POST['fileSize'] : 0;


// --- SANITIZE AND PREPARE PATHS ---
$safeFileName = preg_replace("/([^a-zA-Z0-9\._\s-]+)/", "", basename($originalName));
$uploadIdentifier = md5($safeFileName);

$finalDirectory = $baseDir . ($targetFolder ? "/$targetFolder" : '');
if (!file_exists($finalDirectory)) {
    mkdir($finalDirectory, 0777, true);
}

// --- LOGGING UPLOAD PROGRESS ---
$ram = get_ram_usage();
$cpu = get_cpu_usage();
$bytesUploaded = ($chunkNumber + 1) * (20 * 1024 * 1024); // Based on new 20MB chunk size
$timeElapsed = microtime(true) - $clientStartTime;
$speed = $timeElapsed > 0 ? ($bytesUploaded / $timeElapsed) / (1024 * 1024) : 0; // MB/s

write_log(sprintf(
    'Uploading "%s" (Cpu Usage : %s, Ram Usage : %.1f GB / %d %%, Transfer Speed : %.1f MB/s)',
    $safeFileName,
    $cpu,
    $ram['size'],
    $ram['percent'],
    $speed
));


// --- PROCESS THE CHUNK ---
$chunkPath = $tempDir . '/' . $uploadIdentifier . '.part' . $chunkNumber;
if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $chunkPath)) {
    http_response_code(500);
    $errorMessage = "Failed to move uploaded chunk to temporary directory.";
    write_log($errorMessage);
    echo $errorMessage;
    exit;
}

// --- ASSEMBLE THE FILE (if it's the last chunk) ---
$isLastChunk = ($chunkNumber === $totalChunks - 1);

if ($isLastChunk) {
    $processingStartTime = microtime(true);
    write_log('Processing "' . $safeFileName . '"');

    $finalDestinationPath = $finalDirectory . '/' . $safeFileName;
    $counter = 1;
    $nameWithoutExt = pathinfo($safeFileName, PATHINFO_FILENAME);
    $extension = pathinfo($safeFileName, PATHINFO_EXTENSION);
    while (file_exists($finalDestinationPath)) {
        $newName = $nameWithoutExt . " ($counter)" . ($extension ? '.' . $extension : '');
        $finalDestinationPath = $finalDirectory . '/' . $newName;
        $counter++;
    }

    $finalFile = fopen($finalDestinationPath, 'wb');
    if (!$finalFile) {
        http_response_code(500);
        write_log("Failed to open final file for writing: " . $finalDestinationPath);
        exit;
    }

    for ($i = 0; $i < $totalChunks; $i++) {
        $partPath = $tempDir . '/' . $uploadIdentifier . '.part' . $i;
        $chunkStream = fopen($partPath, 'rb');
        if ($chunkStream === false) {
            fclose($finalFile);
            unlink($finalDestinationPath);
            http_response_code(500);
            write_log("Failed to read chunk #$i.");
            exit;
        }
        stream_copy_to_stream($chunkStream, $finalFile);
        fclose($chunkStream);
        unlink($partPath);
    }
    fclose($finalFile);

    $processingTime = microtime(true) - $processingStartTime;
    $uploadTime = $processingStartTime - $clientStartTime;
    $totalTime = microtime(true) - $clientStartTime;

    function format_bytes($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    write_log(sprintf(
        'Uploaded "%s" (%s) in %.0f sec (Uploading : %.0f sec, Processing : %.0f sec)',
        basename($finalDestinationPath),
        format_bytes($fileSize),
        $totalTime,
        $uploadTime,
        $processingTime
    ));

    echo "Upload complete: " . basename($finalDestinationPath);
} else {
    http_response_code(200);
    echo "Chunk #$chunkNumber of $totalChunks received.";
}
