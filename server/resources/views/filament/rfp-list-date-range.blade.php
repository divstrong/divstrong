<div class="flex items-center gap-2 mr-5">
    <select wire:model.live="filters.date_range"
            class="text-sm bg-white border border-gray-300 rounded-lg px-3 py-1.5 focus:border-red-500 focus:ring-1 focus:ring-red-500/20 outline-none cursor-pointer">
        <option value="all_time">All Time</option>
        <option value="this_month">This Month</option>
        <option value="this_quarter">This Quarter</option>
        <option value="this_year">This Year</option>
        <option value="last_month">Last Month</option>
        <option value="last_quarter">Last Quarter</option>
        <option value="last_year">Last Year</option>
        <option value="custom">Custom</option>
    </select>

    <template x-if="$wire.filters.date_range === 'custom'">
        <span class="flex items-center gap-2">
            <input type="date" wire:model.live="filters.date_start"
                   class="text-sm bg-white border border-gray-300 rounded-lg px-2 py-1.5 focus:border-red-500 focus:ring-1 focus:ring-red-500/20 outline-none">
            <span class="text-xs text-gray-400">to</span>
            <input type="date" wire:model.live="filters.date_end"
                   class="text-sm bg-white border border-gray-300 rounded-lg px-2 py-1.5 focus:border-red-500 focus:ring-1 focus:ring-red-500/20 outline-none">
        </span>
    </template>
</div>

<style>
    /* Pop-on-hover for the Screenah stat cards */
    .fi-wi-stats-overview-stat {
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.18), 0 4px 8px -4px rgba(0, 0, 0, 0.08);
        border-color: rgb(229, 231, 235);
    }
    .fi-wi-stats-overview-stat a {
        cursor: pointer;
    }
</style>
