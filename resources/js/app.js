import './bootstrap';
import { bootDeviceHooks } from './customer/device';

// Customer security helpers — session-only preferences + RTL stub.
window.addEventListener('DOMContentLoaded', () => bootDeviceHooks());
if (document.readyState !== 'loading') {
    bootDeviceHooks();
}
