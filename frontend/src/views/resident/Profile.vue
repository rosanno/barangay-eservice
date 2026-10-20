<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { getProfile, updateProfile, changePassword } from '@/api/residentProfile'

const loading = ref(true)
const loadError = ref('')
const account = ref(null)
const profile = ref(null)

// ── Snackbar ─────────────────────────────────────────────────────────
const snack = reactive({ open: false, text: '', color: 'success' })
function notify(text, color = 'success') {
  Object.assign(snack, { open: true, text, color })
}

// ── Editable details ─────────────────────────────────────────────────
// Must match UpdateProfileRequest on the backend. Anything not listed here
// (name, birth details, purok, parents' names) stays admin-managed.
const EDITABLE = [
  'religion',
  'blood_type',
  'mother_occupation',
  'father_occupation',
  'spouse_first_name',
  'spouse_middle_name',
  'spouse_last_name',
  'spouse_suffix',
  'number_of_children',
  'emergency_contact_first_name',
  'emergency_contact_middle_name',
  'emergency_contact_last_name',
  'emergency_contact_suffix',
  'emergency_contact_number',
]

const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']

const form = reactive(Object.fromEntries(EDITABLE.map((k) => [k, ''])))
const original = ref('')
const saving = ref(false)
const fieldErrors = ref({})

function fillForm(p) {
  for (const k of EDITABLE) form[k] = p[k] ?? ''
  original.value = JSON.stringify(form)
}

const dirty = computed(() => JSON.stringify(form) !== original.value)

const canSave = computed(
  () =>
    dirty.value &&
    String(form.emergency_contact_first_name).trim() &&
    String(form.emergency_contact_last_name).trim() &&
    String(form.emergency_contact_number).trim()
)

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await getProfile()
    account.value = data.account
    profile.value = data.profile
    if (data.profile) fillForm(data.profile)
  } catch (e) {
    loadError.value = e.response?.data?.message ?? 'We could not load your profile. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  fieldErrors.value = {}
  try {
    const data = await updateProfile({ ...form })
    profile.value = data.profile
    fillForm(data.profile)
    notify('Your details were updated.')
  } catch (e) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      fieldErrors.value = e.response.data.errors
    } else {
      notify(e.response?.data?.message ?? 'Could not save your changes.', 'error')
    }
  } finally {
    saving.value = false
  }
}

function resetForm() {
  if (profile.value) fillForm(profile.value)
  fieldErrors.value = {}
}

// ── Read-only identity details ───────────────────────────────────────
function formatDob(iso) {
  return new Date(`${iso}T00:00:00`).toLocaleDateString('en-PH', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })
}

const personalFacts = computed(() => {
  const p = profile.value
  if (!p) return []
  return [
    { label: 'Full name', value: p.full_name },
    { label: 'Sex', value: p.sex_label },
    {
      label: 'Date of birth',
      value: p.date_of_birth ? `${formatDob(p.date_of_birth)} (${p.age} yrs)` : '',
    },
    { label: 'Place of birth', value: p.place_of_birth },
    { label: 'Citizenship', value: p.citizenship },
    { label: 'Purok', value: p.purok },
    { label: "Mother's name", value: p.mother_full_name },
    { label: "Father's name", value: p.father_full_name },
  ]
})

const displayName = computed(() => profile.value?.full_name ?? account.value?.name ?? '')

const initials = computed(() =>
  displayName.value
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0])
    .join('')
    .toUpperCase()
)

// ── Change password ──────────────────────────────────────────────────
const pw = reactive({ current: '', next: '', confirm: '' })
const showPw = ref(false)
const pwSaving = ref(false)
const pwErrors = ref({})

const mismatch = computed(() => pw.confirm && pw.confirm !== pw.next)
const canChangePw = computed(
  () => pw.current && pw.next.length >= 8 && pw.next === pw.confirm && pw.next !== pw.current
)

async function savePassword() {
  pwSaving.value = true
  pwErrors.value = {}
  try {
    await changePassword({
      current_password: pw.current,
      password: pw.next,
      password_confirmation: pw.confirm,
    })
    Object.assign(pw, { current: '', next: '', confirm: '' })
    notify('Password updated. Other devices have been signed out.')
  } catch (e) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      pwErrors.value = e.response.data.errors
    } else {
      notify(e.response?.data?.message ?? 'Could not change your password.', 'error')
    }
  } finally {
    pwSaving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <header class="head">
      <h1>My profile</h1>
      <p class="sub">Your details as recorded by the barangay, and your sign-in settings.</p>
    </header>

    <div v-if="loading" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate size="26" color="#0B1F44" />
    </div>

    <div v-else-if="loadError" class="state-card">
      <v-icon size="36" color="#c0392b">mdi-alert-circle-outline</v-icon>
      <p class="state-card__text">{{ loadError }}</p>
      <v-btn variant="outlined" style="text-transform: none" @click="load">Try again</v-btn>
    </div>

    <template v-else>
      <!-- ─── Identity header ──────────────────────────────────── -->
      <section class="card identity">
        <span class="avatar">{{ initials }}</span>
        <div class="identity__text">
          <h2>{{ displayName }}</h2>
          <p>{{ account.email }}</p>
          <p v-if="profile" class="identity__meta">{{ profile.purok }} · {{ profile.age }} years old</p>
        </div>
      </section>

      <v-alert v-if="!profile" type="info" variant="tonal" class="mb-4">
        Your barangay profile hasn't been set up yet. Please visit the barangay office so staff can
        complete your record. You can still change your password below.
      </v-alert>

      <template v-if="profile">
        <!-- ─── Read-only personal details ─────────────────────── -->
        <section class="card">
          <h3>Personal details</h3>
          <p class="hint">
            These were verified when you were registered. To correct any of them, visit the barangay office.
          </p>
          <dl class="facts">
            <div v-for="fact in personalFacts" :key="fact.label">
              <dt>{{ fact.label }}</dt>
              <dd>{{ fact.value || '—' }}</dd>
            </div>
          </dl>
        </section>

        <!-- ─── Editable details ───────────────────────────────── -->
        <section class="card">
          <h3>Details you can update</h3>
          <p class="hint">Keep your emergency contact current so the barangay can reach someone if needed.</p>

          <div class="grid-2">
            <v-text-field
              v-model="form.religion"
              label="Religion"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.religion"
            />
            <v-select
              v-model="form.blood_type"
              :items="BLOOD_TYPES"
              label="Blood type"
              variant="outlined"
              density="comfortable"
              clearable
              :error-messages="fieldErrors.blood_type"
            />
            <v-text-field
              v-model="form.mother_occupation"
              label="Mother's occupation"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.mother_occupation"
            />
            <v-text-field
              v-model="form.father_occupation"
              label="Father's occupation"
              variant="outlined"
              density="comfortable"
              :error-messages="fieldErrors.father_occupation"
            />
          </div>

          <p class="group-label">Spouse (if applicable)</p>
          <div class="grid-3">
            <v-text-field v-model="form.spouse_first_name" label="First name" variant="outlined" density="comfortable" :error-messages="fieldErrors.spouse_first_name" />
            <v-text-field v-model="form.spouse_middle_name" label="Middle name" variant="outlined" density="comfortable" :error-messages="fieldErrors.spouse_middle_name" />
            <v-text-field v-model="form.spouse_last_name" label="Last name" variant="outlined" density="comfortable" :error-messages="fieldErrors.spouse_last_name" />
            <v-text-field v-model="form.spouse_suffix" label="Suffix" placeholder="Jr., Sr., III" variant="outlined" density="comfortable" :error-messages="fieldErrors.spouse_suffix" />
            <v-text-field v-model.number="form.number_of_children" label="Number of children" type="number" min="0" variant="outlined" density="comfortable" :error-messages="fieldErrors.number_of_children" />
          </div>

          <p class="group-label">Emergency contact</p>
          <div class="grid-3">
            <v-text-field v-model="form.emergency_contact_first_name" label="First name *" variant="outlined" density="comfortable" :error-messages="fieldErrors.emergency_contact_first_name" />
            <v-text-field v-model="form.emergency_contact_middle_name" label="Middle name" variant="outlined" density="comfortable" :error-messages="fieldErrors.emergency_contact_middle_name" />
            <v-text-field v-model="form.emergency_contact_last_name" label="Last name *" variant="outlined" density="comfortable" :error-messages="fieldErrors.emergency_contact_last_name" />
            <v-text-field v-model="form.emergency_contact_suffix" label="Suffix" placeholder="Jr., Sr., III" variant="outlined" density="comfortable" :error-messages="fieldErrors.emergency_contact_suffix" />
            <v-text-field v-model="form.emergency_contact_number" label="Contact number *" variant="outlined" density="comfortable" :error-messages="fieldErrors.emergency_contact_number" />
          </div>

          <div class="actions">
            <v-btn variant="text" style="text-transform: none" :disabled="!dirty || saving" @click="resetForm">
              Discard changes
            </v-btn>
            <v-btn class="primary-btn" :loading="saving" :disabled="!canSave" @click="save">
              Save changes
            </v-btn>
          </div>
        </section>
      </template>

      <!-- ─── Password ───────────────────────────────────────────── -->
      <section class="card">
        <h3>Change password</h3>
        <p class="hint">After you change it, your other signed-in devices are signed out.</p>

        <div class="pw-grid">
          <v-text-field
            v-model="pw.current"
            label="Current password"
            :type="showPw ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            autocomplete="current-password"
            :error-messages="pwErrors.current_password"
          />
          <v-text-field
            v-model="pw.next"
            label="New password"
            :type="showPw ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            autocomplete="new-password"
            hint="At least 8 characters"
            :error-messages="pwErrors.password"
          />
          <v-text-field
            v-model="pw.confirm"
            label="Confirm new password"
            :type="showPw ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            autocomplete="new-password"
            :error-messages="mismatch ? ['Passwords do not match'] : []"
          />
        </div>

        <div class="actions actions--between">
          <v-checkbox v-model="showPw" label="Show passwords" density="compact" hide-details />
          <v-btn class="primary-btn" :loading="pwSaving" :disabled="!canChangePw" @click="savePassword">
            Update password
          </v-btn>
        </div>
      </section>
    </template>

    <v-snackbar v-model="snack.open" :color="snack.color" timeout="3500">{{ snack.text }}</v-snackbar>
  </div>
</template>

<style scoped>
.page { max-width: 820px; margin: 0 auto; padding: 24px 16px 48px; }
.head { margin-bottom: 20px; }
h1 { font-size: 1.6rem; font-weight: 700; color: #0B1F44; }
h2 { font-size: 1.1rem; font-weight: 700; color: #1F2937; }
h3 { font-size: 1rem; font-weight: 700; color: #0B1F44; margin-bottom: 4px; }
.sub { color: #6B7280; margin-top: 2px; font-size: .92rem; }
.hint { color: #6B7280; font-size: .85rem; margin-bottom: 16px; }

.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }

.identity { display: flex; align-items: center; gap: 16px; }
.avatar { width: 56px; height: 56px; border-radius: 50%; background: #0f1e3d; color: #f5a623; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700; flex-shrink: 0; }
.identity__text p { color: #6B7280; font-size: .88rem; margin-top: 1px; }
.identity__meta { color: #9CA3AF !important; }

.facts { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; margin: 0; }
dt { font-size: .8rem; color: #6B7280; }
dd { font-weight: 600; color: #1F2937; margin-top: 2px; font-size: .92rem; }

.group-label { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: .04em; margin: 8px 0 12px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 16px; }
.pw-grid { display: grid; grid-template-columns: 1fr; gap: 0; max-width: 420px; }

.actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
.actions--between { justify-content: space-between; align-items: center; max-width: 420px; }
.primary-btn { background: #0B1F44 !important; color: #fff !important; text-transform: none; letter-spacing: 0; font-weight: 600; }

.state-card { display: flex; flex-direction: column; align-items: center; text-align: center; background: #fff; border-radius: 14px; padding: 48px 20px; gap: 12px; }
.state-card__text { color: #6B7280; font-size: .9rem; max-width: 340px; }

@media (max-width: 720px) {
  .facts, .grid-2, .grid-3 { grid-template-columns: 1fr; }
}
</style>