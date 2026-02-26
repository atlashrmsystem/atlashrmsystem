import { createI18n } from 'vue-i18n';
import en from './en.json';
import ar from './ar.json';

const i18n = createI18n({
  legacy: false,
  locale: 'en', // default locale
  fallbackLocale: 'en',
  messages: { en, ar }
});

export function setDocumentDirection(locale) {
  document.documentElement.setAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
  document.documentElement.setAttribute('lang', locale);
}

export default i18n;
