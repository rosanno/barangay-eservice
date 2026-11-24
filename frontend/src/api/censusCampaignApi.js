import api from '@/services/api'

export const getCampaigns = (params = {}) =>
  api.get('/admin/census-campaigns', { params }).then((res) => res.data.data)

export const getCampaign = (id) =>
  api.get(`/admin/census-campaigns/${id}`).then((res) => res.data.data)

export const getCampaignOptions = () =>
  api.get('/admin/census-campaigns/options').then((res) => res.data.data)

export const createCampaign = (payload) =>
  api.post('/admin/census-campaigns', payload).then((res) => res.data.data)

export const updateCampaign = (id, payload) =>
  api.put(`/admin/census-campaigns/${id}`, payload).then((res) => res.data.data)

export const updateCampaignStatus = (id, status) =>
  api.patch(`/admin/census-campaigns/${id}/status`, { status }).then((res) => res.data.data)

export const deleteCampaign = (id) =>
  api.delete(`/admin/census-campaigns/${id}`).then((res) => res.data)