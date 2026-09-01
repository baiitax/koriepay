<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * CUSTOMER BANKING — Stage 5 (language).
 *
 * en/fr/ha locale switch. The locale is applied to the running app and kept
 * in the session. The translations layer is a stub: UI strings remain in
 * English and only the locale plumbing (__()/@lang()) is live, with an RTL
 * layout stub handled client-side (device.js sets dir=rtl when the locale
 * needs it).
 */
class LanguageSwitcher extends Component
{
    public string $locale = 'en';

    public array $available = [
        'en' => 'English',
        'fr' => 'Français',
        'ha' => 'Hausa',
    ];

    public function mount(): void
    {
        $current = Session::get('locale', 'en');
        $this->locale = in_array($current, array_keys($this->available), true) ? $current : 'en';
    }

    public function switchTo(string $locale): void
    {
        if (! in_array($locale, array_keys($this->available), true)) {
            return;
        }

        $this->locale = $locale;
        App::setLocale($locale);
        Session::put('locale', $locale);

        // Stub: real translations will live in lang/{locale}/*.php. The UI
        // strings stay English for now; the RTL attribute is applied by
        // device.js when this event fires.
        $this->dispatch('locale-changed', locale: $locale);
        $this->dispatch('toast', message: __('Language preference saved: '.$this->available[$locale]), type: 'info');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.customer.language-switcher');
    }
}
