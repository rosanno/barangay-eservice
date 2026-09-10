import api from '@/services/api'

export function searchResidents(search) {
  if (!search?.trim()) return Promise.resolve([])
  return api.get('/admin/residents/lookup', { params: { search } }).then((res) => res.data.data)
}