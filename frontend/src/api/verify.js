import api from '@/services/api'

/**
 * Deliberately a plain call, not going through anything that expects an
 * auth token to be present — this page is reachable by someone with no
 * account at all.
 */
export function verifyTrackingNumber(trackingNumber) {
  return api.get(`/verify/${trackingNumber}`)
}