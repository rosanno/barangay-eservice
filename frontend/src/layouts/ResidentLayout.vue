<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchMyDocumentRequests } from '@/api/documentRequests'
import { fetchNotifications, markNotificationRead, markAllNotificationsRead } from '@/api/notifications'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const pendingCount = ref(0)

onMounted(async () => {
  try {
    const { meta } = await fetchMyDocumentRequests({ status: 'pending', per_page: 1 })
    pendingCount.value = meta?.total ?? 0
  } catch {
    pendingCount.value = 0
  }
  loadNotifications()
})

const pageTitle = computed(() => route.meta.title || 'Dashboard')
const pageSubtitle = computed(() => route.meta.subtitle || '')

const initials = computed(() => {
  const name = auth.user?.name || 'Resident'
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
})

// ── Notifications ──────────────────────────────────────────────────
const notifications = ref([])
const unreadCount = ref(0)
const loadingNotifications = ref(false)
const notifMenuOpen = ref(false)

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

function onNotifMenuToggle(isOpen) {
  notifMenuOpen.value = isOpen
  if (isOpen) loadNotifications()
}

async function handleNotificationClick(note) {
  if (!note.read) {
    note.read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    try {
      await markNotificationRead(note.id)
    } catch {
      // Non-critical — self-corrects next time the list is opened.
    }
  }
}

async function handleMarkAllRead() {
  notifications.value = notifications.value.map((n) => ({ ...n, read: true }))
  unreadCount.value = 0
  try {
    await markAllNotificationsRead()
  } catch {
    // Non-critical.
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

async function signOut() {
  await auth.logout?.()
  router.push('/login')
}
</script>

<template>
  <div class="resident-shell">
    <aside class="sidebar">
      <div class="sidebar__brand">
        <div class="sidebar__logo">
          <v-icon icon="mdi-home-city-outline" size="20" color="white" />
        </div>
        <div>
          <p class="sidebar__brand-name">Brgy. San Roque</p>
          <p class="sidebar__brand-sub">Resident Portal</p>
        </div>
      </div>

      <nav class="sidebar__nav">
        <p class="sidebar__section-label">Overview</p>
        <router-link to="/dashboard" class="sidebar__link" active-class="sidebar__link--active">
          <v-icon icon="mdi-view-dashboard-outline" size="18" />
          <span>Dashboard</span>
        </router-link>

        <p class="sidebar__section-label">Services</p>
        <router-link
          to="/documents/request"
          class="sidebar__link"
          active-class="sidebar__link--active"
        >
          <v-icon icon="mdi-file-plus-outline" size="18" />
          <span>Request a document</span>
        </router-link>
        <router-link to="/documents" class="sidebar__link" active-class="sidebar__link--active">
          <v-icon icon="mdi-file-document-outline" size="18" />
          <span>My requests</span>
          <span v-if="pendingCount" class="sidebar__badge">{{ pendingCount }}</span>
        </router-link>
        <router-link
          to="/documents/track"
          class="sidebar__link"
          active-class="sidebar__link--active"
        >
          <v-icon icon="mdi-magnify" size="18" />
          <span>Track a request</span>
        </router-link>
        <router-link to="/appointments" class="sidebar__link" active-class="sidebar__link--active">
          <v-icon icon="mdi-calendar-check-outline" size="18" />
          <span>Appointments</span>
        </router-link>

        <p class="sidebar__section-label">Account</p>
        <router-link to="/profile" class="sidebar__link" active-class="sidebar__link--active">
          <v-icon icon="mdi-account-outline" size="18" />
          <span>Profile</span>
        </router-link>
        <router-link to="/settings" class="sidebar__link" active-class="sidebar__link--active">
          <v-icon icon="mdi-cog-outline" size="18" />
          <span>Settings</span>
        </router-link>
      </nav>

      <button type="button" class="sidebar__signout" @click="signOut">
        <v-icon icon="mdi-logout" size="18" />
        <span>Sign out</span>
      </button>
    </aside>

    <div class="resident-shell__body">
      <header class="topbar">
        <div>
          <h1 class="topbar__title">{{ pageTitle }}</h1>
          <p v-if="pageSubtitle" class="topbar__subtitle">{{ pageSubtitle }}</p>
        </div>

        <div class="topbar__actions">
          <router-link to="/documents/request" class="topbar__new-request">
            <v-icon icon="mdi-plus" size="16" />
            New request
          </router-link>

          <v-menu location="bottom end" min-width="300" @update:model-value="onNotifMenuToggle">
            <template #activator="{ props }">
              <button type="button" class="topbar__icon-btn" aria-label="Notifications" v-bind="props">
                <v-icon icon="mdi-bell-outline" size="20" />
                <span v-if="unreadCount > 0" class="topbar__icon-dot" aria-hidden="true" />
              </button>
            </template>

            <div class="notif-panel">
              <div class="notif-panel__header">
                <span>Notifications</span>
                <button
                  v-if="unreadCount > 0"
                  type="button"
                  class="notif-panel__mark-all"
                  @click="handleMarkAllRead"
                >
                  Mark all read
                </button>
              </div>

              <div v-if="loadingNotifications" class="notif-panel__loading">
                <v-progress-circular indeterminate size="18" />
              </div>

              <p v-else-if="!notifications.length" class="notif-panel__empty">
                No notifications yet.
              </p>

              <button
                v-for="note in notifications"
                :key="note.id"
                type="button"
                class="notif-item"
                :class="{ 'notif-item--unread': !note.read }"
                @click="handleNotificationClick(note)"
              >
                <span class="notif-item__dot" :class="{ 'notif-item__dot--unread': !note.read }" />
                <span class="notif-item__text">
                  <span class="notif-item__message">{{ note.message }}</span>
                  <span class="notif-item__time">{{ timeAgo(note.created_at) }}</span>
                </span>
              </button>
            </div>
          </v-menu>

          <div class="topbar__avatar-wrapper">
            <v-menu location="bottom end">
              <template #activator="{ props }">
                <button type="button" class="topbar__avatar" v-bind="props">
                  {{ initials }}
                </button>
              </template>

              <v-list density="compact" min-width="180">
                <v-list-item to="/profile" prepend-icon="mdi-account-outline">
                  <v-list-item-title>Profile</v-list-item-title>
                </v-list-item>
                <v-list-item to="/settings" prepend-icon="mdi-cog-outline">
                  <v-list-item-title>Settings</v-list-item-title>
                </v-list-item>
                <v-divider />
                <v-list-item prepend-icon="mdi-logout" @click="signOut">
                  <v-list-item-title>Sign out</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </div>
        </div>
      </header>

      <main class="resident-shell__main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped src="./ResidentLayoutCss.css">
</style>