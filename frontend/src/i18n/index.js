import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import fil from './locales/fil.json'

const saved = localStorage.getItem('locale') || 'en'

export const i18n = createI18n({
    legacy: false,
    locale: saved,
    fallbackLocale: 'en',
    messages: { en, fil },
})

export function setLocale(l) {
    i18n.global.locale.value = l
    localStorage.setItem('locale', l)
    document.documentElement.lang = l === 'fil' ? 'fil' : 'en'
}