<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth' 

const router = useRouter()
const auth = useAuthStore()

const links = [
  { title: 'Household Survey', icon: 'mdi-clipboard-list-outline', to: '/staff/household-surveys' },
]

async function logout() {
  await auth.logout() 
  router.push('/login')
}
</script>

<template>
  <v-app>
    <v-app-bar flat border color="white">
      <v-app-bar-title class="font-weight-bold" style="color:#0B1F4B">Barangay E-Services · Staff</v-app-bar-title>
      <v-btn v-for="l in links" :key="l.to" :to="l.to" :prepend-icon="l.icon" variant="text">{{ l.title }}</v-btn>
      <v-btn v-if="auth.user?.role === 'admin'" to="/admin" variant="text" prepend-icon="mdi-shield-account-outline">Admin</v-btn>
      <span class="text-body-2 text-medium-emphasis mx-3">{{ auth.user?.name }}</span>
      <v-btn icon="mdi-logout" variant="text" title="Log out" @click="logout" />
    </v-app-bar>

    <v-main style="background:#F5F6FA">
      <router-view />
    </v-main>
  </v-app>
</template>