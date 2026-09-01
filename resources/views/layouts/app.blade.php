<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SahelPay | Liquidity Network</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 selection:bg-[#158987] selection:text-white">
    
    <div class="min-h-screen flex flex-col">
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-2 cursor-pointer" onclick="window.location='{{ route('customer.dashboard') }}'">
                        <div class="w-8 h-8 bg-[#158987] rounded-xl flex items-center justify-center text-white shadow-md">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-lg font-black tracking-tight text-slate-900">Sahel<span class="text-[#158987]">Pay</span></span>
                    </div>

                    <div class="flex items-center gap-4">
                        
                        <livewire:global.notification-bell />

                        <div class="relative ml-3 border-l border-slate-200 pl-4">
                            <button type="button" class="flex items-center gap-2 group" id="user-menu-button">
                                <span class="hidden sm:block text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-slate-900 transition-colors">{{ Auth::user()->name }}</span>
                                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center font-black text-[#158987] border border-slate-200 shadow-sm">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>
        
    </div>

    @livewireScripts
</body>
</html>