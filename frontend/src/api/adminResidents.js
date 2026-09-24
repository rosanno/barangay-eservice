import api from '@/services/api'

export function searchResidents(search) {
  if (!search?.trim()) return Promise.resolve([])
  return api.get('/admin/residents/lookup', { params: { search } }).then((res) => res.data.data)
}

/**
 * Full paginated residents list, for the Residents management page —
 * distinct from searchResidents() above, which is the lightweight
 * typeahead used by the new-request resident picker.
 */
export function fetchAdminResidents(params = {}) {
  return api.get('/admin/residents', { params }).then((res) => res.data)
}

/**
 * payload carries every field from the registration form — personal,
 * family background, emergency contact, and account credentials — in one
 * flat object, matching StoreResidentRequest's validation on the backend.
 */
export function createResident(payload) {
  return api.post('/admin/residents', payload).then((res) => res.data.data)
}