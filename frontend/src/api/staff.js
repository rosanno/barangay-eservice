import api from '@/services/api'

// List returns the whole body: { data: [...], meta: {...}, summary: {...} }
export function fetchStaff(params) {
  return api.get('/admin/users', { params }).then((res) => res.data)
}

// Create returns { data: {...user}, temporary_password } (password is shown once)
export function createStaff(payload) {
  return api.post('/admin/users', payload).then((res) => res.data)
}

export function updateStaff(id, payload) {
  return api.put(`/admin/users/${id}`, payload).then((res) => res.data.data)
}

export function setStaffStatus(id, isActive) {
  return api
    .patch(`/admin/users/${id}/status`, { is_active: isActive })
    .then((res) => res.data.data)
}

// Returns { temporary_password }
export function resetStaffPassword(id) {
  return api.post(`/admin/users/${id}/reset-password`).then((res) => res.data)
}

export function deleteStaff(id) {
  return api.delete(`/admin/users/${id}`).then(() => true)
}