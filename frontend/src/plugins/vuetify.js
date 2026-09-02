import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

const barangayLight = {
  dark: false,
  colors: {
    background: '#F7F4EC',
    surface: '#FFFFFF',
    primary: '#0B1B32',   // deep navy
    secondary: '#122A4A', // raised navy panel
    accent: '#C9A227',    // muted gold
    error: '#8A2E2E',
    'on-primary': '#F7F4EC',
    'on-background': '#1C1C1C',
    'on-surface': '#1C1C1C',
  },
}

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'barangayLight',
    themes: { barangayLight },
  },
  defaults: {
    VBtn: { rounded: 'sm', style: 'letter-spacing: 0.01em;' },
    VTextField: { variant: 'outlined', color: 'primary', density: 'comfortable' },
  },
})
