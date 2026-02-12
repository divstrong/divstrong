<div class="min-h-screen">
    @php $hasCover = (bool) $proposal->cover_image; @endphp

    {{-- ========== STICKY NAV BAR ========== --}}
    <nav x-data="{
            scrolled: false,
            active: '',
            sections: ['overview', 'scope', 'investment', 'milestones', 'changes', 'terms'],
            labels: { overview: 'Overview', scope: 'Scope', investment: 'Investment', milestones: 'Milestones', changes: 'Changes', terms: 'Terms' },
            updateNav() {
                this.scrolled = window.scrollY > window.innerHeight * 0.6;
                let current = '';
                for (const id of this.sections) {
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top <= 100) current = id;
                }
                this.active = current;
            }
         }"
         x-init="updateNav()"
         @scroll.window.throttle.50ms="updateNav()"
         class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
         :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 translate-y-0' : '-translate-y-full'"
    >
        <div class="max-w-6xl mx-auto px-3 sm:px-6 flex items-center h-14 gap-2 sm:gap-0 sm:justify-center relative">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener" class="hidden sm:block absolute left-6 flex-shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-6">
            </a>
            <div class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto scrollbar-hide flex-1 sm:flex-none sm:justify-center">
                <template x-for="id in sections" :key="id">
                    <a :href="'#' + id"
                       class="px-2 sm:px-3 py-1.5 text-[11px] sm:text-xs font-medium rounded-full transition-colors duration-200 whitespace-nowrap flex-shrink-0"
                       :class="active === id ? 'bg-brand text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
                       x-text="labels[id]"
                       @click.prevent="document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })"
                    ></a>
                </template>
            </div>
            <div class="hidden sm:block sm:absolute sm:right-6">
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

    {{-- ========== COVER SECTION ========== --}}
    <section class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 bg-gray-900">
        {{-- Background video --}}
        <div class="absolute inset-0 overflow-hidden">
            <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover opacity-40">
                <source src="{{ asset('videos/abstractbg1.mp4') }}" type="video/mp4">
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

        {{-- Admin: Share button --}}
        @if($isAdmin)
            <div class="absolute top-4 right-4 z-20"
                 x-data="{ showShare: false, shareSent: false }"
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

        {{-- Bottom left: Client name + website (centered on mobile) --}}
        <div class="absolute bottom-32 sm:bottom-8 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:left-8 z-10 text-center sm:text-left">
            <p class="text-white font-semibold text-sm">{{ $proposal->client_name }}</p>
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
                    <input type="date"
                           wire:model.live="editingProposalDate"
                           class="bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 text-white/80 text-sm cursor-pointer p-0 transition-colors text-center">
                @else
                    <p class="text-white/80 text-sm">{{ $proposal->proposal_date->format('m/d/Y') }}</p>
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
    @if($proposal->introduction || $isAdmin)
    <section id="overview" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-10">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <h2 class="text-3xl font-bold text-gray-900">Overview</h2>
            </div>
            <div class="sm:pl-8">
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
            </div>
        </div>
    </section>
    @endif

    {{-- ========== SCOPE OF WORK SECTION ========== --}}
    @if($proposal->scopeItems->count() || $isAdmin)
    <section id="scope" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-12">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <h2 class="text-3xl font-bold text-gray-900">Scope of Work</h2>

                {{-- Add Item button --}}
                @if($isAdmin)
                    <div class="ml-auto" x-data="{ open: false, selected: [] }">
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
                        <div class="space-y-4">
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

    {{-- ========== COST / INVESTMENT SECTION ========== --}}
    @if($proposal->costItems->count() || $isAdmin)
    <section id="investment" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-10">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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
                            <tr class="bg-gray-50 border-t-2 border-gray-200">
                                <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-3 sm:px-6 py-5 text-right">
                                    <span class="text-gray-900 font-bold text-lg">Total</span>
                                </td>
                                <td class="px-3 sm:px-6 py-5 text-right">
                                    <span class="text-gray-900 font-bold text-xl sm:text-2xl">${{ number_format($proposal->subtotal, 0) }}</span>
                                </td>
                                @if($isAdmin)<td></td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Valid until --}}
            <div class="mt-4 px-2">
                @if($isAdmin)
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <span>Valid until</span>
                        <input type="date"
                               wire:model.live="editingValidUntil"
                               class="bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 text-gray-600 text-sm cursor-pointer p-0 transition-colors">
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

    {{-- ========== MILESTONES SECTION ========== --}}
    @if($proposal->milestones->count() || $isAdmin)
    <section id="milestones" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-10">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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
                                    <span class="text-gray-500">(${{ number_format(($milestone->percentage / 100) * $proposal->subtotal, 0) }})</span>
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

    {{-- ========== CHANGE REQUESTS SECTION ========== --}}
    <section id="changes" class="py-12 sm:py-20 px-4 sm:px-6 bg-gray-50 scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-10">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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

    {{-- ========== TERMS & CONDITIONS SECTION ========== --}}
    <section id="terms" class="py-12 sm:py-20 px-4 sm:px-6 bg-white scroll-mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-3 mb-10">
                <svg class="hidden sm:block w-7 h-7 text-brand flex-shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <h2 class="text-3xl font-bold text-gray-900">Terms & Conditions</h2>

                @if($isAdmin)
                    <button wire:click="addTerm"
                            class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand border border-brand/30 rounded-lg hover:bg-brand hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Term
                    </button>
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
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-emerald-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Signed by {{ $proposal->tc_signature_name }}</p>
                                <p class="text-xs text-gray-400">{{ $proposal->tc_signed_at->format('F j, Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                        @if($proposal->tc_signature_data)
                            <div class="inline-block bg-gray-50 border border-gray-100 rounded-lg p-3">
                                <img src="{{ $proposal->tc_signature_data }}" alt="Signature" class="max-h-16">
                            </div>
                        @endif
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

    {{-- ========== FOOTER ========== --}}
    <footer class="py-8 px-6 bg-white border-t border-gray-200">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener"><img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-6"></a>
            <p class="text-gray-400 text-xs">&copy; {{ date('Y') }} DivStrong. All rights reserved.</p>
        </div>
    </footer>
</div>
