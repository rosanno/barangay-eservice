import axios from 'axios'

/**
 * Adjust this to reuse your project's existing configured axios instance
 * (e.g. one that already attaches the Sanctum bearer token / base URL)
 * instead of creating a second one here.
 */
const http = axios.create({
  baseURL: '/api',
})

export function fetchDocumentTypes() {
  return http.get('/document-types').then((res) => res.data.data)
}

export function fetchMyDocumentRequests(params = {}) {
  return http.get('/document-requests', { params }).then((res) => res.data)
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

  return http
    .post('/document-requests', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then((res) => res.data.data)
}

export function cancelDocumentRequest(uuid) {
  return http.post(`/document-requests/${uuid}/cancel`).then((res) => res.data.data)
}
