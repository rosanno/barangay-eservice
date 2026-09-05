import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

import '@mdi/font/css/materialdesignicons.css'
import vuetify from './plugins/vuetify'
import './assets/styles/auth.css'
import '@/styles/document-request-tokens.css'

import App from './App.vue'
import { authRoutes } from './router/auth-routes'
import { adminRoutes } from './router/admin-routes'
import { documentRoutes } from './router/document-routes.js'
import { residentRoutes } from './router/resident-routes.js'

const routes = [
  { path: '/', redirect: '/login' },
  ...authRoutes,
  ...adminRoutes,
  ...residentRoutes,
  ...documentRoutes,
  // ...your other routes (dashboard, admin, etc.) go here
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

app.mount('#app')