<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['status']);
    }

    /**
     * Get system metrics (CPU, memory, uptime).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics()
    {
        $metrics = [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'uptime' => $this->getSystemUptime(),
            'disk' => $this->getDiskUsage(),
        ];

        return response()->json([
            'success' => true,
            'metrics' => $metrics
        ]);
    }

    /**
     * Get current server time (Asia/Jerusalem timezone).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function serverTime()
    {
        $serverTime = Carbon::now('Asia/Jerusalem');

        return response()->json([
            'success' => true,
            'serverTime' => [
                'datetime' => $serverTime->toDateTimeString(),
                'timezone' => 'Asia/Jerusalem',
                'timestamp' => $serverTime->timestamp,
                'formatted' => $serverTime->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Health check endpoint.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'status' => 'online',
            'version' => '1.0.0',
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Get CPU usage.
     *
     * @return array
     */
    private function getCpuUsage()
    {
        $cpu = [
            'usage' => 0,
            'load' => [],
        ];

        // Get system load average (Linux/Unix only)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpu['load'] = [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];

            // Calculate approximate CPU usage percentage (1-minute load)
            // Assumes single core; adjust for multi-core systems
            $cpu['usage'] = min(round($load[0] * 100, 2), 100);
        }

        return $cpu;
    }

    /**
     * Get memory usage.
     *
     * @return array
     */
    private function getMemoryUsage()
    {
        $memory = [
            'total' => 0,
            'free' => 0,
            'used' => 0,
            'usagePercent' => 0,
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - limited info available
            $memory['phpUsage'] = memory_get_usage(true);
            $memory['phpUsageFormatted'] = $this->formatBytes(memory_get_usage(true));
        } else {
            // Linux/Unix - read from /proc/meminfo
            if (file_exists('/proc/meminfo')) {
                $meminfo = file_get_contents('/proc/meminfo');

                if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matches)) {
                    $memory['total'] = (int)$matches[1] * 1024; // Convert KB to bytes
                }

                if (preg_match('/MemFree:\s+(\d+)/', $meminfo, $matches)) {
                    $memory['free'] = (int)$matches[1] * 1024;
                }

                if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $matches)) {
                    $memory['available'] = (int)$matches[1] * 1024;
                }

                $memory['used'] = $memory['total'] - $memory['free'];
                $memory['usagePercent'] = $memory['total'] > 0
                    ? round(($memory['used'] / $memory['total']) * 100, 2)
                    : 0;

                $memory['totalFormatted'] = $this->formatBytes($memory['total']);
                $memory['usedFormatted'] = $this->formatBytes($memory['used']);
                $memory['freeFormatted'] = $this->formatBytes($memory['free']);
            }
        }

        return $memory;
    }

    /**
     * Get system uptime.
     *
     * @return array
     */
    private function getSystemUptime()
    {
        $uptime = [
            'seconds' => 0,
            'formatted' => 'Unknown',
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - use systeminfo command (slower)
            $output = shell_exec('net statistics workstation');
            if ($output && preg_match('/Statistics since\s+(.+)/', $output, $matches)) {
                try {
                    $bootTime = Carbon::parse($matches[1]);
                    $uptime['seconds'] = $bootTime->diffInSeconds(Carbon::now());
                    $uptime['formatted'] = $bootTime->diffForHumans(Carbon::now(), true);
                } catch (\Exception $e) {
                    // Failed to parse
                }
            }
        } else {
            // Linux/Unix - read from /proc/uptime
            if (file_exists('/proc/uptime')) {
                $uptimeData = file_get_contents('/proc/uptime');
                $uptimeArray = explode(' ', $uptimeData);
                $uptime['seconds'] = (int)$uptimeArray[0];

                $days = floor($uptime['seconds'] / 86400);
                $hours = floor(($uptime['seconds'] % 86400) / 3600);
                $minutes = floor(($uptime['seconds'] % 3600) / 60);

                $uptime['formatted'] = sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes);
            }
        }

        return $uptime;
    }

    /**
     * Get disk usage.
     *
     * @return array
     */
    private function getDiskUsage()
    {
        $disk = [
            'total' => 0,
            'free' => 0,
            'used' => 0,
            'usagePercent' => 0,
        ];

        $path = base_path();

        if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
            $total = disk_total_space($path);
            $free = disk_free_space($path);

            if ($total && $free) {
                $disk['total'] = $total;
                $disk['free'] = $free;
                $disk['used'] = $total - $free;
                $disk['usagePercent'] = round(($disk['used'] / $disk['total']) * 100, 2);

                $disk['totalFormatted'] = $this->formatBytes($disk['total']);
                $disk['usedFormatted'] = $this->formatBytes($disk['used']);
                $disk['freeFormatted'] = $this->formatBytes($disk['free']);
            }
        }

        return $disk;
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param  int  $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
