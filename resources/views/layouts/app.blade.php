<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PhoneStore - nền tảng mua sắm điện thoại trực tuyến">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PhoneStore')</title>
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @php
            $viteManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $viteCss = $viteManifest['resources/css/app.css']['file'] ?? null;
            $viteJs = $viteManifest['resources/js/app.js']['file'] ?? null;
        @endphp
        @if ($viteCss)
            <link rel="stylesheet" href="{{ '/build/' . $viteCss }}">
        @endif
        @if ($viteJs)
            <script type="module" src="{{ '/build/' . $viteJs }}"></script>
        @endif
    @endif
</head>
<body class="ps-body">
    @include('partials.header')
    @include('partials.navbar')
    <main class="ps-main">
        @if (session('status'))
            <div class="container py-3"><div class="alert alert-info mb-0" role="status">{{ session('status') }}</div></div>
        @endif
        @yield('content')
    </main>
    @include('partials.footer')
    <div id="compare-bar" class="ps-compare-bar" hidden></div>
    <x-chat-widget />
</body>
</html>
