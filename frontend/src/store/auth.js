import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
    token: localStorage.getItem('auth_token') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isStaff: (state) => state.user?.role === 'staff',
  },

  actions: {
    persist(user, token) {
      this.user = user
      this.token = token
      localStorage.setItem('auth_user', JSON.stringify(user))
      localStorage.setItem('auth_token', token)
    },

    async login(credentials) {
      const { data } = await api.post('/login', credentials)
      this.persist(data.user, data.token)
      return data.user
    },

    async register(payload) {
      const { data } = await api.post('/register', payload)
      this.persist(data.user, data.token)
      return data.user
    },

    async logout() {
      try {
        await api.post('/logout')
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('auth_user')
        localStorage.removeItem('auth_token')
      }
    },

    async fetchCurrentUser() {
      const { data } = await api.get('/user')
      this.user = data
      localStorage.setItem('auth_user', JSON.stringify(data))
      return data
    },
  },
})
