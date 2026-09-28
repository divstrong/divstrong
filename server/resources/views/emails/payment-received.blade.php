<x-mail.layout
    :title="'Payment Received: ' . $proposal->project_title"
    :preheader="'$' . number_format($payment->amount, 2) . ' received from ' . $proposal->client_name . '.'"
>
    <x-mail.status tone="success" symbol="&#36;" title="Payment Received">
        {{ $proposal->client_name }} paid ${{ number_format($payment->amount, 2) }} on {{ $proposal->project_title }}.
    </x-mail.status>

    <x-mail.details :rows="[
        ['label' => 'Amount', 'value' => '$' . number_format($payment->amount, 2) . ' ' . $payment->currency, 'accent' => true],
        ['label' => 'Project', 'value' => $proposal->project_title],
        ['label' => 'Client', 'value' => $proposal->client_name],
        $payment->milestone ? ['label' => 'Milestone', 'value' => $payment->milestone->title] : null,
        $payment->payer_email ? ['label' => 'Payer email', 'value' => $payment->payer_email, 'url' => 'mailto:' . $payment->payer_email] : null,
        ['label' => 'Capture ID', 'value' => $payment->paypal_capture_id],
        ['label' => 'Paid at', 'value' => $payment->paid_at->format('F j, Y g:i A')],
    ]" />

    <x-mail.button :url="url('/admin')">View in Admin</x-mail.button>
</x-mail.layout>
