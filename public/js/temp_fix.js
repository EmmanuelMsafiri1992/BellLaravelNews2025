                    if (isJson) {
                        if (result.success === true) {
                            console.log("Login: JSON success. User authenticated:", result.user);
                            // Since this is an SPA, reload the page to trigger authenticated view
                            window.location.reload();
                        } else if (result.success === false && result.message) {
                            // For JSON errors on login, make the message persistent
                            showFlashMessage(result.message, "error", 'loginFlashContainer', false); 
                            console.log("Login: JSON error. Displaying flash message.");
                        } else {
                            // Generic JSON response, not handled specifically, make it persistent
                            showFlashMessage("Login failed due to an unexpected server response.", "error", 'loginFlashContainer', false);
                            console.error("Login: Unexpected JSON response structure.", result);
                        }
                    }
