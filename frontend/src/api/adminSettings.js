import api from '@/services/api'

export function fetchBarangaySettings() {
    return api.get('/admin/settings').then((res) => res.data.data)
}

export function updateBarangaySettings(payload) {
    return api.put('/admin/settings', payload).then((res) => res.data.data)
}