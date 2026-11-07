<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { getMyHousehold, createMyHousehold, updateMyHousehold } from '@/api/household'

const opts = {
  tenure:   [['owned', 'Owned'], ['rented', 'Rented'], ['shared', 'Shared with relatives'], ['informal_settler', 'Informal settler']],
  material: [['concrete', 'Concrete'], ['semi_concrete', 'Semi-concrete'], ['light_materials', 'Light materials']],
  water:    [['piped', 'Piped / Level 3'], ['deep_well', 'Deep well'], ['spring', 'Spring'], ['delivered', 'Delivered / Refilling station'], ['other', 'Other']],
  toilet:   [['water_sealed', 'Water-sealed'], ['pit', 'Pit latrine'], ['shared', 'Shared'], ['none', 'None']],
  income:   [['below_10k', 'Below ₱10,000'], ['10k_20k', '₱10,000 – ₱20,000'], ['20k_40k', '₱20,000 – ₱40,000'], ['above_40k', 'Above ₱40,000']],
  sex:      [['male', 'Male'], ['female', 'Female']],
  civil:    [['single', 'Single'], ['married', 'Married'], ['widowed', 'Widowed'], ['separated', 'Separated'], ['live_in', 'Live-in']],
}
const toItems = (pairs) => pairs.map(([value, title]) => ({ value, title }))

const STATUS_ALERT = {
  pending:      { type: 'warning', text: 'Your household profile is waiting for verification by barangay staff.' },
  verified:     { type: 'success', text: 'Your household profile is verified.' },
  needs_update: { type: 'info',    text: 'Barangay staff asked you to update your profile.' },
  rejected:     { type: 'error',   text: 'Your household profile was rejected. Please visit the barangay hall.' },
  inactive:     { type: 'grey',    text: 'This household is marked inactive.' },
}

const blankMember = (isHead = false) => ({
  first_name: '', middle_name: '', last_name: '', suffix: '',
  relationship_to_head: isHead ? 'Head' : '', is_head: isHead,
  birthdate: '', sex: null, civil_status: null, education: '', occupation: '',
  is_pwd: false, is_solo_parent: false, is_registered_voter: false,
})

const loading = ref(true)
const saving = ref(false)
const existing = ref(null)
const errors = ref({})
const snackbar = reactive({ show: false, text: '', color: 'success' })

const form = reactive({
  purok: '', street_address: '',
  housing_tenure: null, house_material: null, water_source: null, toilet_type: null,
  has_electricity: true, income_bracket: null, is_4ps: false,
  consent: false,
  members: [blankMember(true)],
})

const isLocked = computed(() => existing.value && ['rejected', 'inactive'].includes(existing.value.status))
const errorList = computed(() => Object.values(errors.value).flat())

function fill(h) {
  Object.assign(form, {
    purok: h.purok, street_address: h.street_address,
    housing_tenure: h.housing_tenure, house_material: h.house_material,
    water_source: h.water_source, toilet_type: h.toilet_type,
    has_electricity: h.has_electricity, income_bracket: h.income_bracket, is_4ps: h.is_4ps,
    consent: false, // must re-consent on every submission
    members: h.members.map(({ id, full_name, age, ...m }) => ({ ...m, middle_name: m.middle_name ?? '', suffix: m.suffix ?? '', education: m.education ?? '', occupation: m.occupation ?? '' })),
  })
}

function addMember() { form.members.push(blankMember()) }
function removeMember(i) { form.members.splice(i, 1) }
function makeHead(i) {
  form.members.forEach((m, idx) => {
    m.is_head = idx === i
    if (idx === i) m.relationship_to_head = 'Head'
    else if (m.relationship_to_head === 'Head') m.relationship_to_head = ''
  })
}

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const payload = JSON.parse(JSON.stringify(form))
    existing.value = existing.value ? await updateMyHousehold(payload) : await createMyHousehold(payload)
    form.consent = false
    Object.assign(snackbar, { show: true, text: 'Household profile submitted for verification.', color: 'success' })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else Object.assign(snackbar, { show: true, text: e.response?.data?.message ?? 'Something went wrong.', color: 'error' })
  } finally {
    saving.value = false
  }
}

const err = (field) => errors.value[field]?.[0]

onMounted(async () => {
  try {
    existing.value = await getMyHousehold()
    if (existing.value) fill(existing.value)
  } finally { loading.value = false }
})
</script>

<template>
  <div class="pa-6" style="max-width: 960px; margin: 0 auto">
    <!-- <h1 class="text-h5 font-weight-bold">My Household</h1> -->
    <p class="text-body-2 text-medium-emphasis mb-5">Complete your household profile once. It helps the barangay process your document requests faster.</p>

    <v-progress-linear v-if="loading" indeterminate />

    <template v-else>
      <v-alert v-if="existing" :type="STATUS_ALERT[existing.status].type" variant="tonal" class="mb-5">
        <div class="font-weight-medium">{{ existing.household_number }}</div>
        {{ STATUS_ALERT[existing.status].text }}
        <div v-if="existing.remarks" class="mt-1">“{{ existing.remarks }}”</div>
      </v-alert>

      <v-alert v-if="errorList.length" type="error" variant="tonal" class="mb-5">
        <div v-for="(m, i) in errorList.slice(0, 5)" :key="i">{{ m }}</div>
      </v-alert>

      <v-form :disabled="isLocked" @submit.prevent="submit">
        <!-- Household info -->
        <v-card rounded="xl" elevation="0" border class="pa-5 mb-5">
          <div class="text-subtitle-1 font-weight-bold mb-3">Household Information</div>
          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="form.purok" label="Purok / Zone" variant="outlined" :error-messages="err('purok')" /></v-col>
            <v-col cols="12" sm="8"><v-text-field v-model="form.street_address" label="Street / House No." variant="outlined" :error-messages="err('street_address')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.housing_tenure" :items="toItems(opts.tenure)" label="Housing tenure" variant="outlined" :error-messages="err('housing_tenure')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.house_material" :items="toItems(opts.material)" label="House material" variant="outlined" :error-messages="err('house_material')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.water_source" :items="toItems(opts.water)" label="Water source" variant="outlined" :error-messages="err('water_source')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.toilet_type" :items="toItems(opts.toilet)" label="Toilet facility" variant="outlined" :error-messages="err('toilet_type')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.income_bracket" :items="toItems(opts.income)" label="Monthly household income (optional)" variant="outlined" clearable /></v-col>
            <v-col cols="12" sm="6" class="d-flex flex-column justify-center">
              <v-switch v-model="form.has_electricity" label="Has electricity" color="primary" hide-details density="compact" />
              <v-switch v-model="form.is_4ps" label="4Ps beneficiary" color="primary" hide-details density="compact" />
            </v-col>
          </v-row>
        </v-card>

        <!-- Members -->
        <div class="d-flex align-center justify-space-between mb-2">
          <div class="text-subtitle-1 font-weight-bold">Household Members ({{ form.members.length }})</div>
          <v-btn prepend-icon="mdi-plus" variant="tonal" color="primary" :disabled="form.members.length >= 30" @click="addMember">Add member</v-btn>
        </div>

        <v-card v-for="(m, i) in form.members" :key="i" rounded="xl" elevation="0" border class="pa-5 mb-4">
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center ga-2">
              <span class="font-weight-medium">Member {{ i + 1 }}</span>
              <v-chip v-if="m.is_head" size="small" color="primary" label>Head</v-chip>
              <v-btn v-else size="x-small" variant="text" @click="makeHead(i)">Set as head</v-btn>
            </div>
            <v-btn v-if="form.members.length > 1 && !m.is_head" icon="mdi-delete-outline" size="small" variant="text" color="error" @click="removeMember(i)" />
          </div>
          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="m.first_name" label="First name" variant="outlined" density="comfortable" :error-messages="err(`members.${i}.first_name`)" /></v-col>
            <v-col cols="12" sm="3"><v-text-field v-model="m.middle_name" label="Middle name" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="8" sm="3"><v-text-field v-model="m.last_name" label="Last name" variant="outlined" density="comfortable" :error-messages="err(`members.${i}.last_name`)" /></v-col>
            <v-col cols="4" sm="2"><v-text-field v-model="m.suffix" label="Suffix" variant="outlined" density="comfortable" /></v-col>

            <v-col cols="12" sm="4"><v-text-field v-model="m.relationship_to_head" label="Relationship to head" variant="outlined" density="comfortable" :readonly="m.is_head" :error-messages="err(`members.${i}.relationship_to_head`)" /></v-col>
            <v-col cols="12" sm="3"><v-text-field v-model="m.birthdate" type="date" label="Birthdate" variant="outlined" density="comfortable" :error-messages="err(`members.${i}.birthdate`)" /></v-col>
            <v-col cols="6" sm="2"><v-select v-model="m.sex" :items="toItems(opts.sex)" label="Sex" variant="outlined" density="comfortable" :error-messages="err(`members.${i}.sex`)" /></v-col>
            <v-col cols="6" sm="3"><v-select v-model="m.civil_status" :items="toItems(opts.civil)" label="Civil status" variant="outlined" density="comfortable" :error-messages="err(`members.${i}.civil_status`)" /></v-col>

            <v-col cols="12" sm="6"><v-text-field v-model="m.education" label="Highest education (optional)" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="m.occupation" label="Occupation (optional)" variant="outlined" density="comfortable" /></v-col>

            <v-col cols="12" class="d-flex flex-wrap ga-4">
              <v-checkbox v-model="m.is_pwd" label="PWD" hide-details density="compact" />
              <v-checkbox v-model="m.is_solo_parent" label="Solo parent" hide-details density="compact" />
              <v-checkbox v-model="m.is_registered_voter" label="Registered voter" hide-details density="compact" />
            </v-col>
          </v-row>
        </v-card>

        <!-- Consent -->
        <v-card rounded="xl" elevation="0" border class="pa-5 mb-5">
          <v-checkbox v-model="form.consent" color="primary" hide-details :error-messages="err('consent')">
            <template #label>
              <span class="text-body-2">I confirm the information above is true and I consent to the Barangay collecting and processing it for census and public-service purposes in accordance with the Data Privacy Act of 2012 (RA 10173).</span>
            </template>
          </v-checkbox>
        </v-card>

        <div class="d-flex justify-end">
          <v-btn type="submit" color="primary" size="large" variant="flat" :loading="saving" :disabled="!form.consent || isLocked">
            {{ existing ? 'Update & resubmit' : 'Submit household profile' }}
          </v-btn>
        </div>
      </v-form>
    </template>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3500">{{ snackbar.text }}</v-snackbar>
  </div>
</template>
