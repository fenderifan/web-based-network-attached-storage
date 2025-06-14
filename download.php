<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/logging.php';
    $settings = load_settings();
    date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');
    $baseDir = realpath(__DIR__ . '/files');
    $requestedPath = $_GET['path'] ?? '';
    if (!$requestedPath) {
        http_response_code(400);
        exit("Missing path.");
    }
    $relativePath = ltrim(str_replace('/files', '', rawurldecode($requestedPath)), '/');
    $fullPath = realpath($baseDir . '/' . $relativePath);

    if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
        http_response_code(403);
        exit("Access Denied or file not found.");
    }
    setcookie('download_in_progress', 'true', ['expires' => time() + 60, 'path' => '/']);
    $filename = basename($fullPath);
    $filesize = filesize($fullPath);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $filesize);
    flush();
    $downloadStartTime = microtime(true);
    $logInterval = 1;
    $lastLogTime = $downloadStartTime;
    $bytesSent = 0;
    $statsLog = [];
    $firstTenStatsLog = [];
    $file = @fopen($fullPath, 'rb');
    if ($file) {
        while (!feof($file) && !connection_aborted()) {
            echo fread($file, 1024 * 1024);
            flush();
            $bytesSent = ftell($file);
            $currentTime = microtime(true);
            if ($currentTime - $lastLogTime >= $logInterval) {
                $ram = get_ram_usage();
                $cpu = get_cpu_usage();
                $timeElapsed = $currentTime - $downloadStartTime;
                $speed = $timeElapsed > 0 ? ($bytesSent / $timeElapsed) / (1024 * 1024) : 0;
                $currentStats = [
                    'cpu' => $cpu,
                    'ram_kb' => $ram['used_kb'],
                    'ram_pct' => $ram['percent'],
                    'speed_mbps' => $speed
                ];
                $statsLog[] = $currentStats;
                if(count($statsLog) <= 10) {
                    $firstTenStatsLog[] = $currentStats;
                }
                write_log(sprintf(
                    'Downloading "%s" (Cpu: %.1f%%, Ram: %s / %d%%, Speed: %.1f MB/s)',
                    $filename, $cpu, format_ram($ram['used_kb']), $ram['percent'], $speed
                ));
                $lastLogTime = $currentTime;
            }
        }
        fclose($file);
    }
    setcookie('download_in_progress', '', ['expires' => time() - 3600, 'path' => '/']);
    $totalTime = microtime(true) - $downloadStartTime;
    write_log(sprintf(
        'Downloaded "%s" (%s) in %.1f sec',
        $filename, format_bytes($filesize), $totalTime
    ));
    if (!empty($firstTenStatsLog)) {
        $cpuStats = calculate_stats(array_column($firstTenStatsLog, 'cpu'));
        $ramPctStats = calculate_stats(array_column($firstTenStatsLog, 'ram_pct'));
        $ramSizeStats = calculate_stats(array_column($firstTenStatsLog, 'ram_kb'));
        $speedStats = calculate_stats(array_column($firstTenStatsLog, 'speed_mbps'));
        write_log(sprintf(
            'Download Stats (first 10s Peak/Avg): CPU (%.1f%% / %.1f%%), RAM (%s (%d%%) / %s (%d%%)), Speed (%.1f MBps / %.1f MBps)',
            $cpuStats['peak'], $cpuStats['avg'],
            format_ram($ramSizeStats['peak']), $ramPctStats['peak'],
            format_ram($ramSizeStats['avg']), $ramPctStats['avg'],
            $speedStats['peak'], $speedStats['avg']
        ));
    }
    if (!empty($statsLog)) {
        $cpuStats = calculate_stats(array_column($statsLog, 'cpu'));
        $ramPctStats = calculate_stats(array_column($statsLog, 'ram_pct'));
        $ramSizeStats = calculate_stats(array_column($statsLog, 'ram_kb'));
        $speedStats = calculate_stats(array_column($statsLog, 'speed_mbps'));
        write_log(sprintf(
            'Download Stats (Total Peak/Avg): CPU (%.1f%% / %.1f%%), RAM (%s (%d%%) / %s (%d%%)), Speed (%.1f MBps / %.1f MBps)',
            $cpuStats['peak'], $cpuStats['avg'],
            format_ram($ramSizeStats['peak']), $ramPctStats['peak'],
            format_ram($ramSizeStats['avg']), $ramPctStats['avg'],
            $speedStats['peak'], $speedStats['avg']
        ));
    }
    exit;
?>