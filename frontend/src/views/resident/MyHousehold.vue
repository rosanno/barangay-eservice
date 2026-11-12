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

// Same colors as the status chips on the admin pages.
const STATUS_INFO = {
  pending:      { label: 'Pending verification', color: '#a05e00', bg: '#fff3e0', text: 'Your household profile is waiting for verification by barangay staff.' },
  verified:     { label: 'Verified',             color: '#1b6e34', bg: '#e8f5e9', text: 'Your household profile is verified.' },
  needs_update: { label: 'Needs update',         color: '#1a5fd8', bg: '#e8f0fe', text: 'Barangay staff asked you to update your profile.' },
  rejected:     { label: 'Rejected',             color: '#c0392b', bg: '#fdecea', text: 'Your household profile was rejected. Please visit the barangay hall.' },
  inactive:     { label: 'Inactive',             color: '#6b7280', bg: '#f3f4f6', text: 'This household is marked inactive.' },
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
const statusInfo = computed(() => STATUS_INFO[existing.value?.status] ?? null)

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
  <div class="page">
    <!-- <h1>My household</h1> -->
    <p class="sub">Complete your household profile once. It helps the barangay process your document requests faster.</p>

    <div v-if="loading" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate size="26" color="#0B1F44" />
    </div>

    <template v-else>
      <!-- ─── Verification status ──────────────────────────────── -->
      <section
        v-if="existing && statusInfo"
        class="card status-card"
        :style="{ borderLeftColor: statusInfo.color }"
      >
        <div class="status-card__top">
          <span class="status-card__number">{{ existing.household_number }}</span>
          <span class="status-chip" :style="{ color: statusInfo.color, background: statusInfo.bg }">
            {{ statusInfo.label }}
          </span>
        </div>
        <p class="status-card__text">{{ statusInfo.text }}</p>
        <p v-if="existing.remarks" class="status-card__remarks">“{{ existing.remarks }}”</p>
      </section>

      <v-alert v-if="errorList.length" type="error" variant="tonal" class="mb-4">
        <div v-for="(m, i) in errorList.slice(0, 5)" :key="i">{{ m }}</div>
      </v-alert>

      <v-form :disabled="isLocked" @submit.prevent="submit">
        <!-- ─── Household info ─────────────────────────────────── -->
        <section class="card">
          <h3>Household information</h3>
          <p class="hint">Where you live and the basic condition of your home.</p>

          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="form.purok" label="Purok / Zone" variant="outlined" density="comfortable" :error-messages="err('purok')" /></v-col>
            <v-col cols="12" sm="8"><v-text-field v-model="form.street_address" label="Street / House No." variant="outlined" density="comfortable" :error-messages="err('street_address')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.housing_tenure" :items="toItems(opts.tenure)" label="Housing tenure" variant="outlined" density="comfortable" :error-messages="err('housing_tenure')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.house_material" :items="toItems(opts.material)" label="House material" variant="outlined" density="comfortable" :error-messages="err('house_material')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.water_source" :items="toItems(opts.water)" label="Water source" variant="outlined" density="comfortable" :error-messages="err('water_source')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.toilet_type" :items="toItems(opts.toilet)" label="Toilet facility" variant="outlined" density="comfortable" :error-messages="err('toilet_type')" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.income_bracket" :items="toItems(opts.income)" label="Monthly household income (optional)" variant="outlined" density="comfortable" clearable /></v-col>
            <v-col cols="12" sm="6" class="d-flex flex-column justify-center">
              <v-switch v-model="form.has_electricity" label="Has electricity" color="primary" hide-details density="compact" inset />
              <v-switch v-model="form.is_4ps" label="4Ps beneficiary" color="primary" hide-details density="compact" inset />
            </v-col>
          </v-row>
        </section>

        <!-- ─── Members ─────────────────────────────────────────── -->
        <div class="members-head">
          <div>
            <h3>Household members</h3>
            <p class="hint">
              {{ form.members.length }} {{ form.members.length === 1 ? 'member' : 'members' }} · include everyone who lives in your household.
            </p>
          </div>
          <v-btn
            variant="outlined"
            style="text-transform: none"
            prepend-icon="mdi-plus"
            :disabled="isLocked || form.members.length >= 30"
            @click="addMember"
          >
            Add member
          </v-btn>
        </div>

        <section
          v-for="(m, i) in form.members"
          :key="i"
          class="card member"
          :class="{ 'member--head': m.is_head }"
        >
          <div class="member__top">
            <div class="member__title">
              <span class="member__name">Member {{ i + 1 }}</span>
              <span v-if="m.is_head" class="status-chip chip--head">Head</span>
              <button v-else type="button" class="link-btn" :disabled="isLocked" @click="makeHead(i)">
                Set as head
              </button>
            </div>
            <v-btn
              v-if="form.members.length > 1 && !m.is_head"
              icon="mdi-delete-outline"
              variant="text"
              density="comfortable"
              class="action-btn action-btn--danger"
              aria-label="Remove member"
              @click="removeMember(i)"
            />
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
              <v-checkbox v-model="m.is_pwd" label="PWD" color="primary" hide-details density="compact" />
              <v-checkbox v-model="m.is_solo_parent" label="Solo parent" color="primary" hide-details density="compact" />
              <v-checkbox v-model="m.is_registered_voter" label="Registered voter" color="primary" hide-details density="compact" />
            </v-col>
          </v-row>
        </section>

        <!-- ─── Consent ─────────────────────────────────────────── -->
        <section class="card">
          <v-checkbox v-model="form.consent" color="primary" hide-details :error-messages="err('consent')">
            <template #label>
              <span class="consent-text">I confirm the information above is true and I consent to the Barangay collecting and processing it for census and public-service purposes in accordance with the Data Privacy Act of 2012 (RA 10173).</span>
            </template>
          </v-checkbox>
        </section>

        <div class="actions">
          <v-btn
            type="submit"
            class="primary-btn"
            size="large"
            variant="flat"
            :loading="saving"
            :disabled="!form.consent || isLocked"
          >
            {{ existing ? 'Update & resubmit' : 'Submit household profile' }}
          </v-btn>
        </div>
      </v-form>
    </template>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3500">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<style scoped>
/* Points Vuetify's "primary" at the system navy for everything inside this
   page (focus rings, switches, checkboxes), so color="primary" stays valid. */
.page { --v-theme-primary: 11, 31, 68; max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }

h3 { font-size: 1rem; font-weight: 700; color: #0B1F44; margin-bottom: 4px; }
.sub { color: #6B7280; font-size: .92rem; margin-bottom: 20px; }
.hint { color: #6B7280; font-size: .85rem; margin-bottom: 16px; }

.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }

/* Status chip: same shape as the admin pages */
.status-chip { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; display: inline-block; white-space: nowrap; }

/* Verification status card */
.status-card { border-left: 3px solid; }
.status-card__top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.status-card__number { font-size: .95rem; font-weight: 700; color: #1F2937; }
.status-card__text { color: #4B5563; font-size: .88rem; margin-top: 6px; }
.status-card__remarks { color: #6B7280; font-size: .85rem; font-style: italic; margin-top: 6px; }

/* Members */
.members-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin: 8px 0 4px; flex-wrap: wrap; }
.members-head .hint { margin-bottom: 12px; }
.member--head { border-left: 3px solid #f5a623; }
.member__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.member__title { display: flex; align-items: center; gap: 10px; }
.member__name { font-size: .95rem; font-weight: 600; color: #1F2937; }
.chip--head { color: #1a5fd8; background: #e8f0fe; }

.link-btn { border: 0; background: transparent; font: inherit; font-size: .8rem; font-weight: 600; color: #0B1F44; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
.link-btn:hover:not(:disabled) { background: #f3f1ec; }
.link-btn:disabled { opacity: .4; cursor: default; }

.action-btn { width: 32px; height: 32px; border-radius: 8px; color: #8a8f98; transition: background .15s, color .15s; }
.action-btn:hover { background: #f1f3f7; }
.action-btn--danger:hover { background: #fdecea; color: #c0392b; }

.consent-text { font-size: .85rem; color: #4B5563; line-height: 1.5; }

.actions { display: flex; justify-content: flex-end; }
.primary-btn { background: #0B1F44 !important; color: #fff !important; text-transform: none; letter-spacing: 0; font-weight: 600; }
.primary-btn:disabled,
.primary-btn.v-btn--disabled { background: #e5e7eb !important; color: #9ca3af !important; }
</style>