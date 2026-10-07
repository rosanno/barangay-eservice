<template>
  <div>
    <div class="d-flex align-center mb-4">
      <v-icon icon="mdi-cog-outline" size="16" style="color: #f5a623; margin-right: 7px" />
      <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Settings</span>
    </div>

    <v-tabs v-model="activeTab" class="settings-tabs">
      <v-tab value="info">Barangay Information</v-tab>
      <v-tab value="types">Document Types</v-tab>
    </v-tabs>

    <v-window v-model="activeTab" class="mt-5">
      <!-- ─── Barangay Information ─────────────────────────────── -->
      <v-window-item value="info">
        <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none; max-width: 560px">
          <v-card-text class="pa-5">
            <div v-if="infoLoading" class="d-flex justify-center py-10">
              <v-progress-circular indeterminate size="24" color="#0f1e3d" />
            </div>

            <template v-else>
              <div class="field-block">
                <label class="field-label">Barangay Name <span class="req">*</span></label>
                <v-text-field v-model="infoForm.name" density="compact" variant="outlined" hide-details />
              </div>
              <div class="field-block">
                <label class="field-label">Address</label>
                <v-text-field v-model="infoForm.address" density="compact" variant="outlined" hide-details />
              </div>
              <div class="grid-2">
                <div class="field-block">
                  <label class="field-label">Contact Number</label>
                  <v-text-field v-model="infoForm.contact_number" density="compact" variant="outlined" hide-details />
                </div>
                <div class="field-block">
                  <label class="field-label">Email</label>
                  <v-text-field v-model="infoForm.email" type="email" density="compact" variant="outlined" hide-details />
                </div>
              </div>
              <div class="field-block">
                <label class="field-label">Office Hours</label>
                <v-text-field
                  v-model="infoForm.office_hours"
                  density="compact"
                  variant="outlined"
                  placeholder="e.g. Mon–Fri, 8:00 AM – 5:00 PM"
                  hide-details
                />
              </div>

              <div class="d-flex justify-end mt-4">
                <v-btn
                  style="background: #0f1e3d; color: #fff; text-transform: none"
                  :loading="infoSaving"
                  :disabled="!infoForm.name.trim()"
                  @click="saveInfo"
                >
                  Save changes
                </v-btn>
              </div>
            </template>
          </v-card-text>
        </v-card>
      </v-window-item>

      <!-- ─── Document Types ────────────────────────────────────── -->
      <v-window-item value="types">
        <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
          <v-text-field
            v-model="typeSearch"
            density="compact"
            variant="outlined"
            placeholder="Search document types"
            prepend-inner-icon="mdi-magnify"
            hide-details
            class="filter-input"
            style="width: 240px"
          />
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            prepend-icon="mdi-plus"
            @click="openCreateType"
          >
            Add document type
          </v-btn>
        </div>

        <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
          <v-card-text class="px-5 pt-5 pb-5">
            <div v-if="typesLoading" class="d-flex justify-center py-10">
              <v-progress-circular indeterminate size="24" color="#0f1e3d" />
            </div>

            <p v-else-if="!filteredTypes.length" style="font-size: 13px; color: #999; padding: 32px 20px">
              No document types match that search.
            </p>

            <v-table v-else density="comfortable">
              <thead>
                <tr>
                  <th v-for="col in typeColumns" :key="col || 'actions'" class="table-head" :class="{ 'table-head--actions': !col }">
                    {{ col }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="type in filteredTypes" :key="type.id" class="table-row">
                  <td style="font-size: 13px; font-weight: 500; color: #1a1a1a; padding: 12px 8px">
                    {{ type.name }}
                  </td>
                  <td style="font-size: 11px; color: #999; padding: 12px 8px">{{ type.code }}</td>
                  <td style="font-size: 12px; color: #666; padding: 12px 8px">
                    {{ type.fee > 0 ? `₱${Number(type.fee).toFixed(2)}` : 'No fee' }}
                  </td>
                  <td style="font-size: 12px; color: #666; padding: 12px 8px">
                    {{ type.processing_days }} {{ type.processing_days === 1 ? 'day' : 'days' }}
                  </td>
                  <td style="padding: 12px 8px">
                    <span class="status-chip" :class="type.is_active ? 'status-approved' : 'status-rejected'">
                      {{ type.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="actions-cell">
                    <div class="row-actions">
                      <v-tooltip text="Edit" location="top">
                        <template #activator="{ props }">
                          <v-btn
                            v-bind="props"
                            icon="mdi-pencil-outline"
                            variant="text"
                            density="comfortable"
                            class="action-btn"
                            @click="openEditType(type)"
                          />
                        </template>
                      </v-tooltip>
                      <v-tooltip :text="type.is_active ? 'Deactivate' : 'Activate'" location="top">
                        <template #activator="{ props }">
                          <v-btn
                            v-bind="props"
                            :icon="type.is_active ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                            variant="text"
                            density="comfortable"
                            class="action-btn"
                            @click="toggleActive(type)"
                          />
                        </template>
                      </v-tooltip>
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- ─── Add / edit document type dialog ─────────────────────── -->
    <v-dialog v-model="typeDialog.open" max-width="480">
      <v-card>
        <v-card-title style="font-size: 15px">
          {{ typeDialog.editing ? 'Edit document type' : 'Add document type' }}
        </v-card-title>
        <v-card-text>
          <div class="field-block">
            <label class="field-label">Code <span class="req">*</span></label>
            <v-text-field
              v-model="typeForm.code"
              density="compact"
              variant="outlined"
              placeholder="e.g. BARANGAY_CLEARANCE"
              hide-details
            />
          </div>
          <div class="field-block">
            <label class="field-label">Name <span class="req">*</span></label>
            <v-text-field v-model="typeForm.name" density="compact" variant="outlined" hide-details />
          </div>
          <div class="field-block">
            <label class="field-label">Description</label>
            <v-textarea v-model="typeForm.description" density="compact" variant="outlined" rows="2" hide-details />
          </div>
          <div class="grid-2">
            <div class="field-block">
              <label class="field-label">Fee (₱) <span class="req">*</span></label>
              <v-text-field v-model.number="typeForm.fee" type="number" min="0" step="0.01" density="compact" variant="outlined" hide-details />
            </div>
            <div class="field-block">
              <label class="field-label">Processing Days <span class="req">*</span></label>
              <v-text-field v-model.number="typeForm.processing_days" type="number" min="1" density="compact" variant="outlined" hide-details />
            </div>
          </div>
          <div class="field-block">
            <label class="field-label">Requirements</label>
            <v-textarea
              v-model="requirementsText"
              density="compact"
              variant="outlined"
              rows="3"
              placeholder="One requirement per line, e.g.&#10;Valid ID&#10;Proof of residency"
              hide-details
            />
          </div>
          <v-switch v-model="typeForm.is_active" label="Active (visible to residents)" color="#0f1e3d" hide-details />

          <p v-if="typeDialog.error" style="font-size: 12px; color: #c0392b; margin-top: 10px">
            {{ typeDialog.error }}
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="typeDialog.open = false">Cancel</v-btn>
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="typeDialog.submitting"
            :disabled="!canSubmitType"
            @click="submitType"
          >
            {{ typeDialog.editing ? 'Save changes' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.open" :color="snackbar.color" timeout="3500">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fetchBarangaySettings, updateBarangaySettings } from '@/api/adminSettings'
import { fetchAdminDocumentTypes, createDocumentType, updateDocumentType } from '@/api/adminDocumentTypes'

const activeTab = ref('info')
const snackbar = reactive({ open: false, text: '', color: 'success' })
function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.open = true
}

// ── Barangay Information ─────────────────────────────────────────────
const infoLoading = ref(true)
const infoSaving = ref(false)
const infoForm = reactive({ name: '', address: '', contact_number: '', email: '', office_hours: '' })

async function loadInfo() {
  infoLoading.value = true
  try {
    const data = await fetchBarangaySettings()
    Object.assign(infoForm, {
      name: data.name || '',
      address: data.address || '',
      contact_number: data.contact_number || '',
      email: data.email || '',
      office_hours: data.office_hours || '',
    })
  } finally {
    infoLoading.value = false
  }
}

async function saveInfo() {
  infoSaving.value = true
  try {
    await updateBarangaySettings({ ...infoForm })
    notify('Barangay information updated.')
  } catch (error) {
    notify(error?.response?.data?.message || 'Could not save changes.', 'error')
  } finally {
    infoSaving.value = false
  }
}

// ── Document Types ───────────────────────────────────────────────────
const typeColumns = ['Name', 'Code', 'Fee', 'Processing', 'Status', '']
const typesLoading = ref(true)
const types = ref([])
const typeSearch = ref('')

const filteredTypes = computed(() => {
  if (!typeSearch.value.trim()) return types.value
  const term = typeSearch.value.trim().toLowerCase()
  return types.value.filter(
    (t) => t.name.toLowerCase().includes(term) || t.code.toLowerCase().includes(term)
  )
})

async function loadTypes() {
  typesLoading.value = true
  try {
    types.value = await fetchAdminDocumentTypes()
  } catch {
    types.value = []
  } finally {
    typesLoading.value = false
  }
}

const typeDialog = reactive({ open: false, editing: null, submitting: false, error: '' })
const typeForm = reactive({
  code: '',
  name: '',
  description: '',
  fee: 0,
  processing_days: 1,
  is_active: true,
})
const requirementsText = ref('')

const canSubmitType = computed(
  () => typeForm.code.trim() && typeForm.name.trim() && typeForm.fee >= 0 && typeForm.processing_days >= 1
)

function openCreateType() {
  typeDialog.editing = null
  typeDialog.error = ''
  Object.assign(typeForm, { code: '', name: '', description: '', fee: 0, processing_days: 1, is_active: true })
  requirementsText.value = ''
  typeDialog.open = true
}

function openEditType(type) {
  typeDialog.editing = type
  typeDialog.error = ''
  Object.assign(typeForm, {
    code: type.code,
    name: type.name,
    description: type.description || '',
    fee: Number(type.fee),
    processing_days: type.processing_days,
    is_active: type.is_active,
  })
  requirementsText.value = (type.requirements || []).join('\n')
  typeDialog.open = true
}

async function submitType() {
  typeDialog.submitting = true
  typeDialog.error = ''
  const payload = {
    ...typeForm,
    requirements: requirementsText.value
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean),
  }
  try {
    if (typeDialog.editing) {
      await updateDocumentType(typeDialog.editing.id, payload)
      notify('Document type updated.')
    } else {
      await createDocumentType(payload)
      notify('Document type created.')
    }
    typeDialog.open = false
    await loadTypes()
  } catch (error) {
    typeDialog.error =
      error?.response?.data?.message ||
      Object.values(error?.response?.data?.errors || {})[0]?.[0] ||
      'Could not save this document type.'
  } finally {
    typeDialog.submitting = false
  }
}

async function toggleActive(type) {
  try {
    await updateDocumentType(type.id, { ...type, fee: Number(type.fee), is_active: !type.is_active })
    notify(`${type.name} ${type.is_active ? 'deactivated' : 'activated'}.`)
    await loadTypes()
  } catch (error) {
    notify(error?.response?.data?.message || 'Could not update this document type.', 'error')
  }
}

onMounted(() => {
  loadInfo()
  loadTypes()
})
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped>
.settings-tabs :deep(.v-tab) {
  text-transform: none;
  font-size: 13px;
  letter-spacing: 0;
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

.req {
  color: #c0392b;
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

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

.table-head--actions {
  width: 80px;
}

.table-row td {
  vertical-align: middle;
  border-bottom: 1px solid #f7f5f0;
}

.table-row:last-child td {
  border-bottom: none;
}

.table-row:hover {
  background: #fafbfc;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input) {
  font-size: 12.5px;
}

.actions-cell {
  padding: 8px 5px;
  width: 80px;
}

.row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 2px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #8a8f98;
  transition: background 0.15s, color 0.15s;
}

.action-btn .v-icon {
  font-size: 18px;
}

.action-btn:hover {
  background: #f1f3f7;
  color: #0f1e3d;
}

@media (max-width: 600px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>