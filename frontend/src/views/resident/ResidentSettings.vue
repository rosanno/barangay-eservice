<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getPreferences, updatePreferences, signOutOtherDevices } from '@/api/residentPreferences'

const router = useRouter()

const loading = ref(true)
const loadError = ref('')
const email = ref('')
const emailUpdates = ref(true)
const saving = ref(false)

const snack = reactive({ open: false, text: '', color: 'success' })
function notify(text, color = 'success') {
  Object.assign(snack, { open: true, text, color })
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const data = await getPreferences()
    email.value = data.email
    emailUpdates.value = data.email_request_updates
  } catch (e) {
    loadError.value = e.response?.data?.message ?? 'We could not load your settings. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

// Saves as soon as the switch is flipped; if the save fails, the switch
// goes back to where it was so the screen never lies about what's saved.
async function toggleEmailUpdates(value) {
  const previous = emailUpdates.value
  emailUpdates.value = value
  saving.value = true
  try {
    const data = await updatePreferences({ email_request_updates: value })
    emailUpdates.value = data.email_request_updates
    notify(value ? 'Email updates turned on.' : 'Email updates turned off.')
  } catch (e) {
    emailUpdates.value = previous
    notify(e.response?.data?.message ?? 'Could not save that change. Please try again.', 'error')
  } finally {
    saving.value = false
  }
}

// ── Sign out other devices ───────────────────────────────────────────
const confirmSignOut = ref(false)
const signingOut = ref(false)

async function doSignOutOthers() {
  signingOut.value = true
  try {
    const data = await signOutOtherDevices()
    confirmSignOut.value = false
    notify(
      data.signed_out > 0
        ? `Signed out of ${data.signed_out} other ${data.signed_out === 1 ? 'device' : 'devices'}.`
        : 'No other devices were signed in.'
    )
  } catch (e) {
    confirmSignOut.value = false
    notify(e.response?.data?.message ?? 'Could not sign out other devices.', 'error')
  } finally {
    signingOut.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <header class="head">
      <h1>Settings</h1>
      <p class="sub">Choose how the barangay contacts you and keep your account secure.</p>
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
      <!-- ─── Notifications ────────────────────────────────────── -->
      <section class="card">
        <h3>Notifications</h3>

        <div class="setting-row">
          <div class="setting-row__text">
            <p class="setting-row__title">Email me about my requests</p>
            <p class="setting-row__desc">
              Get an email when a request is processed, ready for pickup, released, or rejected.
              Sent to <strong>{{ email }}</strong>.
            </p>
          </div>
          <v-switch
            :model-value="emailUpdates"
            :loading="saving"
            :disabled="saving"
            color="#0B1F44"
            hide-details
            inset
            aria-label="Email me about my requests"
            @update:model-value="toggleEmailUpdates"
          />
        </div>

        <p class="note">
          Updates always appear under the bell icon in the app, whatever you choose here.
        </p>
      </section>

      <!-- ─── Security ─────────────────────────────────────────── -->
      <section class="card">
        <h3>Security</h3>

        <div class="setting-row">
          <div class="setting-row__text">
            <p class="setting-row__title">Sign out of other devices</p>
            <p class="setting-row__desc">
              Use this if you signed in on a shared or public computer. You'll stay signed in here.
            </p>
          </div>
          <v-btn variant="outlined" style="text-transform: none" @click="confirmSignOut = true">
            Sign out others
          </v-btn>
        </div>

        <div class="setting-row">
          <div class="setting-row__text">
            <p class="setting-row__title">Password</p>
            <p class="setting-row__desc">Change your password from your profile.</p>
          </div>
          <v-btn variant="outlined" style="text-transform: none" @click="router.push('/resident/profile')">
            Go to profile
          </v-btn>
        </div>
      </section>
    </template>

    <v-dialog v-model="confirmSignOut" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">Sign out of other devices?</v-card-title>
        <v-card-text style="font-size: 13px; color: #555">
          Anyone signed in to your account on another phone or computer will be signed out. You'll stay
          signed in on this device.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmSignOut = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="signingOut" @click="doSignOutOthers">Sign out others</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.open" :color="snack.color" timeout="3500">{{ snack.text }}</v-snackbar>
  </div>
</template>

<style scoped>
.page { max-width: 820px; margin: 0 auto; padding: 24px 16px 48px; }
.head { margin-bottom: 20px; }
h1 { font-size: 1.6rem; font-weight: 700; color: #0B1F44; }
h3 { font-size: 1rem; font-weight: 700; color: #0B1F44; margin-bottom: 8px; }
.sub { color: #6B7280; margin-top: 2px; font-size: .92rem; }

.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }

.setting-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 14px 0; }
.setting-row + .setting-row { border-top: 1px solid #F1F2F4; }
.setting-row__title { font-weight: 600; color: #1F2937; font-size: .95rem; }
.setting-row__desc { color: #6B7280; font-size: .85rem; margin-top: 2px; max-width: 480px; }

.note { color: #9CA3AF; font-size: .8rem; margin-top: 4px; }

.state-card { display: flex; flex-direction: column; align-items: center; text-align: center; background: #fff; border-radius: 14px; padding: 48px 20px; gap: 12px; }
.state-card__text { color: #6B7280; font-size: .9rem; max-width: 340px; }

@media (max-width: 560px) {
  .setting-row { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>