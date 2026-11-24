<!-- src/components/dashboard/CampaignFormDialog.vue -->
<script setup>
import { ref, watch, computed } from 'vue'
import { createCampaign, updateCampaign } from '@/api/censusCampaignApi'

const props = defineProps({
  modelValue: Boolean,
  campaign: { type: Object, default: null }, // null = create
  options: { type: Object, default: () => ({ puroks: [], enumerators: [] }) },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const blank = () => ({
  name: '', description: '', start_date: '', end_date: '', puroks: [], enumerator_ids: [],
})
const form = ref(blank())
const saving = ref(false)
const error = ref('')
const fieldErrors = ref({})

const isEdit = computed(() => !!props.campaign)

watch(() => props.modelValue, (open) => {
  if (!open) return
  error.value = ''
  fieldErrors.value = {}
  form.value = props.campaign
    ? {
        name: props.campaign.name,
        description: props.campaign.description || '',
        start_date: props.campaign.start_date,
        end_date: props.campaign.end_date,
        puroks: props.campaign.puroks.map((p) => p.purok),
        enumerator_ids: props.campaign.enumerators.map((e) => e.id),
      }
    : blank()
})

const close = () => emit('update:modelValue', false)

async function save() {
  saving.value = true
  error.value = ''
  fieldErrors.value = {}
  try {
    const saved = isEdit.value
      ? await updateCampaign(props.campaign.id, form.value)
      : await createCampaign(form.value)
    emit('saved', saved)
    close()
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save campaign.'
    fieldErrors.value = e.response?.data?.errors || {}
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog :model-value="modelValue" max-width="560" persistent @update:model-value="close">
    <v-card style="border-radius: 10px">
      <v-card-title style="font-size: 15px; font-weight: 600; padding: 18px 24px 4px">
        {{ isEdit ? 'Edit census campaign' : 'New census campaign' }}
      </v-card-title>
      <p class="dialog-sub">
        {{ isEdit ? 'Update the schedule, target puroks or assigned enumerators.' : 'Set the schedule, then choose which puroks to cover and who surveys them.' }}
      </p>

      <v-card-text class="px-6 pt-0 pb-2">
        <div v-if="error" class="error-note">
          <v-icon icon="mdi-alert-circle-outline" size="16" />
          <span>{{ error }}</span>
        </div>

        <p class="section-label">Details</p>
        <v-text-field
          v-model="form.name"
          placeholder="Campaign name, e.g. 2026 Annual Barangay Census"
          variant="outlined"
          density="compact"
          class="form-field"
          hide-details="auto"
          :error-messages="fieldErrors.name"
        />
        <v-textarea
          v-model="form.description"
          placeholder="Description (optional)"
          rows="2"
          auto-grow
          variant="outlined"
          density="compact"
          class="form-field"
          hide-details="auto"
        />

        <p class="section-label">Schedule</p>
        <v-row dense class="mb-0">
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.start_date"
              type="date"
              label="Start date"
              variant="outlined"
              density="compact"
              class="form-field"
              hide-details="auto"
              :error-messages="fieldErrors.start_date"
            />
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field
              v-model="form.end_date"
              type="date"
              label="End date"
              variant="outlined"
              density="compact"
              class="form-field"
              hide-details="auto"
              :min="form.start_date"
              :error-messages="fieldErrors.end_date"
            />
          </v-col>
        </v-row>

        <p class="section-label">Coverage</p>
        <v-select
          v-model="form.puroks"
          :items="options.puroks"
          placeholder="Target puroks"
          multiple
          chips
          closable-chips
          variant="outlined"
          density="compact"
          class="form-field"
          hide-details="auto"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          :error-messages="fieldErrors.puroks"
        />
        <v-select
          v-model="form.enumerator_ids"
          :items="options.enumerators"
          item-title="name"
          item-value="id"
          placeholder="Assigned enumerators"
          multiple
          chips
          closable-chips
          variant="outlined"
          density="compact"
          class="form-field"
          hide-details="auto"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          :error-messages="fieldErrors.enumerator_ids"
        />
      </v-card-text>

      <v-card-actions class="px-6 pb-4 pt-2">
        <v-spacer />
        <v-btn variant="text" class="dialog-btn" @click="close">Cancel</v-btn>
        <v-btn
          variant="flat"
          color="#0f1e3d"
          class="dialog-btn"
          :loading="saving"
          @click="save"
        >
          {{ isEdit ? 'Save changes' : 'Create campaign' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.dialog-sub {
  font-size: 12px;
  color: #888;
  margin: 0 24px 14px;
  line-height: 1.5;
}

.section-label {
  font-size: 11px;
  font-weight: 600;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 14px 0 8px;
}

.error-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #fdecea;
  color: #c0392b;
  font-size: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 4px;
}

.form-field {
  margin-bottom: 10px;
}
.form-field :deep(.v-field__input),
.form-field :deep(input),
.form-field :deep(textarea),
.form-field :deep(.v-select__selection-text),
.form-field :deep(.v-label) {
  font-size: 12.5px;
}
.form-field :deep(.v-field__input) {
  min-height: 36px;
}
.form-field :deep(.v-chip) {
  font-size: 11.5px;
  background: #f7f5f0;
  color: #1a1a1a;
}
.form-field :deep(.v-messages__message) {
  font-size: 11px;
}

.dialog-btn {
  height: 34px;
  font-size: 12.5px;
  font-weight: 500;
  letter-spacing: 0;
  text-transform: none;
  border-radius: 8px;
}
</style>