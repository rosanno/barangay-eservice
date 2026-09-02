import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminDashboard from '@/views/admin/Dashboard.vue'

export const adminRoutes = [
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      { path: '', redirect: '/admin/dashboard' },
      { path: 'dashboard', name: 'admin-dashboard', component: AdminDashboard },
      // Add as you build them out:
      // { path: 'residents', name: 'admin-residents', component: () => import('@/views/admin/Residents.vue') },
      // { path: 'clearances', name: 'admin-clearances', component: () => import('@/views/admin/Clearances.vue') },
      // { path: 'appointments', name: 'admin-appointments', component: () => import('@/views/admin/Appointments.vue') },
      // { path: 'documents', name: 'admin-documents', component: () => import('@/views/admin/Documents.vue') },
      // { path: 'ai-assistant', name: 'admin-ai-assistant', component: () => import('@/views/admin/AiAssistant.vue') },
      // { path: 'users', name: 'admin-users', component: () => import('@/views/admin/Users.vue') },
    ],
  },
]

// requiresAdmin here also matches the "staff" role, since staff needs the
// same dashboard shell — refine the guard if staff should see a reduced
// nav or different landing page:
//
// if (to.meta.requiresAdmin && !['admin', 'staff'].includes(auth.user?.role)) {
//   return next('/dashboard')
// }
