<?php
// logging.php

/**
 * Gets the current CPU usage.
 * This is a simplified version and might not work on all systems, especially Windows.
 * It's more reliable on Linux systems.
 *
 * @return string The CPU usage percentage or "N/A".
 */
function get_cpu_usage() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        // Assuming 1 core, a load of 1.00 is 100% utilization.
        // This is a rough estimate and can be improved.
        return ($load[0] * 100) . '%';
    }
    return 'N/A';
}

/**
 * Gets the current RAM usage.
 *
 * @return array An array containing used RAM in GB and the usage percentage.
 */
function get_ram_usage() {
    if (stristr(PHP_OS, 'WIN')) {
        // Windows doesn't have a simple way to get this via PHP.
        return ['size' => 'N/A', 'percent' => 'N/A'];
    }

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2] / $mem[1] * 100;

    return [
        'size' => round($mem[2] / 1048576, 1), // Used RAM in GB
        'percent' => round($memory_usage),
    ];
}

/**
 * A centralized function to write messages to the error log.
 *
 * @param string $message The message to log.
 */
function write_log($message) {
    // The second parameter '0' means the message is sent to the configured PHP error log.
    error_log($message, 0);
}

?>
