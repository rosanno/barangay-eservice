import api from '@/services/api'

export function getPreferences() {
  return api.get('/preferences').then((res) => res.data.data)
}

export function updatePreferences(payload) {
  return api.patch('/preferences', payload).then((res) => res.data.data)
}

export function signOutOtherDevices() {
  return api.post('/preferences/sign-out-others').then((res) => res.data.data)
}