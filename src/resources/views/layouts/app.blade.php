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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    function openAlert(title, text, image = '') {
        htmlText = '<div style="display: flex;">';
        htmlText += '<div style="flex: 1; text-align: left;">' + text + '</div>';
        if(image.length > 0) {
            htmlText += '<div style="flex 1; margin-left: 1rem;"><img src="' + image + '"/><div>';
        }
        htmlText += '</div>';
        Swal.fire({
                    title: title,
                    html: htmlText,
                    showCloseButton: true,
                    confirmButtonText: 'OK'
                });
    }

    function checkAll(source, key) {
        let checkboxes = document.querySelectorAll('input[name^="' + key + '["][name$="][delete]"]');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }
        </script>

</body>
</html>
