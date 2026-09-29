@php
    $company = $prospect->company ?: 'your shop';
    $answeredLike = $prospect->likes_design !== null;
    $rated = $prospect->fit_rating !== null;
    $answeredInterest = $prospect->interested !== null;
    $hasMeeting = $prospect->hasBookedMeeting();

    // Answer buttons: white until chosen, red/gold on hover, and the chosen one stays red.
    // They remain clickable afterwards, so changing your mind is one click.
    $choice = 'flex-1 rounded-lg border-2 px-5 py-3 font-semibold transition disabled:opacity-60';
    $open = 'border-gray-200 bg-white text-gray-700 hover:border-brand hover:bg-amber-50 hover:text-brand';
    $chosen = 'border-brand bg-brand text-white';
@endphp

<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="https://www.divstrong.com" target="_blank" rel="noopener">
                <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-7 w-auto sm:h-8">
            </a>
            <p class="text-xs text-gray-400 sm:text-sm">Built for {{ $company }}</p>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-5 py-10 sm:px-8 sm:py-14">

        {{-- Hero --}}
        <section class="text-center">
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand">A concept, partially built</p>

            <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight text-gray-900 sm:text-4xl">
                {{ $company }}, this could be your new website.
            </h1>

            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-gray-600 sm:text-lg">
                It is live and working — not a mockup. Have a look around, then tell us what you
                think using the feedback buttons below:
            </p>

            <a href="{{ $prospect->preview_url }}"
               target="_blank"
               rel="noopener"
               wire:click="openedSite"
               class="mt-7 inline-flex items-center gap-2 rounded-xl bg-brand px-8 py-4 text-base font-semibold text-white shadow-lg shadow-brand/25 transition hover:bg-gray-900">
                Open your site
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
            </a>

            {{-- The design itself, in browser chrome so it reads as a site rather than a picture. --}}
            @if($prospect->preview_image)
                <a href="{{ $prospect->preview_url }}" target="_blank" rel="noopener" wire:click="openedSite"
                   class="mt-10 block overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                    <div class="flex items-center gap-1.5 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span class="ml-3 truncate text-xs text-gray-400">{{ $prospect->preview_url }}</span>
                    </div>
                    <img src="{{ \Illuminate\Support\Str::startsWith($prospect->preview_image, 'http') ? $prospect->preview_image : \Illuminate\Support\Facades\Storage::url($prospect->preview_image) }}"
                         alt="Website concept for {{ $company }}"
                         class="w-full">
                </a>
            @endif
        </section>

        {{-- The two questions --}}
        <section class="mt-12 sm:mt-16">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                {{-- Question one --}}
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Do you like the direction?</h2>

                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <button wire:click="answerLike(true)" wire:loading.attr="disabled" @class([$choice, $chosen => $prospect->likes_design === true, $open => $prospect->likes_design !== true])>
                            Yes, I like it
                        </button>
                        <button wire:click="answerLike(false)" wire:loading.attr="disabled" @class([$choice, $chosen => $prospect->likes_design === false, $open => $prospect->likes_design !== false])>
                            Not quite
                        </button>
                    </div>

                    @if($answeredLike)
                        @if($prospect->likes_design === false)
                            @if($likeCommentSent)
                                <p class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    Got it — thank you. That is the useful half.
                                </p>
                            @else
                                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <label class="block text-sm font-medium text-gray-700">
                                        What would you change?
                                    </label>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        Layout, colors, the wording, the photos — whatever put you off. No wrong answers.
                                    </p>

                                    <textarea wire:model="likeComment" rows="3"
                                              class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand"
                                              placeholder="It feels too corporate for our shop…"></textarea>
                                    @error('likeComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                    <button wire:click="submitLikeComment" wire:loading.attr="disabled"
                                            class="mt-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand disabled:opacity-60">
                                        <span wire:loading.remove wire:target="submitLikeComment">Send feedback</span>
                                        <span wire:loading wire:target="submitLikeComment">Sending…</span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>

                {{-- Question two, revealed once the first is answered. Re-rating is allowed. --}}
                @if($answeredLike)
                    @php($fit = (int) $prospect->fit_rating)
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <h2 class="text-lg font-bold text-gray-900">
                            How well does it fit {{ $company }}?
                        </h2>

                        <div class="mt-3 flex items-center gap-1" x-data="{ hover: 0 }" @mouseleave="hover = 0">
                            @foreach(range(1, 5) as $star)
                                <button type="button"
                                        wire:click="rateFit({{ $star }})"
                                        @mouseenter="hover = {{ $star }}"
                                        aria-label="{{ $star }} out of 5"
                                        class="p-0.5 transition hover:scale-110">
                                    <svg class="h-9 w-9 transition-colors"
                                         :class="(hover || {{ $fit }}) >= {{ $star }} ? 'text-amber-400' : 'text-gray-200'"
                                         fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.12 5.11a.56.56 0 0 0 .48.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 0 0-.19.56l1.29 5.38a.56.56 0 0 1-.84.61l-4.73-2.89a.56.56 0 0 0-.58 0l-4.73 2.89a.56.56 0 0 1-.84-.61l1.29-5.38a.56.56 0 0 0-.19-.56l-4.2-3.6a.56.56 0 0 1 .32-.99l5.52-.44a.56.56 0 0 0 .48-.35l2.12-5.11Z"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>

                        <p class="mt-2 text-sm text-gray-500">
                            @if($fit)
                                You rated it {{ $fit }} out of 5 — thank you. Tap a star to change it.
                            @else
                                1 is nowhere near, 5 is exactly right.
                            @endif
                        </p>
                    </div>
                @endif

                {{-- Question three, the one that leads to the call. Revealed once they have rated;
                     also shown to anyone who answered it before the rating question existed. --}}
                @if($answeredLike && ($rated || $answeredInterest))
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <h2 class="text-lg font-bold text-gray-900">
                            Would you consider moving {{ $company }} onto it?
                        </h2>

                        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                            <button wire:click="answerInterest(true)" wire:loading.attr="disabled" @class([$choice, $chosen => $prospect->interested === true, $open => $prospect->interested !== true])>
                                Yes, I'm interested
                            </button>
                            <button wire:click="answerInterest(false)" wire:loading.attr="disabled" @class([$choice, $chosen => $prospect->interested === false, $open => $prospect->interested !== false])>
                                No thanks
                            </button>
                        </div>

                        @if($answeredInterest)
                            @if($prospect->interested)
                                <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">
                                    Brilliant — pick a time below and we will talk it through.
                                </p>
                            @else
                                <p class="mt-3 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                    Understood — we will leave it there, and you will not hear from us again about
                                    this. The site stays online if you ever change your mind.
                                </p>

                                @if($interestCommentSent)
                                    <p class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                        Thank you — genuinely useful, and it changes what we build next.
                                    </p>
                                @else
                                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Mind saying why? It is the most useful thing you could tell us.
                                        </label>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Timing, cost, you already have someone, the site is fine as it is — all fair.
                                        </p>

                                        <textarea wire:model="interestComment" rows="3"
                                                  class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand"
                                                  placeholder="We redid ours last year and are happy with it…"></textarea>
                                        @error('interestComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                        <button wire:click="submitInterestComment" wire:loading.attr="disabled"
                                                class="mt-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand disabled:opacity-60">
                                            <span wire:loading.remove wire:target="submitInterestComment">Send feedback</span>
                                            <span wire:loading wire:target="submitInterestComment">Sending…</span>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        @endif
                    </div>
                @endif

            </div>
        </section>

        {{-- Booking --}}
        @if($showBooking || $hasMeeting)
            <section class="mt-8" id="book">
                @include('livewire.partials.booking')
            </section>
        @elseif($prospect->interested !== false)
            <p class="mt-8 text-center text-sm text-gray-500">
                Would rather just talk?
                <button wire:click="startBooking" class="font-semibold text-brand underline">Book 30 minutes</button>
            </p>
        @endif

    </main>
</div>
