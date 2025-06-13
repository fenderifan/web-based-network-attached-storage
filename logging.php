<?php
// logging.php

/**
 * Gets the current CPU usage percentage on Linux.
 * This is a more accurate, non-blocking version that calculates usage
 * based on the change in /proc/stat over time.
 *
 * @return string The CPU usage percentage or "N/A".
 */
function get_cpu_usage() {
    // This function is designed for Linux.
    if (stristr(PHP_OS, 'WIN')) {
        return 'N/A';
    }

    $statFile = '/proc/stat';
    $cacheFile = sys_get_temp_dir() . '/php_cpu_usage.cache';

    if (!is_readable($statFile)) {
        return 'N/A';
    }

    // Read the first line of /proc/stat which contains the aggregate CPU times
    $fh = @fopen($statFile, 'r');
    if (!$fh) return 'N/A';
    $stat_line = fgets($fh);
    fclose($fh);

    $info = array_slice(preg_split('/\s+/', trim($stat_line)), 1);

    // Sum of all times
    $total = array_sum($info);
    // Idle time is the 4th value in the array (index 3)
    $idle = $info[3] ?? 0;

    // Get previous stats from cache
    $prev_stats = file_exists($cacheFile) ? unserialize(file_get_contents($cacheFile)) : null;

    $cpu_usage = 0.0;
    if (is_array($prev_stats) && isset($prev_stats['total'], $prev_stats['idle'])) {
        $total_diff = $total - $prev_stats['total'];
        $idle_diff = $idle - $prev_stats['idle'];

        if ($total_diff > 0) {
            // Calculate the percentage of time spent NOT being idle
            $cpu_usage = (100.0 * ($total_diff - $idle_diff) / $total_diff);
        }
    }

    // Cache current stats for the next run
    file_put_contents($cacheFile, serialize(['total' => $total, 'idle' => $idle]));

    return round($cpu_usage, 1) . '%';
}


/**
 * Gets the current RAM usage on Linux.
 *
 * @return array An array containing used RAM in GB and the usage percentage.
 */
function get_ram_usage() {
    if (stristr(PHP_OS, 'WIN')) {
        // Windows doesn't have a simple way to get this via PHP.
        return ['size' => 0, 'percent' => 0];
    }

    $free = @shell_exec('free');
    if (empty($free)) {
         return ['size' => 0, 'percent' => 0];
    }
    
    $free_arr = explode("\n", trim($free));
    $mem = preg_split('/\s+/', $free_arr[1]);

    // Indexes: 1=total, 2=used, 3=free
    $total_mem = $mem[1] ?? 0;
    $used_mem = $mem[2] ?? 0;
    
    if ($total_mem == 0) {
        return ['size' => 0, 'percent' => 0];
    }

    $memory_usage_percent = ($used_mem / $total_mem) * 100;

    return [
        'size' => round($used_mem / 1048576, 1), // Used RAM in GB (from KB)
        'percent' => round($memory_usage_percent),
    ];
}

/**
 * A centralized function to write messages to the PHP error log.
 *
 * @param string $message The message to log.
 */
function write_log($message) {
    // The '0' parameter sends the message to the system's configured PHP error log.
    error_log($message, 0);
}

?>
