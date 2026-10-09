import api from '@/services/api'

export const getMyRequests = async (params = {}) => {
  const res = await api.get('/resident/requests', { params })
  return res.data.data
}

export const getMyRequest = async (id) => {
  const res = await api.get(`/resident/requests/${id}`)
  return res.data.data
}

export const createRequest = async (payload) => {
  const res = await api.post('/resident/requests', payload)
  return res.data.data
}

export const cancelRequest = async (id) => {
  const res = await api.post(`/resident/requests/${id}/cancel`)
  return res.data.data
}