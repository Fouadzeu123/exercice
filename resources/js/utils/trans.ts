import { ref } from 'vue';

export const currentLocale = ref<string>(localStorage.getItem('locale') || 'FR');

/**
 * Translates text on the fly based on current locale.
 */
export function t(frText: string, enText: string): string {
    return currentLocale.value === 'FR' ? frText : enText;
}

/**
 * Toggles the current locale between FR and EN.
 */
export function toggleLocale() {
    currentLocale.value = currentLocale.value === 'FR' ? 'EN' : 'FR';
    localStorage.setItem('locale', currentLocale.value);
}

/**
 * Sets a specific locale.
 */
export function setLocale(locale: string) {
    currentLocale.value = locale;
    localStorage.setItem('locale', locale);
}
