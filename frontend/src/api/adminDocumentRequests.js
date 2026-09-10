import api from '@/services/api'

/**
 * Admin/staff-scoped: all residents' document requests, not just the
 * current user's. Hits GET /api/admin/document-requests, guarded by
 * DocumentRequestPolicy::viewAny() on the backend.
 */
export function fetchAdminDocumentRequests(params = {}) {
  return api.get('/admin/document-requests', { params }).then((res) => res.data)
}

/**
 * Move a request to a new status. The backend enforces which transitions
 * are actually allowed from the request's current status (see
 * DocumentRequestStatus::allowedTransitions() / DocumentRequestService) —
 * an invalid jump comes back as a 422, not a silent no-op.
 */
export function updateAdminDocumentRequestStatus(uuid, payload) {
  return api.patch(`/admin/document-requests/${uuid}/status`, payload).then((res) => res.data.data)
}

/**
 * Admin creating a request on behalf of a resident (e.g. a walk-in at the
 * barangay hall) — distinct from the resident's own self-service submit,
 * since this one names who the request is for via residentId.
 */
export function createAdminDocumentRequest(payload) {
  const form = new FormData()
  form.append('resident_id', payload.residentId)
  form.append('document_type_id', payload.documentTypeId)
  form.append('purpose', payload.purpose)
 
  Object.entries(payload.details || {}).forEach(([key, value]) => {
    if (value !== null && value !== '') form.append(`details[${key}]`, value)
  })
 
  ;(payload.attachments || []).forEach((attachment, index) => {
    form.append(`attachments[${index}][file]`, attachment.file)
    if (attachment.label) form.append(`attachments[${index}][label]`, attachment.label)
  })
 
  return api
    .post('/admin/document-requests', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then((res) => res.data.data)
}