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
import { useAuthStore } from '@/store/auth'   // ← add this

const routes = [
  ...authRoutes,
  ...adminRoutes,
  ...residentRoutes,
  ...documentRoutes,
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return next('/dashboard')
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return next('/dashboard')
  }

  next()
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

app.mount('#app')