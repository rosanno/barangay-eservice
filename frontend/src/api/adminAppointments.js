import api from '@/services/api'

/**
 * Admin/staff-scoped. Defaults to today; pass { date: 'YYYY-MM-DD' } for
 * another day once you build a fuller appointments page.
 */
export function fetchAdminAppointments(params = {}) {
  return api.get('/admin/appointments', { params }).then((res) => res.data.data)
}