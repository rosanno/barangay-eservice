export const staffRoutes = [
  {
    path: '/staff',
    component: () => import('@/layouts/StaffLayout.vue'),
    meta: { requiresAuth: true, roles: ['staff', 'admin'] },
    redirect: '/staff/census-surveys',
    children: [
      {
        path: 'census-surveys',
        name: 'staff-census-surveys',
        component: () => import('@/views/staff/HouseholdSurveyList.vue'),
      },
      {
        path: 'census-surveys/:id(\\d+)',
        name: 'staff-census-survey',
        component: () => import('@/views/staff/HouseholdSurveyForm.vue'),
      },
    ],
  },
]