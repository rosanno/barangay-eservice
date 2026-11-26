import api from '@/services/api'

const base = '/household-surveys'

export const getSurveyOptions = () => api.get(`${base}/options`).then((res) => res.data.data)
export const getSurveyTargets = (params = {}) => api.get(base, { params }).then((res) => res.data.data)
export const startSurvey = (targetId) =>
  api.post(base, { census_campaign_household_id: targetId }).then((res) => res.data.data)
export const getSurvey = (id) => api.get(`${base}/${id}`).then((res) => res.data.data)
export const saveSurvey = (id, payload) => api.put(`${base}/${id}`, payload).then((res) => res.data.data)
export const submitSurvey = (id) => api.post(`${base}/${id}/submit`).then((res) => res.data.data)
export const getSurveyComparison = (id) => api.get(`${base}/${id}/comparison`).then((res) => res.data.data)
export const verifySurvey = (id, payload) => api.post(`${base}/${id}/verify`, payload).then((res) => res.data.data)
export const searchResidents = (q) => api.get(`${base}/residents`, { params: { q } }).then((res) => res.data.data)

// Admin verification queue → { surveys: paginator, counts: { for_verification, needs_revision, approved } }
export const getVerificationQueue = (params = {}) =>
  api.get('/admin/household-surveys/verification', { params }).then((res) => res.data.data)