@php
    $data = $this->getGoalData();
    $percentage = $data['percentage'];
    $goal = $data['goal'];
    $converted = $data['converted'];
    // Semi-circle arc length: π * radius (80) ≈ 251.33
    $arcLength = 251.33;
    $filledLength = $arcLength * ($percentage / 100);
    $offset = $arcLength - $filledLength;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Revenue Goal</h3>
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">${{ number_format($goal) }}</span>
        </div>

        {{-- Gauge --}}
        <div class="flex flex-col items-center">
            <svg viewBox="0 0 200 110" class="w-56 h-auto">
                {{-- Background arc --}}
                <path d="M 20 100 A 80 80 0 0 1 180 100"
                      fill="none" stroke="#e5e7eb" stroke-width="16" stroke-linecap="round"/>
                {{-- Filled arc --}}
                <path d="M 20 100 A 80 80 0 0 1 180 100"
                      fill="none" stroke="#ed2537" stroke-width="16" stroke-linecap="round"
                      stroke-dasharray="{{ $arcLength }}"
                      stroke-dashoffset="{{ $offset }}"
                      class="transition-all duration-700"/>
            </svg>

            {{-- Percentage --}}
            <div class="-mt-14 text-center">
                <span class="text-4xl font-bold text-gray-950 dark:text-white">{{ $percentage }}%</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${{ number_format($converted, 0) }} converted</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
