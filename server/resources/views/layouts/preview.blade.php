<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- These pages are personal to one recipient, and the booking page is not a landing
         page we want ranking. Neither belongs in an index. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'divStrong' }}</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/proposal.css'])
    @livewireStyles
</head>
<body class="bg-white text-gray-800 font-sans antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
