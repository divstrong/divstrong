<div class="min-h-screen bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="https://www.divstrong.com"><img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-7 w-auto sm:h-8"></a>
            <p class="text-xs text-gray-400 sm:text-sm">Your call</p>
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-5 py-12 sm:px-8">
        @if($meeting->isCanceled())
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <h1 class="text-2xl font-bold text-gray-900">That call is canceled</h1>
                <p class="mt-2 text-gray-600">The time has been released — nothing further is needed.</p>
                <a href="{{ url('/book') }}" class="mt-6 inline-block rounded-lg bg-brand px-7 py-3 font-semibold text-white transition hover:bg-gray-900">
                    Pick a new time
                </a>
            </div>
        @elseif($meeting->isPast())
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <h1 class="text-2xl font-bold text-gray-900">This call has already happened</h1>
                <p class="mt-2 text-gray-600">{{ $meeting->readableTime() }}</p>
                <a href="{{ url('/book') }}" class="mt-6 inline-block rounded-lg bg-brand px-7 py-3 font-semibold text-white transition hover:bg-gray-900">
                    Book another
                </a>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand">Confirmed</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $meeting->readableTime() }}</h1>

                <dl class="mt-6 divide-y divide-gray-100 border-y border-gray-100">
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-sm text-gray-500">With</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ config('scheduling.host.name') }} · divStrong</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-sm text-gray-500">How</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ config('scheduling.location') }}</dd>
                    </div>
                    @if($meeting->phone)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-sm text-gray-500">We will call</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $meeting->phone }}</dd>
                        </div>
                    @endif
                </dl>

                @if($confirmingCancel)
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4">
                        <p class="text-sm text-red-700">Release this time? We will email you both to confirm.</p>
                        <div class="mt-3 flex gap-2">
                            <button wire:click="cancel" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                                Yes, cancel it
                            </button>
                            <button wire:click="$set('confirmingCancel', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                                Keep it
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                        <a href="{{ url('/book') }}" class="flex-1 rounded-lg bg-brand px-5 py-3 text-center font-semibold text-white transition hover:bg-gray-900">
                            Move to another time
                        </a>
                        <button wire:click="$set('confirmingCancel', true)" class="flex-1 rounded-lg border border-gray-300 bg-white px-5 py-3 font-medium text-gray-700 transition hover:bg-gray-50">
                            Cancel the call
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">
                        Moving it books the new time first — cancel this one afterwards so the slot frees up.
                    </p>
                @endif
            </div>
        @endif
    </main>
</div>
