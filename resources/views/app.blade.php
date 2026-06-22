<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'ARM Holding') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- SEO Meta Tags -->
        <meta name="description" content="ARM Holding - Plateforme d'investissement leader. Maximisez vos rendements financiers grâce à des solutions d'investissement sécurisées, transparentes et performantes. Rejoignez notre communauté d'investisseurs dès aujourd'hui.">
        <meta name="keywords" content="ARM, ARM Holding, investissement, placement financier, rendement, rentabilité, capital, crypto, finance, Cameroun, investir">
        <meta name="author" content="ARM Holding">
        <meta name="robots" content="index, follow">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://armicm.com">
        <meta property="og:title" content="ARM Holding | Plateforme d'Investissement Sécurisée">
        <meta property="og:description" content="Maximisez vos rendements financiers grâce à des solutions d'investissement sécurisées, transparentes et performantes. Rejoignez ARM Holding dès aujourd'hui.">
        <meta property="og:image" content="https://armicm.com/images/logo.jpg">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="https://armicm.com">
        <meta property="twitter:title" content="ARM Holding | Plateforme d'Investissement Sécurisée">
        <meta property="twitter:description" content="Maximisez vos rendements financiers grâce à des solutions d'investissement sécurisées, transparentes et performantes. Rejoignez ARM Holding dès aujourd'hui.">
        <meta property="twitter:image" content="https://armicm.com/images/logo.jpg">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
        <style>
            @keyframes loader-pulse {
                0%, 100% { opacity: 0.3; transform: scale(1); filter: blur(8px); }
                50% { opacity: 0.6; transform: scale(1.08); filter: blur(10px); }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <!-- PREMIUM COSMIC LAUNCH LOADER -->
        <div id="global-loader" style="position: fixed; inset: 0; z-index: 99999; background-color: #05020c; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: opacity 0.4s ease-out, visibility 0.4s ease-out; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;">
            <div style="width: 260px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 18px;">
                <!-- Logo with cosmic glowing halo -->
                <div style="position: relative; width: 62px; height: 62px;">
                    <img src="/images/logo.jpg" alt="ARM Holding" style="width: 62px; height: 62px; border-radius: 16px; border: 1px solid rgba(168, 85, 247, 0.25); box-shadow: 0 0 15px rgba(168, 85, 247, 0.3); object-fit: cover;" />
                    <div style="position: absolute; inset: -4px; border-radius: 20px; background: linear-gradient(45deg, #a855f7, #ec4899); opacity: 0.3; animation: loader-pulse 2s infinite;"></div>
                </div>
                
                <!-- Status text and percentage -->
                <div style="color: #ffffff; font-weight: 800; font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; font-family: monospace;">
                    Lancement <span id="loader-percent" style="color: #a855f7; font-weight: 900;">0%</span>
                </div>
                
                <!-- Purple loading bar container -->
                <div style="width: 100%; height: 5px; background-color: rgba(255, 255, 255, 0.04); border-radius: 9999px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.03);">
                    <!-- Glowing progress bar fill -->
                    <div id="loader-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #8b5cf6, #d946ef); box-shadow: 0 0 8px #a855f7; border-radius: 9999px; transition: width 0.1s ease-out;"></div>
                </div>
                
                <!-- Subtitle status -->
                <div style="color: rgba(255, 255, 255, 0.35); font-size: 7.5px; font-weight: 800; letter-spacing: 0.2em; text-transform: uppercase; font-family: monospace; margin-top: -2px;">
                    [ SECURE CONNECTION ]
                </div>
            </div>
        </div>
        <script>
            (function() {
                const loader = document.getElementById('global-loader');
                const percentText = document.getElementById('loader-percent');
                const bar = document.getElementById('loader-bar');
                
                let progress = 0;
                let interval;
                
                function updateProgress() {
                    if (progress < 90) {
                        // Fast cosmic acceleration for 1s max runtime
                        const increment = progress < 45 ? 12 : (progress < 75 ? 5 : 2);
                        progress = Math.min(90, progress + increment);
                        
                        if (percentText) percentText.innerText = Math.round(progress) + '%';
                        if (bar) bar.style.width = progress + '%';
                    }
                }
                
                interval = setInterval(updateProgress, 20);

                // Fail-safe backup timer to guarantee loader clears in max 1000ms (700ms progress + 300ms fadeout)
                const backupTimeout = setTimeout(dismissLoader, 700);
                
                function dismissLoader() {
                    clearTimeout(backupTimeout);
                    clearInterval(interval);
                    progress = 100;
                    if (percentText) percentText.innerText = '100%';
                    if (bar) bar.style.width = '100%';
                    
                    setTimeout(function() {
                        if (loader) {
                            loader.style.opacity = '0';
                            loader.style.visibility = 'hidden';
                            setTimeout(function() {
                                loader.remove();
                            }, 250);
                        }
                    }, 50);
                }
                
                // Hide when assets have finished loading
                window.addEventListener('load', dismissLoader);
            })();
        </script>
        @inertia
    </body>
</html>
