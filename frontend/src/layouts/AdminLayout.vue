<template>
  <v-app>
    <!-- ─── Sidebar ─────────────────────────────────────────────── -->
    <v-navigation-drawer
      v-model="drawerOpen"
      :permanent="mdAndUp"
      :temporary="!mdAndUp"
      width="232"
      class="nav-drawer"
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

      <!-- Nav -->
      <div class="px-2 pt-3">
        <v-list
          v-model:opened="openedGroups"
          nav
          density="compact"
          bg-color="transparent"
          class="pa-0"
        >
          <template v-for="item in navItems" :key="item.label">
            <!-- Group with children -->
            <v-list-group v-if="item.children" :value="item.label">
              <template #activator="{ props }">
                <v-list-item
                  v-bind="props"
                  :prepend-icon="item.icon"
                  :title="item.label"
                  rounded="sm"
                  class="nav-item mb-1"
                  :class="{ 'nav-item--active': isGroupActive(item) }"
                />
              </template>

              <!-- FIX: :active takes over Vuetify's prefix-based highlighting -->
              <v-list-item
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                :active="isActive(child.to)"
                :title="child.label"
                rounded="sm"
                class="nav-item nav-item--child"
                :class="{ 'nav-item--active': isActive(child.to) }"
              >
                <template v-if="child.badge" #append>
                  <span class="nav-badge">{{ child.badge }}</span>
                </template>
              </v-list-item>
            </v-list-group>

            <!-- Single item -->
            <!-- FIX: :active here too -->
            <v-list-item
              v-else
              :to="item.to"
              :active="isActive(item.to)"
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
          </template>
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
        :to="'/admin/requests/new'"
      >
        New request
      </v-btn>

      <!-- Notifications -->
      <v-menu location="bottom end" @update:model-value="onMenuToggle" min-width="320">
        <template #activator="{ props }">
          <v-btn icon variant="text" class="mr-1" style="color: #888" v-bind="props">
            <v-badge
              :model-value="unreadCount > 0"
              :content="unreadCount > 9 ? '9+' : unreadCount"
              color="#e8523a"
            >
              <v-icon icon="mdi-bell-outline" size="20" />
            </v-badge>
          </v-btn>
        </template>

        <v-card style="max-height: 380px; overflow-y: auto">
          <div class="d-flex align-center justify-space-between px-4 py-3" style="border-bottom: 1px solid #f0ede3">
            <span style="font-size: 13px; font-weight: 600; color: #1a1a1a">Notifications</span>
            <button
              v-if="unreadCount > 0"
              type="button"
              class="mark-all-btn"
              @click="handleMarkAllRead"
            >
              Mark all read
            </button>
          </div>

          <div v-if="loadingNotifications" class="d-flex justify-center py-6">
            <v-progress-circular indeterminate size="20" color="#0f1e3d" />
          </div>

          <p v-else-if="!notifications.length" class="empty-note">
            No notifications yet.
          </p>

          <router-link
            v-for="note in notifications"
            :key="note.id"
            :to="{ path: '/admin/documents', query: { search: note.tracking_number } }"
            class="notification-item"
            :class="{ 'notification-item--unread': !note.read }"
            @click="handleNotificationClick(note)"
          >
            <span class="notification-dot" :class="{ 'notification-dot--unread': !note.read }" />
            <span class="notification-text">
              <span class="notification-message">{{ note.message }}</span>
              <span class="notification-time">{{ timeAgo(note.created_at) }}</span>
            </span>
          </router-link>
        </v-card>
      </v-menu>

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
import { computed, onMounted, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchAdminDocumentRequests } from '@/api/adminDocumentRequests'
import { fetchAdminAppointments } from '@/api/adminAppointments'
import {
  fetchNotifications,
  markNotificationRead,
  markAllNotificationsRead,
} from '@/api/notifications'

const { mdAndUp } = useDisplay()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const drawerOpen = ref(true)

// Real counts for the nav badges. 0 is falsy, so the badge
// <template v-if="item.badge"> hides itself once a queue is clear.
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
    const data = await fetchAdminAppointments({ status: 'scheduled' })
    todaysAppointmentsCount.value = data.length
  } catch {
    todaysAppointmentsCount.value = 0
  }
}

// ── Notifications ──────────────────────────────────────────────────
const notifications = ref([])
const unreadCount = ref(0)
const loadingNotifications = ref(false)

async function loadNotifications() {
  loadingNotifications.value = true
  try {
    const { data, unread_count } = await fetchNotifications()
    notifications.value = data
    unreadCount.value = unread_count
  } catch {
    notifications.value = []
  } finally {
    loadingNotifications.value = false
  }
}

function onMenuToggle(isOpen) {
  if (isOpen) loadNotifications()
}

async function handleNotificationClick(note) {
  if (!note.read) {
    note.read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    try {
      await markNotificationRead(note.id)
    } catch {
      // Non-critical — the list will self-correct next time it's opened.
    }
  }
}

async function handleMarkAllRead() {
  notifications.value = notifications.value.map((n) => ({ ...n, read: true }))
  unreadCount.value = 0
  try {
    await markAllNotificationsRead()
  } catch {
    // Non-critical — next open will reflect the server's real state.
  }
}

function timeAgo(iso) {
  const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

onMounted(() => {
  loadBadgeCounts()
  loadNotifications() // populates the badge count on load, not just on open
})

// ── Navigation ─────────────────────────────────────────────────────
const navItems = computed(() => [
  { label: 'Dashboard', to: '/admin/dashboard', icon: 'mdi-view-dashboard-outline' },
  {
    label: 'Resident Management',
    icon: 'mdi-account-group-outline',
    children: [
      { label: 'Residents', to: '/admin/residents' },
      { label: 'Households', to: '/admin/households' },
      { label: 'Puroks', to: '/admin/puroks' },
    ],
  },
  {
    label: 'Census Management',
    icon: 'mdi-clipboard-list-outline',
    children: [
      { label: 'Census Campaigns', to: '/admin/census/campaigns' },
      { label: 'Household Surveys', to: '/admin/census/surveys' },
      { label: 'Enumerator Assignments', to: '/admin/census/enumerators' },
      { label: 'Census Verification', to: '/admin/census/verification' },
    ],
  },
  {
    label: 'Document Services',
    icon: 'mdi-file-document-outline',
    children: [
      {
        label: 'Document Requests',
        to: '/admin/requests',
        badge: pendingRequestsCount.value,
      },
      { label: 'Services', to: '/admin/services' },
    ],
  },
  {
    label: 'Appointments',
    to: '/admin/appointments',
    icon: 'mdi-calendar-check-outline',
    badge: todaysAppointmentsCount.value,
  },
  {
    label: 'Reports & Statistics',
    icon: 'mdi-chart-bar',
    children: [
      { label: 'Demographic Statistics', to: '/admin/reports/demographics' },
      { label: 'Household Statistics', to: '/admin/reports/households' },
      { label: 'Census Reports', to: '/admin/reports/census' },
      // FIX: was '/admin/reports', which is a prefix of every sibling above/below
      { label: 'Document Request Reports', to: '/admin/reports/requests' },
      { label: 'Appointment Reports', to: '/admin/reports/appointments' },
    ],
  },
  { label: 'AI Assistant', to: '/admin/assistant', icon: 'mdi-robot-outline' },
  { label: 'Settings', to: '/admin/settings', icon: 'mdi-cog-outline' },
])

// Open the group that contains the current page
const openedGroups = ref([])

// Matches the exact path or a real sub-path (e.g. /admin/requests/new
// keeps "Document Requests" active, but /admin/residents-x does not).
function isActive(to) {
  return route.path === to || route.path.startsWith(to + '/')
}

function isGroupActive(item) {
  return item.children?.some((c) => isActive(c.to))
}

watch(
  () => route.path,
  () => {
    const active = navItems.value.find(isGroupActive)
    if (active && !openedGroups.value.includes(active.label)) {
      openedGroups.value = [...openedGroups.value, active.label]
    }
  },
  { immediate: true },
)

// ── Page title / subtitle ──────────────────────────────────────────
// First startsWith match wins, so more specific paths come before shorter ones.
const pageMeta = {
  '/admin/dashboard': ['Dashboard', 'Overview of barangay e-services'],
  '/admin/requests/new': ['New Clearance', 'Submit a clearance application'],
  '/admin/requests': ['Document Requests', 'Manage and process incoming requests'],
  '/admin/services': ['Services', 'Manage available document services'],
  '/admin/appointments': ['Appointments', 'Book and manage resident appointments'],
  '/admin/documents': ['Document Tracking', 'Track real-time document status'],
  '/admin/residents/new': ['Register Resident', 'Add a new resident and create their account'],
  '/admin/residents': ['Residents', 'Registered barangay residents'],
  '/admin/households': ['Households', 'Manage households'],
  '/admin/puroks': ['Puroks', 'Manage puroks'],
  '/admin/census/campaigns': ['Census Campaigns', 'Plan and manage census campaigns'],
  '/admin/census/surveys': ['Household Surveys', 'Review household survey responses'],
  '/admin/census/enumerators': ['Enumerator Assignments', 'Assign enumerators to areas'],
  '/admin/census/verification': ['Census Verification', 'Verify submitted census data'],
  '/admin/reports/demographics': ['Demographic Statistics', 'Population breakdown'],
  '/admin/reports/households': ['Household Statistics', 'Household figures'],
  '/admin/reports/census': ['Census Reports', 'Census summaries'],
  '/admin/reports/requests': ['Document Request Reports', 'Request volume and turnaround'],
  '/admin/reports/appointments': ['Appointment Reports', 'Appointment activity'],
  '/admin/assistant': ['AI Assistant', 'Ask questions and get help'],
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

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped src="./AdminLayoutCss.css"></style>

<!--
  Wiggle fix. Kept in a separate block so your existing AdminLayoutCss.css
  stays untouched. These rules use :deep() plus !important so they win over
  whatever that file sets on the same elements.
-->
<style scoped>
/* 4. Tighten the gap between the nav icon and its label.
      Vuetify's default is 32px, set either as a margin on the icon
      or as a width on the spacer, depending on the version. */
.nav-drawer :deep(.v-list-item__prepend > .v-icon) {
  margin-inline-end: 5px !important;
}

.nav-drawer :deep(.v-list-item__prepend > .v-icon ~ .v-list-item__spacer) {
  width: 12px !important;
}
/* 5. Pull the child items closer to the parent labels.
      Vuetify indents group children via --indent-padding
      (default is about 56px for a nav list with prepend icons). */
.nav-drawer :deep(.v-list-group__items) {
  --indent-padding: 0px !important;
}

.nav-drawer :deep(.v-list-group__items .v-list-item) {
  padding-inline-start: 40px !important; /* try 36–48px to line up with parent labels */
}
/* 1. Expanding a group makes the nav taller. If it overflows, a scrollbar
      appears, narrows the content ~15px and shifts everything sideways.
      Reserve the gutter and never allow horizontal scroll. */
.nav-drawer :deep(.v-navigation-drawer__content) {
  overflow-x: hidden;
  overflow-y: auto;
  scrollbar-gutter: stable;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

/* 2. Stop `transition: all` on nav items from animating padding, margin and
      height while Vuetify is already animating the group's height. */
.nav-drawer :deep(.nav-item) {
  transition:
    background-color 0.15s ease,
    color 0.15s ease !important;
}

/* 3. Clip the collapsing child list so items can't spill out mid-animation,
      and keep child margins out of Vuetify's height calculation. */
.nav-drawer :deep(.v-list-group__items) {
  overflow: hidden;
}

.nav-drawer :deep(.nav-item--child) {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}
</style>