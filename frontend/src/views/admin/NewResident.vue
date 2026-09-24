<template>
  <div style="max-width: 760px">
    <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
      {{ errorMessage }}
    </v-alert>

    <!-- ─── Personal information ─────────────────────────────────── -->
    <v-card variant="flat" class="section-card">
      <p class="section-title">Personal Information</p>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Purok <span class="req">*</span></label>
          <v-text-field v-model="form.purok" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">First name <span class="req">*</span></label>
          <v-text-field v-model="form.first_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Middle name</label>
          <v-text-field v-model="form.middle_name" v-bind="fieldProps" />
        </div>
      </div>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Last name <span class="req">*</span></label>
          <v-text-field v-model="form.last_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Sex <span class="req">*</span></label>
          <v-select
            v-model="form.sex"
            :items="sexOptions"
            item-title="label"
            item-value="value"
            v-bind="fieldProps"
          />
        </div>
        <div class="field-block">
          <label class="field-label">Date of birth <span class="req">*</span></label>
          <input v-model="form.date_of_birth" type="date" class="native-input" :max="todayIso">
        </div>
      </div>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Age</label>
          <div class="computed-value">{{ computedAge ?? '—' }}</div>
        </div>
        <div class="field-block">
          <label class="field-label">Place of birth <span class="req">*</span></label>
          <v-text-field v-model="form.place_of_birth" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Citizenship <span class="req">*</span></label>
          <v-text-field v-model="form.citizenship" v-bind="fieldProps" />
        </div>
      </div>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Religion</label>
          <v-text-field v-model="form.religion" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Blood type</label>
          <v-select v-model="form.blood_type" :items="bloodTypeOptions" v-bind="fieldProps" clearable />
        </div>
      </div>
    </v-card>

    <!-- ─── Family background ────────────────────────────────────── -->
    <v-card variant="flat" class="section-card">
      <p class="section-title">Family Background</p>

      <p class="subsection-label">Mother</p>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">First name <span class="req">*</span></label>
          <v-text-field v-model="form.mother_first_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Middle name</label>
          <v-text-field v-model="form.mother_middle_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Last name <span class="req">*</span></label>
          <v-text-field v-model="form.mother_last_name" v-bind="fieldProps" />
        </div>
      </div>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Occupation</label>
          <v-text-field v-model="form.mother_occupation" v-bind="fieldProps" />
        </div>
      </div>

      <p class="subsection-label">Father</p>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">First name <span class="req">*</span></label>
          <v-text-field v-model="form.father_first_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Middle name</label>
          <v-text-field v-model="form.father_middle_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Last name <span class="req">*</span></label>
          <v-text-field v-model="form.father_last_name" v-bind="fieldProps" />
        </div>
      </div>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Suffix</label>
          <v-text-field v-model="form.father_suffix" placeholder="Jr., Sr., III" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Occupation</label>
          <v-text-field v-model="form.father_occupation" v-bind="fieldProps" />
        </div>
      </div>

      <p class="subsection-label">Spouse (if applicable)</p>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">First name</label>
          <v-text-field v-model="form.spouse_first_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Middle name</label>
          <v-text-field v-model="form.spouse_middle_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Last name</label>
          <v-text-field v-model="form.spouse_last_name" v-bind="fieldProps" />
        </div>
      </div>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Suffix</label>
          <v-text-field v-model="form.spouse_suffix" placeholder="Jr., Sr., III" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Number of children</label>
          <v-text-field v-model.number="form.number_of_children" type="number" min="0" v-bind="fieldProps" />
        </div>
      </div>
    </v-card>

    <!-- ─── Emergency contact ────────────────────────────────────── -->
    <v-card variant="flat" class="section-card">
      <p class="section-title">Emergency Contact</p>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">First name <span class="req">*</span></label>
          <v-text-field v-model="form.emergency_contact_first_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Middle name</label>
          <v-text-field v-model="form.emergency_contact_middle_name" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Last name <span class="req">*</span></label>
          <v-text-field v-model="form.emergency_contact_last_name" v-bind="fieldProps" />
        </div>
      </div>
      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Suffix</label>
          <v-text-field v-model="form.emergency_contact_suffix" placeholder="Jr., Sr., III" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Contact number <span class="req">*</span></label>
          <v-text-field v-model="form.emergency_contact_number" v-bind="fieldProps" />
        </div>
      </div>
    </v-card>

    <!-- ─── Account credentials ──────────────────────────────────── -->
    <v-card variant="flat" class="section-card">
      <p class="section-title">System Account</p>
      <p class="section-hint">
        Separate from the profile above — this is what the resident uses to log in.
      </p>

      <div class="grid-3">
        <div class="field-block">
          <label class="field-label">Email <span class="req">*</span></label>
          <v-text-field v-model="form.email" type="email" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Password <span class="req">*</span></label>
          <v-text-field v-model="form.password" type="password" v-bind="fieldProps" />
        </div>
        <div class="field-block">
          <label class="field-label">Confirm password <span class="req">*</span></label>
          <v-text-field v-model="form.password_confirmation" type="password" v-bind="fieldProps" />
        </div>
      </div>
    </v-card>

    <div class="d-flex ga-2 justify-end">
      <v-btn variant="text" style="text-transform: none" to="/admin/residents">Cancel</v-btn>
      <v-btn
        style="background: #0f1e3d; color: #fff; text-transform: none"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="submit"
      >
        Register resident
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createResident } from '@/api/adminResidents'

const router = useRouter()

const fieldProps = { density: 'compact', variant: 'outlined', hideDetails: true }

const sexOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
]

const bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']

const todayIso = new Date().toISOString().slice(0, 10)

const form = reactive({
  purok: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  sex: null,
  date_of_birth: '',
  place_of_birth: '',
  citizenship: 'Filipino',
  religion: '',
  blood_type: null,

  mother_first_name: '',
  mother_middle_name: '',
  mother_last_name: '',
  mother_occupation: '',

  father_first_name: '',
  father_middle_name: '',
  father_last_name: '',
  father_suffix: '',
  father_occupation: '',

  spouse_first_name: '',
  spouse_middle_name: '',
  spouse_last_name: '',
  spouse_suffix: '',
  number_of_children: null,

  emergency_contact_first_name: '',
  emergency_contact_middle_name: '',
  emergency_contact_last_name: '',
  emergency_contact_suffix: '',
  emergency_contact_number: '',

  email: '',
  password: '',
  password_confirmation: '',
})

const computedAge = computed(() => {
  if (!form.date_of_birth) return null
  const dob = new Date(form.date_of_birth)
  const diff = Date.now() - dob.getTime()
  return Math.floor(diff / (365.25 * 24 * 60 * 60 * 1000))
})

const requiredFields = [
  'purok', 'first_name', 'last_name', 'sex', 'date_of_birth', 'place_of_birth', 'citizenship',
  'mother_first_name', 'mother_last_name',
  'father_first_name', 'father_last_name',
  'emergency_contact_first_name', 'emergency_contact_last_name', 'emergency_contact_number',
  'email', 'password', 'password_confirmation',
]

const canSubmit = computed(() => {
  const hasAllRequired = requiredFields.every((key) => String(form[key] ?? '').trim())
  const passwordsMatch = form.password === form.password_confirmation
  const passwordLongEnough = form.password.length >= 8
  return hasAllRequired && passwordsMatch && passwordLongEnough
})

const submitting = ref(false)
const errorMessage = ref('')

async function submit() {
  errorMessage.value = ''
  submitting.value = true
  try {
    await createResident({ ...form })
    router.push('/admin/residents')
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      Object.values(error?.response?.data?.errors || {})[0]?.[0] ||
      'Could not register this resident. Please check the form and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.section-card {
  background: #ffffff;
  border-radius: 10px;
  box-shadow: none;
  padding: 20px 24px;
  margin-bottom: 18px;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 4px;
}

.section-hint {
  font-size: 11px;
  color: #999;
  margin: 0 0 16px;
}

.subsection-label {
  font-size: 11px;
  font-weight: 600;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 18px 0 10px;
}

.grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-bottom: 4px;
}

.field-block {
  margin-bottom: 14px;
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

.native-input {
  width: 100%;
  padding: 9px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  height: 40px;
  box-sizing: border-box;
}

.computed-value {
  padding: 9px 10px;
  border: 1px dashed #ddd;
  border-radius: 6px;
  font-size: 13px;
  color: #666;
  background: #fafafa;
  height: 40px;
  display: flex;
  align-items: center;
  box-sizing: border-box;
}

@media (max-width: 720px) {
  .grid-3 {
    grid-template-columns: 1fr;
  }
}
</style>