<template>
  <div>
    <!-- ─── Header + search ──────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-account-group-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Residents</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ meta.total ?? 0 }} registered
        </p>
      </div>

      <div class="d-flex align-center ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search by name or email"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input search-field"
          @update:model-value="onSearchInput"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-plus"
          @click="openAddDialog"
        >
          Add resident
        </v-btn>
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!residents.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No residents match that search.
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th v-for="col in tableColumns" :key="col" class="table-head">{{ col }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="resident in residents" :key="resident.id" class="table-row">
              <td style="padding: 12px 8px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: resident.avatarBg }">
                    <span style="font-size: 10px; font-weight: 600" :style="{ color: resident.avatarText }">
                      {{ initials(resident.name) }}
                    </span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">
                    {{ resident.name }}
                  </span>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ resident.email }}</td>
              <td style="font-size: 11px; color: #999; padding: 12px 8px; white-space: nowrap">
                {{ resident.joinedLabel }}
              </td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ resident.requestCount }}
              </td>
              <td style="padding: 12px 8px; text-align: right">
                <router-link
                  :to="{ path: '/admin/clearances', query: { search: resident.name } }"
                  class="view-link"
                >
                  View requests
                </router-link>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="meta.last_page > 1" class="d-flex justify-center py-4">
          <v-pagination
            v-model="page"
            :length="meta.last_page"
            density="compact"
            :total-visible="6"
            @update:model-value="loadResidents"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Add resident dialog ──────────────────────────────────── -->
    <v-dialog v-model="addDialog.open" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">Add resident</v-card-title>
        <v-card-text>
          <div class="field-block">
            <label class="field-label">Full name</label>
            <v-text-field
              v-model="addDialog.name"
              density="compact"
              variant="outlined"
              hide-details
            />
          </div>
          <div class="field-block">
            <label class="field-label">Email</label>
            <v-text-field
              v-model="addDialog.email"
              type="email"
              density="compact"
              variant="outlined"
              hide-details
            />
          </div>
          <div class="field-block">
            <label class="field-label">Initial password</label>
            <v-text-field
              v-model="addDialog.password"
              type="password"
              density="compact"
              variant="outlined"
              hint="At least 8 characters. Share this with the resident directly."
              persistent-hint
            />
          </div>
          <div class="field-block">
            <label class="field-label">Confirm password</label>
            <v-text-field
              v-model="addDialog.passwordConfirmation"
              type="password"
              density="compact"
              variant="outlined"
              hide-details
            />
          </div>

          <p v-if="addDialog.error" style="font-size: 12px; color: #c0392b; margin: 4px 0 0">
            {{ addDialog.error }}
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="addDialog.open = false">Cancel</v-btn>
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="addDialog.submitting"
            :disabled="!canSubmitAdd"
            @click="submitAddResident"
          >
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.open" :color="snackbar.color" timeout="3500">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fetchAdminResidents, createResident } from '@/api/adminResidents'

const tableColumns = ['Name', 'Email', 'Joined', 'Requests', '']

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const search = ref('')
const page = ref(1)
const residents = ref([])
const meta = ref({ total: 0, last_page: 1 })
const loading = ref(true)

let searchDebounce = null
function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => loadResidents(1), 350)
}

async function loadResidents(targetPage = page.value) {
  page.value = targetPage
  loading.value = true
  try {
    const { data, meta: pageMeta } = await fetchAdminResidents({
      page: targetPage,
      per_page: 20,
      search: search.value || undefined,
    })

    residents.value = data.map((resident, index) => {
      const palette = AVATAR_PALETTE[index % AVATAR_PALETTE.length]
      return {
        id: resident.id,
        name: resident.name,
        email: resident.email,
        requestCount: resident.request_count,
        joinedLabel: formatDate(resident.joined_at),
        avatarBg: palette.bg,
        avatarText: palette.text,
      }
    })
    meta.value = pageMeta
  } catch (error) {
    residents.value = []
    console.error('Failed to load residents', error)
  } finally {
    loading.value = false
  }
}

function initials(name) {
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const addDialog = reactive({
  open: false,
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  submitting: false,
  error: '',
})

const snackbar = reactive({ open: false, text: '', color: 'success' })

const canSubmitAdd = computed(
  () =>
    addDialog.name.trim() &&
    addDialog.email.trim() &&
    addDialog.password.length >= 8 &&
    addDialog.password === addDialog.passwordConfirmation
)

function openAddDialog() {
  addDialog.name = ''
  addDialog.email = ''
  addDialog.password = ''
  addDialog.passwordConfirmation = ''
  addDialog.error = ''
  addDialog.open = true
}

async function submitAddResident() {
  addDialog.error = ''
  addDialog.submitting = true
  try {
    await createResident({
      name: addDialog.name.trim(),
      email: addDialog.email.trim(),
      password: addDialog.password,
      passwordConfirmation: addDialog.passwordConfirmation,
    })

    addDialog.open = false
    snackbar.text = `${addDialog.name} was added as a resident.`
    snackbar.color = 'success'
    snackbar.open = true

    await loadResidents(1)
  } catch (error) {
    addDialog.error =
      error?.response?.data?.message ||
      Object.values(error?.response?.data?.errors || {})[0]?.[0] ||
      'Could not create this resident. Please check the details and try again.'
  } finally {
    addDialog.submitting = false
  }
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => loadResidents(1))
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped src="./ResidentsCss.css">
</style>