<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laravel API Wrapper')</title>

    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN — no build step required -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 text-white">

    <!-- Navigation -->
    <nav class="border-b border-slate-700 bg-slate-900/80 backdrop-blur-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-white hover:text-blue-400 transition-colors">
                🌐 Laravel API Wrapper
            </a>
            <div class="flex gap-6">
                <a href="{{ route('currency.index') }}"
                   class="text-slate-300 hover:text-white transition-colors {{ request()->routeIs('currency.*') ? 'text-white font-semibold border-b-2 border-blue-400 pb-1' : '' }}">
                    💱 Currency
                </a>
                <a href="{{ route('weather.index') }}"
                   class="text-slate-300 hover:text-white transition-colors {{ request()->routeIs('weather.*') ? 'text-white font-semibold border-b-2 border-blue-400 pb-1' : '' }}">
                    🌤 Weather
                </a>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <main class="max-w-4xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-700 mt-16 py-6 text-center text-slate-500 text-sm">
        Built with Laravel &amp; Alpine.js · Powered by
        <a href="https://www.exchangerate-api.com" class="hover:text-slate-300 underline" target="_blank" rel="noopener">ExchangeRate-API</a>
        &amp;
        <a href="https://open-meteo.com" class="hover:text-slate-300 underline" target="_blank" rel="noopener">Open-Meteo</a>
    </footer>

</body>
</html>
