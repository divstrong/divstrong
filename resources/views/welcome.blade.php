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
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- ==================== NAVIGATION ==================== --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                {{-- Logo --}}
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8">
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#about" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">About</a>
                    <div class="relative group">
                        <button class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors flex items-center gap-1">
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
                    <a href="#work" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Portfolio</a>
                    <a href="#contact" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Contact</a>
                </div>

                {{-- Right side: Client Portal --}}
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Client Portal
                    </a>
                </div>

                {{-- Mobile menu button --}}
                <button id="mobile-menu-btn" class="md:hidden p-2 text-gray-600 hover:text-gray-900" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            {{-- Mobile Nav --}}
            <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-100">
                <div class="pt-4 flex flex-col gap-3">
                    <a href="#about" class="text-sm font-medium text-gray-600 hover:text-gray-900">About</a>
                    <a href="#services" class="text-sm font-medium text-gray-600 hover:text-gray-900">Services</a>
                    <a href="#work" class="text-sm font-medium text-gray-600 hover:text-gray-900">Portfolio</a>
                    <a href="#contact" class="text-sm font-medium text-gray-600 hover:text-gray-900">Contact</a>
                    <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Client Portal
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ==================== HERO SECTION ==================== --}}
    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
        {{-- Background gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-gray-50 via-white to-gray-100"></div>
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-brand/5 to-transparent"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-20 lg:py-32 w-full">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div>
                    <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-4">Est. 2009 &mdash; Richmond, VA</p>
                    <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight leading-[1.05] mb-6">
                        Full Stack,<br>
                        <span class="text-brand">Web 3.0</span><br>
                        App Builds
                    </h1>
                    <p class="text-lg lg:text-xl text-gray-600 leading-relaxed max-w-lg mb-8">
                        We plan, design, and develop full-stack applications for B2B/B2C businesses worldwide.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#contact" class="inline-flex items-center px-8 py-3.5 bg-brand text-white font-semibold rounded-lg hover:bg-gray-900 transition-colors shadow-lg shadow-brand/25">
                            Get in Touch
                        </a>
                        <a href="#work" class="inline-flex items-center px-8 py-3.5 border-2 border-gray-200 text-gray-700 font-semibold rounded-lg hover:border-gray-900 hover:text-gray-900 transition-colors">
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

                {{-- Hero visual --}}
                <div class="relative hidden lg:block">
                    <div class="relative aspect-square rounded-3xl overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/creativity.png') }}" alt="Creative digital solutions" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== CLIENT LOGOS ==================== --}}
    @php
        $clients = [
            ['file' => 'pepsico.png', 'name' => 'PepsiCo'],
            ['file' => 'performancepickleball.png', 'name' => 'Performance Pickleball'],
            ['file' => 'freshmovemedia.png', 'name' => 'Fresh Move Media'],
            ['file' => 'impromptu.png', 'name' => 'Impromptu'],
            ['file' => 'boden.png', 'name' => 'Boden Agency'],
            ['file' => 'cottonnatural.png', 'name' => 'Cotton Natural'],
            ['file' => 'tsipromotionals.png', 'name' => 'TSI Promotionals'],
            ['file' => 'sls.png', 'name' => 'Secured Link Society'],
            ['file' => 'hemsworth.png', 'name' => 'Hemsworth Communications'],
            ['file' => 'goldenseed.png', 'name' => 'Golden Seed'],
            ['file' => 'casasrva.png', 'name' => 'Casas RVA'],
            ['file' => 'fitfoodfresh.png', 'name' => 'Fit Food Fresh'],
            ['file' => 'cryptofantasy.png', 'name' => 'Crypto Fantasy'],
            ['file' => 'cousinsmainelobster.png', 'name' => 'Cousins Maine Lobster'],
            ['file' => 'captapp.png', 'name' => 'The Capt App'],
            ['file' => 'partnersnapier.png', 'name' => 'Partners and Napier'],
        ];
    @endphp
    <section class="py-16 lg:py-20 bg-gray-50 border-y border-gray-100 overflow-hidden">
        <p class="text-center text-xs font-semibold text-gray-400 uppercase tracking-widest mb-10">Our Clients</p>
        <div class="logo-carousel relative">
            <div class="logo-carousel-track">
                @foreach($clients as $client)
                    <div class="logo-carousel-item">
                        <img src="{{ asset('images/logo/' . $client['file']) }}" alt="{{ $client['name'] }}" class="max-h-20 max-w-[200px] object-contain">
                    </div>
                @endforeach
                {{-- Duplicate for seamless loop --}}
                @foreach($clients as $client)
                    <div class="logo-carousel-item">
                        <img src="{{ asset('images/logo/' . $client['file']) }}" alt="{{ $client['name'] }}" class="max-h-20 max-w-[200px] object-contain">
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== SERVICES ==================== --}}
    <section id="services" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">What We Do</p>
                <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-4">Custom Digital Solutions</h2>
                <p class="text-lg text-gray-500">From strategy to launch and beyond, we handle every stage of your digital product's lifecycle.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                {{-- Strategy --}}
                <div class="group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300">
                    <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Strategy</h3>
                    <p class="text-gray-500 leading-relaxed">Consulting and planning to craft digital solutions to solve client problems.</p>
                </div>

                {{-- Design --}}
                <div class="group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300">
                    <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Design</h3>
                    <p class="text-gray-500 leading-relaxed">Modern, feature-rich, user interface concepts provide a visual roadmap to success.</p>
                </div>

                {{-- Coding --}}
                <div class="group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300">
                    <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Coding</h3>
                    <p class="text-gray-500 leading-relaxed">Developing a functional product that matches the strategic + creative vision.</p>
                </div>

                {{-- Hosting --}}
                <div class="group relative p-8 rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300">
                    <div class="w-12 h-12 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand/20 transition-colors">
                        <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Hosting</h3>
                    <p class="text-gray-500 leading-relaxed">Our cloud hosting solutions ensure uptime, performance, and proper maintenance.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ABOUT ==================== --}}
    <section id="about" class="py-24 lg:py-32 bg-neutral-900 text-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">About Us</p>
                    <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6">Building APIs & MVPs Since 2009</h2>
                    <p class="text-lg text-gray-400 leading-relaxed mb-6">
                        Our AI-enabled team of strategists, designers, developers, and testers create custom, full-stack solutions for companies seeking to innovate, automate, and ultimately enhance their digital ecosystem.
                    </p>
                    <p class="text-lg text-gray-400 leading-relaxed mb-8">
                        We create 
                    </p>
                    <div class="grid grid-cols-3 gap-8">
                        <div>
                            <p class="text-3xl font-extrabold text-brand">17+</p>
                            <p class="text-sm text-gray-500 mt-1">Years in Business</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-brand">500+</p>
                            <p class="text-sm text-gray-500 mt-1">Clients</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-brand">1000+</p>
                            <p class="text-sm text-gray-500 mt-1">Projects Delivered</p>
                        </div>
                    </div>
                </div>

                {{-- About visual --}}
                <div class="relative">
                    <div class="aspect-[4/3] rounded-2xl overflow-hidden">
                        <img src="{{ asset('images/team2.png') }}" alt="divStrong team" class="w-full h-full object-cover">
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
                <p class="text-lg text-gray-500">A selection of projects we've built for clients across industries.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                @foreach([
                    ['name' => 'Performance Pickleball', 'desc' => 'E-commerce platform for premium pickleball equipment and gear.'],
                    ['name' => 'PromoSoft', 'desc' => 'Brand identity and web presence for a natural cotton products company.'],
                    ['name' => 'Secured Link Society', 'desc' => 'Real estate platform connecting buyers with Richmond-area properties.'],
                    ['name' => 'Strokin', 'desc' => 'Social events platform designed to bring people together spontaneously.'],
                ] as $project)
                    <div class="group relative rounded-2xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300">
                        {{-- Project image placeholder --}}
                        <div class="aspect-[16/10] bg-gradient-to-br from-gray-100 to-gray-200 group-hover:from-gray-200 group-hover:to-gray-300 transition-colors duration-300">
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-xs opacity-50">Project screenshot</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1 group-hover:text-brand transition-colors">{{ $project['name'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $project['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== VALUE PROPOSITION / CTA ==================== --}}
    <section class="relative py-24 lg:py-32 bg-neutral-900 overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ asset('images/church-hill.png') }}" alt="" class="w-full h-full object-cover grayscale opacity-15">
        </div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                "We should have gone the custom route sooner!"
            </h2>
            <p class="text-lg text-gray-400 mb-10 max-w-2xl mx-auto">
                -- Nearly Every Client
            </p>
            <a href="#contact" class="inline-flex items-center px-10 py-4 bg-brand text-white font-semibold text-lg rounded-lg hover:bg-gray-900 transition-colors shadow-lg shadow-brand/25">
                Start a Project
            </a>
        </div>
    </section>

    {{-- ==================== CONTACT ==================== --}}
    <section id="contact" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16">
                <div>
                    <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Get in Touch</p>
                    <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6">Let's Build Something Together</h2>
                    <p class="text-lg text-gray-500 leading-relaxed mb-8">
                        Whether you need a full-stack application, a brand refresh, or cloud hosting, we're here to help bring your vision to life.
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

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Location</p>
                                <p class="text-gray-500">Richmond, VA</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact form --}}
                <div class="bg-gray-50 rounded-2xl p-8 lg:p-10" x-data="{
                    selectedDate: '',
                    selectedTime: '',
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
                            alert('Please select a weekday (Monday\u2013Friday).');
                        }
                    },
                    get timeSlots() {
                        const slots = [];
                        for (let h = 10; h <= 16; h++) {
                            const hour12 = h > 12 ? h - 12 : h;
                            const ampm = h >= 12 ? 'PM' : 'AM';
                            const val = (h < 10 ? '0' : '') + h + ':00';
                            slots.push({ value: val, label: hour12 + ':00 ' + ampm + ' EST' });
                            if (h < 16) {
                                const val30 = (h < 10 ? '0' : '') + h + ':30';
                                slots.push({ value: val30, label: hour12 + ':30 ' + ampm + ' EST' });
                            }
                        }
                        return slots;
                    }
                }">
                    <h3 class="text-xl font-bold mb-6">Request an Appointment</h3>
                    <form class="space-y-5">
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
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Preferred Date</label>
                                <input type="date" id="date" name="date" required
                                       x-model="selectedDate"
                                       :min="minDate"
                                       @change="validateDate()"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all">
                                <p class="text-xs text-gray-400 mt-1">Mon&ndash;Fri only</p>
                            </div>
                            <div>
                                <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label>
                                <select id="time" name="time" required x-model="selectedTime"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all bg-white">
                                    <option value="">Select a time</option>
                                    <template x-for="slot in timeSlots" :key="slot.value">
                                        <option :value="slot.value" x-text="slot.label"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">10 AM&ndash;4 PM EST</p>
                            </div>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Tell Us About Your Project</label>
                            <textarea id="description" name="description" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all resize-none" placeholder="Brief description of what you're looking to build or discuss..."></textarea>
                        </div>
                        <button type="submit" class="w-full px-8 py-3.5 bg-brand text-white font-semibold rounded-lg hover:bg-gray-900 transition-colors shadow-lg shadow-brand/25">
                            Request Appointment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="bg-neutral-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-10 mb-12">
                {{-- Brand --}}
                <div class="md:col-span-1">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8 brightness-0 invert">
                    <p class="text-gray-500 text-sm mt-4 leading-relaxed">
                        Full-stack digital agency building scalable, open-source cloud solutions since 2009.
                    </p>
                    <div class="flex items-center gap-3 mt-4 text-lg">
                        <span>&#127482;&#127480;</span>
                        <span>&#127464;&#127462;</span>
                        <span>&#127482;&#127462;</span>
                        <span>&#127470;&#127466;</span>
                    </div>
                </div>

                {{-- Menu --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Menu</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Overview</a></li>
                        <li><a href="#about" class="text-sm text-gray-500 hover:text-white transition-colors">About</a></li>
                        <li><a href="#work" class="text-sm text-gray-500 hover:text-white transition-colors">Portfolio</a></li>
                        <li><a href="#services" class="text-sm text-gray-500 hover:text-white transition-colors">Services</a></li>
                        <li><a href="#contact" class="text-sm text-gray-500 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                {{-- Legal --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Legal</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Accessibility</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Terms</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-white transition-colors">Sitemap</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Contact</h4>
                    <ul class="space-y-3">
                        <li><a href="mailto:hello@divstrong.com" class="text-sm text-gray-500 hover:text-white transition-colors">hello@divstrong.com</a></li>
                        <li><a href="tel:+18043159609" class="text-sm text-gray-500 hover:text-white transition-colors">+1 (804) 315-9609</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-600">&copy; {{ date('Y') }} divStrong. All rights reserved.</p>
                <a href="https://www.linkedin.com/company/divstrong" target="_blank" rel="noopener" class="text-gray-600 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <style>
        html { scroll-behavior: smooth; }

        .logo-carousel {
            mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        }
        .logo-carousel-track {
            display: flex;
            align-items: center;
            width: max-content;
            animation: logo-scroll 80s linear infinite;
        }
        .logo-carousel-track:hover {
            animation-play-state: paused;
        }
        .logo-carousel-item {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 2.5rem;
        }
        @keyframes logo-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
    </style>
</body>
</html>
