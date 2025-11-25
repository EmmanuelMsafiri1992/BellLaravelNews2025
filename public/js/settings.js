// Manages system settings (network, time).
import { showFlashMessage, openModal, closeModal } from './ui.js';
import { systemSettings, setSystemSettings } from './globals.js';
import { fetchSystemSettingsAndUpdateUI } from './main.js';

/**
 * Opens the system settings modal and fetches/populates current settings from the backend.
 */
export async function showSettings() {
    try {
        const response = await fetch('/api/system_settings', { credentials: 'same-origin' });
        const data = await response.json();
        if (!data.success) {
            showFlashMessage(data.message || 'Failed to fetch settings', 'error', 'dashboardFlashContainer');
            return;
        }
        setSystemSettings(data.settings); // Update global systemSettings object
        console.log("Fetched System Settings:", data.settings);

        const dynamicIpRadio = document.getElementById('dynamicIp');
        const staticIpRadio = document.getElementById('staticIp');

        if (dynamicIpRadio && staticIpRadio) {
            // Laravel API returns 'network' not 'networkSettings'
            if (systemSettings.network?.ipType === 'static') {
                staticIpRadio.checked = true;
            } else {
                dynamicIpRadio.checked = true;
            }
        }
        toggleStaticIpFields();

        const ipAddressElem = document.getElementById('ipAddress');
        const subnetMaskElem = document.getElementById('subnetMask');
        const gatewayElem = document.getElementById('gateway');
        const dnsServerElem = document.getElementById('dnsServer');
        // UPDATED: Ubuntu Device IP Address element
        const ubuntuDeviceIpAddressElem = document.getElementById('ubuntuDeviceIpAddress');

        // Laravel API returns 'network' not 'networkSettings'
        if (ipAddressElem) ipAddressElem.value = systemSettings.network?.ipAddress || '';
        if (subnetMaskElem) subnetMaskElem.value = systemSettings.network?.subnetMask || '';
        if (gatewayElem) gatewayElem.value = systemSettings.network?.gateway || '';
        if (dnsServerElem) dnsServerElem.value = systemSettings.network?.dnsServer || '';
        // UPDATED: Populate Ubuntu Device IP Address
        if (ubuntuDeviceIpAddressElem) ubuntuDeviceIpAddressElem.value = systemSettings.ubuntuDeviceIpAddress || '';


        // Populate time settings (optional - may not be present in Laravel API yet)
        const ntpOption = document.getElementById('ntpOption');
        const manualOption = document.getElementById('manualOption');
        const ntpServerElem = document.getElementById('ntpServer');
        const manualDateElem = document.getElementById('manualDate');
        const manualTimeElem = document.getElementById('manualTime');

        if (systemSettings.timeSettings?.timeType === 'manual') {
            selectTimeType('manual');
        } else {
            selectTimeType('ntp');
        }
        if (ntpServerElem) ntpServerElem.value = systemSettings.timeSettings?.ntpServer || '';
        if (manualDateElem) manualDateElem.value = systemSettings.timeSettings?.manualDate || '';
        if (manualTimeElem) manualTimeElem.value = systemSettings.timeSettings?.manualTime || '';


        openModal('settingsModal');
    } catch (error) {
        console.error("Error fetching system settings:", error);
        showFlashMessage("Failed to load system settings. " + error.message, "error", 'dashboardFlashContainer');
    }
}

/**
 * Event listener for the settings form submission.
 * Gathers data, performs client-side validation, and sends updates to the backend.
 */
export async function handleSettingsSubmit(e) {
    e.preventDefault();
    console.log("Settings form submitted.");

    // FIX: Define ipRegex at the beginning of the function for broader scope
    const ipRegex = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    const updatedSettings = {
        network: {},
        timeSettings: {},
        API_SERVICE_URL: systemSettings.API_SERVICE_URL, // Retain existing API URL
        // UPDATED: Add Ubuntu Device IP Address to updated settings (if element exists)
        ubuntuDeviceIpAddress: document.getElementById('ubuntuDeviceIpAddress')?.value?.trim() || ''
    };

    const selectedIpType = document.querySelector('input[name="ipType"]:checked');
    updatedSettings.network.ipType = selectedIpType ? selectedIpType.value : 'dynamic';

    if (updatedSettings.network.ipType === 'static') {
        const ipAddress = document.getElementById('ipAddress');
        const subnetMask = document.getElementById('subnetMask');
        const gateway = document.getElementById('gateway');
        const dnsServer = document.getElementById('dnsServer');

        const ipAddressValue = ipAddress ? ipAddress.value : '';
        const subnetMaskValue = subnetMask ? subnetMask.value : '';
        const gatewayValue = gateway ? gateway.value : '';
        const dnsServerValue = dnsServer ? dnsServer.value : '';

        // ipRegex is now defined above, accessible here
        if (!ipRegex.test(ipAddressValue) || !ipRegex.test(subnetMaskValue) || !ipRegex.test(gatewayValue) || (dnsServerValue && !ipRegex.test(dnsServerValue))) {
            showFlashMessage("Please enter valid IPv4 addresses for static IP configuration.", "error", 'dashboardFlashContainer');
            return;
        }
        updatedSettings.network.ipAddress = ipAddressValue;
        updatedSettings.network.subnetMask = subnetMaskValue;
        updatedSettings.network.gateway = gatewayValue;
        updatedSettings.network.dnsServer = dnsServerValue;
    } else {
        updatedSettings.network.ipAddress = '';
        updatedSettings.network.subnetMask = '';
        updatedSettings.network.gateway = '';
        updatedSettings.network.dnsServer = '';
    }

    const selectedTimeType = document.querySelector('.toggle-option.active');
    updatedSettings.timeSettings.timeType = selectedTimeType ? selectedTimeType.dataset.timeType : 'ntp';

    if (updatedSettings.timeSettings.timeType === 'ntp') {
        const ntpServer = document.getElementById('ntpServer');
        updatedSettings.timeSettings.ntpServer = ntpServer ? ntpServer.value : '';
        updatedSettings.timeSettings.manualDate = '';
        updatedSettings.timeSettings.manualTime = '';
    } else {
        const manualDate = document.getElementById('manualDate');
        const manualTime = document.getElementById('manualTime');
        updatedSettings.timeSettings.ntpServer = '';
        updatedSettings.timeSettings.manualDate = manualDate ? manualDate.value : '';
        updatedSettings.timeSettings.manualTime = manualTime ? manualTime.value : '';
        if (!updatedSettings.timeSettings.manualDate || !updatedSettings.timeSettings.manualTime) {
            showFlashMessage("Please enter both manual date and time for manual time setting.", "error", 'dashboardFlashContainer');
            return;
        }
    }

    // Validate Ubuntu Device IP if provided
    if (updatedSettings.ubuntuDeviceIpAddress && !ipRegex.test(updatedSettings.ubuntuDeviceIpAddress)) {
        showFlashMessage("Please enter a valid IPv4 address for the Ubuntu Device IP Address.", "error", 'dashboardFlashContainer');
        return;
    }


    console.log("Sending updated settings:", updatedSettings);

    try {
        // Step 1: Save settings to database
        const response = await fetch('/api/system_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedSettings)
        });
        const result = await response.json();
        console.log("Settings Save Response:", result);

        if (!result.success) {
            showFlashMessage(result.message, "error", 'dashboardFlashContainer');
            return;
        }

        // Step 2: Apply network settings to the system (calls nmcli on Linux/NanoPi)
        try {
            const applyResponse = await fetch('/api/apply_network_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updatedSettings.network)
            });
            const applyResult = await applyResponse.json();
            console.log("Apply Network Response:", applyResult);

            if (applyResult.success) {
                showFlashMessage("Settings saved and network configuration applied!", "success", 'dashboardFlashContainer');

                // If IP changed, warn user about potential disconnect
                if (updatedSettings.network.ipType === 'static' && updatedSettings.network.ipAddress) {
                    const currentHost = window.location.hostname;
                    if (updatedSettings.network.ipAddress !== currentHost) {
                        setTimeout(() => {
                            showFlashMessage(
                                `IP changed to ${updatedSettings.network.ipAddress}. Reconnect at http://${updatedSettings.network.ipAddress}:8000`,
                                "info",
                                'dashboardFlashContainer'
                            );
                        }, 2000);
                    }
                }
            } else {
                // Network apply failed (Windows dev environment or permission issue)
                showFlashMessage(result.message + " (Note: " + (applyResult.message || "Network apply skipped") + ")", "success", 'dashboardFlashContainer');
            }
        } catch (applyError) {
            // Network apply request failed - likely connection lost due to IP change
            console.log("Apply network error (expected if IP changed):", applyError);
            showFlashMessage("Settings saved. If IP changed, reconnect to the new address.", "success", 'dashboardFlashContainer');
        }

        closeModal('settingsModal');
        await fetchSystemSettingsAndUpdateUI();
    } catch (error) {
        console.error("Error saving system settings:", error);
        showFlashMessage("Network error saving settings. " + error.message, "error", 'dashboardFlashContainer');
    }
}

/**
 * Toggles the visibility and required attributes for static IP input fields
 * based on the selected IP configuration type (Dynamic/Static).
 */
export function toggleStaticIpFields() {
    const staticIpRadio = document.getElementById('staticIp');
    const staticIpFields = document.getElementById('staticIpFields');
    const ipAddressInput = document.getElementById('ipAddress');
    const subnetMaskInput = document.getElementById('subnetMask');
    const gatewayInput = document.getElementById('gateway');

    if (!staticIpRadio || !staticIpFields || !ipAddressInput || !subnetMaskInput || !gatewayInput) {
        console.warn("Missing elements for toggleStaticIpFields. Skipping execution.");
        return;
    }

    if (staticIpRadio.checked) {
        staticIpFields.classList.remove('hidden');
        ipAddressInput.setAttribute('required', 'required');
        subnetMaskInput.setAttribute('required', 'required');
        gatewayInput.setAttribute('required', 'required');
    } else {
        staticIpFields.classList.add('hidden');
        ipAddressInput.removeAttribute('required');
        subnetMaskInput.removeAttribute('required');
        gatewayInput.removeAttribute('required');
    }
}

/**
 * Toggles between NTP server and Manual time setting options in the settings modal.
 * Updates active class for buttons and shows/hides relevant input fields.
 * @param {string} type - The type of time setting to activate ('ntp' or 'manual').
 */
export function selectTimeType(type) {
    const ntpOption = document.getElementById('ntpOption');
    const manualOption = document.getElementById('manualOption');
    const ntpSettingsFields = document.getElementById('ntpSettingsFields');
    const manualTimeFields = document.getElementById('manualTimeFields');
    const manualDateInput = document.getElementById('manualDate');
    const manualTimeInput = document.getElementById('manualTime');

    if (!ntpOption || !manualOption || !ntpSettingsFields || !manualTimeFields || !manualDateInput || !manualTimeInput) {
        console.warn("Missing elements for selectTimeType. Skipping function.");
        return;
    }

    ntpOption.classList.remove('active');
    manualOption.classList.remove('active');

    if (type === 'ntp') {
        ntpOption.classList.add('active');
        ntpSettingsFields.classList.remove('hidden');
        manualTimeFields.classList.add('hidden');
        manualDateInput.removeAttribute('required');
        manualTimeInput.removeAttribute('required');
    } else { // type === 'manual'
        manualOption.classList.add('active');
        ntpSettingsFields.classList.add('hidden');
        manualTimeFields.classList.remove('hidden');
        manualDateInput.setAttribute('required', 'required');
        manualTimeInput.setAttribute('required', 'required');
    }
}
