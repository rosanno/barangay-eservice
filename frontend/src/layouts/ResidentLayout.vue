<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchMyDocumentRequests } from '@/api/documentRequests'

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

function signOut() {
  auth.logout?.()
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

          <button type="button" class="topbar__icon-btn" aria-label="Notifications">
            <v-icon icon="mdi-bell-outline" size="20" />
            <span class="topbar__icon-dot" aria-hidden="true" />
          </button>

          <div class="topbar__avatar">{{ initials }}</div>
        </div>
      </header>

      <main class="resident-shell__main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.resident-shell {
  display: flex;
  min-height: 100vh;
  background: var(--brgy-paper);
  font-family: var(--brgy-font-body);
}

/* Sidebar */
.sidebar {
  width: 260px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: var(--brgy-navy);
  padding: 20px 14px;
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 16px;
}

.sidebar__logo {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--brgy-gold);
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar__brand-name {
  color: #ffffff;
  font-size: 0.9rem;
  font-weight: 600;
  margin: 0;
}

.sidebar__brand-sub {
  color: rgba(255, 255, 255, 0.55);
  font-size: 0.72rem;
  margin: 0;
}

.sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar__section-label {
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin: 16px 10px 6px;
}

.sidebar__section-label:first-child {
  margin-top: 0;
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: var(--brgy-radius-sm);
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.86rem;
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}

.sidebar__link:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
}

.sidebar__link--active {
  background: var(--brgy-gold);
  color: var(--brgy-navy);
  font-weight: 600;
}

.sidebar__badge {
  margin-left: auto;
  background: #e5484d;
  color: white;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 999px;
}

.sidebar__signout {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  margin-top: 12px;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  font-family: var(--brgy-font-body);
  font-size: 0.86rem;
  cursor: pointer;
  border-radius: var(--brgy-radius-sm);
}

.sidebar__signout:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
}

/* Body */
.resident-shell__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 32px;
  background: var(--brgy-paper-raised);
  border-bottom: 1px solid var(--brgy-line);
}

.topbar__title {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--brgy-ink);
  margin: 0;
}

.topbar__subtitle {
  font-size: 0.85rem;
  color: var(--brgy-ink-muted);
  margin: 2px 0 0;
}

.topbar__actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.topbar__new-request {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-sm);
  color: var(--brgy-ink);
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  background: var(--brgy-paper-raised);
  transition: border-color 0.15s ease;
}

.topbar__new-request:hover {
  border-color: var(--brgy-gold);
}

.topbar__icon-btn {
  position: relative;
  background: none;
  border: none;
  color: var(--brgy-ink-muted);
  cursor: pointer;
  padding: 6px;
}

.topbar__icon-dot {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #e5484d;
}

.topbar__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--brgy-gold);
  color: var(--brgy-navy);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
}

.resident-shell__main {
  flex: 1;
  padding: 28px 32px 48px;
}

@media (max-width: 960px) {
  .sidebar {
    display: none;
  }
}
</style>
