{{--
    The calendar, included by both the preview page and /book. It runs inside the host
    component's scope, so it reads the BooksMeetings trait's state directly rather than
    taking props.
--}}
@php
    $meeting = $this->bookedMeeting;
@endphp

@if($meeting)
    {{-- Booked. --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h3 class="text-xl font-bold text-gray-900">You're on the calendar</h3>
        <p class="mt-2 text-gray-600">{{ $meeting->readableTime() }}</p>
        <p class="mt-3 text-sm text-gray-500">
            A confirmation is on its way to {{ $meeting->email }} with an invitation you can add
            to your calendar in one tap.
        </p>

        <a href="{{ $meeting->cancelUrl() }}" class="mt-5 inline-block text-sm text-gray-500 underline hover:text-brand">
            Need a different time?
        </a>
    </div>
@else
    <div x-data
         x-init="$wire.setTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone)"
         class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm">

        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Pick a time</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ \App\Support\Availability::durationMinutes() }} minutes, no slides.
                    {{ config('scheduling.location') }}.
                </p>
            </div>

            <p class="text-xs text-gray-400">
                Times shown in {{ str_replace('_', ' ', $this->timezone) }}
            </p>
        </div>

        @if($this->bookingError)
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $this->bookingError }}
            </div>
        @endif

        {{-- Days --}}
        <div class="mt-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Day</p>

            <div class="mt-2 flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                @forelse($this->bookingDays as $day)
                    <button type="button"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            class="shrink-0 rounded-xl border px-4 py-3 text-center transition
                                   {{ $selectedDate === $day['date']
                                        ? 'border-brand bg-brand text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50' }}">
                        <span class="block text-[11px] font-semibold uppercase tracking-wide {{ $selectedDate === $day['date'] ? 'text-white/70' : 'text-gray-400' }}">
                            {{ $day['day'] }}
                        </span>
                        <span class="block text-sm font-semibold">{{ $day['label'] }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">
                        No times are open at the moment — email
                        <a href="mailto:{{ config('scheduling.contact_email') }}" class="text-brand underline">{{ config('scheduling.contact_email') }}</a>
                        and we will sort something out.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Slots --}}
        @if($selectedDate)
            <div class="mt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Time</p>

                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @forelse($this->bookingSlots as $slot)
                        <button type="button"
                                wire:click="selectSlot('{{ $slot['value'] }}')"
                                class="rounded-lg border px-3 py-2.5 text-sm font-semibold transition
                                       {{ $selectedSlot === $slot['value']
                                            ? 'border-brand bg-brand text-white shadow-sm'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50' }}">
                            {{ $slot['label'] }}
                        </button>
                    @empty
                        <p class="col-span-full text-sm text-gray-500">That day just filled up. Try another.</p>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Details --}}
        @if($selectedSlot)
            <form wire:submit="book" class="mt-6 border-t border-gray-100 pt-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">Your name</label>
                        <input type="text" wire:model="bookingName"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand">
                        @error('bookingName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">Email</label>
                        <input type="email" wire:model="bookingEmail"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand">
                        @error('bookingEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">Company</label>
                        <input type="text" wire:model="bookingCompany"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">
                            Phone <span class="text-gray-400">— the number we should ring</span>
                        </label>
                        <input type="tel" wire:model="bookingPhone"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-600">
                        Anything you want covered? <span class="text-gray-400">Optional</span>
                    </label>
                    <textarea wire:model="bookingNotes" rows="3"
                              class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 outline-none transition focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="mt-5 w-full rounded-lg bg-brand px-6 py-3.5 font-semibold text-white shadow-lg shadow-brand/20 transition hover:bg-gray-900 disabled:opacity-60 sm:w-auto sm:px-10">
                    <span wire:loading.remove wire:target="book">Confirm this time</span>
                    <span wire:loading wire:target="book">Booking…</span>
                </button>
            </form>
        @endif
    </div>
@endif
