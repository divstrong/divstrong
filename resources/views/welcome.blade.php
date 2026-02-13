<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>divStrong | Full Stack, Web 3.0 App Builds</title>
    <meta name="description" content="We plan, design, and develop full-stack applications for B2B/B2C businesses worldwide.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('nav', { mobileOpen: false });
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- ==================== NAVIGATION ==================== --}}
    <nav
        x-data="{ scrolled: false }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 })"
        :class="scrolled ? 'translate-y-0' : '-translate-y-full'"
        class="fixed top-0 inset-x-0 z-50 bg-white border-b border-gray-100 transition-transform duration-500 ease-out -translate-y-full"
    >
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-12 lg:h-14">
                {{-- Logo --}}
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8">
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#about" class="text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-gray-900 transition-colors">About</a>
                    <div class="relative group">
                        <button class="text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-gray-900 transition-colors flex items-center gap-1">
                            Services
                            <svg class="w-3.5 h-3.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="absolute top-full left-0 pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="bg-white rounded-lg shadow-lg border border-gray-100 py-2 min-w-[160px]">
                                <a href="#services" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Strategy</a>
                                <a href="#services" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Design</a>
                                <a href="#services" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Coding</a>
                                <a href="#services" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Hosting</a>
                            </div>
                        </div>
                    </div>
                    <a href="#work" class="text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-gray-900 transition-colors">Portfolio</a>
                    <a href="#contact" class="text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-gray-900 transition-colors">Contact</a>
                </div>

                {{-- Right side: Client Portal --}}
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ url('/admin') }}" class="btn-brand inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-lg transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Client Portal
                    </a>
                </div>

                {{-- Mobile menu button (hamburger / X toggle) --}}
                <button @click="$store.nav.mobileOpen = !$store.nav.mobileOpen" class="md:hidden relative p-2 text-gray-600 hover:text-gray-900 w-10 h-10 flex items-center justify-center">
                    <span class="sr-only">Toggle menu</span>
                    <span class="block w-6 h-5 relative">
                        <span :class="$store.nav.mobileOpen ? 'rotate-45 top-2' : 'top-0'" class="absolute left-0 w-full h-0.5 bg-current transition-all duration-300 ease-in-out"></span>
                        <span :class="$store.nav.mobileOpen ? 'opacity-0 scale-x-0' : 'opacity-100 scale-x-100'" class="absolute left-0 top-2 w-full h-0.5 bg-current transition-all duration-300 ease-in-out"></span>
                        <span :class="$store.nav.mobileOpen ? '-rotate-45 top-2' : 'top-4'" class="absolute left-0 w-full h-0.5 bg-current transition-all duration-300 ease-in-out"></span>
                    </span>
                </button>
            </div>

        </div>
    </nav>

    {{-- Mobile Flyout Menu --}}
    <div x-data x-show="$store.nav.mobileOpen" class="fixed inset-0 z-[60] md:hidden" x-cloak>
        {{-- Backdrop --}}
        <div
            x-show="$store.nav.mobileOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="$store.nav.mobileOpen = false"
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        ></div>

        {{-- Slide-in Panel --}}
        <div
            x-show="$store.nav.mobileOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute top-0 right-0 h-full w-72 bg-white shadow-2xl flex flex-col"
        >
            {{-- Panel header with close --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-7">
                <button @click="$store.nav.mobileOpen = false" class="p-2 text-gray-400 hover:text-gray-900 cursor-pointer transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Nav links --}}
            <div class="flex-1 px-6 py-6 flex flex-col gap-1">
                <a @click="$store.nav.mobileOpen = false" href="#about" class="block px-3 py-3 text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-brand hover:bg-gray-50 rounded-lg transition-colors">About</a>
                <a @click="$store.nav.mobileOpen = false" href="#services" class="block px-3 py-3 text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-brand hover:bg-gray-50 rounded-lg transition-colors">Services</a>
                <a @click="$store.nav.mobileOpen = false" href="#work" class="block px-3 py-3 text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-brand hover:bg-gray-50 rounded-lg transition-colors">Portfolio</a>
                <a @click="$store.nav.mobileOpen = false" href="#contact" class="block px-3 py-3 text-sm font-bold uppercase tracking-wide text-gray-600 hover:text-brand hover:bg-gray-50 rounded-lg transition-colors">Contact</a>
            </div>

            {{-- Client Portal at bottom --}}
            <div class="px-6 py-6 border-t border-gray-100">
                <a href="{{ url('/admin') }}" class="btn-brand w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-white text-sm font-medium rounded-lg transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Client Portal
                </a>
            </div>
        </div>
    </div>

    {{-- ==================== HERO SECTION ==================== --}}
    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
        {{-- Background video --}}
        <div class="absolute inset-0">
            <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover opacity-90">
                <source src="{{ asset('videos/dalibg4.mp4') }}" type="video/mp4">
            </video>
        </div>
        {{-- Dark overlay for text readability --}}
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-20 lg:py-32 w-full">
            <div class="items-center">
                <div>
                    <!-- <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-4">Est. 2009 &mdash; Richmond, VA</p> -->
                    <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight leading-[1.05] mb-6 text-white" style="text-shadow: 0 2px 10px rgba(0,0,0,0.4), 0 4px 30px rgba(0,0,0,0.2);">
                        Full Stack,<br>
                        <span class="text-brand">Web 3.0</span><br>
                        App Builds
                    </h1>
                    <p class="text-lg lg:text-xl text-white/80 leading-relaxed max-w-lg mb-8" style="text-shadow: 0 1px 6px rgba(0,0,0,0.3);">
                        We plan, design, and develop AI-enabled applications for B2B/B2C clients worldwide.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#contact" class="btn-brand inline-flex items-center gap-2 px-8 py-3.5 text-white font-semibold rounded-lg transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            Get in Touch
                        </a>
                        <a href="#work" class="inline-flex items-center gap-2 px-8 py-3.5 border-2 border-white/30 text-white font-semibold rounded-lg hover:border-white hover:bg-white/10 transition-colors shadow-lg shadow-black/25">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                            </svg>
                            View Our Work
                        </a>
                    </div>

                    {{-- Team flags --}}
                    <!-- <div class="mt-12 flex items-center gap-6">
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Distributed Team</span>
                        <div class="flex items-center gap-3 text-2xl">
                            <span title="USA">&#127482;&#127480;</span>
                            <span title="Canada">&#127464;&#127462;</span>
                            <span title="Ukraine">&#127482;&#127462;</span>
                            <span title="Ireland">&#127470;&#127466;</span>
                        </div>
                    </div> -->
                </div>

                {{-- Hero visual (hidden — bg video replaces this)
                <div class="relative hidden lg:block">
                    <div class="relative aspect-square rounded-3xl overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/dali2.png') }}" alt="Creative digital solutions" class="w-full h-full object-cover">
                    </div>
                </div>
                --}}
            </div>
        </div>

        {{-- ========== CLIENT LOGOS (bottom of hero) ========== --}}
        @php
            $clients = [
                ['file' => 'pepsico.png', 'name' => 'PepsiCo', 'url' => 'https://www.pepsico.com'],
                ['file' => 'performancepickleball.png', 'name' => 'Performance Pickleball', 'url' => 'https://www.performancepickleball.com'],
                ['file' => 'freshmovemedia.png', 'name' => 'Fresh Move Media', 'url' => 'https://www.freshmovemedia.com'],
                ['file' => 'impromptu.png', 'name' => 'Impromptu', 'url' => 'https://www.lessplanmoredo.com'],
                ['file' => 'boden.png', 'name' => 'Boden Agency', 'url' => 'https://www.bodenagency.com'],
                ['file' => 'cottonnatural.png', 'name' => 'Cotton Natural', 'url' => 'https://www.cottonnatural.com'],
                ['file' => 'tsipromotionals.png', 'name' => 'TSI Promotionals', 'url' => 'https://www.tsipromotionals.com'],
                ['file' => 'sls.png', 'name' => 'Secured Link Society', 'url' => 'https://www.securedlinksociety.com'],
                ['file' => 'hemsworth.png', 'name' => 'Hemsworth Communications', 'url' => 'https://www.hemsworthcommunications.com'],
                ['file' => 'goldenseed.png', 'name' => 'Golden Seed', 'url' => 'https://www.goldenseed.com'],
                ['file' => 'casasrva.png', 'name' => 'Casas RVA', 'url' => 'https://www.casasrva.com'],
                ['file' => 'cousins.jpg', 'name' => 'Cousins Maine Lobster', 'url' => 'https://www.cousinsmainelobster.com'],
                ['file' => 'captapp.png', 'name' => 'The Capt App', 'url' => 'https://www.thecaptapp.com'],
                ['file' => 'partnersnapier.png', 'name' => 'Partners and Napier', 'url' => 'https://www.partnersandnapier.com'],
            ];
        @endphp
    </section>

    {{-- ==================== SERVICES ==================== --}}
    <section id="services" class="py-24 lg:py-32" x-data="{ active: null }">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">What We Do</p>
                <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-4">Custom Digital Solutions</h2>
                <p class="text-lg text-gray-500">From strategy to launch and beyond, we handle every stage of your digital product's lifecycle.</p>
            </div>

            {{-- Service Cards Grid --}}
            <div
                x-show="!active"
                x-transition:enter="transition ease-out duration-300 delay-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 origin-center"
            >
                {{-- Strategy --}}
                <div @click="active = 'strategy'" class="service-card group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300 cursor-pointer">
                    <div class="wave-icon w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 wave-text"><span style="--i:0">S</span><span style="--i:1">t</span><span style="--i:2">r</span><span style="--i:3">a</span><span style="--i:4">t</span><span style="--i:5">e</span><span style="--i:6">g</span><span style="--i:7">y</span></h3>
                    <p class="text-gray-500 leading-relaxed">Consulting and planning to craft digital solutions to solve client problems.</p>
                </div>

                {{-- Design --}}
                <div @click="active = 'design'" class="service-card group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300 cursor-pointer">
                    <div class="wave-icon w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 wave-text"><span style="--i:0">D</span><span style="--i:1">e</span><span style="--i:2">s</span><span style="--i:3">i</span><span style="--i:4">g</span><span style="--i:5">n</span></h3>
                    <p class="text-gray-500 leading-relaxed">Modern, feature-rich, user interface concepts provide a visual roadmap to success.</p>
                </div>

                {{-- Coding --}}
                <div @click="active = 'coding'" class="service-card group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300 cursor-pointer">
                    <div class="wave-icon w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 wave-text"><span style="--i:0">C</span><span style="--i:1">o</span><span style="--i:2">d</span><span style="--i:3">i</span><span style="--i:4">n</span><span style="--i:5">g</span></h3>
                    <p class="text-gray-500 leading-relaxed">Developing a functional product that matches the strategic + creative vision.</p>
                </div>

                {{-- Hosting --}}
                <div @click="active = 'hosting'" class="service-card group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300 cursor-pointer">
                    <div class="wave-icon w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 wave-text"><span style="--i:0">H</span><span style="--i:1">o</span><span style="--i:2">s</span><span style="--i:3">t</span><span style="--i:4">i</span><span style="--i:5">n</span><span style="--i:6">g</span></h3>
                    <p class="text-gray-500 leading-relaxed">Our cloud hosting solutions ensure uptime, performance, and proper maintenance.</p>
                </div>
            </div>

            {{-- Strategy Expanded --}}
            <div
                x-show="active === 'strategy'"
                x-transition:enter="transition ease-out duration-400 delay-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-cloak
                class="relative rounded-2xl border border-brand/20 shadow-xl shadow-brand/5 bg-gradient-to-br from-white to-brand/5 p-10 lg:p-14 origin-center"
            >
                <button @click="active = null" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 hover:bg-brand/10 flex items-center justify-center transition-colors group cursor-pointer">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-brand transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
                    <div>
                        <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold mb-4">Strategy</h3>
                        <p class="text-gray-500 text-lg leading-relaxed">Consulting and planning to craft digital solutions to solve client problems. We work with you to define goals, identify opportunities, and map out the path to success.</p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(item, i) in ['Discovery workshops & stakeholder interviews', 'Competitive analysis & market research', 'User journey mapping & persona development', 'Technical architecture & roadmap planning']" :key="i">
                            <div
                                x-show="active === 'strategy'"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-4"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                :style="'transition-delay: ' + (i * 100 + 200) + 'ms'"
                                class="flex items-start gap-3 p-4 rounded-xl bg-white/80 border border-gray-100"
                            >
                                <div class="w-6 h-6 rounded-full bg-brand/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-gray-700 font-medium" x-text="item"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Design Expanded --}}
            <div
                x-show="active === 'design'"
                x-transition:enter="transition ease-out duration-400 delay-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-cloak
                class="relative rounded-2xl border border-brand/20 shadow-xl shadow-brand/5 bg-gradient-to-br from-white to-brand/5 p-10 lg:p-14 origin-center"
            >
                <button @click="active = null" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 hover:bg-brand/10 flex items-center justify-center transition-colors group cursor-pointer">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-brand transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
                    <div>
                        <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold mb-4">Design</h3>
                        <p class="text-gray-500 text-lg leading-relaxed">Modern, feature-rich, user interface concepts provide a visual roadmap to success. We create designs that are both beautiful and functional.</p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(item, i) in ['Wireframing & interactive prototyping', 'UI/UX design with modern design systems', 'Brand identity & visual language development', 'Responsive design for all devices & platforms']" :key="i">
                            <div
                                x-show="active === 'design'"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-4"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                :style="'transition-delay: ' + (i * 100 + 200) + 'ms'"
                                class="flex items-start gap-3 p-4 rounded-xl bg-white/80 border border-gray-100"
                            >
                                <div class="w-6 h-6 rounded-full bg-brand/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-gray-700 font-medium" x-text="item"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Coding Expanded --}}
            <div
                x-show="active === 'coding'"
                x-transition:enter="transition ease-out duration-400 delay-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-cloak
                class="relative rounded-2xl border border-brand/20 shadow-xl shadow-brand/5 bg-gradient-to-br from-white to-brand/5 p-10 lg:p-14 origin-center"
            >
                <button @click="active = null" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 hover:bg-brand/10 flex items-center justify-center transition-colors group cursor-pointer">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-brand transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
                    <div>
                        <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold mb-4">Coding</h3>
                        <p class="text-gray-500 text-lg leading-relaxed">Developing a functional product that matches the strategic and creative vision. Clean, maintainable code built with modern technologies.</p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(item, i) in ['Full-stack web application development', 'API design & third-party integrations', 'Performance optimization & scalability', 'Quality assurance & automated testing']" :key="i">
                            <div
                                x-show="active === 'coding'"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-4"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                :style="'transition-delay: ' + (i * 100 + 200) + 'ms'"
                                class="flex items-start gap-3 p-4 rounded-xl bg-white/80 border border-gray-100"
                            >
                                <div class="w-6 h-6 rounded-full bg-brand/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-gray-700 font-medium" x-text="item"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Hosting Expanded --}}
            <div
                x-show="active === 'hosting'"
                x-transition:enter="transition ease-out duration-400 delay-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-cloak
                class="relative rounded-2xl border border-brand/20 shadow-xl shadow-brand/5 bg-gradient-to-br from-white to-brand/5 p-10 lg:p-14 origin-center"
            >
                <button @click="active = null" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 hover:bg-brand/10 flex items-center justify-center transition-colors group cursor-pointer">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-brand transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
                    <div>
                        <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold mb-4">Hosting</h3>
                        <p class="text-gray-500 text-lg leading-relaxed">Our cloud hosting solutions ensure uptime, performance, and proper maintenance. We keep your digital products running smoothly around the clock.</p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(item, i) in ['Managed cloud hosting & deployment', 'SSL certificates & security monitoring', 'Automated backups & disaster recovery', 'Performance monitoring & uptime guarantees']" :key="i">
                            <div
                                x-show="active === 'hosting'"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-4"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                :style="'transition-delay: ' + (i * 100 + 200) + 'ms'"
                                class="flex items-start gap-3 p-4 rounded-xl bg-white/80 border border-gray-100"
                            >
                                <div class="w-6 h-6 rounded-full bg-brand/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-gray-700 font-medium" x-text="item"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Client logo carousel --}}
    <div class="-mt-6 lg:-mt-8 pb-16 lg:pb-24 overflow-hidden">
        <div class="logo-carousel relative">
            <div class="logo-carousel-track">
                @foreach($clients as $client)
                    <div class="logo-carousel-item">
                        <a href="{{ $client['url'] }}" target="_blank" rel="noopener" class="group relative flex items-center justify-center bg-white rounded-2xl border border-gray-200 px-8 py-6 block hover:border-brand/30 hover:scale-[1.03] transition-all duration-300">
                            <img src="{{ asset('images/logo/' . $client['file']) }}" alt="{{ $client['name'] }}" class="max-h-14 max-w-[160px] object-contain grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-300">
                        </a>
                    </div>
                @endforeach
                @foreach($clients as $client)
                    <div class="logo-carousel-item">
                        <a href="{{ $client['url'] }}" target="_blank" rel="noopener" class="group relative flex items-center justify-center bg-white rounded-2xl border border-gray-200 px-8 py-6 block hover:border-brand/30 hover:scale-[1.03] transition-all duration-300">
                            <img src="{{ asset('images/logo/' . $client['file']) }}" alt="{{ $client['name'] }}" class="max-h-14 max-w-[160px] object-contain grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-300">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ==================== ABOUT ==================== --}}
    <section id="about" class="relative py-24 lg:py-32 bg-neutral-900 text-white overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <img src="{{ asset('images/church-hill.png') }}" alt="" class="w-full h-full object-cover grayscale opacity-10 animate-slow-zoom">
        </div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">About Us</p>
                    <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6">Building APIs & MVPs Since 2009</h2>
                    <p class="text-lg text-gray-400 leading-relaxed mb-6">
                        Our AI-enabled team of strategists, designers, and  developers create full-stack solutions for companies seeking to innovate, automate, and ultimately enhance their bottom line by investing in the digital ecosystem.
                    </p>
                    <p class="text-lg text-gray-400 leading-relaxed mb-8">
                        We create 
                    </p>
                    <div
                        class="grid grid-cols-3 gap-8"
                        x-data="{ started: false, years: 0, clients: 0, projects: 0 }"
                        x-init="
                            $nextTick(() => {
                                const observer = new IntersectionObserver((entries) => {
                                    if (entries[0].isIntersecting && !started) {
                                        started = true;
                                        observer.disconnect();
                                        const ease = (t) => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
                                        const animate = (target, setter, duration) => {
                                            const start = performance.now();
                                            const step = (now) => {
                                                const progress = Math.min((now - start) / duration, 1);
                                                setter(Math.round(ease(progress) * target));
                                                if (progress < 1) requestAnimationFrame(step);
                                            };
                                            requestAnimationFrame(step);
                                        };
                                        animate(17, (v) => years = v, 1800);
                                        setTimeout(() => animate(500, (v) => clients = v, 2000), 200);
                                        setTimeout(() => animate(1000, (v) => projects = v, 2200), 400);
                                    }
                                }, { threshold: 0.3, rootMargin: '0px 0px -100px 0px' });
                                observer.observe($el);
                            });
                        "
                    >
                        <div>
                            <p class="text-3xl font-extrabold text-brand"><span x-text="years">0</span>+</p>
                            <p class="text-sm text-gray-500 mt-1">Years in Business</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-brand"><span x-text="clients">0</span>+</p>
                            <p class="text-sm text-gray-500 mt-1">Clients</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-brand"><span x-text="projects.toLocaleString()">0</span>+</p>
                            <p class="text-sm text-gray-500 mt-1">Projects Delivered</p>
                        </div>
                    </div>
                </div>

                {{-- About visual --}}
                <div class="relative">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden">
                        <img src="{{ asset('images/team.gif') }}" alt="divStrong team" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FEATURED WORK ==================== --}}
    <section id="work" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Portfolio</p>
                <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-4">Featured Work</h2>
                <p class="text-lg text-gray-500">Here are some recent examples of successful projects we built for clients:</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                @foreach([
                    ['name' => 'Performance Pickleball', 'desc' => 'Web + native application builds to create a "country club" type experience for a premiere indoor pickleball facility.', 'image' => 'pickleball.png', 'url' => 'https://apps.apple.com/us/app/performance-pickleball/id6470171328'],
                    ['name' => 'PromoSoft', 'desc' => 'Custom software created by indsutry professionals to organize and automate the order fulfillment process for apparel decorators.', 'image' => 'thepromosoft.png', 'url' => 'https://www.thepromosoft.com'],
                    ['name' => 'Secured Link Society', 'desc' => 'Web + native application builds to support a networking group passing $Ms in referral business around Richmond, VA.', 'image' => 'sls.png', 'url' => 'https://www.securedlinksociety.us'],
                    ['name' => 'Strokin', 'desc' => 'AI-enabled native application that provides an easy way to score + handicap golf competitions among friends.', 'image' => 'strokin.png', 'url' => 'https://strokin.app'],
                ] as $project)
                    <a href="{{ $project['url'] ?? '#' }}" target="{{ $project['url'] ? '_blank' : '_self' }}" rel="{{ $project['url'] ? 'noopener' : '' }}" class="group relative rounded-2xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300 block">
                        @if($project['image'])
                            <div class="aspect-[16/10] overflow-hidden">
                                <img src="{{ asset('images/' . $project['image']) }}" alt="{{ $project['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                        @else
                            <div class="aspect-[16/10] bg-gradient-to-br from-gray-100 to-gray-200 group-hover:from-gray-200 group-hover:to-gray-300 transition-colors duration-300">
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="text-center text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <p class="text-xs opacity-50">Project screenshot</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1 group-hover:text-brand transition-colors">{{ $project['name'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $project['desc'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== VALUE PROPOSITION / CTA ==================== --}}
    <section class="relative py-24 lg:py-32 bg-neutral-900 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <img src="{{ asset('videos/cherryblossoms.gif') }}" alt="" class="w-full h-full object-cover grayscale opacity-15 animate-slow-zoom">
        </div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                "We should have gone the custom route sooner!"
            </h2>
            <p class="text-lg text-gray-400 mb-10 max-w-2xl mx-auto">
                -- Almost Every Client
            </p>
            <a href="#contact" class="btn-brand inline-flex items-center gap-2 px-10 py-4 text-white font-semibold text-lg rounded-lg transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>
                Start a Project
            </a>
        </div>
    </section>

    {{-- ==================== CONTACT ==================== --}}
    <section id="contact" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16">
                <div>
                    <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Contact Us</p>
                    <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6">Let's Build Something Together</h2>
                    <p class="text-lg text-gray-500 leading-relaxed mb-8">
                        Help us understand your current challenges & project goals so we can craft a solution to fit the need...
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Phone</p>
                                <a href="tel:+18043159609" class="text-gray-500 hover:text-brand transition-colors">+1 (804) 315-9609</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Email</p>
                                <a href="mailto:hello@divstrong.com" class="text-gray-500 hover:text-brand transition-colors">hello@divstrong.com</a>
                            </div>
                        </div>

                        <a href="https://maps.app.goo.gl/jaYY7THz7W9npx3F8" target="_blank" rel="noopener" class="flex items-start gap-4 group">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 group-hover:text-brand transition-colors">Location</p>
                                <p class="text-gray-500 group-hover:text-brand transition-colors">Richmond, VA</p>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Contact form --}}
                <div class="bg-white rounded-2xl p-8 lg:p-10 shadow-xl" x-data="{
                    selectedDate: '',
                    selectedTime: '',
                    submitted: false,
                    submitting: false,
                    error: '',
                    isWeekday(dateStr) {
                        const d = new Date(dateStr + 'T12:00:00');
                        const day = d.getDay();
                        return day !== 0 && day !== 6;
                    },
                    get minDate() {
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        return tomorrow.toISOString().split('T')[0];
                    },
                    validateDate() {
                        if (this.selectedDate && !this.isWeekday(this.selectedDate)) {
                            this.selectedDate = '';
                        }
                    },
                    async submitForm(e) {
                        this.submitting = true;
                        this.error = '';
                        const form = e.target;
                        const data = Object.fromEntries(new FormData(form));
                        try {
                            const res = await fetch('/appointment', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(data)
                            });
                            if (!res.ok) {
                                const err = await res.json();
                                this.error = err.message || 'Something went wrong. Please try again.';
                                this.submitting = false;
                                return;
                            }
                            this.submitted = true;
                        } catch (err) {
                            this.error = 'Something went wrong. Please try again.';
                            this.submitting = false;
                        }
                    }
                }">
                    <div x-show="submitted" x-cloak class="text-center py-12">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Thank You!</h3>
                        <p class="text-gray-500 leading-relaxed">Your appointment request has been sent. We'll review the details and get back to you shortly to confirm.</p>
                    </div>
                    <div x-show="!submitted">
                        <!-- <h3 class="text-xl font-bold mb-6">Request an Appointment</h3> -->
                        <form class="space-y-5" @submit.prevent="submitForm($event)">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" id="name" name="name" required class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all" placeholder="Your name">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all" placeholder="you@company.com">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                        <input type="date" id="date" name="date" required
                                               x-model="selectedDate"
                                               :min="minDate"
                                               @change="validateDate()"
                                               class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                                    </div>
                                    <div>
                                        <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                                        <select id="time" name="time" required x-model="selectedTime"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all bg-white">
                                            <option value="">Select a time</option>
                                            <option value="10:00 AM">10:00 AM EST</option>
                                            <option value="10:30 AM">10:30 AM EST</option>
                                            <option value="11:00 AM">11:00 AM EST</option>
                                            <option value="11:30 AM">11:30 AM EST</option>
                                            <option value="12:00 PM">12:00 PM EST</option>
                                            <option value="12:30 PM">12:30 PM EST</option>
                                            <option value="1:00 PM">1:00 PM EST</option>
                                            <option value="1:30 PM">1:30 PM EST</option>
                                            <option value="2:00 PM">2:00 PM EST</option>
                                            <option value="2:30 PM">2:30 PM EST</option>
                                            <option value="3:00 PM">3:00 PM EST</option>
                                            <option value="3:30 PM">3:30 PM EST</option>
                                            <option value="4:00 PM">4:00 PM EST</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Describe Your Project</label>
                                    <textarea id="description" name="description" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all resize-none" placeholder="Brief description of what you're looking to build or discuss..."></textarea>
                                </div>
                                <p x-show="error" x-text="error" class="text-red-600 text-sm"></p>
                                <button type="submit" :disabled="submitting" class="btn-brand w-full inline-flex items-center justify-center gap-2 px-8 py-3.5 text-white font-semibold rounded-lg transition-all duration-300 disabled:opacity-50 cursor-pointer">
                                    <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                    </svg>
                                    <span x-show="!submitting">Request Appointment</span>
                                    <span x-show="submitting">Sending...</span>
                                </button>
                            </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="relative bg-neutral-900 text-white py-16 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <img src="{{ asset('images/rva-street.png') }}" alt="" class="w-full h-full object-cover grayscale opacity-10 animate-slow-zoom">
        </div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-10 mb-12">
                {{-- Brand --}}
                <div class="md:col-span-1">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8 brightness-0 invert">
                    <p class="text-gray-300 text-sm mt-4 leading-relaxed">
                        Full-stack digital agency building scalable, open-source cloud solutions from our offices in Central Virginia.
                    </p>
                    <div class="flex items-center gap-3 mt-4">
                        <a href="https://www.linkedin.com/company/divstrong" target="_blank" rel="noopener" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Menu --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white mb-4">Menu</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-gray-300 hover:text-white transition-colors">Overview</a></li>
                        <li><a href="#about" class="text-sm text-gray-300 hover:text-white transition-colors">About</a></li>
                        <li><a href="#work" class="text-sm text-gray-300 hover:text-white transition-colors">Portfolio</a></li>
                        <li><a href="#services" class="text-sm text-gray-300 hover:text-white transition-colors">Services</a></li>
                        <li><a href="#contact" class="text-sm text-gray-300 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                {{-- Legal --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white mb-4">Legal</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('accessibility') }}" class="text-sm text-gray-300 hover:text-white transition-colors">Accessibility</a></li>
                        <li><a href="{{ route('terms') }}" class="text-sm text-gray-300 hover:text-white transition-colors">Terms</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-sm text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-300 hover:text-white transition-colors">Sitemap</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-white mb-4">Contact</h4>
                    <ul class="space-y-3">
                        <li><a href="mailto:hello@divstrong.com" class="text-sm text-gray-300 hover:text-white transition-colors">hello@divstrong.com</a></li>
                        <li><a href="tel:+18043159609" class="text-sm text-gray-300 hover:text-white transition-colors">+1 (804) 315-9609</a></li>
                        <li class="text-sm text-gray-300 leading-relaxed">14321 Winter Breeze Drive Suite #55<br>Midlothian, VA 23113</li>
                    </ul>
                </div>
            </div>

            <div class="pt-8">
                <p class="text-sm text-gray-300">&copy; 2009-{{ date('Y') }} <a href="https://divstrong.com">divStrong</a></p>
            </div>
        </div>
    </footer>

    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }

        .logo-carousel {
            mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        }
        .logo-carousel-track {
            display: flex;
            align-items: center;
            width: max-content;
            animation: logo-scroll 60s linear infinite;
        }
        .logo-carousel-track:hover {
            animation-play-state: paused;
        }
        .logo-carousel-item {
            flex-shrink: 0;
            padding: 0 0.75rem;
        }
        @keyframes logo-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @keyframes slow-zoom {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        .animate-slow-zoom {
            animation: slow-zoom 20s ease-in-out infinite;
        }
        .btn-brand {
            background: linear-gradient(to bottom, #f43f4f 0%, #ed2537 40%, #c91e2e 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 2px 8px rgba(237,37,55,0.3);
            border: 1px solid #b91a27;
            transition: transform 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
        }
        .btn-brand:hover {
            transform: scale(1.06);
            color: #111827 !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 6px 20px rgba(237,37,55,0.4);
        }
        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .service-card:hover {
            transform: scale(1.04);
        }
        .wave-icon {
            transition: transform 0.3s ease;
        }
        .group:hover .wave-icon {
            animation: wave-bubble-icon 0.45s ease forwards;
        }
        .wave-text span {
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .group:hover .wave-text span {
            animation: wave-bubble 0.4s ease forwards;
            animation-delay: calc(0.08s + var(--i) * 0.05s);
        }
        @keyframes wave-bubble-icon {
            0% { transform: translateY(0) scale(1); }
            35% { transform: translateY(-8px) scale(1.18); }
            100% { transform: translateY(0) scale(1); }
        }
        @keyframes wave-bubble {
            0% { transform: translateY(0) scale(1); }
            40% { transform: translateY(-6px) scale(1.15); }
            100% { transform: translateY(0) scale(1); }
        }
    </style>
</body>
</html>
