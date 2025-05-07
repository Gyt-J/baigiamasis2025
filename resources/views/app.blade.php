<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Field Mapping App</title>
        @if(Route::is('login', 'register'))
            <!-- Only load CSS for auth pages -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        @else
            <!-- Load Vue only for authenticated pages -->
            @vite(['resources/js/app.js'])
        @endif
    </head>
    
    <body>
        @if(Route::is('login', 'register'))
            @yield('content')
        @else
            <div id="app"></div>
        @endif
    </body>
</html>