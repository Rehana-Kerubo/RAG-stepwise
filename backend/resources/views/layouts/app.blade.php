<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'StepWise')</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#F2F3EF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            counter: '#F2F3EF',
                            slip: '#FFFFFF',
                            ink: '#17191F',
                            muted: '#5B6070',
                            hairline: '#D9DCE3',
                            cobalt: { DEFAULT: '#2A3EDC', soft: '#E8EBFC', deep: '#1B2AA3' },
                            ledger: { DEFAULT: '#0B7A52', soft: '#E4F4EC' },
                            amber: { DEFAULT: '#F2B01E', soft: '#FFF3D1' }
                        },
                        fontFamily: {
                            sans: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif']
                        }
                    }
                }
            };
        }
    </script>

    <style>
        html { -webkit-text-size-adjust: 100%; }
        body {
            -webkit-tap-highlight-color: transparent;
            font-family: "Bricolage Grotesque", ui-sans-serif, system-ui, sans-serif;
        }
        button, a, label, select { touch-action: manipulation; }
        button:focus-visible, select:focus-visible, input:focus-visible, a:focus-visible {
            outline: 3px solid #2A3EDC;
            outline-offset: 2px;
        }
    </style>
</head>

<body class="bg-[#E4E6E1] font-sans text-ink antialiased">

    @yield('content')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>

    @stack('scripts')
</body>
</html>