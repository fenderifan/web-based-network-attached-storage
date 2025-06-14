<?php
    function write_log($message) {
        error_log($message, 0);
    }
    function get_cpu_usage() {
        if (stristr(PHP_OS, 'WIN')) {
            return 0.0;
        }
        $statFile = '/proc/stat';
        $cacheFile = sys_get_temp_dir() . '/php_cpu_usage.cache';
        if (!is_readable($statFile)) return 0.0;
        $fh = @fopen($statFile, 'r');
        if (!$fh) return 0.0;
        $stat_line = fgets($fh);
        fclose($fh);
        $info = array_slice(preg_split('/\s+/', trim($stat_line)), 1);
        $total = array_sum($info);
        $idle = $info[3] ?? 0;
        $prev_stats = file_exists($cacheFile) ? unserialize(file_get_contents($cacheFile)) : null;
        $cpu_usage = 0.0;
        if (is_array($prev_stats) && isset($prev_stats['total'], $prev_stats['idle'])) {
            $total_diff = $total - $prev_stats['total'];
            $idle_diff = $idle - $prev_stats['idle'];
            if ($total_diff > 0) {
                $cpu_usage = (100.0 * ($total_diff - $idle_diff) / $total_diff);
            }
        }
        file_put_contents($cacheFile, serialize(['total' => $total, 'idle' => $idle]));

        return round($cpu_usage, 1);
    }
    function get_ram_usage() {
        if (stristr(PHP_OS, 'WIN')) {
            return ['used_kb' => 0, 'total_kb' => 0, 'percent' => 0];
        }
        $free = @shell_exec('free');
        if (empty($free)) {
            return ['used_kb' => 0, 'total_kb' => 0, 'percent' => 0];
        }
        $free_arr = explode("\n", trim($free));
        $mem = preg_split('/\s+/', $free_arr[1]);
        $total_mem_kb = $mem[1] ?? 0;
        $used_mem_kb = $mem[2] ?? 0;
        if ($total_mem_kb == 0) {
            return ['used_kb' => 0, 'total_kb' => 0, 'percent' => 0];
        }
        $memory_usage_percent = ($used_mem_kb / $total_mem_kb) * 100;
        return [
            'used_kb' => (int)$used_mem_kb,
            'total_kb' => (int)$total_mem_kb,
            'percent' => round($memory_usage_percent),
        ];
    }
    function format_ram($kb) {
        if ($kb >= 1048576) { // 1 GB in KB
            return number_format($kb / 1048576, 1) . ' GB';
        }
        return number_format($kb / 1024, 0) . ' MB';
    }
    function format_bytes($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        if ($bytes > 0) return $bytes . ' B';
        return '0 B';
    }
    function calculate_stats(array $statsArray) {
        if (empty($statsArray)) {
            return ['peak' => 0, 'avg' => 0];
        }
        $peak = max($statsArray);
        $avg = array_sum($statsArray) / count($statsArray);
        return ['peak' => round($peak, 1), 'avg' => round($avg, 1)];
    }
?>
