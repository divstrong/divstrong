<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.gtag')
    <title>{{ $title ?? 'Proposal' }} - DivStrong</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/proposal.css', 'resources/js/signature-pad.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @livewireStyles

    <style>
        /* Printing straight from the browser should match what ?pdf=1 produces:
           no sticky nav, no video, none of the interactive/admin chrome. The
           .pdf-hide class is the existing convention for that. */
        @media print {
            nav,
            video,
            .pdf-hide,
            [x-cloak],
            /* The bug reporter mounts a fixed-position host. The widget hides
               itself too, but a cached copy of bug-reporter.js would not. */
            #divstrong-bug-reporter-host {
                display: none !important;
            }

            /* The roadmap chevrons overlap their own labels at paper width, so
               print the stacked timeline the small-screen layout already
               provides instead of maintaining a third variant. */
            .roadmap-chevrons { display: none !important; }
            .roadmap-stack { display: block !important; }
            .roadmap-stack > div { break-inside: avoid; page-break-inside: avoid; }

            /* Phase numbers sit on a coloured background, which disappears when
               the browser is printing without background graphics — white text on
               white paper. Fall back to an outlined circle with dark text. */
            .roadmap-stack-num {
                background: transparent !important;
                color: #111827 !important;
                border: 2px solid;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    {{ $slot }}

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @stack('scripts')
    @livewireScripts
    <script src="https://www.divstrong.com/bug-reporter.js" data-site-key="bk_mXPKI0XMTPeSgOSWHqfhcwUrNwwpcgwSYnDayfsy" defer></script>
</body>
</html>
