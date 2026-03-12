@php
    $manifestPath = public_path('asset-manifest.json');
    $manifest = [];
    if (file_exists($manifestPath)) {
        $manifestData = json_decode(file_get_contents($manifestPath), true);
        $manifest = $manifestData['files'] ?? [];
    }
    
    // Use current timestamp to force cache bust on every request
    $cacheBuster = '?v=' . time();
    
    // Ensure absolute paths from manifest or empty string
    $mainJs = isset($manifest['main.js']) ? asset($manifest['main.js']) . $cacheBuster : '';
    $mainCss = isset($manifest['main.css']) ? asset($manifest['main.css']) . $cacheBuster : '';
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />

    <!-- Cache-Busting Meta Tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <meta name="theme-color" content="#000000" />
    <meta name="description" content="KoboPoint - Digital Services Platform" />
    <link rel="apple-touch-icon" href="/logo192.png" />
    <link rel="manifest" href="/manifest.json" />
    <title>{{ env('APP_NAME', 'KoboPoint') }}</title>

    <script>
        // Cache-Nuke: Force fresh load on deployment without logging out users
        (function () {
            const currentVersion = "{{ $mainJs }}";
            const savedVersion = localStorage.getItem('app_version');
            
            // 1. Unregister ALL Service Workers immediately
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function (registrations) {
                    for (let registration of registrations) {
                        registration.unregister();
                    }
                });
            }

            // 2. Clear ALL caches (but NOT localStorage - keeps user logged in)
            if (window.caches) {
                caches.keys().then(function (names) {
                    for (let name of names) caches.delete(name);
                });
            }

            // 3. Force reload ONCE if version changed
            if (savedVersion && savedVersion !== currentVersion) {
                localStorage.setItem('app_version', currentVersion);
                // Hard reload bypassing cache
                window.location.reload(true);
            } else {
                localStorage.setItem('app_version', currentVersion);
            }
        })();
    </script>

    @if($mainCss)
        <link href="{{ $mainCss }}" rel="stylesheet">
    @endif

    @if($mainJs)
        <script defer="defer" src="{{ $mainJs }}"></script>
    @endif
</head>

<body>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>
</body>

</html>