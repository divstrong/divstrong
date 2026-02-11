<div class="min-h-screen">
    @php $hasCover = (bool) $proposal->cover_image; @endphp

    {{-- ========== COVER SECTION ========== --}}
    <section class="relative min-h-screen flex items-center justify-center px-6 {{ $hasCover ? 'bg-gray-900' : 'bg-white' }}">
        @if($hasCover)
            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image: url('{{ Storage::url($proposal->cover_image) }}');">
                <div class="absolute inset-0 bg-black/50"></div>
            </div>
        @endif

        {{-- Top accent bar --}}
        <div class="absolute top-0 left-0 w-full h-1 bg-brand"></div>

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

        <div class="relative z-10 text-center max-w-3xl mx-auto">
            <div class="text-brand text-xs tracking-[0.4em] uppercase mb-6 font-medium">Web Development Proposal</div>

            {{-- Project Title --}}
            @if($isAdmin)
                <div class="mb-8">
                    <input type="text"
                           wire:model.blur="editingProjectTitle"
                           class="w-full text-center bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 p-0 text-4xl sm:text-5xl lg:text-6xl font-bold {{ $hasCover ? 'text-white placeholder-white/50' : 'text-gray-900' }} leading-tight transition-colors"
                           placeholder="Project Title">
                </div>
            @else
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold {{ $hasCover ? 'text-white' : 'text-gray-900' }} mb-8 leading-tight">
                    {{ $proposal->project_title }}
                </h1>
            @endif

            {{-- Client --}}
            <div class="{{ $hasCover ? 'text-gray-300' : 'text-gray-500' }} space-y-2 text-lg">
                @if($isAdmin)
                    <div class="inline-block relative">
                        <select wire:model.live="editingClientId"
                                class="appearance-none bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 {{ $hasCover ? 'text-white' : 'text-gray-900' }} font-semibold text-lg text-center cursor-pointer pr-8 pl-2 py-1 transition-colors">
                            <option value="" class="text-gray-900">Select Client</option>
                            @foreach($this->clients as $client)
                                <option value="{{ $client->id }}" class="text-gray-900">{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center {{ $hasCover ? 'text-gray-300' : 'text-gray-400' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                @else
                    <p>Prepared for
                        <span class="{{ $hasCover ? 'text-white' : 'text-gray-900' }} font-semibold">{{ $proposal->client_name }}</span>
                    </p>
                @endif
                @if($proposal->client_company)
                    <p class="{{ $hasCover ? 'text-gray-400' : 'text-gray-400' }}">{{ $proposal->client_company }}</p>
                @endif
                @if($proposal->client_domain)
                    <p class="{{ $hasCover ? 'text-brand-light' : 'text-brand/70' }} text-sm">{{ $proposal->client_domain }}</p>
                @endif
            </div>

            {{-- Date --}}
            <div class="mt-10 pt-8 border-t {{ $hasCover ? 'border-white/20' : 'border-gray-200' }} inline-block">
                @if($isAdmin)
                    <input type="date"
                           wire:model.live="editingProposalDate"
                           class="bg-transparent border-0 border-b-2 border-dashed border-transparent hover:border-gray-300 focus:border-brand focus:ring-0 {{ $hasCover ? 'text-gray-300' : 'text-gray-400' }} text-sm cursor-pointer p-0 transition-colors">
                @else
                    <p class="{{ $hasCover ? 'text-gray-300' : 'text-gray-400' }} text-sm">{{ $proposal->proposal_date->format('F j, Y') }}</p>
                @endif
            </div>
        </div>

        {{-- Logo - bottom right --}}
        <div class="absolute bottom-8 right-8 z-10">
            <div class="bg-white rounded-xl px-5 py-3 shadow-sm border border-gray-100">
                <img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-8">
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 {{ $hasCover ? 'text-white/40' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </section>

    {{-- ========== INTRODUCTION SECTION ========== --}}
    @if($proposal->introduction)
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-10">
                <div class="w-10 h-[2px] bg-brand"></div>
                <h2 class="text-3xl font-bold text-gray-900">Introduction</h2>
            </div>
            <div class="prose-light max-w-none text-lg leading-relaxed">
                {!! $proposal->introduction !!}
            </div>
        </div>
    </section>
    @endif

    {{-- ========== SCOPE OF WORK SECTION ========== --}}
    @if($proposal->scopeItems->count())
    <section class="py-20 px-6 bg-white">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-12">
                <div class="w-10 h-[2px] bg-brand"></div>
                <h2 class="text-3xl font-bold text-gray-900">Scope of Work</h2>
            </div>

            @foreach($proposal->scopeItems->groupBy('category') as $category => $items)
                <div class="mb-10">
                    <h3 class="text-xl font-semibold text-brand mb-5 flex items-center gap-3">
                        <span class="w-2 h-2 bg-brand rounded-full flex-shrink-0"></span>
                        {{ $category ?: 'General' }}
                    </h3>
                    <div class="space-y-4 pl-5 border-l border-gray-200">
                        @foreach($items as $item)
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 hover:border-gray-300 transition-colors">
                                <h4 class="font-semibold text-gray-900 text-base">{{ $item->title }}</h4>
                                @if($item->description)
                                    <p class="text-gray-500 mt-2 text-sm leading-relaxed">{{ $item->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ========== COST / INVESTMENT SECTION ========== --}}
    @if($proposal->costItems->count())
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-10">
                <div class="w-10 h-[2px] bg-brand"></div>
                <h2 class="text-3xl font-bold text-gray-900">Investment</h2>
            </div>

            @if($proposal->cost_notes)
                <p class="text-gray-500 mb-8 leading-relaxed">{{ $proposal->cost_notes }}</p>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4">Service</th>
                            <th class="px-6 py-4 text-center">Qty</th>
                            <th class="px-6 py-4 text-right">Unit Price</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($proposal->costItems as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-900">{{ $item->description }}</td>
                            <td class="px-6 py-4 text-center text-gray-500">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-900 font-semibold">${{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t-2 border-gray-200">
                            <td colspan="3" class="px-6 py-5 text-right">
                                <span class="text-gray-900 font-bold text-lg">Total</span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <span class="text-brand font-bold text-2xl">${{ number_format($proposal->subtotal, 2) }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($proposal->valid_until)
                <p class="text-gray-400 mt-4 text-sm text-right">
                    This proposal is valid until <span class="text-gray-600">{{ $proposal->valid_until->format('F j, Y') }}</span>
                </p>
            @endif
        </div>
    </section>
    @endif

    {{-- ========== MILESTONES SECTION ========== --}}
    @if($proposal->milestones->count())
    <section class="py-20 px-6 bg-white">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-12">
                <div class="w-10 h-[2px] bg-brand"></div>
                <h2 class="text-3xl font-bold text-gray-900">Payment Milestones</h2>
            </div>

            <div class="space-y-6">
                @foreach($proposal->milestones as $index => $milestone)
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-brand/10 border border-brand/20 rounded-full flex items-center justify-center text-brand font-bold text-sm">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 bg-gray-50 border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-start gap-4">
                            <h3 class="font-semibold text-gray-900">{{ $milestone->title }}</h3>
                            <div class="text-right flex-shrink-0">
                                @if($milestone->percentage)
                                    <span class="text-brand font-bold">{{ number_format($milestone->percentage, 0) }}%</span>
                                @endif
                                @if($milestone->amount)
                                    <span class="text-brand font-bold block">${{ number_format($milestone->amount, 2) }}</span>
                                @endif
                            </div>
                        </div>
                        @if($milestone->description)
                            <p class="text-gray-500 mt-2 text-sm">{{ $milestone->description }}</p>
                        @endif
                        @if($milestone->due_description)
                            <p class="text-gray-400 mt-2 text-xs uppercase tracking-wide">Due: {{ $milestone->due_description }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ========== ACCEPTANCE SECTION ========== --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-2xl mx-auto">
            @if($accepted)
                {{-- ACCEPTED STATE --}}
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

            @else
                {{-- ACCEPTANCE FORM --}}
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="w-10 h-[2px] bg-brand"></div>
                        <h2 class="text-3xl font-bold text-gray-900">Accept Proposal</h2>
                        <div class="w-10 h-[2px] bg-brand"></div>
                    </div>
                    <p class="text-gray-500">Please review the proposal above and sign below to accept.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm">
                    {{-- Full Name --}}
                    <div class="mb-6">
                        <label class="block text-sm text-gray-600 mb-2 font-medium">Full Name</label>
                        <input type="text" wire:model="signatureName"
                               placeholder="Enter your full name"
                               class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3
                                      text-gray-900 placeholder-gray-400
                                      focus:border-brand focus:ring-1 focus:ring-brand
                                      outline-none transition">
                        @error('signatureName')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Signature Pad --}}
                    <div class="mb-6" x-data="signaturePad()" x-init="init()">
                        <label class="block text-sm text-gray-600 mb-2 font-medium">Signature</label>
                        <div class="bg-gray-50 border border-gray-300 rounded-lg overflow-hidden relative" style="height: 200px;">
                            <canvas x-ref="canvas" class="w-full h-full cursor-crosshair"></canvas>
                            <div x-show="!hasSignature" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span class="text-gray-300 text-sm">Sign here</span>
                            </div>
                        </div>
                        <div class="flex justify-end mt-2">
                            <button type="button" x-on:click="clearSignature()"
                                    class="text-sm text-gray-400 hover:text-brand transition cursor-pointer">
                                Clear Signature
                            </button>
                        </div>
                        <input type="hidden" wire:model="signatureData">
                        @error('signatureData')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8 pt-6 border-t border-gray-200">
                        <button wire:click="acceptProposal"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="px-10 py-3.5 bg-brand hover:bg-brand-dark text-white
                                       font-semibold rounded-lg transition-all duration-200 text-center
                                       shadow-lg shadow-brand/20 hover:shadow-brand/40 cursor-pointer">
                            <span wire:loading.remove wire:target="acceptProposal">Accept Proposal</span>
                            <span wire:loading wire:target="acceptProposal">Processing...</span>
                        </button>
                        <button wire:click="declineProposal"
                                wire:confirm="Are you sure you want to decline this proposal?"
                                class="px-10 py-3.5 bg-transparent border border-gray-300 hover:border-red-500
                                       hover:text-red-500 text-gray-500 rounded-lg transition-all duration-200 text-center cursor-pointer">
                            Decline
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ========== FOOTER ========== --}}
    <footer class="py-8 px-6 bg-white border-t border-gray-200">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="DivStrong" class="h-6">
            <p class="text-gray-400 text-xs">&copy; {{ date('Y') }} DivStrong. All rights reserved.</p>
        </div>
    </footer>
</div>
