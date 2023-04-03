<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? stripslashes($title) : 'Alpha Tiles Assistant' }}</title>

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @if (config('app.env') !== 'local')
    <script src="https://t.usermaven.com/lib.js" 
    data-key="UMzy8FcNX1" 
    data-tracking-host="https://events.usermaven.com"
    data-autocapture="true"   
    defer>
    </script>
    <script>window.usermaven = window.usermaven || (function(){(window.usermavenQ = window.usermavenQ || []).push(arguments);})</script>    
    @endif
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-light bg-white shadow-sm">
            <div class="flex w-full">
                <div>
                    <a href="{{ !Auth::guest() ? '/dashboard' : '/' }}">
                        <img src="/images/logo.png" style="width:150px;">
                    </a> 
                </div>    
                <div class="w-full">           
                    <div class="float-right">
                        <ul id="nav-login">
                            @include('layouts/loginnav')          
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <main class="m-4 min-h-screen">
            @yield('content')
        </main>

        <div class="my-5 border-t-2 text-center">
            <ul id="footer">
            </ul>                                
        </div>
    </div>

    @yield('scripts')

</body>
</html>
