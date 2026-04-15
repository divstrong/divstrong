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
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    {{ $slot }}

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @stack('scripts')
    @livewireScripts
</body>
</html>
