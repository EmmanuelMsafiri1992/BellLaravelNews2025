<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Alarm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckAlarms extends Command
{
    protected $signature = 'alarms:check';
    protected $description = 'Check if any alarms should trigger now and play them';

    private $triggeredToday = [];
    private $cacheFile = '/tmp/bellapp_triggered_alarms.json';

    public function handle()
    {
        try {
            // Load today's triggered alarms from cache
            $this->loadTriggeredCache();

            // Get current time
            $now = Carbon::now('Asia/Jerusalem');
            $currentDay = $now->format('l'); // Monday, Tuesday, etc.
            $currentTime = $now->format('H:i');

            // Find alarms that should trigger
            $alarms = Alarm::where('enabled', true)
                ->where('day', $currentDay)
                ->where('time', $currentTime)
                ->get();

            foreach ($alarms as $alarm) {
                $alarmKey = $alarm->id . '_' . $currentTime . '_' . $currentDay;

                // Check if already triggered today
                if (in_array($alarmKey, $this->triggeredToday)) {
                    continue;
                }

                // Trigger the alarm
                $this->triggerAlarm($alarm);

                // Mark as triggered
                $this->triggeredToday[] = $alarmKey;
            }

            // Save triggered cache
            $this->saveTriggeredCache();

            // Clean old cache at midnight
            $this->cleanCacheIfNewDay();

        } catch (\Exception $e) {
            Log::error('Alarm checker error: ' . $e->getMessage());
            // Don't throw - continue silently to prevent halting
        }

        return 0;
    }

    private function triggerAlarm($alarm)
    {
        try {
            $label = $alarm->label ?? 'Alarm';
            $this->info("Triggering alarm: {$label} at {$alarm->time}");
            Log::info("Alarm triggered: {$label} ID: {$alarm->id}");

            // Play the sound file
            if ($alarm->sound) {
                $soundPath = public_path('audio/' . $alarm->sound);

                if (file_exists($soundPath)) {
                    $this->playSound($soundPath);
                } else {
                    Log::warning("Sound file not found: {$soundPath}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to trigger alarm {$alarm->id}: " . $e->getMessage());
            // Continue even if one alarm fails
        }
    }

    private function playSound($soundPath)
    {
        try {
            // Try multiple audio players (in order of preference)
            $players = [
                'mpg123 -q',      // Best for MP3
                'aplay',          // ALSA player for WAV
                'cvlc --play-and-exit', // VLC
                'ffplay -nodisp -autoexit', // FFmpeg
            ];

            foreach ($players as $player) {
                // Check if player exists
                $checkCmd = explode(' ', $player)[0];
                exec("which {$checkCmd} 2>/dev/null", $output, $return);

                if ($return === 0) {
                    // Player found, use it
                    $cmd = $player . ' ' . escapeshellarg($soundPath) . ' > /dev/null 2>&1 &';
                    exec($cmd);
                    Log::info("Playing sound with {$player}: {$soundPath}");
                    return;
                }
            }

            Log::warning("No audio player found. Install mpg123: sudo apt-get install mpg123");
        } catch (\Exception $e) {
            Log::error("Failed to play sound: " . $e->getMessage());
        }
    }

    private function loadTriggeredCache()
    {
        if (file_exists($this->cacheFile)) {
            $data = json_decode(file_get_contents($this->cacheFile), true);
            if (is_array($data)) {
                $this->triggeredToday = $data;
            }
        }
    }

    private function saveTriggeredCache()
    {
        file_put_contents($this->cacheFile, json_encode($this->triggeredToday));
    }

    private function cleanCacheIfNewDay()
    {
        $now = Carbon::now('Asia/Jerusalem');
        if ($now->format('H:i') === '00:00') {
            $this->triggeredToday = [];
            $this->saveTriggeredCache();
            Log::info("Alarm cache cleared for new day");
        }
    }
}
