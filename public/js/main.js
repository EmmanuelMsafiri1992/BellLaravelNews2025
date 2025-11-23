// main.js
// The entry point that orchestrates the initialization and event listener setup,
// importing from other modules.
// Note: Cache busting is handled by the ?v={{ time() }} parameter on the main.js import
// in the HTML template, combined with nginx no-cache headers for JS/CSS files.

import { handleLogin, logout, showChangePassword, handleChangePassword } from './auth.js';
import { fetchUsers, showUserManagementTab, showResetPasswordModal, handleResetPassword, handleAddUser } from './userManagement.js';
import { showSettings, handleSettingsSubmit, toggleStaticIpFields, selectTimeType } from './settings.js';
import { showLicenseManagement, generateLicenseKey, handleLicenseSubmit, updateLicenseUI, checkUserRoleAndFeatureActivation, openLicenseTab, fetchAndRenderLicensedUsers, activateFeatures } from './license.js';
import { updateCurrentTime, updateMetrics } from './dashboard.js';
import { populateSounds, testSound, deleteSound, handleSoundUpload } from './sounds.js';
import { handleAddAlarm, fetchAlarmsAndRender, renderAlarms, removeAlarm, editAlarm, handleEditAlarm, renderWeeklyAlarms } from './alarms.js';
import { showFlashMessage, closeModal, openModal } from './ui.js';
import { setCurrentUserRole, setCurrentUserFeaturesActivated, setLicenseInfo, setSystemSettings, getLicenseInfo, getCurrentUserRole, getCurrentUserFeaturesActivated } from './globals.js';
import { startAlarmMonitoring } from './alarmChecker.js';


// Expose functions to the global scope for inline HTML event handlers
// This is necessary because imported functions are not directly accessible from global scope (e.g., onclick attributes)
window.logout = logout;
window.showChangePassword = showChangePassword;
window.showSettings = showSettings;
window.showLicenseManagement = showLicenseManagement;
window.generateLicenseKey = generateLicenseKey;
window.openLicenseTab = openLicenseTab;
window.openModal = openModal; // Expose openModal for general use
window.closeModal = closeModal; // Expose closeModal for general use
window.showUserManagementTab = showUserManagementTab; // Expose for tab switching
window.showResetPasswordModal = showResetPasswordModal; // Expose for button click
window.testSound = testSound; // Expose for sound testing
window.deleteSound = deleteSound; // Expose for sound deletion
window.editAlarm = editAlarm; // Expose for alarm editing
window.removeAlarm = removeAlarm; // Expose for alarm removal
window.activateFeatures = activateFeatures; // Expose for feature activation button


/**
 * Displays the login page and hides the dashboard.
 */
export function showLogin() {
    document.getElementById('loginPage').style.display = 'flex';
    document.getElementById('dashboardPage').style.display = 'none';
}

/**
 * Displays the dashboard and hides the login page.
 * Calls the `init()` function to load and update dashboard content.
 */
export function showDashboard() {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('dashboardPage').style.display = 'block';
    init(); // Initialize dashboard components and fetch data when shown
}


/**
 * Fetches all system settings from the backend, including license info
 * and current user's roles/feature activation, then updates the UI.
 * This function is crucial for synchronizing frontend state with backend.
 */
let isFetchingSettings = false;

export async function fetchSystemSettingsAndUpdateUI() {
    if (isFetchingSettings) {
        console.log("[MAIN] fetchSystemSettingsAndUpdateUI already running, skipping...");
        return;
    }

    isFetchingSettings = true;
    console.log("[MAIN] Fetching system settings and updating UI...");
    try {
        const response = await fetch('/api/system_settings', { credentials: 'include' });
        const data = await response.json();
        console.log("[MAIN] System Settings fetched:", data);
        if (!data.success) {
            showFlashMessage(data.message, 'error', 'dashboardFlashContainer');
            return;
        }
        setSystemSettings(data.settings);
        setLicenseInfo(data.settings.license);
        console.log("[MAIN] License Info Status (after fetch):", getLicenseInfo().status);

        setCurrentUserRole(data.user_role);
        setCurrentUserFeaturesActivated(data.current_user_features_activated);
        console.log(`[MAIN] Current User Role: ${getCurrentUserRole()}, Features Activated: ${getCurrentUserFeaturesActivated()}`);

        updateLicenseUI();
        checkUserRoleAndFeatureActivation();

        if (data.settings.timeSettings) { selectTimeType(data.settings.timeSettings.timeType); } // Use data directly for this
        const ntpServerInput = document.getElementById('ntpServer');
        const manualDateInput = document.getElementById('manualDate');
        const manualTimeInput = document.getElementById('manualTime');

        if (ntpServerInput) ntpServerInput.value = data.settings.timeSettings?.ntpServer || '';
        if (manualDateInput) manualDateInput.value = data.settings.timeSettings?.manualDate || '';
        if (manualTimeInput) manualTimeInput.value = data.settings.timeSettings?.manualTime || '';

        toggleStaticIpFields();
        console.log("[MAIN] System settings UI updated successfully.");
    } catch (error) {
        console.error("[MAIN] Error fetching system settings for UI update:", error);
        showFlashMessage("Failed to load system settings for dashboard display. " + error.message, "error", 'dashboardFlashContainer');
    } finally {
        isFetchingSettings = false;
    }
}

/**
 * Initializes all dashboard components: updates current time, fetches system metrics,
 * populates sound library, fetches and renders alarms, and updates system settings related UI.
 * Also sets up recurring intervals for real-time updates.
 */
async function init() {
    console.log("Initializing dashboard...");
    updateCurrentTime();
    await updateMetrics();
    await populateSounds();
    await fetchAlarmsAndRender();
    await fetchSystemSettingsAndUpdateUI();

    setInterval(updateCurrentTime, 1000);
    // Start alarm monitoring system
    startAlarmMonitoring();

    setInterval(updateMetrics, 5000);
    console.log("Dashboard initialization complete. Real-time updates started.");
}

/**
 * Event listener for when the DOM content is fully loaded.
 * Determines whether to show the login page or the dashboard based on Flask's initial login status.
 */
document.addEventListener('DOMContentLoaded', async () => {
    const loginStatusElement = document.getElementById('flaskLoginStatus');
    const isLoggedInFromFlask = loginStatusElement ? loginStatusElement.value === 'true' : false;

    const resetPasswordDashboardBtn = document.getElementById('resetPasswordBtn');
    if (resetPasswordDashboardBtn) {
        resetPasswordDashboardBtn.addEventListener('click', showResetPasswordModal);
        console.log("Event listener attached to dashboard's 'resetPasswordBtn'.");
    } else {
        console.error("Dashboard button with ID 'resetPasswordBtn' not found.");
    }

    // Verify login status - check server if @auth says not logged in
    let actuallyLoggedIn = isLoggedInFromFlask;
    if (!isLoggedInFromFlask) {
        try {
            console.log("Checking session via API...");
            const response = await fetch('/api/system_settings', { credentials: 'include' });
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.user_role) {
                    actuallyLoggedIn = true;
                    // Set all user and system data from API response
                    setCurrentUserRole(data.user_role);
                    setCurrentUserFeaturesActivated(data.current_user_features_activated || false);
                    setSystemSettings(data.settings);
                    setLicenseInfo(data.settings.license);
                    console.log("Session verified via API, user is logged in as:", data.user_role);
                    console.log("License status loaded:", data.settings.license?.status);
                    // Update UI with license info
                    updateLicenseUI();
                    checkUserRoleAndFeatureActivation();
                }
            }
        } catch (error) {
            console.log("Session check failed:", error);
        }
    }

    if (actuallyLoggedIn) {
        console.log("User is logged in. Showing dashboard.");
        showDashboard();
    } else {
        console.log("User is not logged in. Showing login page.");
        showLogin();
    }

    const dynamicIpRadio = document.getElementById('dynamicIp');
    const staticIpRadio = document.getElementById('staticIp');
    if (dynamicIpRadio) {
        dynamicIpRadio.addEventListener('change', toggleStaticIpFields);
    }
    if (staticIpRadio) {
        staticIpRadio.addEventListener('change', toggleStaticIpFields);
    }

    // Attach event listeners for forms
    const loginFormElement = document.getElementById('loginForm');
    if (loginFormElement) {
        loginFormElement.addEventListener('submit', handleLogin, true);
    }
    
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            if (loginFormElement) {
                const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                loginFormElement.dispatchEvent(submitEvent);
            }
        });
    }
    
    document.getElementById('resetPasswordForm')?.addEventListener('submit', handleResetPassword);
    document.getElementById('addUserForm')?.addEventListener('submit', handleAddUser);
    document.getElementById('settingsForm')?.addEventListener('submit', handleSettingsSubmit);
    document.getElementById('licenseForm')?.addEventListener('submit', handleLicenseSubmit);
    document.getElementById("uploadForm")?.addEventListener("submit", handleSoundUpload);
    document.getElementById("addAlarmForm")?.addEventListener("submit", handleAddAlarm);
    document.getElementById("editAlarmForm")?.addEventListener("submit", handleEditAlarm);

    // Attach event listeners for time setting toggles
    const ntpOption = document.getElementById('ntpOption');
    const manualOption = document.getElementById('manualOption');
    if (ntpOption) {
        ntpOption.addEventListener('click', () => selectTimeType('ntp'));
    }
    if (manualOption) {
        manualOption.addEventListener('click', () => selectTimeType('manual'));
    }

    // Attach event listener for feature activation button
    document.getElementById('activateFeaturesBtn')?.addEventListener('click', activateFeatures);

    // Attach event listeners for modal close buttons
    document.getElementById('closeLoginFlashBtn')?.addEventListener('click', () => closeModal('loginFlashContainer'));
    document.getElementById('closeDashboardFlashBtn')?.addEventListener('click', () => closeModal('dashboardFlashContainer'));
    document.getElementById('closeAddModalBtn')?.addEventListener('click', () => closeModal('addModal'));
    document.getElementById('closeEditModalBtn')?.addEventListener('click', () => closeModal('editModal'));
    document.getElementById('closeResetPasswordModalBtn')?.addEventListener('click', () => closeModal('resetPasswordModal'));
    document.getElementById('closeAddUserModalBtn')?.addEventListener('click', () => closeModal('resetPasswordModal')); // Add User is part of resetPasswordModal
    document.getElementById('closeChangePasswordModalBtn')?.addEventListener('click', () => closeModal('changePasswordModal'));
    document.getElementById('closeSettingsModalBtn')?.addEventListener('click', () => closeModal('settingsModal'));
    document.getElementById('closeLicenseModalBtn')?.addEventListener('click', () => closeModal('licenseModal'));
});

// Debug: Check if login was successful on page load
document.addEventListener('DOMContentLoaded', () => {
    const loginSuccess = localStorage.getItem('loginSuccess');
    const lastAttempt = localStorage.getItem('lastLoginAttempt');
    
    if (loginSuccess === 'true') {
        console.log('Previous login was successful!');
        console.log('User:', localStorage.getItem('loginUser'));
        localStorage.removeItem('loginSuccess');
    }
    const lastResponse = localStorage.getItem('lastResponse');
    const lastError = localStorage.getItem('lastError');
    
    if (lastResponse) {
        console.log('Last backend response was:', lastResponse);
        localStorage.removeItem('lastResponse');
    }
    
    if (lastError) {
        console.error('Last error was:', lastError);
        localStorage.removeItem('lastError');
    }
    
    if (lastAttempt) {
        console.log('Last login attempt:', lastAttempt);
    }
});
