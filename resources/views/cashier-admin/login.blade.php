<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthinPocket Login</title>
    @vite(['resources/css/app.css'])
    <link rel="icon" href="{{ asset('assets/favicon.png') }}">
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('assets/login page2.png') }}" alt="Background" class="w-full h-full object-cover">
    </div>

    <div class="w-full max-w-6xl flex items-center justify-between gap-12 relative z-10">
        
        <div class="hidden lg:flex flex-1 items-center justify-center">
            <div class="w-full max-w-2xl">
                <img src="{{ asset('assets/Component 1.png') }}" alt="Healthcare Illustration" class="w-full h-auto">
            </div>
        </div>

        <div class="flex-1 max-w-md">
            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <div class="mb-8 flex items-center justify-center">
                    <img src="{{ asset('assets/healthin-black.png') }}" alt="HealthinPocket Logo" class="h-12">
                </div>

                @if (session('error') || $errors->has('email') || $errors->has('password'))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                        {{ session('error') ?? $errors->first('email') ?? $errors->first('password') }}
                    </div>
                @endif

                <form action="{{ route('cashier.auth.login.store') }}" method="post">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <input type="email" placeholder="Email" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-blue-400 transition-colors {{ $errors->has('email') || session('error') ? 'border-red-300' : 'border-gray-200' }}">
                        </div>
    
                        <div>
                            <input type="password" placeholder="Password" name="password" class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-blue-400 transition-colors {{ $errors->has('password') || session('error') ? 'border-red-300' : 'border-gray-200' }}">
                        </div>
    
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                            <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                        </div>
    
                        <button class="w-full bg-blue-400 hover:bg-blue-500 text-white font-semibold py-3 rounded-lg transition-colors shadow-lg shadow-blue-200 cursor-pointer">
                            Login
                        </button>
    
                        <div class="flex items-center justify-between text-sm pt-2">
                            <button class="text-gray-600 hover:text-gray-800 flex items-center gap-1 cursor-pointer">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 1C4.13 1 1 4.13 1 8s3.13 7 7 7 7-3.13 7-7-3.13-7-7-7zm0 12.5c-3.03 0-5.5-2.47-5.5-5.5S4.97 2.5 8 2.5s5.5 2.47 5.5 5.5-2.47 5.5-5.5 5.5z"/>
                                    <path d="M8 4C6.62 4 5.5 5.12 5.5 6.5h1.25C6.75 5.81 7.31 5.25 8 5.25s1.25.56 1.25 1.25c0 .83-1.25 1.04-1.25 2.5h1.25c0-1.25 1.25-1.46 1.25-2.5C10.5 5.12 9.38 4 8 4zM7.38 10.5h1.25v1.25H7.38z"/>
                                </svg>
                                Forgot your Password?
                            </button>
                            {{-- <button class="text-gray-600 hover:text-gray-800 flex items-center gap-1 cursor-pointer">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 1C4.13 1 1 4.13 1 8s3.13 7 7 7 7-3.13 7-7-3.13-7-7-7zm0 12.5c-3.03 0-5.5-2.47-5.5-5.5S4.97 2.5 8 2.5s5.5 2.47 5.5 5.5-2.47 5.5-5.5 5.5z"/>
                                    <path d="M9 8c0 .55-.45 1-1 1s-1-.45-1-1 .45-1 1-1 1 .45 1 1z"/>
                                    <circle cx="5" cy="8" r="1"/>
                                    <circle cx="11" cy="8" r="1"/>
                                </svg>
                                Create an Account?
                            </button> --}}
                        </div>
                    </div>
                </form>
                
            </div>
        </div>
    </div>

</body>
</html>
