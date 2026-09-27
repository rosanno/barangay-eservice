import api from '@/services/api'

export function fetchAdminReports() {
  return api.get('/admin/reports').then((res) => res.data)
}