<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
  fetchStaff, createStaff, updateStaff,
  setStaffStatus, resetStaffPassword, deleteStaff,
} from '@/api/staff'

// ---------- list state ----------
const items = ref([])
const total = ref(0)
const lastPage = ref(1)
const loading = ref(false)
const page = ref(1)
const perPage = 10
const filters = reactive({ search: '', role: '', status: '' })

const roleOptions = [
  { label: 'All roles', value: '' },
  { label: 'Admins', value: 'admin' },
  { label: 'Staff', value: 'staff' },
]
const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Deactivated', value: 'inactive' },
]

const tableColumns = ['Name', 'Position', 'Email', 'Role', 'Status', 'Last login', '']

async function load() {
  loading.value = true
  try {
    const data = await fetchStaff({
      page: page.value,
      per_page: perPage,
      search: filters.search || undefined,
      role: filters.role || undefined,
      status: filters.status || undefined,
    })
    items.value = data.data
    total.value = data.meta.total
    lastPage.value = data.meta.last_page
  } catch (e) {
    notify(errorMessage(e, 'Could not load accounts.'), 'error')
  } finally {
    loading.value = false
  }
}

function reloadFromFirstPage() {
  if (page.value !== 1) page.value = 1 // the page watcher reloads
  else load()
}

let searchTimer
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(reloadFromFirstPage, 300)
})
watch(() => [filters.role, filters.status], reloadFromFirstPage)
watch(page, load)
onMounted(load)

const rangeText = computed(() => {
  if (!items.value.length) return ''
  const from = (page.value - 1) * perPage + 1
  return `${from}–${from + items.value.length - 1} of ${total.value}`
})

// ---------- add / edit dialog ----------
const formOpen = ref(false)
const editingId = ref(null)
const saving = ref(false)
const formRef = ref(null)
const form = reactive({ name: '', email: '', role: 'staff', position: '', contact_number: '' })
const fieldErrors = ref({})
const isEditing = computed(() => editingId.value !== null)

const required = (label) => (v) => !!String(v ?? '').trim() || `${label} is required`
const emailRule = (v) => /^\S+@\S+\.\S+$/.test(v) || 'Enter a valid email address'

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', email: '', role: 'staff', position: '', contact_number: '' })
  fieldErrors.value = {}
  formOpen.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name, email: row.email, role: row.role,
    position: row.position ?? '', contact_number: row.contact_number ?? '',
  })
  fieldErrors.value = {}
  formOpen.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  saving.value = true
  fieldErrors.value = {}
  try {
    if (isEditing.value) {
      await updateStaff(editingId.value, { ...form })
      notify('Account updated')
    } else {
      const data = await createStaff({ ...form })
      showCredentials(form.name, data.temporary_password)
      notify('Account created')
    }
    formOpen.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      fieldErrors.value = e.response.data.errors
    } else {
      notify(errorMessage(e, 'Could not save the account.'), 'error')
    }
  } finally {
    saving.value = false
  }
}

// ---------- confirm dialog ----------
const confirm = reactive({ open: false, title: '', body: '', action: '', confirmText: '', color: 'primary', row: null, busy: false })

function askConfirm(action, row) {
  const map = {
    deactivate: { title: 'Deactivate account?', body: `${row.name} will be signed out and won't be able to log in until reactivated.`, confirmText: 'Deactivate', color: 'warning' },
    activate:   { title: 'Reactivate account?', body: `${row.name} will be able to log in again.`, confirmText: 'Reactivate', color: 'primary' },
    reset:      { title: 'Reset password?', body: `A new temporary password will be generated for ${row.name}. They'll be signed out and asked to change it at next login.`, confirmText: 'Reset password', color: 'warning' },
    delete:     { title: 'Delete account?', body: `This permanently removes ${row.name}. Deactivate instead if you may need the account later.`, confirmText: 'Delete account', color: 'error' },
  }
  Object.assign(confirm, map[action], { open: true, action, row, busy: false })
}

async function runConfirm() {
  confirm.busy = true
  const { action, row } = confirm
  try {
    if (action === 'deactivate') { await setStaffStatus(row.id, false); notify('Account deactivated') }
    if (action === 'activate')   { await setStaffStatus(row.id, true);  notify('Account reactivated') }
    if (action === 'delete')     { await deleteStaff(row.id);           notify('Account deleted') }
    if (action === 'reset') {
      const data = await resetStaffPassword(row.id)
      showCredentials(row.name, data.temporary_password)
    }
    confirm.open = false
    // deleting the last row of a page should step back one page
    if (action === 'delete' && items.value.length === 1 && page.value > 1) page.value -= 1
    else await load()
  } catch (e) {
    notify(errorMessage(e, 'Something went wrong.'), 'error')
    confirm.open = false
  } finally {
    confirm.busy = false
  }
}

// ---------- one-time temporary password ----------
const creds = reactive({ open: false, name: '', password: '', copied: false })
function showCredentials(name, password) {
  Object.assign(creds, { open: true, name, password, copied: false })
}
async function copyPassword() {
  await navigator.clipboard.writeText(creds.password)
  creds.copied = true
}

// ---------- snackbar ----------
const snack = reactive({ open: false, text: '', color: 'success' })
function notify(text, color = 'success') { Object.assign(snack, { open: true, text, color }) }
function errorMessage(e, fallback) { return e.response?.data?.message || fallback }

// ---------- display helpers ----------
const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]
const avatarStyle = (name) => {
  const sum = [...name].reduce((n, ch) => n + ch.charCodeAt(0), 0)
  const c = AVATAR_PALETTE[sum % AVATAR_PALETTE.length]
  return { background: c.bg, color: c.text }
}
const initials = (name) => name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase()
const fmtDate = (iso) => {
  if (!iso) return 'Never'
  const d = new Date(iso)
  const sameYear = d.getFullYear() === new Date().getFullYear()
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', ...(sameYear ? {} : { year: 'numeric' }) })
}
const fmtFull = (iso) => (iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : 'Has not signed in yet')
</script>

<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-account-group-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Accounts</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ total }} total
        </p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="filters.search"
          density="compact"
          variant="outlined"
          placeholder="Search name, email, or position"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input"
          style="width: 240px"
        />
        <v-select
          v-model="filters.role"
          :items="roleOptions"
          item-title="label"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 150px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-select
          v-model="filters.status"
          :items="statusOptions"
          item-title="label"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 160px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-plus"
          @click="openCreate"
        >
          Add account
        </v-btn>
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading && !items.length" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!items.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No accounts match. Clear the filters or add a new account.
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col || 'actions'"
                class="table-head"
                :class="{ 'table-head--actions': !col }"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody :class="{ dim: loading && items.length }">
            <tr v-for="row in items" :key="row.id" class="table-row">
              <td style="padding: 12px 8px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="avatarStyle(row.name)">
                    <span style="font-size: 10px; font-weight: 600">{{ initials(row.name) }}</span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">{{ row.name }}</span>
                  <span v-if="row.is_self" class="you-badge">You</span>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.position || '—' }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.email }}</td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="row.role === 'admin' ? 'status-processing' : 'status-pending'">
                  {{ row.role === 'admin' ? 'Admin' : 'Staff' }}
                </span>
              </td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="row.is_active ? 'status-approved' : 'status-rejected'">
                  {{ row.is_active ? 'Active' : 'Deactivated' }}
                </span>
              </td>
              <td
                style="font-size: 11px; color: #aaa; padding: 12px 8px; white-space: nowrap"
                :title="fmtFull(row.last_login_at)"
              >
                {{ fmtDate(row.last_login_at) }}
              </td>

              <td class="actions-cell">
                <div class="row-actions">
                  <v-tooltip text="Edit account" location="top">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-pencil-outline"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        :aria-label="`Edit ${row.name}`"
                        @click="openEdit(row)"
                      />
                    </template>
                  </v-tooltip>

                  <v-menu v-if="!row.is_self">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-dots-vertical"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        :aria-label="`More actions for ${row.name}`"
                      />
                    </template>
                    <v-list density="compact">
                      <v-list-item prepend-icon="mdi-lock-reset" title="Reset password" @click="askConfirm('reset', row)" />
                      <v-list-item v-if="row.is_active" prepend-icon="mdi-account-off-outline" title="Deactivate" @click="askConfirm('deactivate', row)" />
                      <v-list-item v-else prepend-icon="mdi-account-check-outline" title="Reactivate" @click="askConfirm('activate', row)" />
                      <v-divider />
                      <v-list-item prepend-icon="mdi-delete-outline" title="Delete" base-color="error" @click="askConfirm('delete', row)" />
                    </v-list>
                  </v-menu>
                  <span v-else class="action-btn action-btn--spacer" />
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="lastPage > 1" class="d-flex align-center justify-space-between py-4">
          <span style="font-size: 11px; color: #999">{{ rangeText }}</span>
          <v-pagination
            v-model="page"
            :length="lastPage"
            density="compact"
            :total-visible="6"
            :disabled="loading"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Add / edit ───────────────────────────────────────────── -->
    <v-dialog v-model="formOpen" max-width="520" persistent>
      <v-card>
        <v-card-title style="font-size: 15px">{{ isEditing ? 'Edit account' : 'Add account' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="save">
            <v-text-field v-model="form.name" label="Full name" variant="outlined" :rules="[required('Full name')]" :error-messages="fieldErrors.name" />
            <v-text-field v-model="form.email" label="Email" type="email" variant="outlined" :rules="[required('Email'), emailRule]" :error-messages="fieldErrors.email" />
            <v-btn-toggle v-model="form.role" mandatory divided class="role-toggle mb-4">
              <v-btn value="staff">Staff</v-btn>
              <v-btn value="admin">Admin</v-btn>
            </v-btn-toggle>
            <p style="font-size: 12px; color: #999; margin: -4px 0 16px">
              {{ form.role === 'admin' ? 'Admins can manage staff accounts and every setting in the portal.' : 'Staff can process requests and appointments but cannot manage accounts.' }}
            </p>
            <v-text-field v-model="form.position" label="Position (e.g. Barangay Secretary)" variant="outlined" :error-messages="fieldErrors.position" />
            <v-text-field v-model="form.contact_number" label="Contact number" variant="outlined" :error-messages="fieldErrors.contact_number" />
          </v-form>
          <p v-if="!isEditing" style="font-size: 12px; color: #999">
            A temporary password is generated after you save. They'll be asked to change it at first login.
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="saving" @click="formOpen = false">Cancel</v-btn>
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="saving"
            @click="save"
          >
            {{ isEditing ? 'Save changes' : 'Create account' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── Confirm ──────────────────────────────────────────────── -->
    <v-dialog v-model="confirm.open" max-width="440">
      <v-card>
        <v-card-title style="font-size: 15px">{{ confirm.title }}</v-card-title>
        <v-card-text style="font-size: 13px; color: #555">{{ confirm.body }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="confirm.busy" @click="confirm.open = false">Cancel</v-btn>
          <v-btn :color="confirm.color" variant="flat" :loading="confirm.busy" @click="runConfirm">{{ confirm.confirmText }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── One-time temporary password ─────────────────────────── -->
    <v-dialog v-model="creds.open" max-width="460" persistent>
      <v-card>
        <v-card-title style="font-size: 15px">Temporary password for {{ creds.name }}</v-card-title>
        <v-card-text>
          <p style="font-size: 12px; color: #666; margin-bottom: 12px">
            Share this securely. It won't be shown again, and they must change it when they sign in.
          </p>
          <v-text-field
            :model-value="creds.password"
            readonly
            variant="outlined"
            hide-details
            class="mono"
            :append-inner-icon="creds.copied ? 'mdi-check' : 'mdi-content-copy'"
            @click:append-inner="copyPassword"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn style="background: #0f1e3d; color: #fff; text-transform: none" @click="creds.open = false">Done</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.open" :color="snack.color" timeout="3500">{{ snack.text }}</v-snackbar>
  </div>
</template>

<style scoped src="./DashboardCss.css"></style>

<style scoped src="./StaffadminsCss.css">
</style>

<style>
.filter-select-menu .v-list-item-title {
  font-size: 12.5px;
}
.filter-select-menu .v-list-item {
  min-height: 34px;
}
</style>