import VerifyDocument from '@/views/public/VerifyDocument.vue'

export const publicRoutes = [
  {
    path: '/verify/:trackingNumber',
    name: 'verify-document',
    component: VerifyDocument,
  },
]