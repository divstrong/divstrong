<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.gtag')
    <title>Accept Invite - divStrong</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url("{{ asset('images/loginbg.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }
        .invite-card {
            position: relative;
            z-index: 1;
            background-color: white;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 24rem;
            margin: 1rem;
        }
        .invite-logo {
            display: block;
            margin: 0 auto 2rem;
            height: 2rem;
        }
        .invite-heading {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 0.25rem;
            text-align: center;
        }
        .invite-sub {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0 0 1.5rem;
            text-align: center;
        }
        .invite-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.375rem;
        }
        .invite-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .invite-input:focus {
            border-color: #ed2537;
            box-shadow: 0 0 0 1px #ed2537;
        }
        .invite-field {
            margin-bottom: 1rem;
        }
        .invite-btn {
            width: 100%;
            background: linear-gradient(to bottom, #f43f4f 0%, #ed2537 40%, #c91e2e 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 2px 8px rgba(237,37,55,0.3);
            border: 1px solid #b91a27;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
        }
        .invite-btn:hover {
            color: #FFD700;
            transform: scale(1.02);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15), 0 6px 20px rgba(237,37,55,0.4);
        }
        .invite-error {
            font-size: 0.75rem;
            color: #dc2626;
            margin-top: 0.25rem;
        }
        .invite-invalid {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .invite-invalid a {
            color: #ed2537;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
