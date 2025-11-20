// Contains functions for updating real-time dashboard data (metrics, current time).
import { showFlashMessage } from './ui.js';

/**
 * Updates the displayed current time and timezone on the dashboard.
 * Uses browser's local time and automatically detected timezone based on user's location.
 */
export async function updateCurrentTime() {
    const currentTimeElement = document.getElementById("currentTime");
    const timezoneElement = document.getElementById("timezone");
    if (currentTimeElement && timezoneElement) {
        // Use browser's local time and timezone (automatically detects user's location)
        const now = new Date();
        currentTimeElement.textContent = now.toLocaleTimeString();
        timezoneElement.textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
}

/**
 * Fetches system metrics (CPU, memory, uptime) from the backend and updates the dashboard UI.
 */
export async function updateMetrics() {
    try {
        const response = await fetch('/api/metrics', { credentials: 'same-origin' });
        const data = await response.json();

        if (!data.success || data.error) {
            console.error("Error fetching metrics:", data.error || "Unknown error");
            return;
        }

        const metrics = data.metrics;

        // Extract metrics from the Laravel API response structure
        const cpu = metrics.cpu?.usage || 0;
        const memory = metrics.memory?.phpUsage ? (metrics.memory.phpUsage / (1024 * 1024)) : 0; // Convert bytes to MB
        const systemMemory = metrics.memory?.usagePercent || 0;
        const uptime = metrics.uptime?.formatted || 'Unknown';

        const cpuBar = document.getElementById("cpuBar");
        const cpuText = document.getElementById("cpuText");
        const memoryText = document.getElementById("memoryText");
        const systemMemoryBar = document.getElementById("systemMemoryBar");
        const systemMemoryText = document.getElementById("systemMemoryText");
        const uptimeText = document.getElementById("uptimeText");

        if (cpuBar) cpuBar.style.width = cpu + "%";
        if (cpuText) cpuText.textContent = cpu.toFixed(1) + "%";
        if (memoryText) memoryText.textContent = memory.toFixed(1) + " MB";
        if (systemMemoryText) systemMemoryText.textContent = systemMemory.toFixed(1) + "%";
        if (systemMemoryBar) systemMemoryBar.style.width = systemMemory + "%";
        if (uptimeText) uptimeText.textContent = uptime;

    } catch (error) {
        console.error("Failed to fetch metrics:", error);
    }
}
