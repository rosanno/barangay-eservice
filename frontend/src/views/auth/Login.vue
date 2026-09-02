<template>
  <AuthLayout
    headline="Welcome back."
    subline="Sign in to continue your requests and appointments."
  >
    <h2 class="auth-layout__form-title text-h4 mb-1" style="color: #1c1c1c">Sign in</h2>
    <p class="text-body-2 mb-8" style="color: #5c5c5c">
      Residents, staff, and admins use the same sign-in.
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
      <label class="text-body-2 font-weight-medium d-block mb-1" style="color: #1c1c1c">
        Email address
      </label>
      <v-text-field
        v-model="form.email"
        type="email"
        placeholder="you@example.com"
        :rules="[rules.required, rules.email]"
        class="mb-4"
        autocomplete="email"
      />

      <label class="text-body-2 font-weight-medium d-block mb-1" style="color: #1c1c1c">
        Password
      </label>
      <v-text-field
        v-model="form.password"
        :type="showPassword ? 'text' : 'password'"
        placeholder="Enter your password"
        :rules="[rules.required]"
        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
        autocomplete="current-password"
        class="mb-2"
        @click:append-inner="showPassword = !showPassword"
      />

      <div class="d-flex justify-end mb-6">
        <RouterLink to="/forgot-password" class="text-body-2" style="color: #0b1b32">
          Forgot password?
        </RouterLink>
      </div>

      <v-btn
        type="submit"
        block
        size="large"
        color="primary"
        :loading="loading"
      >
        Sign in
      </v-btn>
    </v-form>

    <p class="text-body-2 text-center mt-8" style="color: #5c5c5c">
      New here?
      <RouterLink to="/register" class="font-weight-medium" style="color: #0b1b32">
        Create a resident account
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

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

const rules = {
  required: (v) => !!v || 'This field is required.',
  email: (v) => /.+@.+\..+/.test(v) || 'Enter a valid email address.',
}

async function handleSubmit() {
  errorMessage.value = ''
  loading.value = true
  try {
    const user = await authStore.login(form)
    if (user.role === 'admin' || user.role === 'staff') {
      router.push('/admin/dashboard')
    } else {
      router.push('/dashboard')
    }
  } catch (err) {
    errorMessage.value =
      err?.response?.data?.message || 'Unable to sign in. Check your details and try again.'
  } finally {
    loading.value = false
  }
}
</script>
