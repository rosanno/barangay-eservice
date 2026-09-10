<template>
  <v-app>
    <!-- ─── Sidebar ─────────────────────────────────────────────── -->
    <v-navigation-drawer
      v-model="drawerOpen"
      :permanent="mdAndUp"
      :temporary="!mdAndUp"
      width="232"
      style="background: #0f1e3d; border-right: none"
    >
      <!-- Brand -->
      <div class="px-4 pt-5 pb-4">
        <div class="d-flex align-center ga-3">
          <div
            class="d-flex align-center justify-center"
            style="
              width: 36px;
              height: 36px;
              background: #f5a623;
              border-radius: 8px;
              flex-shrink: 0;
            "
          >
            <v-icon icon="mdi-building-columns" size="18" style="color: #0f1e3d" />
          </div>
          <div>
            <div style="color: #ffffff; font-size: 13px; font-weight: 600; line-height: 1.2">
              Brgy. San Roque
            </div>
            <div style="color: rgba(255,255,255,0.45); font-size: 11px; margin-top: 1px">
              E-Services Portal
            </div>
          </div>
        </div>
      </div>

      <div style="border-top: 1px solid rgba(255,255,255,0.08); margin: 0 16px" />

      <!-- Nav groups -->
      <div class="px-2 pt-3">
        <div class="nav-group-label">Overview</div>
        <v-list nav density="compact" bg-color="transparent" class="pa-0">
          <v-list-item
            v-for="item in primaryNav"
            :key="item.to"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.label"
            rounded="sm"
            class="nav-item mb-1"
            :class="{ 'nav-item--active': isActive(item.to) }"
          >
            <template v-if="item.badge" #append>
              <span class="nav-badge">{{ item.badge }}</span>
            </template>
          </v-list-item>
        </v-list>
      </div>

      <div class="px-2 pt-2">
        <div class="nav-group-label">Services</div>
        <v-list nav density="compact" bg-color="transparent" class="pa-0">
          <v-list-item
            v-for="item in serviceNav"
            :key="item.to"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.label"
            rounded="sm"
            class="nav-item mb-1"
            :class="{ 'nav-item--active': isActive(item.to) }"
          >
            <template v-if="item.badge" #append>
              <span class="nav-badge">{{ item.badge }}</span>
            </template>
          </v-list-item>
        </v-list>
      </div>

      <div class="px-2 pt-2">
        <div class="nav-group-label">Admin</div>
        <v-list nav density="compact" bg-color="transparent" class="pa-0">
          <v-list-item
            v-for="item in adminNav"
            :key="item.to"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.label"
            rounded="sm"
            class="nav-item mb-1"
            :class="{ 'nav-item--active': isActive(item.to) }"
          />
        </v-list>
      </div>

      <!-- Footer -->
      <template #append>
        <div style="border-top: 1px solid rgba(255,255,255,0.08); margin: 0 16px" />
        <div class="px-2 py-3">
          <v-list-item
            prepend-icon="mdi-logout"
            title="Sign out"
            rounded="sm"
            class="nav-item"
            style="color: rgba(255,255,255,0.45)"
            @click="handleLogout"
          />
        </div>
      </template>
    </v-navigation-drawer>

    <!-- ─── Top bar ───────────────────────────────────────────────── -->
    <v-app-bar
      flat
      height="56"
      style="background: #ffffff; border-bottom: 1px solid #e8e3d8"
    >
      <v-app-bar-nav-icon
        v-if="!mdAndUp"
        color="#0f1e3d"
        @click="drawerOpen = !drawerOpen"
      />

      <div class="pl-2">
        <div style="color: #1a1a1a; font-size: 15px; font-weight: 600">
          {{ pageTitle }}
        </div>
        <div style="color: #888; font-size: 11px; margin-top: 1px">
          {{ pageSubtitle }}
        </div>
      </div>

      <v-spacer />

      <!-- New request shortcut -->
      <v-btn
        variant="outlined"
        size="small"
        class="mr-2"
        style="
          border-color: #e8e3d8;
          color: #555;
          font-size: 12px;
          text-transform: none;
          letter-spacing: 0;
        "
        prepend-icon="mdi-plus"
        :to="'/admin/clearances/new'"
      >
        New request
      </v-btn>

      <!-- Notifications -->
      <v-btn
        icon
        variant="text"
        class="mr-1"
        style="color: #888"
      >
        <v-badge color="#e8523a" dot>
          <v-icon icon="mdi-bell-outline" size="20" />
        </v-badge>
      </v-btn>

      <!-- User menu -->
      <v-menu>
        <template #activator="{ props }">
          <div
            v-bind="props"
            class="d-flex align-center ga-2 mr-3"
            style="cursor: pointer"
          >
            <v-avatar
              size="30"
              style="background: #0f1e3d; font-size: 11px; font-weight: 600"
            >
              <span style="color: #f5a623">{{ initials }}</span>
            </v-avatar>
          </div>
        </template>
        <v-list density="compact" width="160" class="tight-menu">
          <v-list-item
            title="Profile"
            prepend-icon="mdi-account-outline"
            :to="'/admin/profile'"
          />
          <v-divider />
          <v-list-item
            title="Sign out"
            prepend-icon="mdi-logout"
            @click="handleLogout"
          />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- ─── Main content ───────────────────────────────────────────── -->
    <v-main style="background: #f4f2ed">
      <div class="pa-5 pa-md-7">
        <router-view />
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useDisplay } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchAdminDocumentRequests } from '@/api/adminDocumentRequests'
import { fetchAdminAppointments } from '@/api/adminAppointments'

const { mdAndUp } = useDisplay()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const drawerOpen = ref(true)

// Real counts, replacing the hardcoded 12 / 5 that used to sit directly in
// the nav arrays below. 0 is falsy, so the badge <template v-if="item.badge">
// in the markup above already hides it automatically once a queue is clear
// — no extra null-coercion needed.
const pendingRequestsCount = ref(0)
const todaysAppointmentsCount = ref(0)

async function loadBadgeCounts() {
  try {
    const { meta } = await fetchAdminDocumentRequests({ status: 'pending', per_page: 1 })
    pendingRequestsCount.value = meta?.total ?? 0
  } catch {
    pendingRequestsCount.value = 0
  }

  try {
    // Only "scheduled" (not yet completed/cancelled) counts as something
    // still needing attention today.
    const data = await fetchAdminAppointments({ status: 'scheduled' })
    todaysAppointmentsCount.value = data.length
  } catch {
    todaysAppointmentsCount.value = 0
  }
}

onMounted(loadBadgeCounts)

const primaryNav = computed(() => [
  { label: 'Dashboard', to: '/admin/dashboard', icon: 'mdi-view-dashboard-outline' },
  {
    label: 'Requests',
    to: '/admin/clearances',
    icon: 'mdi-file-document-outline',
    badge: pendingRequestsCount.value,
  },
])

const serviceNav = computed(() => [
  { label: 'Clearance', to: '/admin/clearances/new', icon: 'mdi-certificate-outline' },
  {
    label: 'Appointments',
    to: '/admin/appointments',
    icon: 'mdi-calendar-check-outline',
    badge: todaysAppointmentsCount.value,
  },
  { label: 'Doc tracking', to: '/admin/documents', icon: 'mdi-map-marker-outline' },
])

const adminNav = [
  { label: 'Residents', to: '/admin/residents', icon: 'mdi-account-group-outline' },
  { label: 'Reports', to: '/admin/reports', icon: 'mdi-chart-bar' },
  { label: 'Staff & admins', to: '/admin/users', icon: 'mdi-shield-account-outline' },
  { label: 'Settings', to: '/admin/settings', icon: 'mdi-cog-outline' },
]

const pageMeta = {
  '/admin/dashboard': ['Dashboard', 'Overview of barangay e-services'],
  '/admin/clearances': ['Document Requests', 'Manage and process incoming requests'],
  '/admin/clearances/new': ['New Clearance', 'Submit a clearance application'],
  '/admin/appointments': ['Appointments', 'Book and manage resident appointments'],
  '/admin/documents': ['Document Tracking', 'Track real-time document status'],
  '/admin/residents': ['Residents', 'Registered barangay residents'],
  '/admin/reports': ['Reports', 'Analytics and statistics'],
  '/admin/users': ['Staff & Admins', 'Manage system users'],
  '/admin/settings': ['Settings', 'System configuration'],
}

const pageTitle = computed(() => {
  const match = Object.entries(pageMeta).find(([key]) => route.path.startsWith(key))
  return match?.[1][0] || 'Dashboard'
})

const pageSubtitle = computed(() => {
  const match = Object.entries(pageMeta).find(([key]) => route.path.startsWith(key))
  return match?.[1][1] || ''
})

const initials = computed(() => {
  const name = authStore.user?.name || ''
  return name
    .split(' ')
    .map((p) => p[0])
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

<style scoped src="./AdminLayoutCss.css">
</style>