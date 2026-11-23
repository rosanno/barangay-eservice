<template>
  <div>
    <!-- ─── Header + search ──────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-map-marker-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Puroks</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ puroks.length }} registered
        </p>
      </div>

      <div class="d-flex align-center ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search by purok or leader"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input search-field"
          style="width: 300px"
        />
        <v-select
          v-model="statusFilter"
          :items="statusItems"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 140px"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-plus"
          @click="openCreate"
        >
          Add purok
        </v-btn>
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!filtered.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No puroks match that search.
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col.label"
                class="table-head"
                :style="{ textAlign: col.align || 'left' }"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(purok, index) in filtered" :key="purok.uuid" class="table-row">
              <td style="padding: 12px 8px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: palette(index).bg }">
                    <span style="font-size: 10px; font-weight: 600" :style="{ color: palette(index).text }">
                      {{ initials(purok.name) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div style="font-size: 13px; font-weight: 500; color: #1a1a1a">{{ purok.name }}</div>
                    <div v-if="purok.description" style="font-size: 11px; color: #999">
                      {{ purok.description }}
                    </div>
                  </div>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ purok.leader_name || '—' }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ purok.leader_contact || '—' }}</td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ purok.households_count ?? 0 }}
              </td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ purok.members_count ?? 0 }}
              </td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="purok.is_active ? 'status-approved' : 'status-pending'">
                  {{ purok.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td style="padding: 12px 8px; text-align: right; white-space: nowrap">
                <button type="button" class="view-link" @click="openEdit(purok)">Edit</button>
                <button type="button" class="view-link view-link--danger" @click="confirmDelete(purok)">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- ─── Create / Edit dialog ────────────────────────────────── -->
    <v-dialog v-model="dialog" max-width="520">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between" style="font-size: 14px; font-weight: 600">
          {{ selected ? 'Edit purok' : 'Add purok' }}
          <v-btn icon variant="text" size="small" @click="dialog = false">
            <v-icon icon="mdi-close" size="18" />
          </v-btn>
        </v-card-title>

        <v-card-text>
          <div class="field-block">
            <label class="field-label">Purok name</label>
            <v-text-field
              v-model="form.name"
              variant="outlined"
              density="compact"
              hide-details="auto"
              class="filter-input"
              :error-messages="errors.name"
            />
          </div>
          <div class="field-block">
            <label class="field-label">Description / boundaries</label>
            <v-text-field
              v-model="form.description"
              variant="outlined"
              density="compact"
              hide-details="auto"
              class="filter-input"
              :error-messages="errors.description"
            />
          </div>
          <div class="field-block">
            <label class="field-label">Purok leader</label>
            <v-text-field
              v-model="form.leader_name"
              variant="outlined"
              density="compact"
              hide-details="auto"
              class="filter-input"
              :error-messages="errors.leader_name"
            />
          </div>
          <div class="field-block">
            <label class="field-label">Leader contact number</label>
            <v-text-field
              v-model="form.leader_contact"
              variant="outlined"
              density="compact"
              hide-details="auto"
              class="filter-input"
              :error-messages="errors.leader_contact"
            />
          </div>
          <v-switch v-model="form.is_active" label="Active" color="#0f1e3d" density="compact" hide-details />
        </v-card-text>

        <v-card-actions class="px-6 pb-4">
          <v-spacer />
          <v-btn variant="text" style="text-transform: none" @click="dialog = false">Cancel</v-btn>
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="saving"
            @click="save"
          >
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── Delete dialog ───────────────────────────────────────── -->
    <v-dialog v-model="deleteDialog" max-width="420">
      <v-card>
        <v-card-title style="font-size: 14px; font-weight: 600">Delete {{ selected?.name }}?</v-card-title>
        <v-card-text style="font-size: 12.5px; color: #666">
          This can't be undone. A purok with households can't be deleted.
          <div v-if="errors.delete" class="text-error mt-2">{{ errors.delete[0] }}</div>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer />
          <v-btn variant="text" style="text-transform: none" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" style="text-transform: none" @click="remove">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getPuroks, createPurok, updatePurok, deletePurok } from '@/api/adminPurok'

const tableColumns = [
  { label: 'Purok' },
  { label: 'Leader' },
  { label: 'Contact' },
  { label: 'Households', align: 'center' },
  { label: 'Residents', align: 'center' },
  { label: 'Status' },
  { label: '' },
]

const statusItems = [
  { title: 'All status', value: 'all' },
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
]

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const puroks = ref([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('all')

const dialog = ref(false)
const deleteDialog = ref(false)
const saving = ref(false)
const errors = ref({})
const selected = ref(null)
const form = ref(blankForm())

function blankForm() {
  return { name: '', description: '', leader_name: '', leader_contact: '', is_active: true }
}

function palette(index) {
  return AVATAR_PALETTE[index % AVATAR_PALETTE.length]
}

function initials(name) {
  return (name || '')
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return puroks.value.filter((p) => {
    const matchSearch =
      !q || p.name.toLowerCase().includes(q) || (p.leader_name || '').toLowerCase().includes(q)
    const matchStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' ? p.is_active : !p.is_active)
    return matchSearch && matchStatus
  })
})

async function load() {
  loading.value = true
  try {
    puroks.value = await getPuroks()
  } finally {
    loading.value = false
  }
}

function openCreate() {
  selected.value = null
  form.value = blankForm()
  errors.value = {}
  dialog.value = true
}

function openEdit(p) {
  selected.value = p
  form.value = {
    name: p.name,
    description: p.description || '',
    leader_name: p.leader_name || '',
    leader_contact: p.leader_contact || '',
    is_active: p.is_active,
  }
  errors.value = {}
  dialog.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (selected.value) await updatePurok(selected.value.uuid, form.value)
    else await createPurok(form.value)
    dialog.value = false
    await load()
  } catch (e) {
    errors.value = e.response?.data?.errors || { name: [e.response?.data?.message || 'Failed to save'] }
  } finally {
    saving.value = false
  }
}

function confirmDelete(p) {
  selected.value = p
  errors.value = {}
  deleteDialog.value = true
}

async function remove() {
  try {
    await deletePurok(selected.value.uuid)
    deleteDialog.value = false
    await load()
  } catch (e) {
    errors.value = { delete: [e.response?.data?.message || 'Failed to delete'] }
  }
}

onMounted(load)
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
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text),
.filter-input :deep(.v-field__prepend-inner .v-icon) {
  font-size: 12.5px;
}

.filter-input :deep(.v-field__input) {
  min-height: 36px;
}

.view-link {
  background: none;
  border: none;
  font-size: 12px;
  color: #0f1e3d;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-left: 14px;
}

.view-link:first-child {
  margin-left: 0;
}

.view-link--danger {
  color: #c0392b;
}

.field-block {
  margin-bottom: 16px;
}

.field-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #555;
  margin-bottom: 6px;
}
</style>