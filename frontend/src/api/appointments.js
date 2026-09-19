import api from '@/services/api'

export function fetchMyAppointments() {
  return api.get('/appointments').then((res) => res.data.data)
}

export function bookAppointment(payload) {
  return api
    .post('/appointments', {
      purpose: payload.purpose,
      scheduled_at: payload.scheduledAt,
    })
    .then((res) => res.data.data)
}

export function cancelAppointment(uuid) {
  return api.post(`/appointments/${uuid}/cancel`).then((res) => res.data.data)
}