import api from '@/services/api'

/**
 * Admin/staff-scoped: all residents' document requests, not just the
 * current user's. Hits GET /api/admin/document-requests, guarded by
 * DocumentRequestPolicy::viewAny() on the backend.
 */
export function fetchAdminDocumentRequests(params = {}) {
  return api.get('/admin/document-requests', { params }).then((res) => res.data)
}