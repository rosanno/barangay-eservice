<script setup>
import { ref, watch, computed } from 'vue'
import { searchResidents } from '@/services/householdSurveyService'

const props = defineProps({
  modelValue: Boolean,
  member: { type: Object, default: null }, // null = add
  options: { type: Object, required: true },
  hasHead: Boolean, // true if another member is already the head
  defaultRelationship: { type: String, default: null },
})
const emit = defineEmits(['update:modelValue', 'save'])

const blank = () => ({
  client_key: crypto.randomUUID(), resident_id: null, is_new_resident: false,
  first_name: '', middle_name: '', last_name: '', suffix: '',
  relationship: props.defaultRelationship, sex: null, birthdate: '',
  civil_status: null, education: null, employment_status: null, occupation: '',
  is_pwd: false, is_registered_voter: false, contact_number: '',
})

const form = ref(blank())
const mode = ref('existing') // existing | new
const found = ref([])
const searching = ref(false)
const query = ref('')
const errors = ref([])

watch(() => props.modelValue, (open) => {
  if (!open) return
  errors.value = []
  found.value = []
  query.value = ''
  form.value = props.member ? { ...props.member } : blank()
  mode.value = props.member ? (props.member.resident_id ? 'existing' : props.member.is_new_resident ? 'new' : 'existing') : 'existing'
})

let t
watch(query, (q) => {
  clearTimeout(t)
  if (!q || q.length < 2) return (found.value = [])
  t = setTimeout(async () => {
    searching.value = true
    found.value = await searchResidents(q).catch(() => [])
    searching.value = false
  }, 300)
})

function pick(r) {
  if (!r) return
  Object.assign(form.value, {
    resident_id: r.id, is_new_resident: false,
    first_name: r.first_name, middle_name: r.middle_name || '', last_name: r.last_name,
    suffix: r.suffix || '', birthdate: r.birthdate || '', sex: r.sex || form.value.sex,
    civil_status: r.civil_status || form.value.civil_status,
  })
}

function unlink() { form.value.resident_id = null }

watch(mode, (m) => {
  form.value.is_new_resident = m === 'new'
  if (m === 'new') form.value.resident_id = null
})

const relationshipItems = computed(() =>
  props.options.relationship.filter((o) => o.value !== 'head' || !props.hasHead || form.value.relationship === 'head'))

function save() {
  const f = form.value
  const e = []
  if (!f.first_name?.trim()) e.push('First name is required.')
  if (!f.last_name?.trim()) e.push('Last name is required.')
  if (!f.relationship) e.push('Select the relationship to the household head.')
  if (!f.sex) e.push('Select sex.')
  if (!f.birthdate) e.push('Birthdate is required.')
  errors.value = e
  if (e.length) return
  emit('save', { ...f, birthdate: f.birthdate || null })
  emit('update:modelValue', false)
}
</script>

<template>
  <v-dialog :model-value="modelValue" max-width="620" persistent @update:model-value="emit('update:modelValue', $event)">
    <v-card rounded="xl">
      <v-card-title class="pt-5 px-6 font-weight-bold">{{ member ? 'Edit family member' : 'Add family member' }}</v-card-title>
      <v-card-text class="px-6">
        <v-alert v-if="errors.length" type="error" variant="tonal" density="compact" class="mb-4">
          <div v-for="e in errors" :key="e">{{ e }}</div>
        </v-alert>

        <v-btn-toggle v-model="mode" mandatory density="comfortable" color="#0B1F4B" class="mb-4" divided>
          <v-btn value="existing" size="small">Existing resident</v-btn>
          <v-btn value="new" size="small">New resident</v-btn>
        </v-btn-toggle>

        <template v-if="mode === 'existing'">
          <v-alert v-if="form.resident_id" type="success" variant="tonal" density="compact" class="mb-3">
            Linked to resident record #{{ form.resident_id }}.
            <template #append><v-btn size="x-small" variant="text" @click="unlink">Unlink</v-btn></template>
          </v-alert>
          <v-autocomplete v-else v-model:search="query" :items="found" :loading="searching" no-filter
            :item-title="(r) => `${r.last_name}, ${r.first_name} ${r.middle_name || ''}`.trim()"
            return-object clearable hide-no-data label="Search resident by name" variant="outlined"
            density="comfortable" prepend-inner-icon="mdi-account-search" @update:model-value="pick">
            <template #item="{ props: p, item }">
              <v-list-item v-bind="p" :subtitle="`Born ${item.raw.birthdate || '—'}`" />
            </template>
          </v-autocomplete>
        </template>
        <v-alert v-else type="info" variant="tonal" density="compact" class="mb-3">
          This person will be registered as a new resident once the survey is approved.
        </v-alert>

        <v-row dense>
          <v-col cols="12" sm="5"><v-text-field v-model="form.first_name" label="First name" variant="outlined" density="comfortable" /></v-col>
          <v-col cols="12" sm="4"><v-text-field v-model="form.middle_name" label="Middle name" variant="outlined" density="comfortable" /></v-col>
          <v-col cols="8" sm="5"><v-text-field v-model="form.last_name" label="Last name" variant="outlined" density="comfortable" /></v-col>
          <v-col cols="4" sm="3"><v-text-field v-model="form.suffix" label="Suffix" variant="outlined" density="comfortable" /></v-col>
          <v-col cols="12" sm="6">
            <v-select v-model="form.relationship" :items="relationshipItems" label="Relationship to head" variant="outlined" density="comfortable" />
          </v-col>
          <v-col cols="6" sm="3"><v-select v-model="form.sex" :items="options.sex" label="Sex" variant="outlined" density="comfortable" /></v-col>
          <v-col cols="6" sm="3">
            <v-text-field v-model="form.birthdate" type="date" label="Birthdate" :max="new Date().toISOString().slice(0, 10)" variant="outlined" density="comfortable" />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="px-6 pb-5">
        <v-spacer />
        <v-btn variant="text" @click="emit('update:modelValue', false)">Cancel</v-btn>
        <v-btn color="#0B1F4B" variant="flat" @click="save">{{ member ? 'Save member' : 'Add member' }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>