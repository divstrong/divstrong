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

    /*
    | The business block printed on the PayPal invoice.
    |
    | Invoices created through the API show only what is sent with them — the business
    | profile saved in PayPal's invoice settings fills in invoices made on paypal.com, not
    | these. The logo must be a public https URL; PayPal shows it at up to 250×90.
    */
    'business_name' => env('HOSTING_BUSINESS_NAME', 'DivStrong Productions, LLC'),
    'website' => env('HOSTING_WEBSITE', 'https://www.divstrong.com'),
    'logo_url' => env('HOSTING_LOGO_URL', 'https://www.divstrong.com/images/logo-invoice.png'),
    'address' => [
        'address_line_1' => env('HOSTING_ADDRESS_LINE_1', '14321 Winter Breeze Drive'),
        'address_line_2' => env('HOSTING_ADDRESS_LINE_2', 'Suite #55'),
        'admin_area_2' => env('HOSTING_ADDRESS_CITY', 'Midlothian'),
        'admin_area_1' => env('HOSTING_ADDRESS_STATE', 'VA'),
        'postal_code' => env('HOSTING_ADDRESS_ZIP', '23113'),
        'country_code' => env('HOSTING_ADDRESS_COUNTRY', 'US'),
    ],

    // Who hears about it when a renewal is paid.
    'notify_email' => env('HOSTING_NOTIFY_EMAIL', 'jim@divstrong.com'),
];
