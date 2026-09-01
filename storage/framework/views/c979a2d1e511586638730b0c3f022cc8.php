<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>KoriePay | Secure Grid Access</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=host-grotesk:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans text-slate-900 antialiased selection:bg-korie-green selection:text-white">
    <div class="min-h-screen bg-slate-50 flex">
        <?php echo e($slot); ?>

    </div>
</body>
</html><?php /**PATH /home/user/koriepay_rebuild/resources/views/layouts/guest.blade.php ENDPATH**/ ?>