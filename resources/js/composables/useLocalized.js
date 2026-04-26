import { useI18n } from 'vue-i18n';

/**
 * useLocalized — read a localized field from any object that follows the
 * `name` / `name_lv` convention used throughout this app's schema.
 *
 * Usage:
 *   const localized = useLocalized();
 *   <h2>{{ localized(opening, 'name') }}</h2>
 *
 * Selection rules:
 *   - When current locale is 'lv' and `<key>_lv` exists → use it
 *   - Otherwise fall back to the bare `<key>` field
 *   - If neither exists, return an empty string (never undefined)
 */
export function useLocalized() {
    const { locale } = useI18n();

    const pick = (obj, key) => {
        if (!obj || typeof obj !== 'object') return '';
        const suffix = locale.value;
        const suffixed = `${key}_${suffix}`;
        // Prefer the localized field if non-empty
        if (obj[suffixed] !== undefined && obj[suffixed] !== null && obj[suffixed] !== '') {
            return obj[suffixed];
        }
        // Special-case: explicitly fall back to 'lv' when current locale is 'en'
        // and the 'en' field doesn't exist (most content is authored in lv)
        if (suffix !== 'lv') {
            const lvKey = `${key}_lv`;
            if (obj[lvKey] !== undefined && obj[lvKey] !== null && obj[lvKey] !== '') {
                return obj[lvKey];
            }
        }
        return obj[key] ?? '';
    };

    return pick;
}
