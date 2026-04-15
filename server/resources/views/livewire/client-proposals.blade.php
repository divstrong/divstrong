<div>
    @if($proposals->isEmpty())
        <div style="text-align: center; padding: 2rem 0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #111827;">No proposals</h3>
            <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">No proposals have been created for this client yet.</p>
        </div>
    @else
        <div style="overflow: hidden; border-radius: 0.75rem; border: 1px solid #e5e7eb;">
            <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f9fafb;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6b7280;">Project</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6b7280;">Status</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6b7280;">Date</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 500; color: #6b7280;">Amount</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 500; color: #6b7280;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposals as $proposal)
                        @php
                            $statusColors = [
                                'draft' => ['bg' => '#f3f4f6', 'text' => '#374151'],
                                'sent' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                                'viewed' => ['bg' => '#fef3c7', 'text' => '#b45309'],
                                'accepted' => ['bg' => '#d1fae5', 'text' => '#047857'],
                                'converted' => ['bg' => '#d1fae5', 'text' => '#047857'],
                                'declined' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                            ];
                            $sc = $statusColors[$proposal->status->value] ?? $statusColors['draft'];
                        @endphp
                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem 1rem; color: #111827; font-weight: 500;">
                                {{ $proposal->project_title }}
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span style="display: inline-flex; align-items: center; border-radius: 9999px; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 500; background-color: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">
                                    {{ $proposal->status->getLabel() }}
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #6b7280;">
                                {{ $proposal->proposal_date?->format('M j, Y') }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: right; color: #111827;">
                                ${{ number_format($proposal->subtotal, 2) }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: right;">
                                <a href="{{ ($editUrl)($proposal) }}" style="font-size: 0.875rem; font-weight: 500; color: #ed2537; text-decoration: none;">
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
