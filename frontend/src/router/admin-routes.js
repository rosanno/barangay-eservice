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

      // Residents
      { path: 'residents', name: 'admin-residents', component: () => import('@/views/admin/Residents.vue') },
      { path: 'residents/new', name: 'admin-residents-new', component: () => import('@/views/admin/NewResident.vue') },

      // Households
      {
        path: 'households',
        name: 'admin-households',
        component: () => import('@/views/admin/Households.vue'),
        meta: { roles: ['admin', 'staff'] },
      },
      { path: 'households/new', name: 'admin-household-new', component: () => import('@/views/admin/HouseholdForm.vue') },
      { path: 'households/:id/edit', name: 'admin-household-edit', component: () => import('@/views/admin/HouseholdForm.vue') },

      // Document services
      { path: 'requests', name: 'admin-clearances', component: () => import('@/views/admin/Requests.vue') },
      { path: 'requests/new', name: 'admin-clearances-new', component: () => import('@/views/admin/NewClearance.vue') },
      { path: 'documents', name: 'admin-documents', component: () => import('@/views/admin/Documents.vue') },

      // Appointments
      { path: 'appointments', name: 'admin-appointments', component: () => import('@/views/admin/Appointments.vue') },

      // Reports
      { path: 'reports', redirect: '/admin/reports/requests' }, // keeps old links working
      { path: 'reports/requests', name: 'admin-reports', component: () => import('@/views/admin/Reports.vue') },
      { path: 'reports/demographics', name: 'admin-reports-demographics', component: () => import('@/views/admin/DemographicsPage.vue') },
      { path: 'reports/appointments', name: 'admin-reports-appointments', component: () => import('@/views/admin/AppointmentReport.vue') },

      // Enable when built (path must match the nav: "assistant")
      // { path: 'assistant', name: 'admin-ai-assistant', component: () => import('@/views/admin/AiAssistant.vue') },

      // System
      { path: 'users', name: 'admin-staffadmins', component: () => import('@/views/admin/Staffadmins.vue') },
      { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/Settings.vue') },
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
