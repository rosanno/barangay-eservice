import api from '@/services/api'

// Same style as the rest of your API files: named exports returning res.data.data.

export function getProfile() {
  return api.get('/profile').then((res) => res.data.data)
}

export function updateProfile(payload) {
  return api.patch('/profile', payload).then((res) => res.data.data)
}

export function changePassword(payload) {
  return api.put('/profile/password', payload).then((res) => res.data.data)
}