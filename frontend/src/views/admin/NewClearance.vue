<template>
  <div style="max-width: 640px">
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <div class="d-flex align-center px-5 pt-5 pb-0">
        <v-icon icon="mdi-file-plus-outline" size="16" style="color: #f5a623; margin-right: 7px" />
        <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">New request</span>
      </div>
      <p style="font-size: 12px; color: #999; margin: 4px 0 0 23px; padding-right: 20px">
        For walk-ins at the barangay hall — this creates the request under the resident's
        own account, so they can still track it from their portal.
      </p>

      <v-card-text class="px-5 pt-5 pb-5">
        <div class="field-block">
          <label class="field-label">Resident</label>
          <v-autocomplete
            v-model="form.residentId"
            v-model:search="residentSearch"
            :items="residentOptions"
            item-title="name"
            item-value="id"
            :loading="searchingResidents"
            density="compact"
            variant="outlined"
            placeholder="Search by name"
            hide-details
            no-filter
            class="filter-input"
            @update:search="onResidentSearch"
          >
            <template #no-data>
              <div style="font-size: 12px; color: #999; padding: 8px 16px">
                {{ residentSearch.trim() ? 'No matching resident' : 'Type a name to search' }}
              </div>
            </template>
          </v-autocomplete>
        </div>

        <div class="field-block">
          <label class="field-label">Document type</label>
          <v-select
            v-model="form.documentTypeId"
            :items="documentTypes"
            item-title="name"
            item-value="id"
            density="compact"
            variant="outlined"
            placeholder="Select a document type"
            hide-details
            class="filter-input"
          />
          <p v-if="selectedType" style="font-size: 11px; color: #999; margin: 6px 0 0">
            {{ selectedType.fee > 0 ? `₱${selectedType.fee.toFixed(2)}` : 'No fee' }} ·
            ready in {{ selectedType.processing_days }}
            {{ selectedType.processing_days === 1 ? 'business day' : 'business days' }}
          </p>
        </div>

        <div class="field-block">
          <label class="field-label">Purpose</label>
          <v-textarea
            v-model="form.purpose"
            density="compact"
            variant="outlined"
            rows="3"
            placeholder="e.g. Employment requirement"
            hide-details
          />
        </div>

        <div class="field-block">
          <label class="field-label">Attachments (optional)</label>
          <v-file-input
            v-model="form.files"
            multiple
            density="compact"
            variant="outlined"
            accept=".jpg,.jpeg,.png,.pdf"
            prepend-icon=""
            prepend-inner-icon="mdi-paperclip"
            placeholder="Attach ID or supporting documents"
            show-size
            hide-details
          />
        </div>

        <div class="d-flex ga-2 justify-end mt-4">
          <v-btn variant="text" style="text-transform: none" to="/admin/clearances">
            Cancel
          </v-btn>
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="submitting"
            :disabled="!canSubmit"
            @click="submit"
          >
            Create request
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="snackbar.open" :color="snackbar.color" timeout="3500">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { searchResidents } from '@/api/adminResidents'
import { fetchDocumentTypes } from '@/api/documentRequests'
import { createAdminDocumentRequest } from '@/api/adminDocumentRequests'

const router = useRouter()

const form = reactive({
  residentId: null,
  documentTypeId: null,
  purpose: '',
  files: [],
})

const residentSearch = ref('')
const residentOptions = ref([])
const searchingResidents = ref(false)
let searchDebounce = null

function onResidentSearch(value) {
  clearTimeout(searchDebounce)
  if (!value?.trim()) {
    residentOptions.value = []
    return
  }
  searchDebounce = setTimeout(async () => {
    searchingResidents.value = true
    try {
      residentOptions.value = await searchResidents(value)
    } finally {
      searchingResidents.value = false
    }
  }, 300)
}

const documentTypes = ref([])
onMounted(async () => {
  documentTypes.value = await fetchDocumentTypes()
})

const selectedType = computed(
  () => documentTypes.value.find((t) => t.id === form.documentTypeId) || null
)

const canSubmit = computed(
  () => form.residentId && form.documentTypeId && form.purpose.trim().length >= 5
)

const submitting = ref(false)
const snackbar = reactive({ open: false, text: '', color: 'success' })

async function submit() {
  submitting.value = true
  try {
    const result = await createAdminDocumentRequest({
      residentId: form.residentId,
      documentTypeId: form.documentTypeId,
      purpose: form.purpose,
      attachments: form.files.map((file) => ({ file })),
    })

    snackbar.text = `Request created — tracking number ${result.tracking_number}.`
    snackbar.color = 'success'
    snackbar.open = true

    setTimeout(() => router.push('/admin/clearances'), 1200)
  } catch (error) {
    snackbar.text =
      error?.response?.data?.message || 'Could not create the request. Please check the form.'
    snackbar.color = 'error'
    snackbar.open = true
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.field-block {
  margin-bottom: 18px;
}

.field-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #555;
  margin-bottom: 6px;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text) {
  font-size: 13px;
}
</style>