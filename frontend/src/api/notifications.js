import api from '@/services/api'

export function fetchNotifications() {
    return api.get('/notifications').then((res) => res.data)
}

export function markNotificationRead(id) {
    return api.post(`/notifications/${id}/read`).then((res) => res.data.data)
}

export function markAllNotificationsRead() {
    return api.post('/notifications/read-all').then((res) => res.data.data)
}