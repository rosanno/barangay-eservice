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