import api from '@/services/api'

export const getAppointmentReport = async (params = {}) => {
  const res = await api.get('/admin/reports/appointments', { params })
  return res.data.data
}

export const getAppointmentSummary = async (params = {}) => {
  const res = await api.get('/admin/reports/appointments/summary', { params })
  return res.data.data
}

// Returns a Blob (CSV file), not res.data.data
export const exportAppointmentReport = async (params = {}) => {
  const res = await api.get('/admin/reports/appointments/export', {
    params,
    responseType: 'blob',
  })
  return res.data
}