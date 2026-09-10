import api from '@/services/api'

/**
 * Admin/staff-scoped. Defaults to today; pass { date: 'YYYY-MM-DD' } for
 * another day, and/or { status: 'scheduled' } to filter.
 */
export function fetchAdminAppointments(params = {}) {
  return api.get('/admin/appointments', { params }).then((res) => res.data.data)
}

export function updateAdminAppointmentStatus(uuid, payload) {
  return api.patch(`/admin/appointments/${uuid}/status`, payload).then((res) => res.data.data)
}