{{-- Never 500 the page if Vite hasn't produced a manifest (CI, first deploy). --}}
@if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
