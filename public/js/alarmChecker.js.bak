// alarmChecker.js
// Monitors alarms and triggers sounds at scheduled times (replaces Python alarm_player.py)

import { showFlashMessage } from './ui.js';

let alarmsCache = [];
let triggeredAlarmsToday = new Set(); // Track which alarms triggered today

/**
 * Fetch all enabled alarms from the backend
 */
async function fetchEnabledAlarms() {
    try {
        const response = await fetch('/api/alarms', { credentials: 'same-origin' });
        const data = await response.json();
        
        if (data.success && Array.isArray(data.alarms)) {
            alarmsCache = data.alarms;
            console.log(`Loaded ${alarmsCache.length} alarms for monitoring`);
        }
    } catch (error) {
        console.error("Error fetching alarms for monitoring:", error);
    }
}

/**
 * Check if an alarm should trigger now
 */
function shouldTriggerAlarm(alarm) {
    const now = new Date();
    
    // Get current day name
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const currentDay = days[now.getDay()];
    
    // Check if alarm is for today
    if (alarm.day !== currentDay) {
        return false;
    }
    
    // Parse alarm time (format: "HH:MM")
    if (!alarm.time) {
        return false;
    }
    
    const [alarmHour, alarmMinute] = alarm.time.split(':').map(num => parseInt(num, 10));
    
    // Check if current time matches alarm time (within same minute)
    if (now.getHours() === alarmHour && now.getMinutes() === alarmMinute) {
        // Create unique key for this alarm at this specific time
        const alarmKey = `${alarm.id}_${alarm.time}_${currentDay}`;
        
        // Check if we already triggered this alarm today at this time
        if (triggeredAlarmsToday.has(alarmKey)) {
            return false; // Already triggered
        }
        
        // Mark as triggered
        triggeredAlarmsToday.add(alarmKey);
        
        // Clean up old triggered alarms (keep only current day)
        cleanupTriggeredAlarms();
        
        return true;
    }
    
    return false;
}

/**
 * Clean up triggered alarms from previous days
 */
function cleanupTriggeredAlarms() {
    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const currentDay = days[now.getDay()];
    
    // Keep only alarms from today
    const newSet = new Set();
    for (const key of triggeredAlarmsToday) {
        if (key.endsWith(currentDay)) {
            newSet.add(key);
        }
    }
    triggeredAlarmsToday = newSet;
}

/**
 * Play alarm sound
 */
function playAlarmSound(alarm) {
    if (!alarm.sound) {
        console.warn(`Alarm ${alarm.id} has no sound file`);
        return;
    }
    
    const alarmLabel = alarm.label || 'Alarm';
    console.log(`🔔 Triggering alarm: ${alarmLabel} at ${alarm.time} on ${alarm.day}`);
    
    // Create and play audio
    const audio = new Audio(`/audio/${alarm.sound}`);
    audio.volume = 1.0; // Maximum volume
    
    audio.play()
        .then(() => {
            console.log(`✅ Playing alarm sound: ${alarm.sound}`);
            showFlashMessage(`🔔 Alarm: ${alarmLabel}`, 'info', 'dashboardFlashContainer', false);
        })
        .catch(error => {
            console.error(`❌ Failed to play alarm sound: ${alarm.sound}`, error);
            showFlashMessage(`Failed to play alarm: ${alarmLabel}`, 'error', 'dashboardFlashContainer');
        });
}

/**
 * Check all alarms and trigger any that match current time
 */
function checkAlarms() {
    if (!alarmsCache || alarmsCache.length === 0) {
        return;
    }
    
    for (const alarm of alarmsCache) {
        if (shouldTriggerAlarm(alarm)) {
            playAlarmSound(alarm);
        }
    }
}

/**
 * Initialize alarm monitoring system
 */
export function startAlarmMonitoring() {
    console.log("🚀 Starting alarm monitoring system...");
    
    // Initial fetch of alarms
    fetchEnabledAlarms();
    
    // Reload alarms every 5 minutes (in case they were updated)
    setInterval(fetchEnabledAlarms, 5 * 60 * 1000);
    
    // Check alarms every 10 seconds (frequent enough to not miss any)
    setInterval(checkAlarms, 10 * 1000);
    
    // Also check immediately on the next minute boundary
    const now = new Date();
    const msUntilNextMinute = (60 - now.getSeconds()) * 1000;
    setTimeout(() => {
        checkAlarms();
        // Then check every 10 seconds
        setInterval(checkAlarms, 10 * 1000);
    }, msUntilNextMinute);
    
    console.log("✅ Alarm monitoring system started");
}

/**
 * Stop alarm monitoring (for cleanup)
 */
export function stopAlarmMonitoring() {
    console.log("🛑 Stopping alarm monitoring system...");
    // Note: setInterval IDs would need to be stored to clear them properly
    // For now, this is a placeholder for future implementation
}
