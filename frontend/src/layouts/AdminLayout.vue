<template>
  <v-app>
    <v-navigation-drawer
      v-model="drawerOpen"
      :permanent="mdAndUp"
      :temporary="!mdAndUp"
      width="264"
      style="background: #0b1b32; border: none"
    >
      <div class="pa-6 d-flex align-center ga-3">
        <v-avatar size="36" color="accent" variant="flat">
          <span class="font-weight-bold" style="color: #0b1b32">B</span>
        </v-avatar>
        <div>
          <div class="auth-layout__brand-title text-body-1" style="color: #f7f4ec">
            Barangay
          </div>
          <div class="text-caption" style="color: rgba(247,244,236,0.6)">
            E-Services Admin
          </div>
        </div>
      </div>

      <v-divider style="border-color: rgba(247,244,236,0.1)" />

      <v-list nav class="pa-3" bg-color="transparent">
        <v-list-item
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          rounded="sm"
          class="mb-1"
          :active="isActive(item.to)"
          :prepend-icon="item.icon"
          :title="item.label"
          style="color: rgba(247,244,236,0.85)"
          active-color="accent"
        />
      </v-list>

      <template #append>
        <v-divider style="border-color: rgba(247,244,236,0.1)" />
        <div class="pa-3">
          <v-list-item
            prepend-icon="mdi-logout"
            title="Sign out"
            rounded="sm"
            style="color: rgba(247,244,236,0.85)"
            @click="handleLogout"
          />
        </div>
      </template>
    </v-navigation-drawer>

    <v-app-bar flat height="72" style="background: #f7f4ec; border-bottom: 1px solid #e6e1d3">
      <v-app-bar-nav-icon
        v-if="!mdAndUp"
        color="#0b1b32"
        @click="drawerOpen = !drawerOpen"
      />
      <v-toolbar-title class="auth-layout__form-title" style="color: #1c1c1c">
        {{ pageTitle }}
      </v-toolbar-title>
      <v-spacer />
      <v-menu>
        <template #activator="{ props }">
          <div v-bind="props" class="d-flex align-center ga-3 mr-2" style="cursor: pointer">
            <div class="text-right d-none d-sm-block">
              <div class="text-body-2 font-weight-medium" style="color: #1c1c1c">
                {{ authStore.user?.name }}
              </div>
              <div class="text-caption" style="color: #8a7f5f; text-transform: capitalize">
                {{ authStore.user?.role }}
              </div>
            </div>
            <v-avatar size="36" color="primary">
              <span style="color: #f7f4ec">{{ initials }}</span>
            </v-avatar>
          </div>
        </template>
        <v-list density="compact">
          <v-list-item title="Profile" prepend-icon="mdi-account-outline" to="/admin/profile" />
          <v-list-item title="Sign out" prepend-icon="mdi-logout" @click="handleLogout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main style="background: #f7f4ec">
      <div class="pa-4 pa-md-8">
        <router-view />
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useDisplay } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const { mdAndUp } = useDisplay()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const drawerOpen = ref(true)

const navItems = [
  { label: 'Dashboard', to: '/admin/dashboard', icon: 'mdi-view-dashboard-outline' },
  { label: 'Residents', to: '/admin/residents', icon: 'mdi-account-group-outline' },
  { label: 'Clearance requests', to: '/admin/clearances', icon: 'mdi-file-document-outline' },
  { label: 'Appointments', to: '/admin/appointments', icon: 'mdi-calendar-check-outline' },
  { label: 'Document tracking', to: '/admin/documents', icon: 'mdi-folder-outline' },
  { label: 'AI assistant', to: '/admin/ai-assistant', icon: 'mdi-message-text-outline' },
  { label: 'Staff & admins', to: '/admin/users', icon: 'mdi-shield-account-outline' },
]

const pageTitle = computed(() => {
  const match = navItems.find((item) => route.path.startsWith(item.to))
  return match?.label || 'Dashboard'
})

const initials = computed(() => {
  const name = authStore.user?.name || ''
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
})

function isActive(to) {
  return route.path.startsWith(to)
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
