// routes/documents.js
//
// Add to your existing router/index.js routes array.

import RequestDocument from '@/pages/documents/RequestDocumentPage.vue'

export const documentRoutes = [
  {
    path: '/documents/request',
    name: 'documents-request',
    component: RequestDocument,
    meta: { requiresAuth: true },
  },
  // As you build them out:
  // { path: '/documents', name: 'documents-history', component: () => import('@/views/documents/History.vue') },
]