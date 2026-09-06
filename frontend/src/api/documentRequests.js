import api from '@/services/api'   // ← use your existing configured instance

export function fetchDocumentTypes() {
  return api.get('/document-types').then((res) => res.data.data)
}

export function fetchMyDocumentRequests(params = {}) {
  return api.get('/document-requests', { params }).then((res) => res.data)
}

export function submitDocumentRequest(payload) {
  const form = new FormData()
  form.append('document_type_id', payload.documentTypeId)
  form.append('purpose', payload.purpose)

  Object.entries(payload.details || {}).forEach(([key, value]) => {
    if (value !== null && value !== '') form.append(`details[${key}]`, value)
  })

  payload.attachments.forEach((attachment, index) => {
    form.append(`attachments[${index}][file]`, attachment.file)
    if (attachment.label) form.append(`attachments[${index}][label]`, attachment.label)
  })

  return api
    .post('/document-requests', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then((res) => res.data.data)
}

export function cancelDocumentRequest(uuid) {
  return api.post(`/document-requests/${uuid}/cancel`).then((res) => res.data.data)
}