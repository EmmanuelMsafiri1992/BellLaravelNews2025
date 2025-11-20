{{-- resources/views/admin/users/edit_license.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User License - {{ $user->name }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Add your sidebar and main content styles here, similar to index.blade.php */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                @include('admin.sidebar') {{-- Include your sidebar here --}}
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit License for {{ $user->name }}</h1>
                </div>

                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Warning for expired licenses --}}
                @if ($user->license && $user->license->expires_at && $user->license->expires_at->copy()->endOfDay()->isPast())
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This license expired on {{ $user->license->expires_at->format('F d, Y') }}.
                        Expired licenses cannot be reused or extended. Please delete the old license and create a new one.
                    </div>
                @endif

                {{-- Assign New License Section (only show if user has no license) --}}
                @if (!$user->license)
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-plus-circle me-2"></i>Assign License from Pool
                        </div>
                        <div class="card-body">
                            @if ($availableLicenses->count() > 0)
                                <p class="text-muted">
                                    Select an available license to assign to {{ $user->name }}.
                                    There are <strong>{{ $availableLicenses->count() }}</strong> unused licenses available.
                                </p>
                                <form action="{{ route('admin.users.license.assign', $user->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="license_id" class="form-label">Available Licenses</label>
                                        <select class="form-select" id="license_id" name="license_id" required>
                                            <option value="">-- Select a License --</option>
                                            @foreach ($availableLicenses as $availableLicense)
                                                <option value="{{ $availableLicense->id }}">
                                                    {{ $availableLicense->code }}
                                                    (Expires: {{ $availableLicense->expires_at ? $availableLicense->expires_at->format('M d, Y') : 'No expiration' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('license_id')
                                            <div class="text-danger mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check-circle me-2"></i>Assign License
                                    </button>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </form>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>No available licenses found.</strong>
                                    Please <a href="{{ route('admin.licenses.generate.form') }}" class="alert-link">generate new licenses</a> first.
                                </div>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Only show this card if user HAS a license --}}
                @if ($user->license)
                    <div class="card">
                        <div class="card-header">
                            License Details
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>License Code:</strong> <code>{{ $user->license->code }}</code>
                            </div>
                            <div class="mb-3">
                                <strong>Current Status:</strong>
                                @if ($user->license->is_used && $user->license->expires_at && $user->license->expires_at->copy()->endOfDay()->isFuture())
                                    <span class="badge bg-success">Active</span>
                                @elseif ($user->license->expires_at && $user->license->expires_at->copy()->endOfDay()->isPast())
                                    <span class="badge bg-danger">Expired</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                            <hr>

                            <form action="{{ route('admin.users.license.update', $user->id) }}" method="POST">
                                @csrf
                                @method('PUT') {{-- Use PUT method for updates --}}

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_used" name="is_used"
                                        {{ $user->license->is_used ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_used">License Active (Is Used)</label>
                                </div>

                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">License Expiration Date</label>
                                    <input type="date" class="form-control" id="expires_at" name="expires_at"
                                        value="{{ $user->license->expires_at ? $user->license->expires_at->format('Y-m-d') : '' }}">
                                    @error('expires_at')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update License
                                </button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </form>

                            {{-- Delete License Form --}}
                            <hr class="my-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Delete License:</strong> If this license is expired or you need to assign a different one, delete this license first.
                            </div>
                            <form action="{{ route('admin.users.license.delete', $user->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this license? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Delete License
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </main>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>
