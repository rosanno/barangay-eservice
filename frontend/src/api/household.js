import api from '@/services/api'

// ── Resident ────────────────────────────────────────────────
export const getMyHousehold = () => api.get('/household').then((res) => res.data.data)
export const createMyHousehold = (payload) => api.post('/household', payload).then((res) => res.data.data)
export const updateMyHousehold = (payload) => api.put('/household', payload).then((res) => res.data.data)

// ── Admin ───────────────────────────────────────────────────
// Returns { items, total, page, per_page, last_page }
export const getHouseholds = (params = {}) =>
  api.get('/admin/households', { params }).then((res) => res.data.data)

export const getHousehold = (id) => api.get(`/admin/households/${id}`).then((res) => res.data.data)

export const getHouseholdStats = () => api.get('/admin/households/stats').then((res) => res.data.data)

export const updateHouseholdStatus = (id, status, remarks = null) =>
  api.patch(`/admin/households/${id}/status`, { status, remarks }).then((res) => res.data.data)

export const deleteHousehold = (id) => api.delete(`/admin/households/${id}`).then((res) => res.data)

// Returns a Blob (the endpoint needs the Bearer token, so a plain <a href> won't work)
export const exportHouseholds = () =>
  api.get('/admin/households/export', { responseType: 'blob' }).then((res) => res.data)
