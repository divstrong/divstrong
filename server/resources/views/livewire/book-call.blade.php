<div class="min-h-screen bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="https://www.divstrong.com"><img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-7 w-auto sm:h-8"></a>
            <p class="text-xs text-gray-400 sm:text-sm">Book a call</p>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <div class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Let's talk it through</h1>
            <p class="mx-auto mt-3 max-w-lg text-gray-600">
                {{ \App\Support\Availability::durationMinutes() }} minutes with {{ config('scheduling.host.name') }}.
                No slides, no pitch deck — bring the problem and we will tell you straight whether we can help.
            </p>
        </div>

        <div class="mt-8">
            @include('livewire.partials.booking')
        </div>

        <footer class="mt-12 border-t border-gray-200 pt-8 text-center text-sm text-gray-400">
            divStrong · Richmond, VA ·
            <a href="mailto:{{ config('scheduling.contact_email') }}" class="text-brand hover:underline">Questions?</a>
        </footer>
    </main>
</div>
