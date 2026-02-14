<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Clients | divStrong</title>
    <meta name="description" content="Browse the full list of divStrong clients across industries and service types.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Portfolio</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">Our Clients</h1>
            <p class="text-lg text-gray-500 mt-4 max-w-2xl mx-auto">Companies we've partnered with to build, launch, and scale digital products.</p>
        </div>
    </section>

    {{-- Client Data --}}
    @php
        $clients = [
            ['file' => 'pepsico.png', 'name' => 'PepsiCo', 'url' => 'https://www.pepsico.com', 'description' => 'Marketing campaign landing pages and digital assets for a global food and beverage leader.', 'industry' => 'Food & Beverage', 'service' => 'Web App'],
            ['file' => 'performancepickleball.png', 'name' => 'Performance Pickleball', 'url' => 'https://www.performancepickleball.com', 'description' => 'Web and native apps creating a country-club experience for a premiere indoor pickleball facility.', 'industry' => 'Sports & Recreation', 'service' => 'Native App'],
            ['file' => 'freshmovemedia.png', 'name' => 'Fresh Move Media', 'url' => 'https://www.freshmovemedia.com', 'description' => 'Brand website for a Richmond-based creative video production agency.', 'industry' => 'Marketing & Media', 'service' => 'Web App'],
            ['file' => 'impromptu.png', 'name' => 'Impromptu', 'url' => 'https://www.lessplanmoredo.com', 'description' => 'Platform for spontaneous local event discovery and social planning.', 'industry' => 'Technology', 'service' => 'Web App'],
            ['file' => 'boden.png', 'name' => 'Boden Agency', 'url' => 'https://www.bodenagency.com', 'description' => 'Full website redesign for a marketing and communications agency.', 'industry' => 'Marketing & Media', 'service' => 'Web App'],
            ['file' => 'cottonnatural.png', 'name' => 'Cotton Natural', 'url' => 'https://www.cottonnatural.com', 'description' => 'E-commerce platform for a sustainable, natural fiber apparel brand.', 'industry' => 'Apparel', 'service' => 'E-Commerce'],
            ['file' => 'tsipromotionals.png', 'name' => 'TSI Promotionals', 'url' => 'https://www.tsipromotionals.com', 'description' => 'Custom web platform for a promotional products and branded merchandise company.', 'industry' => 'Apparel', 'service' => 'E-Commerce'],
            ['file' => 'sls.png', 'name' => 'Secured Link Society', 'url' => 'https://www.securedlinksociety.com', 'description' => 'Web and native apps powering a professional networking group passing millions in referral business.', 'industry' => 'Networking', 'service' => 'Native App'],
            ['file' => 'hemsworth.png', 'name' => 'Hemsworth Communications', 'url' => 'https://www.hemsworthcommunications.com', 'description' => 'Corporate website for a top-tier public relations and communications firm.', 'industry' => 'Marketing & Media', 'service' => 'Web App'],
            ['file' => 'goldenseed.png', 'name' => 'Golden Seed', 'url' => 'https://www.goldenseed.com', 'description' => 'Digital presence for an angel investor network funding high-growth startups.', 'industry' => 'Finance', 'service' => 'Web App'],
            ['file' => 'casasrva.png', 'name' => 'Casas RVA', 'url' => 'https://www.casasrva.com', 'description' => 'Property listing website for a Richmond-area real estate group.', 'industry' => 'Real Estate', 'service' => 'Web App'],
            ['file' => 'cousins.jpg', 'name' => 'Cousins Maine Lobster', 'url' => 'https://www.cousinsmainelobster.com', 'description' => 'Digital ordering and franchise platform for the Shark Tank-featured seafood brand.', 'industry' => 'Food & Beverage', 'service' => 'E-Commerce'],
            ['file' => 'captapp.png', 'name' => 'The Capt App', 'url' => 'https://www.thecaptapp.com', 'description' => 'Native mobile app connecting fishing charter captains with customers.', 'industry' => 'Sports & Recreation', 'service' => 'Native App'],
            ['file' => 'partnersnapier.png', 'name' => 'Partners + Napier', 'url' => 'https://www.partnersandnapier.com', 'description' => 'Website build for an award-winning full-service advertising agency.', 'industry' => 'Marketing & Media', 'service' => 'Web App'],
            ['file' => 'cryptofantasy.png', 'name' => 'Crypto Fantasy', 'url' => '#', 'description' => 'Fantasy sports-style platform for cryptocurrency portfolio competitions.', 'industry' => 'Technology', 'service' => 'Web App'],
            ['file' => 'stellwagen.png', 'name' => 'Stellwagen', 'url' => '#', 'description' => 'Corporate website and branding for a business consulting firm.', 'industry' => 'Finance', 'service' => 'Branding'],
        ];

        $industries = collect($clients)->pluck('industry')->unique()->sort()->values()->all();
        $services = collect($clients)->pluck('service')->unique()->sort()->values()->all();
    @endphp

    {{-- Search & Filters + Grid --}}
    <section class="py-16 lg:py-24" x-data="{
        search: '',
        industry: '',
        service: '',
        clients: {{ Js::from($clients) }},
        get filtered() {
            return this.clients.filter(c => {
                const q = this.search.toLowerCase();
                const matchesSearch = !q || c.name.toLowerCase().includes(q) || c.description.toLowerCase().includes(q);
                const matchesIndustry = !this.industry || c.industry === this.industry;
                const matchesService = !this.service || c.service === this.service;
                return matchesSearch && matchesIndustry && matchesService;
            });
        }
    }">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            {{-- Search Bar + Filters --}}
            <div class="max-w-3xl mx-auto mb-12 space-y-4">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search clients..."
                        class="w-full pl-12 pr-4 py-4 text-lg rounded-xl border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all"
                    >
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select x-model="industry" class="flex-1 px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all bg-white text-sm">
                        <option value="">All Industries</option>
                        @foreach($industries as $ind)
                            <option value="{{ $ind }}">{{ $ind }}</option>
                        @endforeach
                    </select>
                    <select x-model="service" class="flex-1 px-4 py-3 rounded-lg border border-gray-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none transition-all bg-white text-sm">
                        <option value="">All Services</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc }}">{{ $svc }}</option>
                        @endforeach
                    </select>
                    <button
                        x-show="search || industry || service"
                        @click="search = ''; industry = ''; service = ''"
                        class="px-4 py-3 text-sm font-medium text-gray-500 hover:text-brand transition-colors cursor-pointer"
                    >Clear All</button>
                </div>
            </div>

            {{-- Results count --}}
            <p class="text-sm text-gray-400 mb-6" x-text="filtered.length + ' client' + (filtered.length !== 1 ? 's' : '')"></p>

            {{-- Mosaic Grid --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="client in filtered" :key="client.name">
                    <a
                        :href="client.url"
                        target="_blank"
                        rel="noopener"
                        class="group rounded-2xl border border-gray-100 hover:border-brand/20 hover:shadow-xl hover:shadow-brand/5 transition-all duration-300 overflow-hidden flex flex-col"
                    >
                        <div class="bg-gray-50 flex items-center justify-center p-8 h-40">
                            <img :src="'/images/logo/' + client.file" :alt="client.name" class="max-h-16 max-w-[180px] object-contain grayscale opacity-60 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-300">
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-lg font-bold mb-1 group-hover:text-brand transition-colors" x-text="client.name"></h3>
                            <p class="text-sm text-gray-500 leading-relaxed mb-3 flex-1" x-text="client.description"></p>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-block px-2.5 py-0.5 text-xs font-medium bg-brand/10 text-brand rounded-full" x-text="client.industry"></span>
                                <span class="inline-block px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full" x-text="client.service"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Empty state --}}
            <div x-show="filtered.length === 0" class="text-center py-20">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="text-gray-400 text-lg">No clients match your filters.</p>
                <button @click="search = ''; industry = ''; service = ''" class="mt-3 text-brand font-medium hover:underline cursor-pointer">Clear filters</button>
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
