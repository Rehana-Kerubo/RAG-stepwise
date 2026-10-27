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
        try {
            var standalone = window.navigator.standalone ||
                window.matchMedia('(display-mode: standalone)').matches;
            if (standalone && !sessionStorage.getItem('splashShown')) {
                sessionStorage.setItem('splashShown', '1');
                document.documentElement.classList.add('splash-on');
            }
        } catch (e) {}
    </script>
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

        document.addEventListener('click', (e) => {
            if (e.target.closest('button, a, label')) navigator.vibrate?.(8);
        });
        document.addEventListener('touchstart', function () {}, { passive: true });

        (function () {
            const bar = document.getElementById('nav-bar');

            document.addEventListener('click', (e) => {
                const tab = e.target.closest('nav a.tap');
                if (!tab || tab.hasAttribute('aria-current')) return;
                bar.style.transition = 'none';
                bar.style.width = '0';
                bar.style.opacity = '1';
                bar.offsetWidth; // forces the browser to register 0 first, so the slide starts from the left
                bar.style.transition = 'width 2.5s cubic-bezier(0.1, 0.6, 0.3, 1)';
                bar.style.width = '85%';
            });

            // Reset if the user comes back to this page from history
            window.addEventListener('pageshow', () => {
                bar.style.transition = 'none';
                bar.style.opacity = '0';
                bar.style.width = '0';
            });
        })();
        
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

        
        button, a, label {
            transition: transform 0.1s ease, opacity 0.1s ease;
        }
        button:active, a:active, label:active {
            transform: scale(0.96);
            opacity: 0.85;
        }

        ::view-transition-old(root) { animation: none; }
        ::view-transition-new(root) { animation: 0.25s cubic-bezier(0.2, 0, 0, 1) both vt-in; }
        @keyframes vt-in { from { opacity: 0; transform: translateY(10px); } }

        @media (prefers-reduced-motion: reduce) {
            ::view-transition-old(root), ::view-transition-new(root) { animation: none; }
        }

        nav a.tap svg { transition: transform 0.3s ease; }
        nav a.tap:active { background: #E8EBFC; }
        nav a.tap:active svg { transform: scale(0.99); }

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

        #splash {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: #F2F3EF;
            transition: opacity 0.35s ease;
        }
        html.splash-on #splash { display: flex; }
        #splash.hide { opacity: 0; pointer-events: none; }
        #splash img { width: 96px; height: 96px; border-radius: 22px; }
        #splash span { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; color: #17191F; }
    </style>
</head>

<body class="bg-[#E4E6E1] font-sans text-ink antialiased">
    <div id="nav-bar" style="position:fixed;top:0;left:0;height:3px;width:0;opacity:0;background:#2A3EDC;z-index:200;pointer-events:none;"></div>
    <div id="splash" aria-hidden="true">
        <img src="/icons/icon-192.png" alt="">
        <span>StepWise</span>
    </div>

    @yield('content')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>

    @stack('scripts')

    <script>
        window.addEventListener('load', function () {
            var splash = document.getElementById('splash');
            if (!splash) return;
            setTimeout(function () {
                splash.classList.add('hide');
                setTimeout(function () { splash.remove(); }, 400);
            }, 700);
        });
    </script>
</body>
</html>