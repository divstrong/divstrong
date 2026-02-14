<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitemap | divStrong</title>
    <meta name="description" content="Navigate all pages on the divStrong website.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- Navigation --}}
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8">
                </a>
                <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">&larr; Back to Home</a>
            </div>
        </div>
    </nav>

    {{-- Header --}}
    <section class="bg-gray-50 py-16 lg:py-24 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Navigation</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">Sitemap</h1>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Main Pages</h2>
                    <ul class="space-y-3">
                        <li><a href="{{ url('/') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Home</a></li>
                        <li><a href="{{ url('/#about') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">About</a></li>
                        <li><a href="{{ url('/#services') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Services</a></li>
                        <li><a href="{{ url('/#work') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Portfolio</a></li>
                        <li><a href="{{ route('clients') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Clients</a></li>
                        <li><a href="{{ url('/#contact') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Legal</h2>
                    <ul class="space-y-3">
                        <li><a href="{{ route('accessibility') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Accessibility</a></li>
                        <li><a href="{{ route('terms') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Terms & Conditions</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-lg text-gray-700 hover:text-brand transition-colors font-medium">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-neutral-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
            <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-7 brightness-0 invert mx-auto mb-4">
            <p class="text-sm text-gray-400">&copy; 2009-{{ date('Y') }} <a href="https://divstrong.com" class="hover:text-white transition-colors">divStrong</a></p>
        </div>
    </footer>

</body>
</html>
