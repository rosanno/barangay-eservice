<template>
  <div class="auth-layout d-flex flex-column flex-md-row">
    <!-- Identity panel -->
    <aside
      class="auth-layout__pattern d-flex flex-column justify-space-between"
      :style="panelStyle"
    >
      <div class="pa-8 pa-md-10">
        <div class="d-flex align-center ga-3 mb-10">
          <v-avatar size="40" color="accent" variant="flat">
            <span class="font-weight-bold" style="color: #0b1b32">B</span>
          </v-avatar>
          <span class="text-body-2" style="color: rgba(247,244,236,0.75); letter-spacing: 0.02em">
            Barangay E-Services
          </span>
        </div>

        <h1
          class="auth-layout__brand-title text-h3 text-md-h2 mb-4"
          style="color: #f7f4ec; max-width: 12ch"
        >
          {{ headline }}
        </h1>
        <hr class="auth-layout__gold-rule mb-4" />
        <p class="text-body-1" style="color: rgba(247,244,236,0.8); max-width: 38ch">
          {{ subline }}
        </p>
      </div>

      <div class="pa-8 pa-md-10 d-none d-md-block">
        <p class="text-caption" style="color: rgba(247,244,236,0.55)">
          Office hours: Mon–Fri, 8:00 AM–5:00 PM<br />
          For urgent concerns, visit the barangay hall directly.
        </p>
      </div>
    </aside>

    <!-- Form panel -->
    <main class="flex-grow-1 d-flex align-center justify-center" style="background: #f7f4ec">
      <div class="pa-6 pa-md-0" style="width: 100%; max-width: 420px">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
defineProps({
  headline: {
    type: String,
    default: 'Barangay services, without the lines.',
  },
  subline: {
    type: String,
    default:
      'Request clearances, book appointments, and track documents from home.',
  },
})

const panelStyle = {
  background: 'linear-gradient(180deg, #0b1b32 0%, #122a4a 100%)',
  flex: '0 0 auto',
  width: '100%',
}
</script>

<style scoped>
@media (min-width: 960px) {
  aside {
    width: 42% !important;
    min-height: 100vh;
  }
}
</style>
