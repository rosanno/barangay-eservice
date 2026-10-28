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
      {
        path: 'appointments',
        name: 'appointments',
        component: () => import('@/views/resident/BookAppointment.vue'),
        meta: { title: 'Appointments', subtitle: 'Book and manage your appointments' },
      },
      {
        path: 'assistant',
        name: 'assistant',
        component: () => import('@/views/AssistantView.vue'),
        meta: { requiresAuth: true }, // match whatever guard your other resident routes use
      },
      // Add as you build them out:
      { path: 'my-requests', name: 'my-requests', component: () => import('@/views/resident/MyRequests.vue'), meta: { title: 'My requests', subtitle: 'Track your document requests' } },
      { path: 'my-requests/:id', name: 'document-request-detail', component: () => import('@/views/resident/RequestDetail.vue'), meta: { title: 'Request details', subtitle: 'Track your document requests' } },
      { path: 'documents/track', name: 'documents-track', component: () => import('@/views/resident/TrackRequest.vue'), meta: { title: 'Track a request' } },
      { path: 'profile', name: 'profile', component: () => import('@/views/resident/Profile.vue'), meta: { title: 'Profile', subtitle: 'View and update your profile' } },
      { path: 'settings', name: 'settings', component: () => import('@/views/resident/ResidentSettings.vue'), meta: { title: 'Settings', subtitle: 'Manage your preferences' } },
    ],
  },
]
