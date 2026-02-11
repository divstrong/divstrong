<div class="space-y-4">
    @if($proposals->isEmpty())
        <div class="text-center py-8">
            <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No proposals</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No proposals have been created for this client yet.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach($proposals as $proposal)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                {{ $proposal->project_title }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $colors = [
                                        'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                        'viewed' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                        'accepted' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                        'declined' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                    ];
                                    $color = $colors[$proposal->status->value] ?? $colors['draft'];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">
                                    {{ $proposal->status->getLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $proposal->proposal_date?->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                ${{ number_format($proposal->subtotal, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ ($editUrl)($proposal) }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
