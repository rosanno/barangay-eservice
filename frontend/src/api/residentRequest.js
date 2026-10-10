import api from '@/services/api'

export const getMyRequests = async (params = {}) => {
  const res = await api.get('/document-requests', { params })
  return res.data.data
}

export const getMyRequest = async (id) => {
  const res = await api.get(`/document-requests/${id}`)
  return res.data.data
}

export const createRequest = async (payload) => {
  const res = await api.post('/document-requests', payload)
  return res.data.data
}

export const cancelRequest = async (id) => {
  const res = await api.post(`/document-requests/${id}/cancel`)
  return res.data.data
}