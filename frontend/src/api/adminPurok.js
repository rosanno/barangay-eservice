import api from '@/services/api'

export const getPuroks = (params = {}) =>
  api.get('/admin/puroks', { params }).then((res) => res.data.data)

export const createPurok = (payload) =>
  api.post('/admin/puroks', payload).then((res) => res.data.data)

export const updatePurok = (uuid, payload) =>
  api.put(`/admin/puroks/${uuid}`, payload).then((res) => res.data.data)

export const deletePurok = (uuid) =>
  api.delete(`/admin/puroks/${uuid}`).then((res) => res.data)

// For resident/admin household forms
export const getPurokOptions = () =>
  api.get('/puroks').then((res) => res.data.data)