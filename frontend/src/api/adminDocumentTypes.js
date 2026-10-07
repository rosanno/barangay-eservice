import api from '@/services/api'

export function fetchAdminDocumentTypes(params = {}) {
  return api.get('/admin/document-types', { params }).then((res) => res.data.data)
}

export function createDocumentType(payload) {
  return api.post('/admin/document-types', payload).then((res) => res.data.data)
}

export function updateDocumentType(id, payload) {
  return api.patch(`/admin/document-types/${id}`, payload).then((res) => res.data.data)
}