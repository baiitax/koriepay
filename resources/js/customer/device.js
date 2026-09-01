/**
 * CUSTOMER BANKING — Stage 5 (device client helpers).
 *
 * KoriePayDevice — support detection + session-only preference mirror:
 *   - `supported`: WebAuthn platform authenticator availability (fingerprint
 *     / face on THIS device/browser).
 *   - `storeProfile`: a tiny in-memory store that keeps the session-only
 *     security preferences (biometric toggle, PIN "enrolled" flag). Nothing
 *     here is ever sent to the server except the boolean toggle, and the
 *     server keeps that in the session, not the database.
 *   - `applyRtl`: layout stub — sets `dir="rtl"` on <html> for locales that
 *     need it (e.g. fr/ha stubs). The CSS variables flip as a stub until
 *     full RTL styling lands.
 */

export const KoriePayDevice = {
    get supported() {
        // WebAuthn platform authenticator availability — the only honest
        // signal for fingerprint/face login support.
        if (typeof window.PublicKeyCredential !== 'function') {
            return false;
        }
        if (typeof window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function') {
            return false;
        }
        // Sync value (cached after first probe).
        if (this._supported !== undefined) {
            return this._supported;
        }
        window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((ok) => {
            this._supported = ok;
            if (window.storeProfile) window.storeProfile.biometricSupported = ok;
        }).catch(() => {
            this._supported = false;
        });
        // Default to false until the probe resolves — never claim support we
        // have not proven.
        return false;
    },

    get isSecureContext() {
        return typeof window.isSecureContext === 'boolean' ? window.isSecureContext : false;
    },

    /**
     * Locale → layout direction stub. en = ltr; fr/ha are rendered LTR for
     * now but the plumbing is live so an RTL stylesheet can drop in later.
     */
    applyRtl(locale) {
        const rtl = ['ar', 'he'].includes(String(locale).split('-')[0]);
        document.documentElement.setAttribute('dir', rtl ? 'rtl' : 'ltr');
        document.documentElement.style.setProperty('--dir', rtl ? 'rtl' : 'ltr');
    },
};

/**
 * Session-only preference store. Plain object on `window` — nothing
 * persisted, no cookies beyond the session Laravel already sets.
 */
export function bootStoreProfile() {
    if (window.storeProfile) {
        return window.storeProfile;
    }
    const store = {
        biometric: false,
        biometricSupported: false,
        pinEnrolled: false,
        setBiometric(enabled) {
            this.biometric = enabled;
        },
        setPinEnrolled(enabled) {
            this.pinEnrolled = enabled;
        },
    };
    window.storeProfile = store;
    return store;
}

export function bootDeviceHooks() {
    const store = bootStoreProfile();
    KoriePayDevice.supported; // prime the probe
    store.biometricSupported = KoriePayDevice.supported;

    // RTL stub — react to locale changes dispatched by the switcher.
    document.addEventListener('locale-changed', (e) => {
        KoriePayDevice.applyRtl(e.detail?.locale || 'en');
    });
    KoriePayDevice.applyRtl(document.documentElement.lang || 'en');

    return store;
}
