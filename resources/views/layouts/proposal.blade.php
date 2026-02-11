<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Proposal' }} - DivStrong</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/proposal.css', 'resources/js/signature-pad.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
