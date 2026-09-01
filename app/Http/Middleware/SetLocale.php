<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('locale')) {
            // 1. Use the language they manually selected
            App::setLocale(Session::get('locale'));
        } else {
            // 2. AUTO-DETECT based on user's country if they haven't chosen one
            if (Auth::check()) {
                // Niger citizens default to French, Nigerians to English
                $defaultLang = Auth::user()->country_code === 'NER' ? 'fr' : 'en';
                App::setLocale($defaultLang);
                Session::put('locale', $defaultLang); // Save it so we don't calculate again
            } else {
                App::setLocale('en'); // Absolute default for guests
            }
        }

        return $next($request);
    }
}