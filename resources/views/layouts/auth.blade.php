<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'KoriePay') }} - Secure Access</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Host+Grostek:ital,wght@0,300;0,700;1,300;1,700&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .font-sans { font-family: 'Host Grostek', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="font-sans text-slate-300 antialiased bg-[#020617] selection:bg-[#29B475]/30">
    {{ $slot }}
</body>
</html>