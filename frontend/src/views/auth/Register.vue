<template>
  <AuthLayout
    headline="Register once, use every service."
    subline="Create a resident account to request clearances and book appointments online."
  >
    <h2 class="auth-layout__form-title text-h4 mb-1" style="color: #1c1c1c">
      Create your account
    </h2>
    <p class="text-body-2 mb-8" style="color: #5c5c5c">
      This registers you as a resident. Staff and admin accounts are issued by the barangay office.
    </p>

    <v-alert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      density="comfortable"
      class="mb-6"
      :text="errorMessage"
    />

    <v-form @submit.prevent="handleSubmit">
      <label class="text-body-2 font-weight-medium d-block mb-1">Full name</label>
      <v-text-field
        v-model="form.name"
        placeholder="Juan Dela Cruz"
        :rules="[rules.required]"
        class="mb-4"
        autocomplete="name"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1">Email address</label>
      <v-text-field
        v-model="form.email"
        type="email"
        placeholder="you@example.com"
        :rules="[rules.required, rules.email]"
        class="mb-4"
        autocomplete="email"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1">Contact number</label>
      <v-text-field
        v-model="form.contact_number"
        placeholder="09xx xxx xxxx"
        class="mb-4"
        autocomplete="tel"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1">Address</label>
      <v-text-field
        v-model="form.address"
        placeholder="House no., Street, Purok"
        class="mb-4"
        autocomplete="street-address"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1">Password</label>
      <v-text-field
        v-model="form.password"
        type="password"
        placeholder="At least 8 characters"
        :rules="[rules.required, rules.minLength]"
        class="mb-4"
        autocomplete="new-password"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1">Confirm password</label>
      <v-text-field
        v-model="form.password_confirmation"
        type="password"
        placeholder="Re-enter your password"
        :rules="[rules.required, rules.matches]"
        class="mb-6"
        autocomplete="new-password"
      />

      <v-btn type="submit" block size="large" color="primary" :loading="loading">
        Create account
      </v-btn>
    </v-form>

    <p class="text-body-2 text-center mt-8" style="color: #5c5c5c">
      Already registered?
      <RouterLink to="/login" class="font-weight-medium" style="color: #0b1b32">
        Sign in instead
      </RouterLink>
    </p>
  </AuthLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  contact_number: '',
  address: '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const errorMessage = ref('')

const rules = {
  required: (v) => !!v || 'This field is required.',
  email: (v) => /.+@.+\..+/.test(v) || 'Enter a valid email address.',
  minLength: (v) => (v && v.length >= 8) || 'Use at least 8 characters.',
  matches: (v) => v === form.password || 'Passwords do not match.',
}

async function handleSubmit() {
  errorMessage.value = ''
  loading.value = true
  try {
    await authStore.register(form)
    router.push('/dashboard')
  } catch (err) {
    const data = err?.response?.data
    errorMessage.value =
      data?.message ||
      Object.values(data?.errors || {})[0]?.[0] ||
      'Unable to create your account. Please check your details.'
  } finally {
    loading.value = false
  }
}
</script>
