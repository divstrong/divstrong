@php
    $term = $invoice->term_start->format('M j, Y') . ' – ' . $invoice->term_end->format('M j, Y');
    $amount = '$' . number_format((float) $invoice->amount, 2) . ' ' . $invoice->currency;
    $greeting = $account->client?->name ? 'Hi ' . strtok($account->client->name, ' ') . ',' : 'Hello,';
@endphp

<x-mail.layout
    :title="$isReminder ? 'Hosting renewal reminder' : 'Hosting renewal'"
    :preheader="$isReminder
        ? 'Your hosting renewal for ' . $account->domain . ' ' . $dueWords . '.'
        : 'Thank you for renewing hosting for ' . $account->domain . ' — your invoice is ready.'"
>
    @if($isReminder)
        <x-mail.heading eyebrow="Payment reminder" :title="'Your renewal ' . $dueWords">
            <x-mail.text>
                {{ $greeting }} a quick reminder that the hosting renewal for {{ $account->domain }}
                is still open. Paying it keeps the site online, maintained and supported for the
                year ahead without interruption.
            </x-mail.text>
        </x-mail.heading>
    @else
        <x-mail.heading eyebrow="Hosting renewal" title="Thank you for another year">
            <x-mail.text>
                {{ $greeting }} thank you for continuing to host {{ $account->domain }} with
                divStrong — we are glad to have you with us for another year. Your renewal
                invoice for the next term is ready below.
            </x-mail.text>
        </x-mail.heading>
    @endif

    <x-mail.details :rows="[
        ['label' => 'Domain', 'value' => $account->domain],
        ['label' => 'Renewal term', 'value' => $term],
        ['label' => 'Amount', 'value' => $amount, 'accent' => true],
        ['label' => 'Due', 'value' => $invoice->due_date->format('F j, Y')],
        $invoice->invoice_number ? ['label' => 'Invoice', 'value' => '#' . $invoice->invoice_number] : null,
    ]" />

    <x-mail.button :url="$invoice->payment_url" :link="true">View &amp; pay invoice</x-mail.button>

    <x-mail.text :space="24" :muted="true">
        You can pay by card or with PayPal on the invoice page — no account needed. Your plan
        covers hosting, maintenance and human support — a person who answers, not a ticket queue.
    </x-mail.text>

    <x-mail.signoff :name="'The ' . config('hosting.billing_name') . ' Department'" :email="config('hosting.billing_email')">
        Thank you for extending your term with us. Questions about this invoice? Just reply to this email.
    </x-mail.signoff>
</x-mail.layout>
