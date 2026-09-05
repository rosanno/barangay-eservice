// routes/resident.js
//
// Add to your existing router/index.js routes array, alongside
// authRoutes and adminRoutes.

import ResidentLayout from '@/layouts/ResidentLayout.vue'
import ResidentDashboard from '@/views/resident/Dashboard.vue'

export const residentRoutes = [
  {
    path: '/',
    component: ResidentLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: ResidentDashboard,
        meta: { title: 'Dashboard', subtitle: 'Overview of barangay e-services' },
      },
      {
        path: 'documents/request',
        name: 'documents-request',
        // Move src/pages/documents/RequestDocumentPage.vue to
        // src/views/resident/RequestDocument.vue to match this project's
        // views/ convention, then point this import at it.
        component: () => import('@/pages/documents/RequestDocumentPage.vue'),
        meta: { title: 'Request a document', subtitle: 'Submit a new document request' },
      },
      // Add as you build them out:
      // { path: 'documents', name: 'documents', component: () => import('@/views/resident/MyRequests.vue'), meta: { title: 'My requests' } },
      // { path: 'documents/track', name: 'documents-track', component: () => import('@/views/resident/TrackRequest.vue'), meta: { title: 'Track a request' } },
      // { path: 'profile', name: 'profile', component: () => import('@/views/resident/Profile.vue'), meta: { title: 'Profile' } },
      // { path: 'settings', name: 'settings', component: () => import('@/views/resident/Settings.vue'), meta: { title: 'Settings' } },
    ],
  },
]
