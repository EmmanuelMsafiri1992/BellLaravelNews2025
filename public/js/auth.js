// Handles login, logout, and password change functionalities.
import { showFlashMessage, openModal } from './ui.js';
import { setCurrentUserRole, setCurrentUserFeaturesActivated } from './globals.js';
import { fetchSystemSettingsAndUpdateUI, showDashboard, showLogin } from './main.js';
import { checkUserRoleAndFeatureActivation } from './license.js';

/**
 * Event listener for the login form submission.
 */
export async function handleLogin(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
    
    localStorage.setItem('lastLoginAttempt', new Date().toISOString());
    console.log("Login form submitted.");
    
    const loginBtn = document.getElementById('loginBtn');
    const btnIcon = loginBtn ? loginBtn.querySelector('i') : null;

    if (btnIcon) {
        btnIcon.className = 'fas fa-spinner fa-spin mr-2';
    }
    if (loginBtn) {
        loginBtn.textContent = 'Authenticating...';
        loginBtn.disabled = true;
    }

    const formData = new FormData(e.target);

    try {
        const response = await fetch('/login', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: formData
        });
        
        console.log("Login Response Status:", response.status);

        if (response.redirected) {
            window.location.href = response.url;
            return;
        }

        const result = await response.json();
        console.log("Login response:", result);
        localStorage.setItem('lastResponse', JSON.stringify(result));

        if (result.success === true) {
            console.log("Login successful! Updating user info...");
            localStorage.setItem('loginSuccess', 'true');
            localStorage.setItem('loginUser', result.user.username);

            // Update global user info from response
            if (result.user && result.user.role) {
                setCurrentUserRole(result.user.role);
                setCurrentUserFeaturesActivated(result.user.features_activated || false);
                console.log("Updated user role to:", result.user.role);
                console.log("Updated features_activated to:", result.user.features_activated);
                // Immediately update UI to show correct features for this role
                checkUserRoleAndFeatureActivation();
            }

            // Show dashboard directly without reload
            console.log("Login successful, showing dashboard...");
            showDashboard();
        } else if (result.success === false && result.message) {
            showFlashMessage(result.message, "error", 'loginFlashContainer', false);
        } else {
            showFlashMessage("Login failed due to an unexpected server response.", "error", 'loginFlashContainer', false);
            console.error("Unexpected response:", result);
        }
    } catch (error) {
        console.error("Login error:", error);
        localStorage.setItem('lastError', error.toString());
        showFlashMessage("Network error during login. Please try again.", "error", 'loginFlashContainer', false);
    } finally {
        if (loginBtn) {
            if (btnIcon) {
                btnIcon.className = 'fas fa-sign-in-alt mr-2';
            }
            loginBtn.textContent = 'Sign In';
            loginBtn.disabled = false;
        }
    }
}

/**
 * Logs out the current user.
 */
export async function logout() {
    showFlashMessage("Logging out...", "info", 'dashboardFlashContainer');
    try {
        const response = await fetch('/logout', {
            method: 'POST',
            credentials: 'include'
        });
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            showLogin();
            showFlashMessage("Logged out successfully.", "success", 'loginFlashContainer');
        }
    } catch (error) {
        console.error("Logout error:", error);
        showFlashMessage("Network error during logout.", "error", 'dashboardFlashContainer');
        showLogin();
    }
}

/**
 * Opens the change password modal.
 */
export function showChangePassword() {
    openModal('changePasswordModal');
}

/**
 * Event listener for the change password form submission.
 */
export async function handleChangePassword(e) {
    e.preventDefault();
    
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    if (!currentPasswordInput || !newPasswordInput || !confirmPasswordInput) {
        showFlashMessage("Password fields not found.", "error", 'dashboardFlashContainer');
        return;
    }

    if (newPasswordInput.value.length < 8) {
        showFlashMessage("New password must be at least 8 characters.", "error", 'dashboardFlashContainer');
        return;
    }
    
    if (newPasswordInput.value !== confirmPasswordInput.value) {
        showFlashMessage("Passwords do not match.", "error", 'dashboardFlashContainer');
        return;
    }

    const formData = new FormData(e.target);
    try {
        const response = await fetch('/change_password', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        if (response.redirected) {
            window.location.href = response.url;
        } else if (response.ok) {
            closeModal('changePasswordModal');
            e.target.reset();
            showFlashMessage("Password changed successfully.", "success", 'dashboardFlashContainer');
        } else {
            showFlashMessage("Failed to change password.", "error", 'dashboardFlashContainer');
        }
    } catch (error) {
        console.error("Password change error:", error);
        showFlashMessage("Network error during password change.", "error", 'dashboardFlashContainer');
    }
}
