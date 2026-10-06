<?php

return [
    /*
    | Automatic renewal invoices.
    |
    | Off until the account data has been checked: switched on against a book with terms
    | that already lapsed, the first scheduled run invoices every one of them at once. With
    | it off, invoices go out only from the "Send renewal invoice" button.
    */
    'auto_invoice' => (bool) env('HOSTING_AUTO_INVOICE', false),

    // How far ahead of the end of the current term the renewal invoice goes out.
    'invoice_days_before' => (int) env('HOSTING_INVOICE_DAYS_BEFORE', 30),

    // Who signs the notices and receives replies to them.
    'billing_name' => env('HOSTING_BILLING_NAME', 'divStrong Billing'),
    'billing_email' => env('HOSTING_BILLING_EMAIL', env('MAIL_FROM_ADDRESS', 'support@divstrong.com')),

    // Printed on the PayPal invoice.
    'business_name' => env('HOSTING_BUSINESS_NAME', 'divStrong'),
    'website' => env('HOSTING_WEBSITE', 'https://www.divstrong.com'),

    // Who hears about it when a renewal is paid.
    'notify_email' => env('HOSTING_NOTIFY_EMAIL', 'jim@divstrong.com'),
];
