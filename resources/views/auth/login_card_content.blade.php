<div class="text-center">
    <h1 class="text-3xl font-bold text-gray-900">{{ __('Login') }}</h1>
</div>

<!-- Display Error Messages -->
@if (session('error'))
    <div class="rounded-md bg-red-50 p-4 border border-red-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif

<!-- Display Success Messages -->
@if (session('success'))
    <div class="rounded-md bg-green-50 p-4 border border-green-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf

    <!-- Password Input -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
        <div class="mt-1 relative">
            <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none {{ app()->getLocale() === 'ar' || app()->getLocale() === 'he' ? 'right-0 left-auto pr-3' : '' }}">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 11c0-1.1-.9-2-2-2s-2 .9-2 2 2 4 2 4m0 0c0 1.1.9 2 2 2s2-.9 2-2-2-4-2-4zm0 0h6m-6 0H6" />
                </svg>
            </div>
            <input id="password" type="password" name="password" required autofocus aria-label="{{ __('Password') }}"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out {{ app()->getLocale() === 'ar' || app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}"
                placeholder="{{ __('Password') }}">
        </div>
        @error('password')
            <span class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</span>
        @enderror
    </div>

    <!-- Remember Me Checkbox -->
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input id="remember" type="checkbox" name="remember"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                aria-label="{{ __('Remember Me') }}">
            <label for="remember" class="ml-2 block text-sm text-gray-900">{{ __('Remember Me') }}</label>
        </div>
    </div>

    <!-- Submit Button -->
    <div>
        <button type="submit"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
            {{ __('Login') }}
        </button>
    </div>
{{--
    <!-- Forgot Password Link -->
    @if (Route::has('password.request'))
        <div class="text-center">
            <a href="{{ route('password.request') }}"
                class="text-sm text-blue-600 hover:text-blue-800 transition duration-150 ease-in-out">
                {{ __('Forgot Your Password?') }}
            </a>
        </div>
    @endif --}}
</form>
