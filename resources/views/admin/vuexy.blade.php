<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding->display_app_name }}</title>
    @if ($branding->favicon_url)
        <link rel="icon" href="{{ $branding->favicon_url }}">
    @endif
    @vite('src/main.js')
</head>
<body>
    <div id="app"></div>
</body>
</html>
