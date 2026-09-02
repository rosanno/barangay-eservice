// Add to your existing router/index.js routes array,
// and apply the guard shown at the bottom.

import Login from '@/views/auth/Login.vue'
import Register from '@/views/auth/Register.vue'

export const authRoutes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
    meta: { guestOnly: true },
  },
]

// --- Navigation guard (register with router.beforeEach) ---
//
// import { useAuthStore } from '@/store/auth'
//
// router.beforeEach((to, from, next) => {
//   const auth = useAuthStore()
//
//   if (to.meta.guestOnly && auth.isAuthenticated) {
//     return next('/dashboard')
//   }
//
//   if (to.meta.requiresAuth && !auth.isAuthenticated) {
//     return next('/login')
//   }
//
//   if (to.meta.requiresAdmin && !auth.isAdmin) {
//     return next('/dashboard')
//   }
//
//   next()
// })
