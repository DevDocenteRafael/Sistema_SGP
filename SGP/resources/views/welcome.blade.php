<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel</title>
</head>
<body>
    <div class="content">
        <h1>Welcome to Laravel</h1>
        
        {{-- Displaying a variable passed from a route --}}
        <p>Hello, {{ $name ?? 'Guest' }}!</p>

        {{-- Conditional login/register links --}}
        @if (Route::has('login'))
            <div class="links">
                @auth
                    <a href="{{ url('/home') }}">Home</a>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</body>
</html>
