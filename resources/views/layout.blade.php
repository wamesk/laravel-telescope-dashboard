<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Telescope Dashboard</title>
    <link rel="stylesheet" href="{{ asset('vendor/telescope-dashboard/css/app.css') }}">
</head>
<body class="antialiased">
    @yield('content')
    <script src="{{ asset('vendor/telescope-dashboard/js/app.js') }}"></script>
</body>
</html>
