<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">    <title>@yield('title', 'StepWise')</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#F2F3EF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="StepWise">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">

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

        /* Feel like an app: no text selection or long-press menu on controls */
        button, a, label, .no-select {
            -webkit-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }

        /* Apple's minimum touch target is 44px */
        button, select, .tap { min-height: 44px; }

        /* Grey placeholder blocks shown while something loads */
        .skeleton {
            display: block;
            border-radius: 8px;
            background: linear-gradient(90deg, #E4E6E1 25%, #EEF0EB 50%, #E4E6E1 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s linear infinite;
        }
        @keyframes shimmer { to { background-position: -200% 0; } }
        @media (prefers-reduced-motion: reduce) { .skeleton { animation: none; } }

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