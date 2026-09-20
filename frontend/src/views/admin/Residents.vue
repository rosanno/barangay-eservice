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
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { fetchAdminResidents } from '@/api/adminResidents'

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

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(() => loadResidents(1))
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped>
.table-head {
  font-size: 11px;
  font-weight: 500;
  color: #999;
  border-bottom: 1px solid #f0ede3;
  padding: 0 8px 8px;
  text-transform: none;
  letter-spacing: 0;
  text-align: left;
}

.table-row {
  border-bottom: 1px solid #f7f5f0;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input) {
  font-size: 12.5px;
}

.search-field {
  width: 200px;
  max-width: 100%;
  flex-shrink: 1;
}

@media (max-width: 540px) {
  .search-field {
    width: 100%;
  }
}

.view-link {
  font-size: 12px;
  color: #0f1e3d;
  font-weight: 600;
  text-decoration: none;
}
</style>