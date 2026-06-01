<div class="min-h-screen">
    @php
        $hasCover = (bool) $proposal->cover_image;
        $isPdfMode = request()->boolean('pdf');
    @endphp

    @if($isPdfMode)
        <style>
            @page { size: Letter; margin: 0.5in; }
            html, body { background: #fff !important; }
            /* Avoid splitting key blocks across pages */
            section, .pdf-keep-together { break-inside: avoid; page-break-inside: avoid; }
            /* Cover should fit to one page */
            section.pdf-cover { min-height: 0 !important; height: auto !important; padding: 3rem 2rem !important; }
            /* Hide interactive-only and chrome elements */
            nav, video, .pdf-hide, [x-show="showShare"], [x-cloak] { display: none !important; }
            /* Freeze animations/transitions so Puppeteer captures a stable frame */
            *, *::before, *::after {
                animation-duration: 0s !important;
                animation-delay: 0s !important;
                transition-duration: 0s !important;
                transition-delay: 0s !important;
            }
        </style>
    @endif

    {{-- ========== STICKY NAV BAR ========== --}}
    @unless($isPdfMode)
    @php
        $navSections = $proposal->nav_sections;
        $navCandidates = $proposal->nav_candidates;
        $navHidden = $proposal->nav_hidden_sections ?? [];
    @endphp
    <nav x-data="{
            scrolled: false,
            active: '',
            menuOpen: false,
            useHamburger: false,
            updateNav() {
                this.scrolled = window.scrollY > window.innerHeight * 0.6;
                let current = '';
                const links = this.$refs.items?.querySelectorAll('[data-section-id]') || [];
                for (const link of links) {
                    const id = link.dataset.sectionId;
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top <= 100) current = id;
                }
                this.active = current;
            },
            measureFit() {
                if (window.innerWidth < 640) { this.useHamburger = true; return; }
                const items = this.$refs.items;
                const container = this.$refs.container;
                if (!items || !container) return;
                const safetyMargin = 240; // logo + approve button + side padding
                this.useHamburger = items.scrollWidth > (container.clientWidth - safetyMargin);
            },
            jumpTo(id) {
                this.menuOpen = false;
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
            }
         }"
         x-init="
            updateNav();
            $nextTick(() => measureFit());
            new ResizeObserver(() => measureFit()).observe($refs.container);
         "
         @scroll.window.throttle.50ms="updateNav()"
         @resize.window.debounce.150ms="measureFit()"
         class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
         :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 translate-y-0' : '-translate-y-full'"
    >
        <div x-ref="container" class="max-w-6xl mx-auto px-3 sm:px-6 flex items-center h-14 gap-2 relative">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener" class="hidden sm:block absolute left-6 flex-shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-6">
            </a>

            {{-- Inline pills (visible when items fit) --}}
            <div x-ref="items"
                 x-show="!useHamburger"
                 class="hidden sm:flex items-center gap-1 mx-auto whitespace-nowrap">
                @foreach($navSections as $section)
                    <a href="#{{ $section['id'] }}"
                       data-section-id="{{ $section['id'] }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-full transition-colors duration-200 flex-shrink-0"
                       :class="active === '{{ $section['id'] }}' ? 'bg-brand text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
                       @click.prevent="jumpTo('{{ $section['id'] }}')"
                    >{{ $section['label'] }}</a>
                @endforeach
            </div>

            {{-- Hamburger (mobile + when desktop pills overflow) --}}
            <div class="flex sm:hidden items-center" :class="{ 'sm:flex': useHamburger }">
                <button @click="menuOpen = !menuOpen"
                        class="p-2 -ml-1 text-gray-600 hover:text-gray-900 transition-colors cursor-pointer"
                        aria-label="Open navigation menu">
                    <svg x-show="!menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="menuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Hamburger dropdown --}}
                <div x-show="menuOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     @click.outside="menuOpen = false"
                     @keydown.escape.window="menuOpen = false"
                     class="absolute top-full left-3 right-3 sm:left-auto sm:right-6 sm:max-w-xs mt-2 bg-white rounded-xl shadow-lg border border-gray-200 py-2 max-h-[70vh] overflow-y-auto">
                    @foreach($navSections as $section)
                        <a href="#{{ $section['id'] }}"
                           class="block px-4 py-2 text-sm font-medium transition-colors"
                           :class="active === '{{ $section['id'] }}' ? 'bg-brand/10 text-brand' : 'text-gray-700 hover:bg-gray-50'"
                           @click.prevent="jumpTo('{{ $section['id'] }}')"
                        >{{ $section['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-2 sm:absolute sm:right-6">
                @if($isAdmin)
                    {{-- Admin: Customize Nav gear --}}
                    <div x-data="{ customizeOpen: false }">
                        <button @click="customizeOpen = true"
                                class="pdf-hide p-1.5 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer rounded-full hover:bg-gray-100"
                                title="Customize nav">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>

                        {{-- Customize modal (teleported to body to escape the nav's transform context) --}}
                        <template x-teleport="body">
                        <div x-show="customizeOpen" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
                             @keydown.escape.window="customizeOpen = false">
                            <div @click.outside="customizeOpen = false"
                                 class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 max-h-[80vh] flex flex-col">
                                <div class="p-6 border-b border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-900">Customize Navigation</h3>
                                    <p class="text-sm text-gray-500 mt-1">Choose which enabled sections appear in the sticky nav. Hidden sections still render in the proposal &mdash; they just don't get a nav link.</p>
                                </div>
                                <div class="p-6 overflow-y-auto flex-1 space-y-1">
                                    @foreach($navCandidates as $section)
                                        @php $isHidden = in_array($section['id'], $navHidden, true); @endphp
                                        <label class="flex items-center justify-between gap-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <span class="text-sm font-medium text-gray-900">{{ $section['label'] }}</span>
                                            <span class="relative inline-flex">
                                                <input type="checkbox"
                                                       wire:click="toggleNavSection('{{ $section['id'] }}')"
                                                       @if(! $isHidden) checked @endif
                                                       class="sr-only peer">
                                                <span class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></span>
                                                <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="p-6 border-t border-gray-100 flex items-center justify-end">
                                    <button @click="customizeOpen = false"
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                        Done
                                    </button>
                                </div>
                            </div>
                        </div>
                        </template>
                    </div>
                @endif

                @if($converted || $proposal->status === \App\Enums\ProposalStatus::Accepted)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 text-xs font-semibold rounded-full border border-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="hidden sm:inline">Approved</span>
                    </span>
                @else
                    <a href="#approval"
                       @click.prevent="document.getElementById('approval')?.scrollIntoView({ behavior: 'smooth' })"
                       class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 bg-brand hover:bg-gray-900 text-white text-xs font-semibold rounded-full transition-colors cursor-pointer shadow-sm whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="hidden sm:inline">Approve</span>
                    </a>
                @endif
            </div>
        </div>
    </nav>
    @endunless

    {{-- ========== COVER SECTION ========== --}}
    <section class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 bg-gray-900 pdf-cover">
        {{-- Background video --}}
        <div class="absolute inset-0 overflow-hidden">
            <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover opacity-40">
                <source src="{{ asset('videos/dalibg1.mp4') }}" type="video/mp4">
            </video>
        </div>

        @if($hasCover)
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-cover bg-center animate-hero-drift"
                     style="background-image: url('{{ Storage::url($proposal->cover_image) }}');">
                </div>
                <div class="absolute inset-0 bg-black/50"></div>
            </div>
        @endif

        {{-- Admin: Cover image upload --}}
        @if($isAdmin)
            <div class="absolute top-4 left-4 z-20"
                 x-data="{ showUpload: false }"
                 x-on:livewire:navigated.window="showUpload = false"
                 @cover-uploaded.window="showUpload = false">
                <button @click="showUpload = !showUpload"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg bg-white/90 border border-gray-200 text-gray-600 hover:bg-white hover:text-gray-900 shadow-sm transition cursor-pointer backdrop-blur-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Cover Image
                </button>
                <div x-show="showUpload" x-cloak @click.outside="showUpload = false"
                     x-transition
                     class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-200 p-4 w-64">
                    <input type="file" wire:model="coverImage" accept="image/*"
                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer">
                    <div wire:loading wire:target="coverImage" class="mt-2 text-xs text-gray-400">Uploading...</div>
                    @if($hasCover)
                        <button wire:click="removeCoverImage"
                                class="mt-3 text-xs text-red-500 hover:text-red-700 transition cursor-pointer">
                            Remove current image
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Top-right actions: Download PDF (logged-in only) + Share (admin only) --}}
        @unless($isPdfMode)
        <div class="absolute top-4 right-4 z-20 flex items-center gap-2 pdf-hide"
             x-data="{
                generating: false,
                error: null,
                downloadPdf() {
                    this.generating = true;
                    this.error = null;
                    let filename = 'proposal.pdf';
                    fetch('{{ route('proposal.pdf', $proposal->uuid) }}')
                        .then(res => {
                            if (!res.ok) throw new Error('PDF generation failed');
                            const cd = res.headers.get('Content-Disposition') || '';
                            const match = cd.match(/filename=&quot;?([^&quot;]+)&quot;?/) || cd.match(/filename=([^;]+)/);
                            if (match) filename = match[1].trim().replace(/^&quot;|&quot;$/g, '');
                            return res.blob();
                        })
                        .then(blob => {
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url; a.download = filename;
                            document.body.appendChild(a); a.click();
                            a.remove(); URL.revokeObjectURL(url);
                        })
                        .catch(() => { this.error = 'Sorry, we could not generate the PDF. Please try again.'; })
                        .finally(() => { this.generating = false; });
                }
             }">
            @auth
            <button @click="downloadPdf()" :disabled="generating"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg bg-white/90 border border-gray-200 text-gray-600 hover:bg-white hover:text-gray-900 shadow-sm transition cursor-pointer backdrop-blur-sm disabled:opacity-60 disabled:cursor-wait">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                <span x-text="generating ? 'Generating…' : 'Download PDF'"></span>
            </button>
            @endauth

            {{-- Generating PDF modal --}}
            <div x-show="generating || error" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center">
                    <template x-if="!error">
                        <div>
                            <div class="w-14 h-14 mx-auto mb-4 rounded-full border-4 border-gray-200 border-t-brand animate-spin"></div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Generating your PDF</h3>
                            <p class="text-sm text-gray-500">This usually takes a few seconds…</p>
                        </div>
                    </template>
                    <template x-if="error">
                        <div>
                            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Something went wrong</h3>
                            <p class="text-sm text-gray-500 mb-5" x-text="error"></p>
                            <button @click="error = null"
                                    class="px-5 py-2 text-sm font-medium text-white bg-brand rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        @if($isAdmin)
            <a href="{{ route('proposal.cover', $proposal->uuid) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg bg-white/90 border border-gray-200 text-gray-600 hover:bg-white hover:text-gray-900 shadow-sm transition cursor-pointer backdrop-blur-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h6m0 0l-3-3m3 3l-3 3M4 7h6a2 2 0 012 2v0M4 7l3-3m-3 3l3 3"/></svg>
                Proposal PDF
            </a>
            <div x-data="{ showShare: false, shareSent: false }"
                 @proposal-shared.window="shareSent = true">
                <button @click="showShare = true; shareSent = false"
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg bg-white/90 border border-gray-200 text-gray-600 hover:bg-white hover:text-gray-900 shadow-sm transition cursor-pointer backdrop-blur-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    Share
                </button>

                {{-- Share Modal --}}
                <div x-show="showShare" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @click.self="showShare = false"
                     @keydown.escape.window="showShare = false">
                    <div x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">

                        {{-- Modal header --}}
                        <div class="px-6 py-5 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900">Share Proposal</h3>
                                <button @click="showShare = false" class="p-1 text-gray-400 hover:text-gray-600 transition cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Send a branded email with a link to this proposal.</p>
                        </div>

                        {{-- Modal body --}}
                        <div class="px-6 py-5">
                            <div x-show="shareSent" x-cloak class="text-center py-4">
                                <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-gray-900 font-semibold">Proposal sent!</p>
                                <p class="text-sm text-gray-500 mt-1">Email delivered to <span x-text="$wire.shareEmail"></span></p>
                                <button @click="showShare = false"
                                        class="mt-4 px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                    Close
                                </button>
                            </div>
                            <div x-show="!shareSent" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                                    <input type="email" wire:model="shareEmail"
                                           placeholder="client@example.com"
                                           class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-brand focus:ring-1 focus:ring-brand outline-none transition text-sm">
                                    @error('shareEmail')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea wire:model="shareNotes"
                                              rows="3"
                                              placeholder="Add a personal note..."
                                              class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-brand focus:ring-1 focus:ring-brand outline-none transition text-sm resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Modal footer --}}
                        <div x-show="!shareSent" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-3 justify-end">
                            <button @click="showShare = false"
                                    class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button wire:click="shareProposal"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-brand rounded-lg hover:bg-gray-900 transition-colors cursor-pointer shadow-sm">
                                <span wire:loading.remove wire:target="shareProposal">Send Email</span>
                                <span wire:loading wire:target="shareProposal">Sending...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        </div>
        @endunless

        {{-- Center content --}}
        <div class="relative z-10 text-center max-w-3xl mx-auto">
            <div class="text-white/60 text-xs tracking-[0.4em] uppercase mb-6 font-medium">Digital Services Proposal</div>

            {{-- Company Name (large heading) --}}
            @if($isAdmin)
                <div class="mb-4 inline-block relative">
                    <select wire:model.live="editingClientId"
                            class="appearance-none bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 text-white font-bold text-4xl sm:text-5xl lg:text-6xl text-center cursor-pointer pr-10 pl-2 py-1 transition-colors leading-tight">
                        <option value="" class="text-gray-900 text-base">Select Client</option>
                        @foreach($this->clients as $client)
                            <option value="{{ $client->id }}" class="text-gray-900 text-base">{{ $client->company ?? $client->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            @else
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight">
                    {{ $proposal->client_company ?: $proposal->client_name }}
                </h1>
            @endif

        </div>

        {{-- Bottom left: Contracting officer + RFP # + website (centered on mobile) --}}
        <div class="absolute bottom-32 sm:bottom-8 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:left-8 z-10 text-center sm:text-left">
            <p class="text-white font-semibold text-sm">{{ $proposal->client_name }}</p>

            {{-- RFP # --}}
            @if($isAdmin)
                <input type="text"
                       wire:model.blur="editingRfpNumber"
                       placeholder="RFP #"
                       class="block bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-white/30 focus:border-brand focus:ring-0 text-white/80 text-sm font-medium tracking-wider placeholder-white/40 px-0 py-0.5 transition-colors w-auto text-center sm:text-left">
            @elseif($proposal->rfp_number)
                <p class="text-white/80 text-sm font-medium tracking-wider">RFP #{{ $proposal->rfp_number }}</p>
            @endif

            @if($proposal->client_domain)
                <p class="text-white/70 text-sm">{{ $proposal->client_domain }}</p>
            @endif
        </div>

        {{-- Logo tab - right edge on desktop, centered bottom tab on mobile --}}
        <div class="absolute bottom-0 sm:bottom-8 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:left-auto sm:right-0 z-10">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener" class="bg-white rounded-t-lg sm:rounded-t-none sm:rounded-l-lg px-5 py-3 shadow-lg block hover:shadow-xl transition-shadow">
                <img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-8">
            </a>
        </div>

        {{-- Date + Scroll indicator --}}
        <div class="absolute bottom-16 sm:bottom-12 left-1/2 -translate-x-1/2 z-10 text-center">
            <div class="mb-4 sm:mb-6">
                @if($isAdmin)
                    <div wire:ignore x-data="{
                        fp: null,
                        init() {
                            this.fp = flatpickr(this.$refs.datepicker, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'F j, Y',
                                altInputClass: 'bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 text-white/80 text-sm cursor-pointer p-0 transition-colors text-center',
                                defaultDate: @js($editingProposalDate),
                                onChange(selectedDates, dateStr) {
                                    Livewire.dispatch('update-proposal-date', { date: dateStr });
                                },
                            });
                        },
                        destroy() { if (this.fp) this.fp.destroy(); }
                    }" class="inline-block">
                        <input x-ref="datepicker" type="text" readonly
                               class="hidden">
                    </div>
                @else
                    <p class="text-white/80 text-sm">{{ $proposal->proposal_date->format('F j, Y') }}</p>
                @endif
            </div>
            <div class="animate-bounce">
                <svg class="w-6 h-6 mx-auto text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
        </div>
    </section>

    {{-- ========== OVERVIEW SECTION ========== --}}
    @if(($proposal->overview_enabled && $proposal->introduction) || $isAdmin)
    @php $hasOverviewImage = (bool) $proposal->overview_image; @endphp
    <section id="overview" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="{{ $hasOverviewImage ? 'max-w-6xl' : 'max-w-4xl' }} mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingOverviewEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Show "Overview" in client proposal</span>
                    </label>
                    @if(!$proposal->overview_enabled)
                        <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            Hidden from client view
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-3xl font-bold text-gray-900">Overview</h2>
                @if($isAdmin)
                    <div class="ml-auto flex items-center gap-2 pdf-hide"
                         x-data="{ showUpload: false }"
                         @overview-image-uploaded.window="showUpload = false">
                        <button @click="showUpload = !showUpload"
                                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-lg bg-white border border-gray-200 text-gray-600 hover:text-gray-900 shadow-sm transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $hasOverviewImage ? 'Change Image' : 'Add Image' }}
                        </button>
                        <div x-show="showUpload" x-cloak @click.outside="showUpload = false"
                             x-transition
                             class="absolute right-4 mt-14 bg-white rounded-xl shadow-lg border border-gray-200 p-4 w-72 z-20">
                            <p class="text-xs text-gray-500 mb-2">Optional exhibit image. Shown beside overview text; clickable for full view.</p>
                            <input type="file" wire:model="overviewImage" accept="image/*"
                                   class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer">
                            <div wire:loading wire:target="overviewImage" class="mt-2 text-xs text-gray-400">Uploading...</div>
                            @if($hasOverviewImage)
                                <button wire:click="removeOverviewImage"
                                        class="mt-3 text-xs text-red-500 hover:text-red-700 transition cursor-pointer">
                                    Remove current image
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="{{ $hasOverviewImage ? 'grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-center' : 'sm:pl-8' }}">
                <div>
                @if($isAdmin)
                    <div x-data="{
                            editing: false,
                            save() {
                                this.editing = false;
                                $wire.set('editingIntroduction', $refs.introEditor.innerHTML);
                                $wire.saveIntroduction();
                            }
                         }"
                         class="relative group">
                        {{-- Read mode --}}
                        <div x-show="!editing"
                             @click="editing = true; $nextTick(() => { $refs.introEditor.focus(); })"
                             class="prose-light max-w-none text-lg leading-relaxed cursor-pointer rounded-lg p-4 -m-4 border-2 border-dashed border-transparent hover:border-gray-300 transition-colors min-h-[60px]">
                            @if($proposal->introduction)
                                {!! $proposal->introduction !!}
                            @else
                                <p class="text-gray-400 italic">Click to add introduction text...</p>
                            @endif
                        </div>
                        {{-- Edit mode --}}
                        <div x-show="editing" x-cloak
                             @click.outside="save()"
                             @keydown.escape.window="save()">
                            <div x-ref="introEditor"
                                 contenteditable="true"
                                 class="prose-light max-w-none text-lg leading-relaxed bg-white border-2 border-brand/30 focus:border-brand rounded-lg p-4 -m-4 focus:outline-none min-h-[120px] transition-colors"
                            >{!! $proposal->introduction !!}</div>
                            <p class="text-xs text-gray-400 mt-3">Click outside or press Escape to save.</p>
                        </div>
                    </div>
                @else
                    <div class="prose-light max-w-none text-lg leading-relaxed">
                        {!! $proposal->introduction !!}
                    </div>
                @endif

                {{-- Signature block --}}
                <div style="margin-top: 2rem; display: flex; align-items: center; gap: 1.25rem;">
                    <img src="{{ asset('images/jim.png') }}"
                         alt="James Doyle"
                         style="width: 56px; height: 56px; border-radius: 9999px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; flex-shrink: 0;">
                    <div style="line-height: 1.25;">
                        <div style="font-weight: 700; color: #111827;">James Doyle</div>
                        <div style="font-size: 0.875rem; color: #4b5563;">Founder &amp; CEO</div>
                    </div>
                    <img src="{{ asset('images/signature.png') }}"
                         alt="Signature"
                         style="height: 96px; width: auto; object-fit: contain; margin-left: 0.5rem;">
                </div>
                </div>
                @if($hasOverviewImage)
                    <div x-data="{ showLightbox: false }">
                        <a href="{{ Storage::url($proposal->overview_image) }}"
                           target="_blank" rel="noopener"
                           @click.prevent="showLightbox = true"
                           class="block group relative rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-shadow bg-white">
                            <img src="{{ Storage::url($proposal->overview_image) }}"
                                 alt="Overview exhibit"
                                 class="w-full h-auto object-contain">
                            <div class="pdf-hide absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-white/95 text-gray-700 rounded-full shadow">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"/></svg>
                                    Click to enlarge
                                </span>
                            </div>
                        </a>
                        {{-- Lightbox --}}
                        <div x-show="showLightbox" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="pdf-hide fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
                             @click.self="showLightbox = false"
                             @keydown.escape.window="showLightbox = false">
                            <button @click="showLightbox = false"
                                    class="absolute top-4 right-4 p-2 text-white/80 hover:text-white transition cursor-pointer"
                                    aria-label="Close">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img src="{{ Storage::url($proposal->overview_image) }}"
                                 alt="Overview exhibit"
                                 class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- ========== ROADMAP SECTION ========== --}}
    @if($proposal->roadmap_enabled || $isAdmin)
    <section id="roadmap" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16 overflow-hidden">
        <div class="max-w-6xl mx-auto">

            {{-- Admin: Toggle + Settings --}}
            @if($isAdmin)
            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200"
                 x-data="{ showSettings: false }">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingRoadmapEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include Roadmap in Proposal</span>
                    </label>
                    <button @click="showSettings = !showSettings"
                            x-show="$wire.editingRoadmapEnabled"
                            class="text-xs text-brand hover:text-gray-900 font-medium transition-colors cursor-pointer">
                        <span x-text="showSettings ? 'Hide Settings' : 'Edit Settings'"></span>
                    </button>
                </div>

                <div x-show="showSettings && $wire.editingRoadmapEnabled" x-cloak x-collapse class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Title</label>
                            <input type="text" wire:model.blur="editingRoadmapTitle"
                                   wire:change="saveRoadmapSettings"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Subtitle</label>
                            <input type="text" wire:model.blur="editingRoadmapSubtitle"
                                   wire:change="saveRoadmapSettings"
                                   placeholder="e.g., Phased Implementation Over 12 Months"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Total Months</label>
                            <input type="number" wire:model.blur="editingRoadmapMonths"
                                   wire:change="saveRoadmapSettings"
                                   min="1"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Hours/Sprint</label>
                            <input type="number" wire:model.blur="editingRoadmapHoursPerSprint"
                                   wire:change="saveRoadmapSettings"
                                   min="1"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($proposal->roadmap_enabled)
            {{-- Roadmap Header --}}
            <div class="text-center mb-10">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">{{ $proposal->roadmap_title ?? 'Project Roadmap' }}</h2>
                @if($proposal->roadmap_subtitle)
                    <p class="text-gray-500 text-lg">{{ $proposal->roadmap_subtitle }}</p>
                @else
                    <p class="text-gray-500 text-lg">
                        Phased Implementation Over {{ $proposal->roadmap_months ?? 12 }} Months
                        <span class="text-gray-400">(~{{ $proposal->roadmap_hours_per_sprint ?? 160 }} Hours Per Sprint)</span>
                    </p>
                @endif
            </div>

            {{-- Month Timeline Bar --}}
            @php
                $totalMonths = $proposal->roadmap_months ?? 12;
                $phases = $proposal->roadmapPhases;
                $monthMarkers = [];
                $current = 1;
                // Show a few key month markers
                if ($totalMonths <= 6) {
                    $monthMarkers = range(1, $totalMonths);
                } else {
                    $step = max(1, floor($totalMonths / 6));
                    for ($m = 1; $m <= $totalMonths; $m += $step) {
                        $monthMarkers[] = $m;
                    }
                    if (end($monthMarkers) != $totalMonths) $monthMarkers[] = $totalMonths;
                }
            @endphp

            <div class="mb-8 hidden sm:block">
                <div class="flex items-center gap-0 bg-gray-100 rounded-lg overflow-hidden h-8">
                    @foreach($monthMarkers as $i => $month)
                        <div class="flex-1 flex items-center justify-center text-xs font-medium text-gray-500 border-r border-gray-200 last:border-r-0">
                            Month {{ $month }}
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Phase Chevrons - Desktop --}}
            <div class="hidden sm:block" x-data="{
                    editingPhaseId: null,
                    editTitle: '', editSubtitle: '', editDescription: '', editColor: '#3B82F6', editIcon: 'clipboard', editWeeks: 4, editHours: 160,
                    deletePhaseId: null, deletePhaseTitle: '',
                    phaseDragOver: null,
                    startEdit(phase) {
                        this.editingPhaseId = phase.id;
                        this.editTitle = phase.title;
                        this.editSubtitle = phase.subtitle || '';
                        this.editDescription = phase.description || '';
                        this.editColor = phase.color;
                        this.editIcon = phase.icon;
                        this.editWeeks = phase.duration_weeks;
                        this.editHours = phase.hours || {{ $proposal->roadmap_hours_per_sprint ?? 160 }};
                    },
                    saveEdit() {
                        if (this.editingPhaseId && this.editTitle.trim()) {
                            $wire.updateRoadmapPhase(this.editingPhaseId, this.editTitle, this.editSubtitle || null, this.editDescription || null, this.editColor, this.editIcon, parseInt(this.editWeeks), parseInt(this.editHours) || null);
                        }
                        this.editingPhaseId = null;
                    },
                    confirmDelete(id, title) { this.deletePhaseId = id; this.deletePhaseTitle = title; },
                    executeDelete() { if (this.deletePhaseId) $wire.deleteRoadmapPhase(this.deletePhaseId); this.deletePhaseId = null; },
                    handleDrop(e, targetId) {
                        this.phaseDragOver = null;
                        const sourceId = parseInt(e.dataTransfer.getData('phase'));
                        if (!sourceId || sourceId === targetId) return;
                        const all = document.querySelectorAll('[data-phase-id]');
                        const ordered = Array.from(all).map(el => parseInt(el.dataset.phaseId));
                        const from = ordered.indexOf(sourceId);
                        const to = ordered.indexOf(targetId);
                        ordered.splice(from, 1);
                        ordered.splice(to, 0, sourceId);
                        $wire.reorderRoadmapPhases(ordered);
                    }
                }">

                {{-- Delete modal --}}
                <div x-show="deletePhaseId" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @keydown.escape.window="deletePhaseId = null">
                    <div @click.outside="deletePhaseId = null"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Remove Phase</h3>
                        <p class="text-sm text-gray-500 mb-6">Remove <span class="font-medium text-gray-700" x-text="deletePhaseTitle"></span>?</p>
                        <div class="flex items-center gap-3">
                            <button @click="deletePhaseId = null" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">Cancel</button>
                            <button @click="executeDelete()" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Remove</button>
                        </div>
                    </div>
                </div>

                {{-- Edit Phase Modal --}}
                <div x-show="editingPhaseId" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
                     @keydown.escape.window="editingPhaseId = null">
                    <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">Edit Phase</h3>
                            <button @click="editingPhaseId = null" class="p-1 text-gray-400 hover:text-gray-600 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Phase Title</label>
                                    <input type="text" x-model="editTitle" class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Subtitle</label>
                                    <input type="text" x-model="editSubtitle" placeholder="e.g., Intranet & Safety Training" class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                                    <textarea x-model="editDescription" rows="2" placeholder="Brief description..." class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Color</label>
                                    <input type="color" x-model="editColor" class="w-full h-10 rounded-lg border border-gray-200 cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Icon</label>
                                    <select x-model="editIcon" class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                        <option value="building">Building</option>
                                        <option value="cog">Gear</option>
                                        <option value="clipboard">Clipboard</option>
                                        <option value="handshake">Handshake</option>
                                        <option value="calculator">Calculator</option>
                                        <option value="truck">Truck</option>
                                        <option value="dollar">Dollar</option>
                                        <option value="shield">Shield</option>
                                        <option value="chart">Chart</option>
                                        <option value="code">Code</option>
                                        <option value="globe">Globe</option>
                                        <option value="paint">Paint</option>
                                        <option value="rocket">Rocket</option>
                                        <option value="users">Users</option>
                                        <option value="lock">Lock</option>
                                        <option value="database">Database</option>
                                        <option value="cloud">Cloud</option>
                                        <option value="mobile">Mobile</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Duration (weeks)</label>
                                    <input type="number" x-model="editWeeks" min="1" class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Hours</label>
                                    <input type="number" x-model="editHours" min="0" class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            <button @click="editingPhaseId = null" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">Cancel</button>
                            <button @click="saveEdit()" class="px-5 py-2.5 text-sm font-medium text-white bg-brand rounded-lg hover:bg-gray-900 transition-colors cursor-pointer shadow-sm">Save Phase</button>
                        </div>
                    </div>
                </div>

                {{-- Phase Arrow Timeline --}}
                <div class="flex items-stretch gap-0 relative">
                    @foreach($phases as $index => $phase)
                    <div class="flex-1 group/phase relative"
                         data-phase-id="{{ $phase->id }}"
                         @if($isAdmin)
                         draggable="true"
                         @dragstart="$event.dataTransfer.setData('phase', '{{ $phase->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                         @dragover.prevent="phaseDragOver = {{ $phase->id }}"
                         @dragleave="phaseDragOver = null"
                         @drop.prevent="handleDrop($event, {{ $phase->id }})"
                         :class="{ 'ring-2 ring-brand ring-dashed': phaseDragOver === {{ $phase->id }} }"
                         @endif>

                        {{-- Chevron Arrow Shape --}}
                        <div class="relative">
                            <svg viewBox="0 0 200 200" class="w-full h-auto" preserveAspectRatio="none">
                                @if($index === 0)
                                <path d="M0,0 L170,0 L200,100 L170,200 L0,200 Z" fill="{{ $phase->color }}" />
                                @elseif($index === count($phases) - 1)
                                <path d="M0,0 L200,0 L200,200 L0,200 L30,100 Z" fill="{{ $phase->color }}" />
                                @else
                                <path d="M0,0 L170,0 L200,100 L170,200 L0,200 L30,100 Z" fill="{{ $phase->color }}" />
                                @endif
                            </svg>

                            {{-- Phase Content Overlay --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-white px-8 py-5">
                                {{-- Icon --}}
                                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mb-4">
                                    @switch($phase->icon)
                                        @case('building')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        @break
                                        @case('cog')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        @break
                                        @case('clipboard')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        @break
                                        @case('handshake')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        @break
                                        @case('calculator')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @break
                                        @case('truck')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                        @break
                                        @case('dollar')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @break
                                        @case('shield')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        @break
                                        @case('chart')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        @break
                                        @case('code')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                        @break
                                        @case('globe')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        @break
                                        @case('paint')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                        @break
                                        @case('rocket')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                                        @break
                                        @case('users')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        @break
                                        @case('lock')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        @break
                                        @case('database')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                        @break
                                        @case('cloud')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                        @break
                                        @case('mobile')
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @break
                                    @endswitch
                                </div>

                                {{-- Phase Title --}}
                                <div class="text-center">
                                    <div class="text-[11px] uppercase tracking-wider font-semibold opacity-80 mb-0.5">Phase {{ $index + 1 }}:</div>
                                    <div class="text-sm font-bold leading-tight">{{ $phase->title }}</div>
                                    @if($phase->subtitle)
                                        <div class="text-[11px] opacity-80 mt-0.5 leading-tight">{{ $phase->subtitle }}</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Admin action buttons --}}
                            @if($isAdmin)
                            <div class="absolute top-1 right-1 flex items-center gap-0.5 opacity-0 group-hover/phase:opacity-100 transition-opacity z-10">
                                <button @click="startEdit(@js($phase->toArray()))" title="Edit" class="p-1 bg-white/90 rounded text-gray-600 hover:text-brand transition-colors cursor-pointer shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="duplicateRoadmapPhase({{ $phase->id }})" title="Duplicate" class="p-1 bg-white/90 rounded text-gray-600 hover:text-brand transition-colors cursor-pointer shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                                <button @click="confirmDelete({{ $phase->id }}, @js($phase->title))" title="Delete" class="p-1 bg-white/90 rounded text-gray-600 hover:text-red-500 transition-colors cursor-pointer shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endif
                        </div>

                        {{-- Phase Details Below Arrow --}}
                        <div class="text-center mt-3 px-2">
                            @if($phase->description)
                                <p class="text-sm text-gray-500 leading-snug mb-1">{{ $phase->description }}</p>
                            @endif
                            <div class="text-sm font-bold text-gray-800">{{ $phase->duration_weeks }} WEEKS</div>
                            @if($phase->hours)
                                <div class="text-[11px] text-gray-400 font-medium">~{{ $phase->hours }} HRS</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Add Phase Button --}}
                @if($isAdmin)
                <div class="mt-6 text-center">
                    <button wire:click="addRoadmapPhase"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Phase
                    </button>
                </div>
                @endif
            </div>

            {{-- Mobile: Vertical Timeline --}}
            <div class="sm:hidden space-y-4">
                @foreach($phases as $index => $phase)
                <div class="flex gap-4">
                    {{-- Phase number circle --}}
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0 shadow-lg" style="background-color: {{ $phase->color }}">
                            {{ $index + 1 }}
                        </div>
                        @if(!$loop->last)
                        <div class="w-0.5 flex-1 mt-2 rounded-full" style="background-color: {{ $phase->color }}33"></div>
                        @endif
                    </div>

                    {{-- Phase content --}}
                    <div class="pb-6 flex-1">
                        <div class="rounded-xl border border-gray-200 p-4 shadow-sm" style="border-left: 3px solid {{ $phase->color }}">
                            <div class="text-[10px] uppercase tracking-wider font-semibold mb-1" style="color: {{ $phase->color }}">Phase {{ $index + 1 }}</div>
                            <h4 class="font-bold text-gray-900 text-sm">{{ $phase->title }}</h4>
                            @if($phase->subtitle)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $phase->subtitle }}</p>
                            @endif
                            @if($phase->description)
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ $phase->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center gap-3 text-xs">
                                <span class="font-bold text-gray-700">{{ $phase->duration_weeks }} weeks</span>
                                @if($phase->hours)
                                    <span class="text-gray-400">~{{ $phase->hours }} hrs</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                @if($isAdmin)
                <div class="text-center pt-2">
                    <button wire:click="addRoadmapPhase"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Phase
                    </button>
                </div>
                @endif
            </div>

            {{-- Footer tagline --}}
            @if($phases->count() > 0)
            <div class="text-center mt-10 text-sm text-gray-400 italic">
                Iterative Sprints + Testing & Feedback = Continuous Improvement
            </div>
            @endif
            @endif

        </div>
    </section>
    @endif

    {{-- ========== ABOUT US SECTION ========== --}}
    @if($proposal->about_enabled || $isAdmin)
    <section id="about" class="relative scroll-mt-16 @if($proposal->about_enabled) bg-neutral-900 @endif">
        {{-- Admin toggle bar (shown above section, outside dark bg for legibility) --}}
        @if($isAdmin)
            <div class="pdf-hide px-4 sm:px-6 pt-8 bg-white">
                <div class="max-w-6xl mx-auto p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingAboutEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "About Us" in Proposal</span>
                        <span class="text-xs text-gray-400 ml-auto">Mirrors the divStrong homepage banner</span>
                    </label>
                </div>
            </div>
        @endif

        @if($proposal->about_enabled)
            <div class="relative overflow-hidden py-20 sm:py-28 px-4 sm:px-6">
                {{-- Subtle background image --}}
                <div class="absolute inset-0 bg-cover bg-center opacity-10"
                     style="background-image: url('{{ asset('images/rva-street.png') }}');"></div>

                <div class="relative max-w-6xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                        <div>
                            <p class="text-brand font-semibold text-sm tracking-widest uppercase mb-3">About Us</p>
                            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-6 leading-tight">
                                Building Apps, APIs &amp; MVPs Since 2009
                            </h2>
                            <p class="text-base sm:text-lg text-gray-400 leading-relaxed mb-8">
                                Our AI-enabled team of strategists, designers, and developers create full-stack solutions for organizations seeking to innovate, automate and invest in creating their own digital products.
                            </p>

                            <div
                                class="grid grid-cols-3 gap-6 sm:gap-8"
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
                                    <p class="text-2xl sm:text-3xl font-extrabold text-brand"><span x-text="years">17</span>+</p>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Years in Business</p>
                                </div>
                                <div>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-brand"><span x-text="clients">500</span>+</p>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Clients</p>
                                </div>
                                <div>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-brand"><span x-text="projects.toLocaleString()">1,000</span>+</p>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Projects Delivered</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/10">
                                <img src="{{ asset('images/team.gif') }}" alt="divStrong team" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
    @endif

    {{-- ========== SCOPE OF WORK SECTION ========== --}}
    @if($proposal->scopeItems->count() || $isAdmin)
    <section id="scope" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Scope of Work</h2>

                {{-- Import + Add Item buttons --}}
                @if($isAdmin)
                <div class="ml-auto flex items-center gap-4">
                    {{-- Import scope from another proposal --}}
                    <div x-data="{
                             importOpen: false,
                             importUuid: '',
                             importBusy: false,
                             importError: '',
                             importSuccess: '',
                             async runImport() {
                                 this.importError = '';
                                 this.importSuccess = '';
                                 if (! this.importUuid.trim()) { this.importError = 'Enter a proposal identifier.'; return; }
                                 this.importBusy = true;
                                 try {
                                     const result = await $wire.importScopeFromProposal(this.importUuid.trim());
                                     if (result?.ok) {
                                         this.importSuccess = result.message;
                                         this.importUuid = '';
                                         setTimeout(() => { this.importOpen = false; this.importSuccess = ''; }, 1400);
                                     } else {
                                         this.importError = result?.message || 'Import failed.';
                                     }
                                 } catch (e) {
                                     this.importError = 'Something went wrong. Please try again.';
                                 } finally {
                                     this.importBusy = false;
                                 }
                             }
                         }">
                        {{-- Import link --}}
                        <button @click="importOpen = true"
                                class="text-sm font-medium text-brand hover:text-gray-900 transition-colors cursor-pointer">
                            Import
                        </button>

                        {{-- Import modal --}}
                        <div x-show="importOpen" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                             @keydown.escape.window="importOpen = false; importError = ''; importSuccess = '';">
                            <div @click.outside="importOpen = false; importError = ''; importSuccess = '';"
                                 class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
                                <div class="p-6 border-b border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-900">Import Scope from Another Proposal</h3>
                                    <p class="text-sm text-gray-500 mt-1">Enter the source proposal's 6-character code (the part after <code class="text-gray-700">/proposal/</code> in its URL). All of its scope items will be appended below the existing ones.</p>
                                </div>
                                <div class="p-6 space-y-3">
                                    <label class="block">
                                        <span class="text-xs font-medium text-gray-700">Proposal code</span>
                                        <input type="text" x-model="importUuid"
                                               @keydown.enter.prevent="if(!importBusy) runImport()"
                                               @input="importUuid = importUuid.toUpperCase()"
                                               maxlength="6"
                                               placeholder="e.g. IGAK96"
                                               class="mt-1 block w-full text-sm font-mono tracking-widest bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none uppercase"
                                               x-bind:disabled="importBusy">
                                    </label>
                                    <p x-show="importError" x-text="importError" x-cloak
                                       class="text-sm text-red-600"></p>
                                    <p x-show="importSuccess" x-text="importSuccess" x-cloak
                                       class="text-sm text-emerald-600"></p>
                                </div>
                                <div class="p-6 border-t border-gray-100 flex items-center justify-end gap-3">
                                    <button @click="importOpen = false; importError = ''; importSuccess = '';"
                                            x-bind:disabled="importBusy"
                                            class="text-sm text-gray-500 hover:text-gray-700 cursor-pointer disabled:opacity-50">Cancel</button>
                                    <button @click="runImport()"
                                            x-bind:disabled="importBusy"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors disabled:opacity-60 disabled:cursor-wait cursor-pointer">
                                        <svg x-show="importBusy" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                                            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span x-text="importBusy ? 'Importing…' : 'Import Scope'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div x-data="{ open: false, selected: [] }">
                        <button @click="open = !open"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Item
                        </button>

                        {{-- Library picker modal --}}
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                             @keydown.escape.window="open = false">
                            <div @click.outside="open = false"
                                 class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[80vh] flex flex-col">
                                <div class="p-6 border-b border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-900">Add from Scope Library</h3>
                                    <p class="text-sm text-gray-500 mt-1">Select items to add to this proposal.</p>
                                </div>
                                <div class="p-6 overflow-y-auto flex-1">
                                    @foreach($this->scopeLibrary->groupBy('category') as $cat => $libItems)
                                        <div class="mb-5">
                                            <h4 class="text-sm font-semibold text-brand uppercase tracking-wide mb-2">{{ $cat }}</h4>
                                            <div class="space-y-2">
                                                @foreach($libItems as $libItem)
                                                    <label class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                                        <input type="checkbox" value="{{ $libItem->id }}" x-model="selected"
                                                               class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand/20">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900">{{ $libItem->title }}</p>
                                                            @if($libItem->description)
                                                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($libItem->description, 80) }}</p>
                                                            @endif
                                                            @if($libItem->bullets && count($libItem->bullets))
                                                                <p class="text-xs text-gray-400 mt-0.5">{{ count($libItem->bullets) }} bullet{{ count($libItem->bullets) !== 1 ? 's' : '' }}</p>
                                                            @endif
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="p-6 border-t border-gray-100 flex items-center justify-between">
                                    <button @click="open = false; selected = []"
                                            class="text-sm text-gray-500 hover:text-gray-700 cursor-pointer">Cancel</button>
                                    <button @click="if(selected.length) { $wire.addScopeItems(selected.map(Number)); selected = []; open = false; }"
                                            x-bind:disabled="selected.length === 0"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                                        <span>Add Selected</span>
                                        <span x-show="selected.length" x-text="'(' + selected.length + ')'" class="text-xs opacity-80"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="sm:pl-8">
            {{-- Scope items grouped by category --}}
            <div x-data="{
                    editingId: null,
                    editTitle: '',
                    editDesc: '',
                    editBullets: [],
                    _bulletUid: 0,
                    dragId: null,
                    deleteId: null,
                    deleteTitle: '',
                    bulletDragIndex: null,
                    bulletDragOverIndex: null,
                    _makeBullet(text) {
                        return { id: ++this._bulletUid, text: text || '' };
                    },
                    startEdit(id, title, description, bullets) {
                        this.editingId = id;
                        this.editTitle = title;
                        this.editDesc = description || '';
                        this.editBullets = bullets && bullets.length
                            ? bullets.map(b => this._makeBullet(b))
                            : [this._makeBullet('')];
                    },
                    addBullet() {
                        this.editBullets.push(this._makeBullet(''));
                        this.$nextTick(() => {
                            const inputs = this.$el.querySelectorAll('[data-bullet-input]');
                            if (inputs.length) inputs[inputs.length - 1].focus();
                        });
                    },
                    removeBullet(index) {
                        this.editBullets.splice(index, 1);
                        if (this.editBullets.length === 0) this.editBullets.push(this._makeBullet(''));
                    },
                    bulletDragStart(index) {
                        this.bulletDragIndex = index;
                    },
                    bulletDragOver(index) {
                        if (this.bulletDragIndex === null) return;
                        this.bulletDragOverIndex = index;
                    },
                    bulletDrop(index) {
                        if (this.bulletDragIndex === null || this.bulletDragIndex === index) {
                            this.bulletDragIndex = null;
                            this.bulletDragOverIndex = null;
                            return;
                        }
                        const item = this.editBullets.splice(this.bulletDragIndex, 1)[0];
                        this.editBullets.splice(index, 0, item);
                        this.bulletDragIndex = null;
                        this.bulletDragOverIndex = null;
                    },
                    bulletDragEnd() {
                        this.bulletDragIndex = null;
                        this.bulletDragOverIndex = null;
                    },
                    async saveEdit() {
                        if (this.editingId && this.editTitle.trim()) {
                            await $wire.updateScopeItem(this.editingId, this.editTitle, this.editDesc, this.editBullets.map(b => b.text));
                        }
                        this.editingId = null;
                    },
                    confirmDelete(id, title) {
                        this.deleteId = id;
                        this.deleteTitle = title;
                    },
                    executeDelete() {
                        if (this.deleteId) {
                            $wire.deleteScopeItem(this.deleteId);
                        }
                        this.deleteId = null;
                    }
                 }">
                {{-- Delete confirmation modal --}}
                <div x-show="deleteId" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @keydown.escape.window="deleteId = null">
                    <div @click.outside="deleteId = null"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Remove Scope Item</h3>
                        <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span class="font-medium text-gray-700" x-text="deleteTitle"></span>? This cannot be undone.</p>
                        <div class="flex items-center gap-3">
                            <button @click="deleteId = null"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button @click="executeDelete()"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                <div @if($isAdmin)
                     x-data="{
                         catDragOver: null,
                         itemDragOver: null,
                         handleCatDrop(e, targetCat) {
                             this.catDragOver = null;
                             const sourceCat = e.dataTransfer.getData('category');
                             if (!sourceCat || sourceCat === targetCat) return;
                             const sections = document.querySelectorAll('[data-scope-category]');
                             const ordered = Array.from(sections).map(el => el.dataset.scopeCategory);
                             const fromIdx = ordered.indexOf(sourceCat);
                             const toIdx = ordered.indexOf(targetCat);
                             ordered.splice(fromIdx, 1);
                             ordered.splice(toIdx, 0, sourceCat);
                             $wire.reorderScopeCategories(ordered);
                         },
                         handleItemDrop(e, targetId) {
                             this.itemDragOver = null;
                             const sourceId = parseInt(e.dataTransfer.getData('item'));
                             if (!sourceId || sourceId === targetId) return;
                             const allItems = document.querySelectorAll('[data-scope-id]');
                             const ordered = Array.from(allItems).map(el => parseInt(el.dataset.scopeId));
                             const fromIdx = ordered.indexOf(sourceId);
                             const toIdx = ordered.indexOf(targetId);
                             ordered.splice(fromIdx, 1);
                             ordered.splice(toIdx, 0, sourceId);
                             $wire.reorderScopeItems(ordered);
                         }
                     }"
                     @endif>
                @foreach($proposal->scopeItems->groupBy('category') as $category => $items)
                    <div class="mb-10 {{ $isAdmin ? 'group/cat' : '' }}"
                         data-scope-category="{{ $category }}"
                         @if($isAdmin)
                         draggable="true"
                         @dragstart.self="$event.dataTransfer.setData('category', @js($category)); $event.dataTransfer.effectAllowed = 'move'"
                         @dragover.prevent="catDragOver = @js($category)"
                         @dragleave.self="catDragOver = null"
                         @drop.self.prevent="handleCatDrop($event, @js($category))"
                         :class="{ 'border-2 border-brand border-dashed rounded-xl p-4 -m-4': catDragOver === @js($category) }"
                         @endif>
                        <h3 class="text-sm font-bold text-brand tracking-[0.15em] uppercase mb-5 flex items-center gap-3">
                            {{ $category ?: 'GENERAL' }}
                            @if($isAdmin)
                                <span class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 opacity-0 group-hover/cat:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                </span>
                            @endif
                        </h3>
                        <div class="space-y-4 sm:pl-4">
                            @foreach($items as $item)
                                <div class="group bg-gray-50 border border-gray-200 rounded-lg p-5 hover:border-gray-300 transition-colors {{ $isAdmin ? 'relative' : '' }}"
                                     data-scope-id="{{ $item->id }}"
                                     @if($isAdmin)
                                     draggable="true"
                                     @dragstart.stop="$event.dataTransfer.setData('item', '{{ $item->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                                     @dragover.prevent.stop="itemDragOver = {{ $item->id }}"
                                     @dragleave.stop="itemDragOver = null"
                                     @drop.prevent.stop="handleItemDrop($event, {{ $item->id }})"
                                     :class="{ 'border-brand border-dashed': itemDragOver === {{ $item->id }} }"
                                     @endif>

                                    {{-- View mode --}}
                                    <div x-show="editingId !== {{ $item->id }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1 {{ $isAdmin ? 'cursor-pointer' : '' }}"
                                                 @if($isAdmin) @click="startEdit({{ $item->id }}, @js($item->title), @js($item->description ?? ''), @js($item->bullets ?? []))" @endif>
                                                <h4 class="font-semibold text-gray-900 text-base">{{ $item->title }}</h4>
                                                @if($item->description)
                                                    <p class="text-gray-500 mt-1.5 text-sm leading-relaxed">{{ $item->description }}</p>
                                                @endif
                                                @if($item->bullets && count($item->bullets))
                                                    <ul class="mt-2 space-y-1.5 ml-4">
                                                        @foreach($item->bullets as $bullet)
                                                            <li class="flex items-start gap-2 text-gray-500 text-sm leading-relaxed">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-900 mt-1.5 flex-shrink-0"></span>
                                                                <span>{{ $bullet }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                            @if($isAdmin)
                                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                    {{-- Item drag handle --}}
                                                    <span class="cursor-grab active:cursor-grabbing p-1.5 text-gray-300 hover:text-gray-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                                    </span>
                                                    {{-- Duplicate --}}
                                                    <button wire:click="duplicateScopeItem({{ $item->id }})"
                                                            title="Duplicate"
                                                            class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    </button>
                                                    {{-- Edit --}}
                                                    <button @click="startEdit({{ $item->id }}, @js($item->title), @js($item->description ?? ''), @js($item->bullets ?? []))"
                                                            title="Edit"
                                                            class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    {{-- Delete --}}
                                                    <button @click="confirmDelete({{ $item->id }}, @js($item->title))"
                                                            class="p-1.5 text-gray-300 hover:text-red-500 transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Edit mode --}}
                                    @if($isAdmin)
                                        <div x-show="editingId === {{ $item->id }}" x-cloak>
                                            <input type="text" x-model="editTitle"
                                                   @keydown.escape="editingId = null"
                                                   class="w-full text-base font-semibold text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none mb-3"
                                                   placeholder="Item title">
                                            <div class="mb-3">
                                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</label>
                                                <textarea x-model="editDesc"
                                                          @keydown.escape="editingId = null"
                                                          rows="2"
                                                          class="w-full text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none mt-1 resize-y"
                                                          placeholder="Brief description (optional)"></textarea>
                                            </div>
                                            <div class="space-y-2 mb-3">
                                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Bullet Points</label>
                                                <template x-for="(bullet, index) in editBullets" :key="bullet.id">
                                                    <div class="flex items-center gap-2"
                                                         draggable="true"
                                                         @dragstart.stop="bulletDragStart(index)"
                                                         @dragover.prevent.stop="bulletDragOver(index)"
                                                         @drop.prevent.stop="bulletDrop(index)"
                                                         @dragend="bulletDragEnd()"
                                                         :class="{ 'opacity-50': bulletDragIndex === index, 'border-t-2 border-brand': bulletDragOverIndex === index && bulletDragIndex !== null && bulletDragIndex !== index }">
                                                        <span class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 flex-shrink-0" title="Drag to reorder">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                                        </span>
                                                        <span class="w-1.5 h-1.5 rounded-full bg-brand flex-shrink-0"></span>
                                                        <input type="text" x-model="bullet.text"
                                                               data-bullet-input
                                                               @keydown.enter.prevent="addBullet()"
                                                               @keydown.backspace="if (bullet.text === '' && editBullets.length > 1) { $event.preventDefault(); removeBullet(index); }"
                                                               class="flex-1 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none"
                                                               placeholder="Bullet point text">
                                                        <button @click="removeBullet(index)"
                                                                x-show="editBullets.length > 1"
                                                                class="p-1 text-gray-300 hover:text-red-500 transition-colors cursor-pointer flex-shrink-0">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                <button @click="addBullet()"
                                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-brand hover:text-brand/80 transition-colors cursor-pointer mt-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Add bullet
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="saveEdit()"
                                                        class="px-3 py-1.5 bg-brand text-white text-xs font-medium rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Save</button>
                                                <button @click="editingId = null"
                                                        class="px-3 py-1.5 text-gray-500 text-xs font-medium hover:text-gray-700 cursor-pointer">Cancel</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========== AGILE PROCESS SECTION ========== --}}
    @if($proposal->process_enabled || $isAdmin)
    @php
        $processBg = $proposal->process_background
            ? (str_starts_with($proposal->process_background, 'images/') ? asset($proposal->process_background) : Storage::url($proposal->process_background))
            : asset('images/street.png');
        $processStages = $proposal->process_stages_resolved;
        $processEyebrow = $proposal->process_eyebrow ?? 'Our Process';
        $processHeading = $proposal->process_heading ?? 'Ship early. Ship often. Level up together.';
        $processSubheading = $proposal->process_subheading ?? "We don't disappear for six months and hand you a finished product. We deliver something usable at every stage — you ride it, learn from it, and we iterate toward the end goal together.";
    @endphp
    <section id="process" class="relative py-12 sm:py-20 px-4 sm:px-6 scroll-mt-16 overflow-hidden bg-neutral-900">
        {{-- Background image --}}
        <div class="absolute inset-0 bg-cover bg-center opacity-20 grayscale"
             style="background-image: url('{{ $processBg }}?v={{ $proposal->updated_at?->timestamp }}');"></div>
        {{-- Contrast overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-neutral-900/60 via-neutral-900/40 to-neutral-900/80"></div>

        <div class="relative max-w-6xl mx-auto">
            {{-- Admin: Toggle + Settings --}}
            @if($isAdmin)
            <div class="pdf-hide mb-8 p-4 bg-white/95 backdrop-blur rounded-xl border border-white/30 shadow-lg"
                 x-data="{
                    showSettings: false,
                    showUpload: false,
                    uploading: false,
                    progress: 0,
                    success: false,
                    error: '',
                    resetStatus() { this.progress = 0; this.success = false; this.error = ''; },
                 }"
                 x-on:livewire-upload-start="uploading = true; resetStatus();"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 x-on:livewire-upload-finish="uploading = false; progress = 100; success = true; setTimeout(() => success = false, 4000);"
                 x-on:livewire-upload-cancel="uploading = false; progress = 0;"
                 x-on:livewire-upload-error="uploading = false; error = 'Upload failed. Please try a different image (JPG/PNG, under 10MB).';">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingProcessEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "Our Process" in Proposal</span>
                    </label>
                    <div class="flex items-center gap-2" x-show="$wire.editingProcessEnabled">
                        <button @click="showUpload = !showUpload; showSettings = false"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-white border border-gray-200 text-gray-600 hover:text-gray-900 shadow-sm transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Background
                        </button>
                        <button @click="showSettings = !showSettings; showUpload = false"
                                type="button"
                                class="text-xs text-brand hover:text-gray-900 font-medium transition-colors cursor-pointer">
                            <span x-text="showSettings ? 'Hide Settings' : 'Edit Settings'"></span>
                        </button>
                    </div>
                </div>

                {{-- Background uploader --}}
                <div x-show="showUpload && $wire.editingProcessEnabled" x-cloak x-collapse class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0">
                            <div class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                                <img src="{{ $processBg }}?v={{ $proposal->updated_at?->timestamp }}"
                                     alt="Current background"
                                     class="absolute inset-0 w-full h-full object-cover">
                            </div>
                            <p class="mt-1 text-[10px] text-center uppercase tracking-wide text-gray-400">
                                {{ $proposal->process_background ? 'Custom' : 'Default' }}
                            </p>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Upload Background Image</label>
                            <p class="text-xs text-gray-500 mb-2">JPG/PNG, under 10MB. Will be desaturated and dimmed for contrast.</p>
                            <input type="file" wire:model="processBackground" accept="image/*"
                                   x-bind:disabled="uploading"
                                   class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer disabled:opacity-50">

                            <div x-show="uploading" x-cloak class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-gray-600 font-medium">Uploading…</span>
                                    <span class="text-gray-500 tabular-nums" x-text="progress + '%'"></span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand transition-all duration-150"
                                         x-bind:style="`width: ${progress}%`"></div>
                                </div>
                            </div>

                            <div x-show="success" x-cloak class="mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Background updated.
                            </div>

                            <div x-show="error" x-cloak class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">
                                <span x-text="error"></span>
                            </div>

                            @error('processBackground')
                                <div class="mt-3 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">{{ $message }}</div>
                            @enderror

                            @if($proposal->process_background)
                                <button wire:click="removeProcessBackground"
                                        x-bind:disabled="uploading"
                                        class="mt-3 text-xs text-red-500 hover:text-red-700 transition cursor-pointer disabled:opacity-50">
                                    Reset to default image
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Settings: header text --}}
                <div x-show="showSettings && $wire.editingProcessEnabled" x-cloak x-collapse class="mt-4 pt-4 border-t border-gray-200">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Eyebrow</label>
                            <input type="text" wire:model.blur="editingProcessEyebrow"
                                   wire:change="saveProcessSettings"
                                   placeholder="Our Process"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Heading</label>
                            <textarea wire:model.blur="editingProcessHeading"
                                      wire:change="saveProcessSettings"
                                      rows="2"
                                      class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Subheading</label>
                            <textarea wire:model.blur="editingProcessSubheading"
                                      wire:change="saveProcessSettings"
                                      rows="3"
                                      class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($proposal->process_enabled)
            <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand mb-3">{{ $processEyebrow }}</p>
                <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight [text-shadow:0_2px_8px_rgba(0,0,0,0.6)]">{{ $processHeading }}</h2>
                <p class="mt-5 text-lg text-gray-300 leading-relaxed">
                    {{ $processSubheading }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-5 gap-8 sm:gap-4"
                 @if($isAdmin) x-data="{ editingStage: null, editLabel: '', editCaption: '' }" @endif>
                @foreach($processStages as $i => $stage)
                    @php
                        $stageImg = str_starts_with($stage['image'], 'images/')
                            ? asset($stage['image'])
                            : Storage::url($stage['image']);
                    @endphp
                    <div class="relative flex flex-col items-center text-center group">
                        {{-- Connector arrow (desktop only, between cards) --}}
                        @if(! $loop->last)
                            <div class="hidden sm:block absolute top-16 left-[60%] w-[80%] h-0.5 -translate-y-1/2 bg-gradient-to-r from-brand/80 to-brand/50 pointer-events-none" aria-hidden="true">
                                <div class="absolute right-0 top-1/2 -translate-y-1/2 w-0 h-0 border-y-[5px] border-l-[7px] border-y-transparent border-l-brand"></div>
                            </div>
                        @endif

                        <div class="relative z-10 w-28 h-28 sm:w-32 sm:h-32 rounded-full bg-white shadow-xl ring-1 ring-white/20 flex items-center justify-center transition-transform duration-300 group-hover:-translate-y-1 group-hover:shadow-2xl">
                            <img src="{{ $stageImg }}?v={{ $proposal->updated_at?->timestamp }}"
                                 alt="{{ $stage['label'] }}"
                                 class="w-20 h-20 sm:w-24 sm:h-24 object-contain">

                            {{-- Admin per-stage controls --}}
                            @if($isAdmin)
                            <div class="pdf-hide absolute -top-2 -right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20"
                                 x-data="{
                                    uploading: false,
                                    progress: 0,
                                    error: '',
                                 }"
                                 x-on:livewire-upload-start="uploading = true; progress = 0; error = '';"
                                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                                 x-on:livewire-upload-finish="uploading = false; progress = 100;"
                                 x-on:livewire-upload-error="uploading = false; error = 'Upload failed';">
                                <label title="Upload image"
                                       class="p-1.5 bg-white rounded-full text-gray-600 hover:text-brand transition-colors cursor-pointer shadow-md">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <input type="file" wire:model="stageImages.{{ $i }}" accept="image/*" class="hidden">
                                </label>
                                @if(! str_starts_with($stage['image'], 'images/'))
                                    <button wire:click="removeStageImage({{ $i }})"
                                            title="Reset to default"
                                            class="p-1.5 bg-white rounded-full text-gray-600 hover:text-red-500 transition-colors cursor-pointer shadow-md">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                @endif
                                <div x-show="uploading" x-cloak class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] text-white bg-neutral-800/90 px-2 py-0.5 rounded whitespace-nowrap">
                                    <span x-text="progress + '%'"></span>
                                </div>
                            </div>
                            @endif
                        </div>

                        @if($isAdmin)
                            <button @click="editingStage = {{ $i }}; editLabel = @js($stage['label']); editCaption = @js($stage['caption']);"
                                    type="button"
                                    class="pdf-hide mt-5 text-base font-semibold text-white hover:text-brand transition-colors cursor-pointer border-b border-dashed border-transparent hover:border-brand">
                                {{ $stage['label'] }}
                            </button>
                            <button @click="editingStage = {{ $i }}; editLabel = @js($stage['label']); editCaption = @js($stage['caption']);"
                                    type="button"
                                    class="pdf-hide mt-1 text-sm text-gray-400 leading-snug px-2 hover:text-gray-200 transition-colors cursor-pointer text-center">
                                {!! $stage['caption'] !!}
                            </button>
                        @else
                            <div class="mt-5 text-base font-semibold text-white">{{ $stage['label'] }}</div>
                            <div class="mt-1 text-sm text-gray-400 leading-snug px-2">{!! $stage['caption'] !!}</div>
                        @endif
                    </div>
                @endforeach

                @if($isAdmin)
                {{-- Edit Stage Modal --}}
                <div x-show="editingStage !== null" x-cloak
                     x-transition.opacity
                     @keydown.escape.window="editingStage = null"
                     class="pdf-hide fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <div @click.outside="editingStage = null"
                         class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900">Edit Stage <span x-text="editingStage !== null ? editingStage + 1 : ''"></span></h3>
                            <button @click="editingStage = null" class="p-1 text-gray-400 hover:text-gray-600 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                                <input type="text" x-model="editLabel"
                                       class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Caption</label>
                                <textarea x-model="editCaption" rows="2"
                                          class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"></textarea>
                                <p class="mt-1 text-[11px] text-gray-400">HTML allowed (e.g. <code class="text-gray-500">&amp;mdash;</code> for em-dash).</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-2xl">
                            <button @click="editingStage = null"
                                    class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">Cancel</button>
                            <button @click="$wire.updateProcessStage(editingStage, editLabel, editCaption); editingStage = null;"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-brand rounded-lg hover:bg-gray-900 transition-colors cursor-pointer shadow-sm">Save</button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ========== COST / INVESTMENT SECTION ========== --}}
    @if(($proposal->investment_enabled && $proposal->costItems->count()) || $isAdmin)
    <section id="investment" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingInvestmentEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Show "Investment" in client proposal</span>
                    </label>
                    @if(!$proposal->investment_enabled)
                        <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            Hidden from client view
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Investment</h2>

                @if($isAdmin)
                    <button wire:click="addCostItem"
                            class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Row
                    </button>
                @endif
            </div>

            <div class="sm:pl-8">
            {{-- Cost notes - centered, larger font --}}
            @if($proposal->cost_notes || $isAdmin)
                @if($isAdmin)
                    <div x-data="{
                            editing: false,
                            save() {
                                this.editing = false;
                                $wire.set('editingCostNotes', $refs.costNotesEditor.innerText);
                                $wire.saveCostNotes();
                            }
                         }"
                         class="relative group mb-10">
                        <div x-show="!editing"
                             @click="editing = true; $nextTick(() => { $refs.costNotesEditor.focus(); })"
                             class="text-gray-500 text-lg leading-relaxed text-center cursor-pointer rounded-lg p-4 -m-4 border-2 border-dashed border-transparent hover:border-gray-300 transition-colors min-h-[40px]">
                            @if($proposal->cost_notes)
                                {{ $proposal->cost_notes }}
                            @else
                                <span class="text-gray-400 italic">Click to add timeline / notes...</span>
                            @endif
                        </div>
                        <div x-show="editing" x-cloak
                             @click.outside="save()"
                             @keydown.escape.window="save()">
                            <div x-ref="costNotesEditor"
                                 contenteditable="true"
                                 class="text-gray-500 text-lg leading-relaxed text-center bg-white border-2 border-brand/30 focus:border-brand rounded-lg p-4 -m-4 focus:outline-none min-h-[40px] transition-colors"
                            >{{ $proposal->cost_notes }}</div>
                            <p class="text-xs text-gray-400 mt-3 text-center">Click outside or press Escape to save.</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 mb-10 text-lg leading-relaxed text-center">{{ $proposal->cost_notes }}</p>
                @endif
            @endif

            {{-- Cost table --}}
            <div x-data="{
                    editingCostId: null,
                    editDesc: '',
                    editQty: 1,
                    editPrice: 0,
                    deleteCostId: null,
                    deleteCostTitle: '',
                    costDragId: null,
                    costDragOver: null,
                    startCostEdit(id, desc, qty, price) {
                        this.editingCostId = id;
                        this.editDesc = desc;
                        this.editQty = qty;
                        this.editPrice = price;
                    },
                    saveCostEdit() {
                        if (this.editingCostId && this.editDesc.trim()) {
                            $wire.updateCostItem(this.editingCostId, this.editDesc, parseInt(this.editQty), parseFloat(this.editPrice));
                        }
                        this.editingCostId = null;
                    },
                    confirmCostDelete(id, title) {
                        this.deleteCostId = id;
                        this.deleteCostTitle = title;
                    },
                    executeCostDelete() {
                        if (this.deleteCostId) {
                            $wire.deleteCostItem(this.deleteCostId);
                        }
                        this.deleteCostId = null;
                    },
                    handleCostDrop(e, targetId) {
                        this.costDragOver = null;
                        const sourceId = parseInt(e.dataTransfer.getData('cost-item'));
                        if (!sourceId || sourceId === targetId) return;
                        const allRows = document.querySelectorAll('[data-cost-id]');
                        const ordered = Array.from(allRows).map(el => parseInt(el.dataset.costId));
                        const fromIdx = ordered.indexOf(sourceId);
                        const toIdx = ordered.indexOf(targetId);
                        ordered.splice(fromIdx, 1);
                        ordered.splice(toIdx, 0, sourceId);
                        $wire.reorderCostItems(ordered);
                    }
                 }">

                {{-- Delete confirmation modal --}}
                <div x-show="deleteCostId" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @keydown.escape.window="deleteCostId = null">
                    <div @click.outside="deleteCostId = null"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Remove Line Item</h3>
                        <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span class="font-medium text-gray-700" x-text="deleteCostTitle"></span>? This cannot be undone.</p>
                        <div class="flex items-center gap-3">
                            <button @click="deleteCostId = null"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button @click="executeCostDelete()"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left text-gray-500 text-xs uppercase tracking-wider">
                                @if($isAdmin)<th class="w-10"></th>@endif
                                <th class="px-3 sm:px-6 py-4">Service</th>
                                <th class="px-2 sm:px-6 py-4 text-center">Qty</th>
                                <th class="px-2 sm:px-6 py-4 text-center">Rate</th>
                                <th class="px-3 sm:px-6 py-4 text-right">Amount</th>
                                @if($isAdmin)<th class="w-24"></th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($proposal->costItems as $index => $item)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50/50' }} group/row hover:bg-gray-50 transition-colors"
                                data-cost-id="{{ $item->id }}"
                                @if($isAdmin)
                                draggable="true"
                                @dragstart="$event.dataTransfer.setData('cost-item', '{{ $item->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                                @dragover.prevent="costDragOver = {{ $item->id }}"
                                @dragleave="costDragOver = null"
                                @drop.prevent="handleCostDrop($event, {{ $item->id }})"
                                :class="{ '!border-brand !border-dashed': costDragOver === {{ $item->id }} }"
                                @endif>

                                {{-- Drag handle --}}
                                @if($isAdmin)
                                <td class="pl-3 pr-0 py-4">
                                    <span class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 opacity-0 group-hover/row:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                    </span>
                                </td>
                                @endif

                                {{-- View mode --}}
                                <template x-if="editingCostId !== {{ $item->id }}">
                                    <td class="px-3 sm:px-6 py-4 text-gray-900 {{ $isAdmin ? 'cursor-pointer' : '' }}"
                                        @if($isAdmin) @click="startCostEdit({{ $item->id }}, @js($item->description), {{ $item->quantity }}, {{ $item->unit_price }})" @endif>
                                        {{ $item->description }}
                                    </td>
                                </template>
                                <template x-if="editingCostId !== {{ $item->id }}">
                                    <td class="px-2 sm:px-6 py-4 text-center text-gray-500">{{ $item->quantity }}</td>
                                </template>
                                <template x-if="editingCostId !== {{ $item->id }}">
                                    <td class="px-2 sm:px-6 py-4 text-center text-gray-500">${{ number_format($item->unit_price, 0) }}</td>
                                </template>
                                <template x-if="editingCostId !== {{ $item->id }}">
                                    <td class="px-3 sm:px-6 py-4 text-right text-gray-900 font-semibold">${{ number_format($item->amount, 0) }}</td>
                                </template>

                                {{-- Edit mode --}}
                                <template x-if="editingCostId === {{ $item->id }}">
                                    <td class="px-4 py-2">
                                        <input type="text" x-model="editDesc"
                                               @keydown.enter="saveCostEdit()" @keydown.escape="editingCostId = null"
                                               class="w-full text-sm text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none"
                                               placeholder="Service description">
                                    </td>
                                </template>
                                <template x-if="editingCostId === {{ $item->id }}">
                                    <td class="px-2 py-2">
                                        <input type="number" x-model="editQty" min="1"
                                               @keydown.enter="saveCostEdit()" @keydown.escape="editingCostId = null"
                                               class="w-20 text-sm text-center text-gray-900 bg-white border border-gray-200 rounded-lg px-2 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                    </td>
                                </template>
                                <template x-if="editingCostId === {{ $item->id }}">
                                    <td class="px-2 py-2">
                                        <input type="number" x-model="editPrice" min="0" step="1"
                                               @keydown.enter="saveCostEdit()" @keydown.escape="editingCostId = null"
                                               class="w-28 text-sm text-right text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none"
                                               placeholder="0">
                                    </td>
                                </template>
                                <template x-if="editingCostId === {{ $item->id }}">
                                    <td class="px-4 py-2 text-right">
                                        <button @click="saveCostEdit()"
                                                class="px-3 py-1.5 bg-brand text-white text-xs font-medium rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Save</button>
                                    </td>
                                </template>

                                {{-- Actions --}}
                                @if($isAdmin)
                                <template x-if="editingCostId !== {{ $item->id }}">
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-1 opacity-0 group-hover/row:opacity-100 transition-opacity justify-end">
                                            <button wire:click="duplicateCostItem({{ $item->id }})"
                                                    title="Duplicate"
                                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                            <button @click="startCostEdit({{ $item->id }}, @js($item->description), {{ $item->quantity }}, {{ $item->unit_price }})"
                                                    title="Edit"
                                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="confirmCostDelete({{ $item->id }}, @js($item->description))"
                                                    title="Delete"
                                                    class="p-1.5 text-gray-300 hover:text-red-500 transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </template>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if($proposal->discount_enabled && $proposal->discount_amount > 0)
                                {{-- Subtotal row --}}
                                <tr class="border-t border-gray-200">
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-3 sm:px-6 py-3 text-right">
                                        <span class="text-gray-600 font-medium">Subtotal</span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-3 text-right">
                                        <span class="text-gray-600 font-medium text-lg">${{ number_format($proposal->subtotal, 0) }}</span>
                                    </td>
                                    @if($isAdmin)<td></td>@endif
                                </tr>

                                {{-- Discount row --}}
                                <tr class="border-t border-gray-100">
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-3 sm:px-6 py-3 text-right">
                                        <span class="text-green-600 font-medium">
                                            Discount
                                            @if($proposal->discount_type === 'percent')
                                                ({{ number_format($proposal->discount_value, 0) }}%)
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-3 text-right">
                                        <span class="text-green-600 font-medium text-lg">(${{ number_format($proposal->discount_amount, 0) }})</span>
                                    </td>
                                    @if($isAdmin)<td></td>@endif
                                </tr>

                                {{-- Total row --}}
                                <tr class="bg-gray-50 border-t-2 border-gray-200">
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-3 sm:px-6 py-5 text-right">
                                        <span class="text-gray-900 font-bold text-lg">Total</span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-5 text-right">
                                        <span class="text-gray-900 font-bold text-xl sm:text-2xl">${{ number_format($proposal->total, 0) }}</span>
                                    </td>
                                    @if($isAdmin)<td></td>@endif
                                </tr>
                            @else
                                {{-- No discount: single Total row --}}
                                <tr class="bg-gray-50 border-t-2 border-gray-200">
                                    <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-3 sm:px-6 py-5 text-right">
                                        <span class="text-gray-900 font-bold text-lg">Total</span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-5 text-right">
                                        <span class="text-gray-900 font-bold text-xl sm:text-2xl">${{ number_format($proposal->subtotal, 0) }}</span>
                                    </td>
                                    @if($isAdmin)<td></td>@endif
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Discount toggle (admin only) --}}
            @if($isAdmin)
            <div class="mt-4 px-2 flex items-center gap-4 flex-wrap">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <span class="text-sm text-gray-400">Add Discount</span>
                    <div class="relative">
                        <input type="checkbox"
                               wire:model.live="editingDiscountEnabled"
                               class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-brand peer-focus:outline-none after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white"></div>
                    </div>
                </label>

                @if($proposal->discount_enabled)
                <div class="flex items-center gap-2">
                    <select wire:model.live="editingDiscountType"
                            class="text-sm bg-white border border-gray-200 rounded-lg px-2 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none cursor-pointer">
                        <option value="percent">%</option>
                        <option value="fixed">$</option>
                    </select>

                    <input type="number"
                           wire:model.live.debounce.500ms="editingDiscountValue"
                           min="0"
                           @if($editingDiscountType === 'percent') max="100" @endif
                           step="1"
                           placeholder="0"
                           class="w-24 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                </div>
                @endif
            </div>
            @endif

            {{-- Valid until --}}
            <div class="mt-4 px-2">
                @if($isAdmin)
                    <div wire:ignore x-data="{
                        fp: null,
                        init() {
                            this.fp = flatpickr(this.$refs.datepicker, {
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'F j, Y',
                                altInputClass: 'bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 text-gray-600 text-sm cursor-pointer p-0 transition-colors',
                                defaultDate: @js($editingValidUntil),
                                onChange(selectedDates, dateStr) {
                                    Livewire.dispatch('update-valid-until', { date: dateStr });
                                },
                            });
                        },
                        destroy() { if (this.fp) this.fp.destroy(); }
                    }" class="flex items-center gap-2 text-sm text-gray-400">
                        <span>Valid until</span>
                        <input x-ref="datepicker" type="text" readonly
                               class="hidden">
                    </div>
                @elseif($proposal->valid_until)
                    <span class="text-gray-400 text-sm">
                        Valid until <span class="text-gray-600">{{ $proposal->valid_until->format('F j, Y') }}</span>
                    </span>
                @endif
            </div>
            </div>

        </div>
    </section>
    @endif

    {{-- ========== DIFFERENTIATOR SECTION ========== --}}
    @if($proposal->differentiator_enabled || $isAdmin)
    @php
        $diffBg = $proposal->differentiator_background
            ? (str_starts_with($proposal->differentiator_background, 'images/') ? asset($proposal->differentiator_background) : Storage::url($proposal->differentiator_background))
            : asset('images/rva-street.png');
    @endphp
    <style>
        @keyframes differentiator-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.82; }
        }
        .differentiator-pulse { animation: differentiator-pulse 3.5s ease-in-out infinite; }
    </style>
    <section id="why-custom" class="relative py-32 sm:py-44 px-4 sm:px-6 scroll-mt-16 overflow-hidden" style="background-color: #111;">
        {{-- Background image at 40% opacity for contrast --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-40"
             style="background-image: url('{{ $diffBg }}?v={{ $proposal->updated_at?->timestamp }}');"></div>

        <div class="relative max-w-5xl mx-auto">

            {{-- Admin: Toggle + Settings --}}
            @if($isAdmin)
            <div class="pdf-hide mb-8 p-4 bg-white/95 backdrop-blur rounded-xl border border-white/30 shadow-lg"
                 x-data="{
                    showSettings: false,
                    showUpload: false,
                    uploading: false,
                    progress: 0,
                    success: false,
                    error: '',
                    resetStatus() { this.progress = 0; this.success = false; this.error = ''; },
                 }"
                 x-on:livewire-upload-start="uploading = true; resetStatus();"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 x-on:livewire-upload-finish="uploading = false; progress = 100; success = true; setTimeout(() => success = false, 4000);"
                 x-on:livewire-upload-cancel="uploading = false; progress = 0;"
                 x-on:livewire-upload-error="uploading = false; error = 'Upload failed. Please try a different image (JPG/PNG, under 10MB).';">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingDifferentiatorEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "Why Custom" in Proposal</span>
                    </label>
                    <div class="flex items-center gap-2" x-show="$wire.editingDifferentiatorEnabled">
                        <button @click="showUpload = !showUpload; showSettings = false"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-white border border-gray-200 text-gray-600 hover:text-gray-900 shadow-sm transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Background
                        </button>
                        <button @click="showSettings = !showSettings; showUpload = false"
                                type="button"
                                class="text-xs text-brand hover:text-gray-900 font-medium transition-colors cursor-pointer">
                            <span x-text="showSettings ? 'Hide Settings' : 'Edit Settings'"></span>
                        </button>
                    </div>
                </div>

                {{-- Background uploader --}}
                <div x-show="showUpload && $wire.editingDifferentiatorEnabled" x-cloak x-collapse class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-start gap-4">
                        {{-- Current preview thumbnail --}}
                        <div class="shrink-0">
                            <div class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                                <img src="{{ $diffBg }}?v={{ $proposal->updated_at?->timestamp }}"
                                     alt="Current background"
                                     class="absolute inset-0 w-full h-full object-cover">
                            </div>
                            <p class="mt-1 text-[10px] text-center uppercase tracking-wide text-gray-400">
                                {{ $proposal->differentiator_background ? 'Custom' : 'Default' }}
                            </p>
                        </div>

                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Upload Background Image</label>
                            <p class="text-xs text-gray-500 mb-2">JPG/PNG, under 10MB. Will be darkened with a red overlay.</p>
                            <input type="file" wire:model="differentiatorBackground" accept="image/*"
                                   x-bind:disabled="uploading"
                                   class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer disabled:opacity-50">

                            {{-- Progress bar --}}
                            <div x-show="uploading" x-cloak class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-gray-600 font-medium flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 animate-spin text-brand" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                        Uploading…
                                    </span>
                                    <span class="text-gray-500 tabular-nums" x-text="progress + '%'"></span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand transition-all duration-150"
                                         x-bind:style="`width: ${progress}%`"></div>
                                </div>
                            </div>

                            {{-- Success message --}}
                            <div x-show="success" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Background updated. Scroll down to see it.
                            </div>

                            {{-- Error message --}}
                            <div x-show="error" x-cloak
                                 class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                <span x-text="error"></span>
                            </div>

                            @error('differentiatorBackground')
                                <div class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            @if($proposal->differentiator_background)
                                <button wire:click="removeDifferentiatorBackground"
                                        x-bind:disabled="uploading"
                                        class="mt-3 text-xs text-red-500 hover:text-red-700 transition cursor-pointer disabled:opacity-50">
                                    Reset to default image
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Settings --}}
                <div x-show="showSettings && $wire.editingDifferentiatorEnabled" x-cloak x-collapse class="mt-4 pt-4 border-t border-gray-200">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Headline</label>
                            <textarea wire:model.blur="editingDifferentiatorHeadline"
                                      wire:change="saveDifferentiatorSettings"
                                      rows="2"
                                      class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Attribution</label>
                            <input type="text" wire:model.blur="editingDifferentiatorAttribution"
                                   wire:change="saveDifferentiatorSettings"
                                   placeholder="— Almost Every Client"
                                   class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($proposal->differentiator_enabled)
            <div class="text-center">
                <h2 class="differentiator-pulse text-[1.7rem] sm:text-[2.7rem] lg:text-[3.4rem] font-semibold text-white leading-tight max-w-4xl mx-auto whitespace-pre-line [text-shadow:0_2px_12px_rgba(0,0,0,0.9)]">{{ $proposal->differentiator_headline ?? '"We should have gone the custom route sooner!"' }}</h2>
                @if($proposal->differentiator_attribution)
                    <p class="mt-6 text-base sm:text-lg text-white/70 tracking-wide [text-shadow:0_1px_6px_rgba(0,0,0,0.9)]">{{ $proposal->differentiator_attribution }}</p>
                @endif
            </div>
            @endif

        </div>
    </section>
    @endif

    {{-- ========== MILESTONES SECTION ========== --}}
    @if(($proposal->milestones_enabled && $proposal->milestones->count()) || $isAdmin)
    <section id="milestones" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingMilestonesEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Show "Payment Milestones" in client proposal</span>
                    </label>
                    @if(!$proposal->milestones_enabled)
                        <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            Hidden from client view
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Payment Milestones</h2>

                @if($isAdmin)
                    <button wire:click="addMilestone"
                            class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Milestone
                    </button>
                @endif
            </div>

            <div class="sm:pl-8">
            <p class="text-gray-500 mb-8 leading-relaxed">Milestone payments will become due throughout the life of the project as various milestones are reached as follows:</p>

            <div x-data="{
                    editingMsId: null,
                    editMsTitle: '',
                    editMsPct: 0,
                    deleteMsId: null,
                    deleteMsTitle: '',
                    msDragOver: null,
                    startMsEdit(id, title, pct) {
                        this.editingMsId = id;
                        this.editMsTitle = title;
                        this.editMsPct = pct;
                    },
                    saveMsEdit() {
                        if (this.editingMsId && this.editMsTitle.trim()) {
                            $wire.updateMilestone(this.editingMsId, this.editMsTitle, parseFloat(this.editMsPct));
                        }
                        this.editingMsId = null;
                    },
                    confirmMsDelete(id, title) {
                        this.deleteMsId = id;
                        this.deleteMsTitle = title;
                    },
                    executeMsDelete() {
                        if (this.deleteMsId) {
                            $wire.deleteMilestone(this.deleteMsId);
                        }
                        this.deleteMsId = null;
                    },
                    handleMsDrop(e, targetId) {
                        this.msDragOver = null;
                        const sourceId = parseInt(e.dataTransfer.getData('milestone'));
                        if (!sourceId || sourceId === targetId) return;
                        const allItems = document.querySelectorAll('[data-milestone-id]');
                        const ordered = Array.from(allItems).map(el => parseInt(el.dataset.milestoneId));
                        const fromIdx = ordered.indexOf(sourceId);
                        const toIdx = ordered.indexOf(targetId);
                        ordered.splice(fromIdx, 1);
                        ordered.splice(toIdx, 0, sourceId);
                        $wire.reorderMilestones(ordered);
                    }
                 }">

                {{-- Delete confirmation modal --}}
                <div x-show="deleteMsId" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @keydown.escape.window="deleteMsId = null">
                    <div @click.outside="deleteMsId = null"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Remove Milestone</h3>
                        <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span class="font-medium text-gray-700" x-text="deleteMsTitle"></span>? This cannot be undone.</p>
                        <div class="flex items-center gap-3">
                            <button @click="deleteMsId = null"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button @click="executeMsDelete()"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden divide-y divide-gray-100">
                    @foreach($proposal->milestones as $index => $milestone)
                    <div class="group/ms flex items-center gap-4 py-4 px-5 hover:bg-gray-50/50 transition-colors"
                         data-milestone-id="{{ $milestone->id }}"
                         @if($isAdmin)
                         draggable="true"
                         @dragstart="$event.dataTransfer.setData('milestone', '{{ $milestone->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                         @dragover.prevent="msDragOver = {{ $milestone->id }}"
                         @dragleave="msDragOver = null"
                         @drop.prevent="handleMsDrop($event, {{ $milestone->id }})"
                         :class="{ 'border border-brand border-dashed': msDragOver === {{ $milestone->id }} }"
                         @endif>

                        {{-- Number --}}
                        <span class="w-7 h-7 bg-brand text-white text-xs font-bold rounded-full flex items-center justify-center shrink-0">{{ $index + 1 }}</span>

                        {{-- View mode --}}
                        <div x-show="editingMsId !== {{ $milestone->id }}" class="flex-1 flex items-center gap-2 min-w-0">
                            <span class="text-gray-900 {{ $isAdmin ? 'cursor-pointer' : '' }}"
                                  @if($isAdmin) @click="startMsEdit({{ $milestone->id }}, @js($milestone->title), {{ $milestone->percentage ?? 0 }})" @endif>
                                @if($milestone->percentage)
                                    <span class="font-bold">{{ number_format($milestone->percentage, 0) }}%</span>
                                    <span class="text-gray-500">(${{ number_format(($milestone->percentage / 100) * $proposal->total, 0) }})</span>
                                @endif
                                {{ $milestone->title }}
                            </span>
                        </div>

                        {{-- Edit mode --}}
                        <div x-show="editingMsId === {{ $milestone->id }}" x-cloak class="flex-1 flex items-center gap-3">
                            <div class="flex items-center gap-1">
                                <input type="number" x-model="editMsPct" min="0" max="100" step="1"
                                       @keydown.enter="saveMsEdit()" @keydown.escape="editingMsId = null"
                                       class="w-16 text-sm text-center text-gray-900 bg-white border border-gray-200 rounded-lg px-2 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                <span class="text-gray-400 text-sm">%</span>
                            </div>
                            <input type="text" x-model="editMsTitle"
                                   @keydown.enter="saveMsEdit()" @keydown.escape="editingMsId = null"
                                   class="flex-1 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-1.5 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none"
                                   placeholder="Milestone description">
                            <button @click="saveMsEdit()"
                                    class="px-3 py-1.5 bg-brand text-white text-xs font-medium rounded-lg hover:bg-gray-900 transition-colors cursor-pointer shrink-0">Save</button>
                        </div>

                        {{-- Actions --}}
                        @if($isAdmin)
                        <div x-show="editingMsId !== {{ $milestone->id }}" class="flex items-center gap-1 opacity-0 group-hover/ms:opacity-100 transition-opacity shrink-0">
                            <span class="cursor-grab active:cursor-grabbing p-1.5 text-gray-300 hover:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </span>
                            <button wire:click="duplicateMilestone({{ $milestone->id }})"
                                    title="Duplicate"
                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button @click="startMsEdit({{ $milestone->id }}, @js($milestone->title), {{ $milestone->percentage ?? 0 }})"
                                    title="Edit"
                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="confirmMsDelete({{ $milestone->id }}, @js($milestone->title))"
                                    title="Delete"
                                    class="p-1.5 text-gray-300 hover:text-red-500 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========== PROJECT TEAM SECTION ========== --}}
    @if($proposal->team_enabled || $isAdmin)
    <section id="team" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-6xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingTeamEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "Project Team" in client proposal</span>
                    </label>
                    <span class="text-xs text-gray-400">
                        Manage the team library in
                        <a href="/admin/team-members" class="text-brand hover:text-brand-dark font-medium">admin &rsaquo; Teams</a>
                    </span>
                </div>
            @endif

            @if($proposal->team_enabled || $isAdmin)
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand mb-3">The People</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">Project Team</h2>
                    <p class="mt-4 text-base text-gray-500 leading-relaxed">
                        The folks who'll actually be bringing this project to friuition &mdash; not a sales handoff.
                    </p>
                </div>

                @php $attachedTeam = $proposal->teamMembers; @endphp

                @if($attachedTeam->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($attachedTeam as $member)
                            <div class="relative group">
                                @if($isAdmin)
                                    <button wire:click="detachTeamMember({{ $member->id }})"
                                            wire:confirm="Remove this team member from the proposal?"
                                            class="pdf-hide absolute top-3 right-3 z-10 w-7 h-7 rounded-full bg-white/95 border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 inline-flex items-center justify-center transition-colors cursor-pointer shadow"
                                            title="Remove from this proposal">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                                <div class="h-full flex flex-col items-center text-center bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 p-8">
                                    <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden bg-gray-100 ring-4 ring-white shadow-md mb-5">
                                        @if($member->avatar_url)
                                            <img src="{{ $member->avatar_url }}"
                                                 alt="{{ $member->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ $member->name }}</h3>
                                    @if($member->title)
                                        <p class="mt-1 text-sm font-medium text-brand uppercase tracking-wide">{{ $member->title }}</p>
                                    @endif
                                    @if($member->description)
                                        <p class="mt-4 text-sm text-gray-500 leading-relaxed">{{ $member->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if($isAdmin)
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-white p-10 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <p class="text-gray-500 text-sm">No team members attached yet. Pick from the library below &mdash; or <a href="/admin/team-members/create" class="text-brand hover:text-brand-dark font-medium">add a new team member</a>.</p>
                        </div>
                    @else
                        <p class="text-center text-gray-400 italic">Team details available on request.</p>
                    @endif
                @endif

                {{-- Admin team picker --}}
                @if($isAdmin)
                    @php $availableTeam = $this->teamLibrary->whereNotIn('id', $attachedTeam->pluck('id')); @endphp
                    @if($availableTeam->count())
                        <div class="pdf-hide mt-8 p-5 rounded-xl border border-gray-200 bg-white shadow-sm"
                             x-data="{ open: {{ $attachedTeam->count() === 0 ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full text-left cursor-pointer">
                                <span class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Attach a Team Member
                                    <span class="text-xs text-gray-400 font-normal">({{ $availableTeam->count() }} available)</span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($availableTeam as $member)
                                    <button type="button"
                                            wire:click="attachTeamMember({{ $member->id }})"
                                            class="text-left p-3 rounded-lg bg-gray-50 hover:bg-white hover:ring-2 hover:ring-brand transition cursor-pointer flex gap-3 items-center">
                                        @if($member->avatar_url)
                                            <img src="{{ $member->avatar_url }}" alt="" class="w-12 h-12 object-cover rounded-full shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gray-200 shrink-0 flex items-center justify-center text-gray-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $member->name }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $member->title ?: '—' }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </section>
    @endif

    {{-- ========== PAST PERFORMANCE SECTION ========== --}}
    @if($proposal->performance_enabled || $isAdmin)
    <section id="performance" class="relative py-12 sm:py-20 px-4 sm:px-6 scroll-mt-16 overflow-hidden bg-neutral-900">
        {{-- Background image --}}
        <div class="absolute inset-0 bg-cover bg-center opacity-15 grayscale"
             style="background-image: url('{{ asset('images/richmond.png') }}');"></div>
        {{-- Contrast overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-neutral-900/70 via-neutral-900/50 to-neutral-900/80"></div>

        <div class="relative max-w-7xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingPerformanceEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "Past Performance" in client proposal</span>
                    </label>
                    <span class="text-xs text-gray-400">Shows recent portfolio examples from divstrong.com</span>
                </div>
            @endif

            @if($proposal->performance_enabled || $isAdmin)
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand mb-3">Portfolio</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight [text-shadow:0_2px_8px_rgba(0,0,0,0.6)]">Past Performance</h2>
                    <p class="mt-4 text-base text-gray-300 leading-relaxed">
                        A sample of recent engagements &mdash; each delivered end-to-end by the divStrong team.
                    </p>
                </div>

                @php $attachedPortfolio = $proposal->portfolioItems; @endphp

                @if($attachedPortfolio->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($attachedPortfolio as $item)
                            <div class="relative group">
                                @if($isAdmin)
                                    <button wire:click="detachPortfolioItem({{ $item->id }})"
                                            wire:confirm="Remove this portfolio item from the proposal?"
                                            class="pdf-hide absolute top-3 right-3 z-10 w-7 h-7 rounded-full bg-white/95 border border-gray-200 text-gray-500 hover:text-red-600 hover:border-red-300 inline-flex items-center justify-center transition-colors cursor-pointer shadow"
                                            title="Remove from this proposal">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                                <div class="rounded-2xl overflow-hidden bg-white ring-1 ring-white/10 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                                    <div class="aspect-[16/10] overflow-hidden bg-gray-100">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}"
                                                 alt="{{ $item->title }}"
                                                 class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-5">
                                        <h3 class="text-base font-bold text-gray-900 group-hover:text-brand transition-colors mb-2">{{ $item->title }}</h3>
                                        @if($item->description)
                                            <p class="text-sm text-gray-500 leading-relaxed">{{ $item->description }}</p>
                                        @endif
                                        @if($item->url)
                                            <a href="{{ $item->url }}" target="_blank" rel="noopener"
                                               class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand underline underline-offset-2 hover:text-black transition-colors">
                                                Visit URL
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        @endif
                                        @if($item->technologies)
                                            <p class="mt-3 text-[11px] uppercase tracking-wider text-gray-400 font-medium">{{ $item->technologies }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if($isAdmin)
                        <div class="rounded-2xl border-2 border-dashed border-white/20 bg-white/5 p-10 text-center">
                            <svg class="w-10 h-10 mx-auto text-white/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-gray-300 text-sm">No portfolio items attached yet. Pick from the library below &mdash; or <a href="/admin/portfolio-items/create" class="text-brand hover:text-brand-light font-medium">add a new project</a>.</p>
                        </div>
                    @else
                        <p class="text-center text-gray-400 italic">Portfolio examples available on request.</p>
                    @endif
                @endif

                {{-- Admin portfolio picker --}}
                @if($isAdmin)
                    @php $availablePortfolio = $this->portfolioLibrary->whereNotIn('id', $attachedPortfolio->pluck('id')); @endphp
                    @if($availablePortfolio->count())
                        <div class="pdf-hide mt-8 p-5 rounded-xl border border-white/10 bg-white/5 backdrop-blur-sm"
                             x-data="{ open: {{ $attachedPortfolio->count() === 0 ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full text-left cursor-pointer">
                                <span class="text-sm font-semibold text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Attach a Portfolio Item
                                    <span class="text-xs text-gray-400 font-normal">({{ $availablePortfolio->count() }} available)</span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($availablePortfolio as $item)
                                    <button type="button"
                                            wire:click="attachPortfolioItem({{ $item->id }})"
                                            class="text-left p-3 rounded-lg bg-white hover:ring-2 hover:ring-brand transition cursor-pointer flex gap-3 items-center">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}" alt="" class="w-12 h-12 object-cover rounded shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded bg-gray-100 shrink-0"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->title }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $item->url ?: ($item->technologies ?: '—') }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </section>
    @endif

    {{-- ========== REFERENCES SECTION ========== --}}
    @if($proposal->references_enabled || $isAdmin)
    <section id="references" class="py-12 sm:py-20 px-4 sm:px-6 scroll-mt-16 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingReferencesEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "References" in client proposal</span>
                    </label>
                    <span class="text-xs text-gray-400">
                        Manage the reference library in
                        <a href="/admin/project-references" class="text-brand hover:text-brand-dark font-medium">admin &rsaquo; References</a>
                    </span>
                </div>
            @endif

            @if($proposal->references_enabled || $isAdmin)
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand mb-3">References</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">Client References</h2>
                    <p class="mt-4 text-base text-gray-500 leading-relaxed">
                        Representative clients who can speak to the quality of our engagements and delivery.
                    </p>
                </div>

                @php $attachedRefs = $proposal->projectReferences; @endphp

                @if($attachedRefs->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($attachedRefs as $ref)
                            <div class="relative rounded-2xl bg-white border border-gray-200 p-6 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                                @if($isAdmin)
                                    <button wire:click="detachReference({{ $ref->id }})"
                                            wire:confirm="Remove this reference from the proposal?"
                                            class="pdf-hide absolute top-3 right-3 w-7 h-7 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-300 inline-flex items-center justify-center transition-colors cursor-pointer"
                                            title="Remove from this proposal">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif

                                <div class="flex items-start gap-3 mb-3">
                                    <div class="shrink-0 w-11 h-11 rounded-full bg-brand/10 text-brand flex items-center justify-center font-bold text-sm">
                                        {{ collect(explode(' ', $ref->name))->filter()->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('') ?: '•' }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-gray-900 leading-tight">{{ $ref->name }}</p>
                                        @if($ref->title || $ref->company)
                                            <p class="text-sm text-gray-500 leading-tight mt-0.5">
                                                {{ $ref->title }}@if($ref->title && $ref->company) <span class="text-gray-300">&middot;</span> @endif{{ $ref->company }}
                                            </p>
                                        @endif
                                        @if($ref->email || $ref->phone)
                                            <div class="flex flex-col items-start gap-1 text-xs text-gray-500 mt-2">
                                                @if($ref->email)
                                                    <a href="mailto:{{ $ref->email }}" class="inline-flex items-center gap-1 hover:text-brand transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        {{ $ref->email }}
                                                    </a>
                                                @endif
                                                @if($ref->phone)
                                                    <a href="tel:{{ $ref->phone }}" class="inline-flex items-center gap-1 hover:text-brand transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.5 4.49a1 1 0 01-.5 1.21l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.21-.5l4.49 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.82 21 3 14.18 3 6V5z"/></svg>
                                                        {{ $ref->phone }}
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($ref->project_description)
                                    <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ $ref->project_description }}</p>
                                @endif

                                @if($ref->year_completed)
                                    <div class="pt-3 border-t border-gray-100">
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-gray-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Completed {{ $ref->year_completed }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    @if($isAdmin)
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-white p-10 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-3-3h-2M9 20H4v-2a3 3 0 013-3h2m3-4a4 4 0 110-8 4 4 0 010 8zm6 8a3 3 0 00-6 0"/></svg>
                            <p class="text-gray-500 text-sm">No references attached yet. Pick from the library below &mdash; or <a href="/admin/project-references/create" class="text-brand hover:text-brand-dark font-medium">add a new reference</a>.</p>
                        </div>
                    @else
                        <p class="text-center text-gray-400 italic">References available on request.</p>
                    @endif
                @endif

                {{-- Admin reference picker --}}
                @if($isAdmin)
                    @php $availableRefs = $this->referenceLibrary->whereNotIn('id', $attachedRefs->pluck('id')); @endphp
                    @if($availableRefs->count())
                        <div class="pdf-hide mt-8 p-5 rounded-xl border border-gray-200 bg-white shadow-sm"
                             x-data="{ open: {{ $attachedRefs->count() === 0 ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full text-left cursor-pointer">
                                <span class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Attach a Reference
                                    <span class="text-xs text-gray-400 font-normal">({{ $availableRefs->count() }} available)</span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($availableRefs as $ref)
                                    <button type="button"
                                            wire:click="attachReference({{ $ref->id }})"
                                            class="text-left p-3 rounded-lg bg-gray-50 hover:bg-white hover:ring-2 hover:ring-brand transition cursor-pointer">
                                        <p class="text-sm font-semibold text-gray-900">{{ $ref->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $ref->title }}@if($ref->title && $ref->company) &middot; @endif{{ $ref->company }}
                                            @if($ref->year_completed) <span class="text-gray-400">&middot; {{ $ref->year_completed }}</span>@endif
                                        </p>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </section>
    @endif

    {{-- ========== CHANGE REQUESTS SECTION ========== --}}
    @if($proposal->changes_enabled || $isAdmin)
    <section id="changes" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingChangesEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Show "Change Requests" in client proposal</span>
                    </label>
                    @if(!$proposal->changes_enabled)
                        <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            Hidden from client view
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Change Requests</h2>
            </div>

            <div class="sm:pl-8">
            {{-- Admin: Editable content --}}
            @if($isAdmin)
                <div class="mb-8" wire:ignore>
                    <div contenteditable="true"
                         class="prose prose-gray max-w-none text-gray-600 leading-relaxed focus:outline-none border-b-2 border-dashed border-transparent hover:border-gray-200 focus:border-brand transition-colors px-1 py-2 min-h-[80px]"
                         x-data
                         x-on:blur="$wire.set('editingChangeRequestContent', $el.innerText); $wire.saveChangeRequestContent()"
                         x-init="$el.innerText = @js($editingChangeRequestContent ?: 'Any changes to the scope of work outlined in this proposal will be documented as a Change Request. Change requests may affect the project timeline and budget. All change requests must be approved in writing before work begins.')"
                    ></div>
                    <p class="text-xs text-gray-300 mt-2">Click to edit change request content</p>
                </div>
            @else
                <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed mb-10 whitespace-pre-line">{{ $proposal->change_request_content ?: 'Any changes to the scope of work outlined in this proposal will be documented as a Change Request. Change requests may affect the project timeline and budget. All change requests must be approved in writing before work begins.' }}</div>
            @endif
            </div>

        </div>
    </section>
    @endif

    {{-- ========== VPAT / ACCESSIBILITY SECTION ========== --}}
    @if($proposal->vpat_enabled || $isAdmin)
    <section id="vpat" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingVpatEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Include "Accessibility / VPAT" in client proposal</span>
                    </label>
                    <span class="text-xs text-gray-400">Include when responding to government / accessibility-mandated RFPs</span>
                </div>
            @endif

            @if($proposal->vpat_enabled)
                <div class="flex items-center gap-3 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand/10 text-brand text-xs font-semibold uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Compliance Statement
                    </span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Accessibility &amp; VPAT</h2>
                <p class="text-gray-500 mb-10 leading-relaxed">
                    Voluntary Product Accessibility Template statement describing how our products and services conform to recognized accessibility standards.
                </p>

                <div class="prose-light max-w-none text-base leading-relaxed space-y-5">
                    <p>
                        divStrong is committed to designing and delivering digital products that are usable by people of all abilities. Our engineering practice aligns with the internationally recognized <strong>Web Content Accessibility Guidelines (WCAG) 2.1 Level AA</strong>, <strong>Section 508 of the Rehabilitation Act</strong>, and the accessibility standards set forth in the State of Colorado Office of Information Technology Rules <strong>8 CCR 1501-11</strong>.
                    </p>
                    <p>
                        Because each of our engagements results in a custom software build rather than a shrink-wrapped product, a final, engagement-specific VPAT (ITI / Revised Section 508 format) is produced at project completion and updated as features evolve. This proposal captures the standards, practices, and controls we build into every project from day one.
                    </p>
                </div>

                {{-- Standards we align to --}}
                <div class="mt-10">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Standards We Align To</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand/10 text-brand mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">WCAG 2.1 Level AA</p>
                            <p class="mt-1 text-xs text-gray-500 leading-relaxed">W3C Web Content Accessibility Guidelines &mdash; the baseline we design and test against.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand/10 text-brand mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">Section 508</p>
                            <p class="mt-1 text-xs text-gray-500 leading-relaxed">Revised Section 508 (36 CFR 1194) of the U.S. Rehabilitation Act, aligned with WCAG 2.0 AA.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-brand/10 text-brand mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a3 3 0 100-6 3 3 0 000 6z"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">Colorado 8 CCR 1501-11</p>
                            <p class="mt-1 text-xs text-gray-500 leading-relaxed">Colorado OIT accessibility rules for technology provided to, or on behalf of, state entities.</p>
                        </div>
                    </div>
                </div>

                {{-- Our practices --}}
                <div class="mt-10">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Practices Built Into Every Engagement</h3>
                    <ul class="space-y-3">
                        @foreach([
                            ['Semantic HTML &amp; ARIA', 'Proper landmark regions, heading structure, form labels, and ARIA attributes so assistive technology can interpret every interface.'],
                            ['Keyboard &amp; Screen-Reader Support', 'All interactive elements are reachable and operable via keyboard, and tested against NVDA, JAWS, and VoiceOver.'],
                            ['Color &amp; Contrast', 'Minimum 4.5:1 contrast for body text, 3:1 for large text and UI components, with non-color status indicators.'],
                            ['Responsive &amp; Zoomable', 'Layouts reflow at 200% zoom, support text resizing, and adapt to desktop, tablet, and mobile viewports.'],
                            ['Media &amp; Imagery', 'Alt text for meaningful images, captions and transcripts for video, no auto-playing audio.'],
                            ['Automated &amp; Manual Testing', 'axe-core, Lighthouse, and Pa11y run in CI, complemented by manual screen-reader and keyboard walkthroughs before release.'],
                        ] as [$title, $body])
                            <li class="flex gap-3">
                                <svg class="shrink-0 w-5 h-5 text-brand mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <div>
                                    <p class="font-semibold text-gray-900">{!! $title !!}</p>
                                    <p class="text-sm text-gray-600 leading-relaxed">{!! $body !!}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Delivery commitment --}}
                <div class="mt-10 rounded-xl border-l-4 border-brand bg-brand/5 p-5 sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Project-Specific VPAT on Delivery</h3>
                    <p class="text-sm text-gray-700 leading-relaxed">
                        At the conclusion of development divStrong will produce a completed <strong>VPAT 2.5 (Revised Section 508 edition)</strong> documenting conformance by success criterion (Supports / Partially Supports / Does Not Support / Not Applicable), along with any remediation commitments. We will work collaboratively with your team to resolve any standards gaps identified during the engagement.
                    </p>
                </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ========== TERMS & CONDITIONS SECTION ========== --}}
    @if($proposal->terms_enabled || $isAdmin)
    <section id="terms" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            @if($isAdmin)
                <div class="pdf-hide mb-8 p-3 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between gap-4 flex-wrap">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model.live="editingTermsEnabled" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-brand transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Show "Terms &amp; Conditions" in client proposal</span>
                    </label>
                    @if(!$proposal->terms_enabled)
                        <span class="text-xs text-amber-600 font-medium inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            Hidden from client view
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Terms & Conditions</h2>

                @if($isAdmin)
                <div class="ml-auto flex items-center gap-4">
                    {{-- Import terms from another proposal --}}
                    <div x-data="{
                             importOpen: false,
                             importUuid: '',
                             importBusy: false,
                             importError: '',
                             importSuccess: '',
                             async runImport() {
                                 this.importError = '';
                                 this.importSuccess = '';
                                 if (! this.importUuid.trim()) { this.importError = 'Enter a proposal identifier.'; return; }
                                 this.importBusy = true;
                                 try {
                                     const result = await $wire.importTermsFromProposal(this.importUuid.trim());
                                     if (result?.ok) {
                                         this.importSuccess = result.message;
                                         this.importUuid = '';
                                         setTimeout(() => { this.importOpen = false; this.importSuccess = ''; }, 1400);
                                     } else {
                                         this.importError = result?.message || 'Import failed.';
                                     }
                                 } catch (e) {
                                     this.importError = 'Something went wrong. Please try again.';
                                 } finally {
                                     this.importBusy = false;
                                 }
                             }
                         }">
                        {{-- Import link --}}
                        <button @click="importOpen = true"
                                class="text-sm font-medium text-brand hover:text-gray-900 transition-colors cursor-pointer">
                            Import
                        </button>

                        {{-- Import modal --}}
                        <div x-show="importOpen" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                             @keydown.escape.window="importOpen = false; importError = ''; importSuccess = '';">
                            <div @click.outside="importOpen = false; importError = ''; importSuccess = '';"
                                 class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
                                <div class="p-6 border-b border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-900">Import Terms from Another Proposal</h3>
                                    <p class="text-sm text-gray-500 mt-1">Enter the source proposal's 6-character code (the part after <code class="text-gray-700">/proposal/</code> in its URL). All of its terms will be appended below the existing ones.</p>
                                </div>
                                <div class="p-6 space-y-3">
                                    <label class="block">
                                        <span class="text-xs font-medium text-gray-700">Proposal code</span>
                                        <input type="text" x-model="importUuid"
                                               @keydown.enter.prevent="if(!importBusy) runImport()"
                                               @input="importUuid = importUuid.toUpperCase()"
                                               maxlength="6"
                                               placeholder="e.g. IGAK96"
                                               class="mt-1 block w-full text-sm font-mono tracking-widest bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none uppercase"
                                               x-bind:disabled="importBusy">
                                    </label>
                                    <p x-show="importError" x-text="importError" x-cloak
                                       class="text-sm text-red-600"></p>
                                    <p x-show="importSuccess" x-text="importSuccess" x-cloak
                                       class="text-sm text-emerald-600"></p>
                                </div>
                                <div class="p-6 border-t border-gray-100 flex items-center justify-end gap-3">
                                    <button @click="importOpen = false; importError = ''; importSuccess = '';"
                                            x-bind:disabled="importBusy"
                                            class="text-sm text-gray-500 hover:text-gray-700 cursor-pointer disabled:opacity-50">Cancel</button>
                                    <button @click="runImport()"
                                            x-bind:disabled="importBusy"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors disabled:opacity-60 disabled:cursor-wait cursor-pointer">
                                        <svg x-show="importBusy" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                                            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span x-text="importBusy ? 'Importing…' : 'Import Terms'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button wire:click="addTerm"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Term
                    </button>
                </div>
                @endif
            </div>

            <div class="sm:pl-8">
            <div x-data="{
                    editingTermId: null,
                    editTermContent: '',
                    deleteTermId: null,
                    deleteTermContent: '',
                    termDragOver: null,
                    startTermEdit(id, content) {
                        this.editingTermId = id;
                        this.editTermContent = content;
                    },
                    saveTermEdit() {
                        if (this.editingTermId && this.editTermContent.trim()) {
                            $wire.updateTerm(this.editingTermId, this.editTermContent);
                        }
                        this.editingTermId = null;
                    },
                    confirmTermDelete(id, content) {
                        this.deleteTermId = id;
                        this.deleteTermContent = content.substring(0, 60) + (content.length > 60 ? '...' : '');
                    },
                    executeTermDelete() {
                        if (this.deleteTermId) {
                            $wire.deleteTerm(this.deleteTermId);
                        }
                        this.deleteTermId = null;
                    },
                    handleTermDrop(e, targetId) {
                        this.termDragOver = null;
                        const sourceId = parseInt(e.dataTransfer.getData('term'));
                        if (!sourceId || sourceId === targetId) return;
                        const allItems = document.querySelectorAll('[data-term-id]');
                        const ordered = Array.from(allItems).map(el => parseInt(el.dataset.termId));
                        const fromIdx = ordered.indexOf(sourceId);
                        const toIdx = ordered.indexOf(targetId);
                        ordered.splice(fromIdx, 1);
                        ordered.splice(toIdx, 0, sourceId);
                        $wire.reorderTerms(ordered);
                    }
                 }">

                {{-- Delete confirmation modal --}}
                <div x-show="deleteTermId" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                     @keydown.escape.window="deleteTermId = null">
                    <div @click.outside="deleteTermId = null"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Remove Term</h3>
                        <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove this term? This cannot be undone.</p>
                        <div class="flex items-center gap-3">
                            <button @click="deleteTermId = null"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button @click="executeTermDelete()"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <ol class="space-y-3 list-none">
                    @foreach($proposal->terms as $index => $term)
                    <li class="group/term flex items-start gap-4 py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors"
                        data-term-id="{{ $term->id }}"
                        @if($isAdmin)
                        draggable="true"
                        @dragstart="$event.dataTransfer.setData('term', '{{ $term->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                        @dragover.prevent="termDragOver = {{ $term->id }}"
                        @dragleave="termDragOver = null"
                        @drop.prevent="handleTermDrop($event, {{ $term->id }})"
                        :class="{ 'border border-brand border-dashed': termDragOver === {{ $term->id }} }"
                        @endif>

                        {{-- Number --}}
                        <span class="text-brand font-bold text-lg shrink-0 w-8 text-right">{{ $index + 1 }}.</span>

                        {{-- View mode --}}
                        <div x-show="editingTermId !== {{ $term->id }}" class="flex-1 min-w-0">
                            <span class="text-gray-700 leading-relaxed {{ $isAdmin ? 'cursor-pointer' : '' }}"
                                  @if($isAdmin) @click="startTermEdit({{ $term->id }}, @js($term->content))" @endif>
                                {{ $term->content }}
                            </span>
                        </div>

                        {{-- Edit mode --}}
                        <div x-show="editingTermId === {{ $term->id }}" x-cloak class="flex-1 flex items-start gap-3">
                            <textarea x-model="editTermContent"
                                      @keydown.escape="editingTermId = null"
                                      rows="3"
                                      class="flex-1 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none resize-none"
                                      placeholder="Term content"></textarea>
                            <button @click="saveTermEdit()"
                                    class="px-3 py-1.5 bg-brand text-white text-xs font-medium rounded-lg hover:bg-gray-900 transition-colors cursor-pointer shrink-0 mt-1">Save</button>
                        </div>

                        {{-- Actions --}}
                        @if($isAdmin)
                        <div x-show="editingTermId !== {{ $term->id }}" class="flex items-center gap-1 opacity-0 group-hover/term:opacity-100 transition-opacity shrink-0">
                            <span class="cursor-grab active:cursor-grabbing p-1.5 text-gray-300 hover:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </span>
                            <button wire:click="duplicateTerm({{ $term->id }})"
                                    title="Duplicate"
                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button @click="startTermEdit({{ $term->id }}, @js($term->content))"
                                    title="Edit"
                                    class="p-1.5 text-gray-300 hover:text-brand transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="confirmTermDelete({{ $term->id }}, @js($term->content))"
                                    title="Delete"
                                    class="p-1.5 text-gray-300 hover:text-red-500 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ol>

                @if($proposal->terms->isEmpty() && !$isAdmin)
                    <p class="text-gray-400 text-center py-8">No terms have been added yet.</p>
                @endif
            </div>

            {{-- Sign & Agree --}}
            <div class="mt-10">
                @if($proposal->tc_signed_at)
                    {{-- Signed state --}}
                    <div class="bg-white border border-emerald-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-50 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Signed by {{ $proposal->tc_signature_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $proposal->tc_signed_at->format('F j, Y \a\t g:i A') }}</p>
                                </div>
                            </div>
                            @if($proposal->tc_signature_data)
                                <div class="bg-gray-50 border border-gray-100 rounded-lg p-3">
                                    <img src="{{ $proposal->tc_signature_data }}" alt="Signature" class="max-h-16">
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif(!$isAdmin && !$converted && !$declined && !$expired)
                    {{-- Client signature form --}}
                    <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Sign & Agree</h3>
                        <p class="text-sm text-gray-500 mb-6">By signing below, you acknowledge the change request policy and agree to the terms and conditions outlined above.</p>

                        <div class="mb-4">
                            <label class="block text-sm text-gray-600 mb-2 font-medium">Full Name</label>
                            <input type="text" wire:model="tcSignatureName"
                                   placeholder="Enter your full name"
                                   class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-brand focus:ring-1 focus:ring-brand outline-none transition">
                            @error('tcSignatureName')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4" x-data="tcSignaturePad()" x-init="init()" wire:ignore>
                            <label class="block text-sm text-gray-600 mb-2 font-medium">Signature</label>
                            <div class="bg-gray-50 border border-gray-300 rounded-lg overflow-hidden relative" style="height: 150px;">
                                <canvas x-ref="canvas" class="w-full h-full cursor-crosshair"></canvas>
                                <div x-show="!hasSignature" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="text-gray-300 text-sm">Sign here</span>
                                </div>
                            </div>
                            <div class="flex justify-end mt-2">
                                <button type="button" x-on:click="clearSignature()"
                                        class="text-sm text-gray-400 hover:text-brand transition cursor-pointer">Clear</button>
                            </div>
                            <input type="hidden" wire:model="tcSignatureData">
                            @error('tcSignatureData')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button wire:click="signTerms"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="w-full px-6 py-3 bg-brand hover:bg-gray-900 text-white font-semibold rounded-lg transition-colors cursor-pointer shadow-lg shadow-brand/20">
                            <span wire:loading.remove wire:target="signTerms">Sign & Agree</span>
                            <span wire:loading wire:target="signTerms">Processing...</span>
                        </button>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </section>

    {{-- ========== APPROVAL SECTION ========== --}}
    <section id="approval" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-2xl mx-auto">
            @if($converted)
                {{-- CONVERTED STATE --}}
                <div class="text-center">
                    <div class="w-20 h-20 bg-emerald-50 border border-emerald-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-emerald-600 mb-4">Proposal Approved</h2>
                    <p class="text-gray-500">
                        This proposal has been fully approved
                        @if($proposal->accepted_at)
                            on {{ $proposal->accepted_at->format('F j, Y \a\t g:i A') }}
                        @endif
                    </p>
                    @if($isAdmin)
                        <div x-data="{ confirmReset: false }" class="mt-8">
                            <button x-show="!confirmReset" @click="confirmReset = true"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset to Draft
                            </button>
                            <div x-show="confirmReset" x-cloak class="inline-flex items-center gap-3">
                                <span class="text-sm text-gray-500">This will clear all signatures. Are you sure?</span>
                                <button wire:click="resetProposalStatus" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Yes, Reset</button>
                                <button @click="confirmReset = false" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 cursor-pointer">Cancel</button>
                            </div>
                        </div>
                    @endif

                    {{-- PAYMENT MILESTONES --}}
                    @if($proposal->milestones->count() && !$isAdmin)
                    <div class="mt-12 pt-12 border-t border-gray-200 text-left max-w-xl mx-auto">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Payment Schedule</h3>
                        <p class="text-sm text-gray-500 mb-6 text-center">Complete milestone payments to get your project started.</p>

                        <div class="space-y-3">
                            @foreach($proposal->milestones as $milestone)
                            @php
                                $msAmount = round(($milestone->percentage / 100) * $proposal->total, 2);
                            @endphp
                            <div class="border rounded-xl p-4 transition-all
                                        {{ $milestone->payment_status === 'paid' ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200' }}">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-semibold text-gray-900">{{ $milestone->title }}</span>
                                        <span class="text-gray-500 text-sm ml-2">
                                            {{ number_format($milestone->percentage, 0) }}% &mdash; ${{ number_format($msAmount, 2) }}
                                        </span>
                                    </div>

                                    @if($milestone->payment_status === 'paid')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Paid
                                        </span>
                                    @else
                                        <button wire:click="selectMilestoneForPayment({{ $milestone->id }})"
                                                class="px-4 py-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg transition-colors cursor-pointer
                                                       {{ $payingMilestoneId === $milestone->id ? 'ring-2 ring-brand/30' : '' }}">
                                            Pay ${{ number_format($msAmount, 2) }}
                                        </button>
                                    @endif
                                </div>

                                {{-- Payment fields for selected milestone --}}
                                @if($payingMilestoneId === $milestone->id)
                                <div class="mt-4 pt-4 border-t border-gray-100"
                                     x-data="paypalCheckout({{ $milestone->id }}, '{{ $proposal->uuid }}')"
                                     x-init="initPayPal()"
                                     wire:ignore.self>

                                    {{-- Card Fields (only shown when eligible) --}}
                                    <div x-show="cardFieldsEligible" x-cloak class="mb-4">
                                        <p class="text-sm font-medium text-gray-700 mb-3">Pay with Credit or Debit Card</p>
                                        <div class="space-y-3">
                                            <div id="card-number-field-{{ $milestone->id }}" class="h-11 bg-white border border-gray-200 rounded-lg overflow-hidden"></div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div id="card-expiry-field-{{ $milestone->id }}" class="h-11 bg-white border border-gray-200 rounded-lg overflow-hidden"></div>
                                                <div id="card-cvv-field-{{ $milestone->id }}" class="h-11 bg-white border border-gray-200 rounded-lg overflow-hidden"></div>
                                            </div>
                                        </div>
                                        <button @click="submitCard()"
                                                :disabled="processing"
                                                class="w-full mt-3 px-4 py-3 bg-gray-900 hover:bg-black text-white font-semibold rounded-lg transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="!processing">Pay ${{ number_format($msAmount, 2) }}</span>
                                            <span x-show="processing" x-cloak class="inline-flex items-center gap-2">
                                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                </svg>
                                                Processing...
                                            </span>
                                        </button>
                                    </div>

                                    {{-- PayPal Button --}}
                                    <div class="mt-4">
                                        <p x-show="!cardFieldsEligible" class="text-sm font-medium text-gray-700 mb-3">Pay with PayPal</p>
                                        <div id="paypal-button-{{ $milestone->id }}"></div>
                                    </div>

                                    {{-- Error display --}}
                                    <div x-show="error" x-cloak class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600" x-text="error"></div>

                                    {{-- Cancel --}}
                                    <button wire:click="cancelPayment"
                                            class="w-full mt-3 text-sm text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                                        Cancel
                                    </button>
                                </div>
                                @endif

                                {{-- Paid timestamp --}}
                                @if($milestone->payment_status === 'paid' && $milestone->paid_at)
                                    <p class="text-xs text-emerald-600 mt-2">Paid on {{ $milestone->paid_at->format('M j, Y \a\t g:i A') }}</p>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        {{-- Success message --}}
                        @if($paymentSuccess)
                        <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                            <p class="text-emerald-700 font-semibold">Payment successful!</p>
                            <p class="text-emerald-600 text-sm mt-1">Confirmation: {{ $lastCaptureId }}</p>
                        </div>
                        @endif

                        {{-- Error message --}}
                        @if($paymentError)
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-center">
                            <p class="text-red-600 text-sm">{{ $paymentError }}</p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

            @elseif($accepted)
                {{-- ACCEPTED STATE (legacy) --}}
                <div class="text-center">
                    <div class="w-20 h-20 bg-emerald-50 border border-emerald-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-emerald-600 mb-4">Proposal Accepted</h2>
                    <p class="text-gray-500">
                        Accepted by <span class="text-gray-900 font-medium">{{ $proposal->signature_name }}</span>
                        on {{ $proposal->accepted_at->format('F j, Y \a\t g:i A') }}
                    </p>
                    @if($proposal->signature_data)
                        <div class="mt-8 inline-block bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <img src="{{ $proposal->signature_data }}" alt="Signature" class="max-h-24">
                        </div>
                    @endif
                    @if($isAdmin)
                        <div x-data="{ confirmReset: false }" class="mt-8">
                            <button x-show="!confirmReset" @click="confirmReset = true"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset to Draft
                            </button>
                            <div x-show="confirmReset" x-cloak class="inline-flex items-center gap-3">
                                <span class="text-sm text-gray-500">This will clear all signatures. Are you sure?</span>
                                <button wire:click="resetProposalStatus" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Yes, Reset</button>
                                <button @click="confirmReset = false" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 cursor-pointer">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>

            @elseif($declined)
                {{-- DECLINED STATE --}}
                <div class="text-center">
                    <div class="w-20 h-20 bg-red-50 border border-red-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-red-500 mb-4">Proposal Declined</h2>
                    <p class="text-gray-500">This proposal has been declined.</p>
                    @if($isAdmin)
                        <div x-data="{ confirmReset: false }" class="mt-8">
                            <button x-show="!confirmReset" @click="confirmReset = true"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset to Draft
                            </button>
                            <div x-show="confirmReset" x-cloak class="inline-flex items-center gap-3">
                                <span class="text-sm text-gray-500">Reset proposal back to draft?</span>
                                <button wire:click="resetProposalStatus" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-gray-900 transition-colors cursor-pointer">Yes, Reset</button>
                                <button @click="confirmReset = false" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 cursor-pointer">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>

            @elseif($expired)
                {{-- EXPIRED STATE --}}
                <div class="text-center">
                    <div class="w-20 h-20 bg-gray-100 border border-gray-300 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-400 mb-4">Proposal Expired</h2>
                    <p class="text-gray-500">This proposal expired on {{ $proposal->valid_until->format('F j, Y') }}. Please contact us for a renewal.</p>
                </div>

            @elseif(!$isAdmin && $proposal->tc_signed_at)
                {{-- APPROVE PROPOSAL - Signature done, accept or decline --}}
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="w-10 h-[2px] bg-brand"></div>
                        <h2 class="text-3xl font-bold text-gray-900">Approve Proposal</h2>
                        <div class="w-10 h-[2px] bg-brand"></div>
                    </div>
                    <p class="text-gray-500">You have signed and agreed to the terms. Click below to approve this proposal.</p>
                </div>

                <div x-data="{ showDeclineModal: false }" class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button wire:click="approveProposal"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            class="inline-flex items-center justify-center gap-3 px-10 py-4 bg-brand hover:bg-gray-900 text-white
                                   font-semibold rounded-lg transition-all duration-200 text-lg
                                   shadow-lg shadow-brand/20 hover:shadow-brand/40 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span wire:loading.remove wire:target="approveProposal">Approve Proposal</span>
                        <span wire:loading wire:target="approveProposal">Processing...</span>
                    </button>
                    <button @click="showDeclineModal = true"
                            class="inline-flex items-center justify-center gap-2 px-10 py-4 bg-transparent border border-gray-300 hover:border-red-500
                                   hover:text-red-500 text-gray-500 rounded-lg transition-all duration-200 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Decline
                    </button>

                    {{-- Decline reconsider modal --}}
                    <div x-show="showDeclineModal" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div @click.outside="showDeclineModal = false"
                             x-show="showDeclineModal"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                            <div class="p-8 text-center">
                                <div class="w-16 h-16 bg-amber-50 border border-amber-200 rounded-full flex items-center justify-center mx-auto mb-5">
                                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Before you go...</h3>
                                <p class="text-gray-500 leading-relaxed mb-2">
                                    We'd love the opportunity to address any concerns you may have. Our team is happy to adjust the scope, timeline, or budget to better fit your needs.
                                </p>
                                <p class="text-gray-400 text-sm mb-8">
                                    Would you like to reconsider, or would you still like to decline?
                                </p>
                                <div class="flex flex-col gap-3">
                                    <button @click="showDeclineModal = false"
                                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-brand hover:bg-brand-dark text-white font-semibold rounded-xl transition-colors cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                        Go Back & Reconsider
                                    </button>
                                    <button @click="showDeclineModal = false; $wire.declineProposal()"
                                            class="w-full px-6 py-3 text-sm text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
                                        No thanks, decline this proposal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ========== QR CODE ========== --}}
    @php
        $proposalQrUrl = $proposal->public_url;
        $proposalQrDataUri = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_M,
            'svgViewBoxSize' => 200,
            'addQuietzone'   => true,
            'cssClass'       => 'w-full h-full',
            'imageBase64'    => false,
        ])))->render($proposalQrUrl);
    @endphp
    <section class="py-12 px-4 sm:px-6 bg-white">
        <div class="max-w-4xl mx-auto flex flex-col items-center text-center">
            <div class="w-40 h-40 p-3 bg-white rounded-xl border border-gray-200 shadow-sm">
                {!! $proposalQrDataUri !!}
            </div>
            <p class="mt-4 text-sm text-gray-500">Scan to view this proposal online</p>
        </div>
    </section>

    {{-- ========== FOOTER ========== --}}
    <footer class="py-8 px-6 bg-white border-t border-gray-200">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener"><img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-6"></a>
            <p class="text-gray-400 text-xs">&copy; 2009-{{ date('Y') }} divStrong</p>
        </div>
    </footer>

    {{-- ========== CHAT SIDEBAR ========== --}}
    @if($isAdmin)
        <div x-data="{ chatOpen: false }" x-cloak>
            {{-- Floating Chat Button --}}
            <button @click="chatOpen = !chatOpen"
                    class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition-all duration-200 cursor-pointer"
                    :class="chatOpen ? 'bg-gray-800 hover:bg-gray-700' : 'bg-brand hover:bg-gray-900'"
                    style="border: none;">
                <svg x-show="!chatOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <svg x-show="chatOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Slide-out Panel --}}
            <div x-show="chatOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="fixed top-0 right-0 z-30 h-full w-full sm:w-[420px] bg-white shadow-2xl border-l border-gray-200 flex flex-col"
                 @keydown.escape.window="chatOpen = false">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-900">Notes</h3>
                    </div>
                    <button @click="chatOpen = false" class="p-1 text-gray-400 hover:text-gray-600 transition cursor-pointer" style="background: none; border: none;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Chat Component --}}
                <div class="flex-1 overflow-hidden p-4">
                    @livewire('proposal-notes', ['proposalId' => $proposal->id], key('proposal-chat-sidebar'))
                </div>
            </div>

            {{-- Backdrop on mobile --}}
            <div x-show="chatOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-20 bg-black/30 sm:hidden"
                 @click="chatOpen = false">
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('paypalCheckout', (milestoneId, proposalUuid) => ({
        processing: false,
        error: '',
        cardFields: null,
        cardFieldsEligible: false,

        loadPayPalSdk() {
            return new Promise((resolve, reject) => {
                if (typeof paypal !== 'undefined') { resolve(); return; }
                if (document.getElementById('paypal-sdk')) {
                    // SDK script already added, wait for it
                    const check = setInterval(() => {
                        if (typeof paypal !== 'undefined') { clearInterval(check); resolve(); }
                    }, 200);
                    setTimeout(() => { clearInterval(check); reject(new Error('PayPal SDK timed out')); }, 10000);
                    return;
                }
                const script = document.createElement('script');
                script.id = 'paypal-sdk';
                script.src = @js(config('paypal.sdk_url') . '?client-id=' . config('paypal.client_id') . '&components=buttons,card-fields&currency=USD&intent=capture&disable-funding=paylater');
                script.onload = resolve;
                script.onerror = () => reject(new Error('PayPal SDK failed to load'));
                document.head.appendChild(script);
            });
        },

        async initPayPal() {
            try {
                await this.loadPayPalSdk();
            } catch (e) {
                this.error = 'Payment system failed to load. Please check your connection and refresh the page.';
                console.error('PayPal SDK error:', e.message);
                return;
            }

            const self = this;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const createOrderFn = async () => {
                const response = await fetch(`/proposal/${proposalUuid}/payment/create-order`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ milestone_id: milestoneId }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Failed to create order');
                return data.id;
            };

            const onApproveFn = async (data) => {
                self.processing = true;
                self.error = '';
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    const response = await fetch(`/proposal/${proposalUuid}/payment/${data.orderID}/capture`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        signal: controller.signal,
                    });
                    clearTimeout(timeoutId);
                    const result = await response.json();
                    if (result.status === 'completed') {
                        self.$wire.markMilestonePaid(milestoneId, result.capture_id);
                    } else {
                        self.error = result.error || 'Payment was not completed. Please try again.';
                    }
                } catch (err) {
                    if (err.name === 'AbortError') {
                        self.error = 'Payment request timed out. Please check your payment status before retrying.';
                    } else {
                        self.error = 'Payment failed. Please try again.';
                    }
                } finally {
                    self.processing = false;
                }
            };

            const onErrorFn = (err) => {
                console.error('PayPal error:', err);
                self.error = 'A payment error occurred. Please try again.';
                self.processing = false;
            };

            // Initialize Card Fields
            try {
                this.cardFields = paypal.CardFields({
                    style: {
                        input: {
                            'font-size': '15px',
                            'font-family': 'Outfit, system-ui, sans-serif',
                            'font-weight': '400',
                            'color': '#111827',
                            'background-color': '#ffffff',
                            'padding': '0 14px',
                        },
                        'input::placeholder': {
                            'color': '#9ca3af',
                        },
                        'input:focus': {
                            'color': '#111827',
                        },
                        '.invalid': {
                            'color': '#dc2626',
                        },
                    },
                    createOrder: createOrderFn,
                    onApprove: onApproveFn,
                    onError: onErrorFn,
                });

                const eligible = this.cardFields.isEligible();
                console.log('PayPal CardFields isEligible:', eligible);
                console.log('PayPal SDK components available:', Object.keys(paypal));

                if (eligible) {
                    this.cardFieldsEligible = true;
                    this.cardFields.NumberField({ placeholder: 'Card number' }).render(`#card-number-field-${milestoneId}`);
                    this.cardFields.ExpiryField({ placeholder: 'MM / YY' }).render(`#card-expiry-field-${milestoneId}`);
                    this.cardFields.CVVField({ placeholder: 'CVV' }).render(`#card-cvv-field-${milestoneId}`);
                } else {
                    console.warn('PayPal Advanced Card Fields not eligible. This requires "Advanced Credit and Debit Card Payments" to be enabled in your PayPal account.');
                }
            } catch (e) {
                console.warn('Card fields not available:', e);
            }

            // Initialize PayPal Buttons
            paypal.Buttons({
                style: { layout: 'horizontal', color: 'gold', shape: 'rect', label: 'pay', height: 45, tagline: false },
                createOrder: createOrderFn,
                onApprove: onApproveFn,
                onError: onErrorFn,
            }).render(`#paypal-button-${milestoneId}`);
        },

        async submitCard() {
            if (this.processing) return;
            if (!this.cardFields || !this.cardFieldsEligible) {
                this.error = 'Card payment is not available. Please use the PayPal button below.';
                return;
            }
            this.processing = true;
            this.error = '';

            try {
                await this.cardFields.submit();
            } catch (err) {
                this.error = err.message || 'Card payment failed. Please check your details and try again.';
                this.processing = false;
            }
        },
    }));
});
</script>
@endpush
