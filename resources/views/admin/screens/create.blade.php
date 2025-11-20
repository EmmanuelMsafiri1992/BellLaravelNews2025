<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Screen</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        @include('admin.sidebar')

        <div class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">
                        <i class="fas fa-plus-circle me-2"></i> Create New Screen
                    </a>
                </div>
            </nav>

            <div class="main-content">
                <div id="alert-container"></div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card p-4">
                            <div class="card-header bg-transparent mb-4">
                                <h4 class="mb-0">Create New Screen</h4>
                            </div>
                            <div class="card-body">
                                <form id="screen-form" action="{{ route('admin.screens.store') }}" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Screen Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="{{ old('name') }}" required placeholder="e.g., TV-A, Reception Screen">
                                        <small class="text-muted">A unique code will be auto-generated from this name</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                               value="{{ old('location') }}" placeholder="e.g., Main Lobby, Conference Room">
                                    </div>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <a href="{{ route('admin.screens.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary" id="submit-button">
                                            <i class="fas fa-save me-2"></i> Save Screen
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const screenForm = document.getElementById('screen-form');
            const submitButton = document.getElementById('submit-button');
            const alertContainer = document.getElementById('alert-container');

            if (screenForm && submitButton) {
                screenForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const originalHtml = submitButton.innerHTML;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                    submitButton.disabled = true;

                    const formData = new FormData(screenForm);

                    fetch(screenForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('success', data.message);
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            throw new Error(data.message || 'Failed to save screen');
                        }
                    })
                    .catch(error => {
                        submitButton.innerHTML = originalHtml;
                        submitButton.disabled = false;
                        showAlert('danger', `Failed to save screen: ${error.message}`);
                    });
                });
            }

            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                alertContainer.innerHTML = alertHtml;
            }
        });
    </script>
</body>
</html>
