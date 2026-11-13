import api from '@/services/api'

export async function getDemographics(params = {}) {
  const res = await api.get('/admin/reports/demographics', { params })
  return res.data.data
}