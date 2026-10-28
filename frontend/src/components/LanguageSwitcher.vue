<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useLocale } from 'vuetify'
import { setLocale, type Locale } from '../i18n'
import api from '@/services/api'

const { locale } = useI18n()
const vuetifyLocale = useLocale()

async function change(l: Locale) {
  setLocale(l)
  vuetifyLocale.current.value = l
  // persist preference for logged-in users (used for emails/PDFs/AI)
  if (localStorage.getItem('auth_token')) {
    await api.patch('/me/locale', { locale: l }).catch(() => {})
  }
}
</script>

<template>
  <v-btn-toggle :model-value="locale" mandatory density="compact" variant="outlined" divided
    @update:model-value="change">
    <v-btn value="en" size="small">EN</v-btn>
    <v-btn value="fil" size="small">FIL</v-btn>
  </v-btn-toggle>
</template>