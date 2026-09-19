<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fetchMyAppointments, bookAppointment, cancelAppointment } from '@/api/appointments'

const appointments = ref([])
const loading = ref(true)

const form = reactive({
  purpose: '',
  date: '',
  time: '',
})

const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const canSubmit = computed(
  () => form.purpose.trim().length >= 3 && form.date && form.time && !submitting.value
)

async function loadAppointments() {
  loading.value = true
  try {
    appointments.value = await fetchMyAppointments()
  } catch {
    appointments.value = []
  } finally {
    loading.value = false
  }
}

async function submit() {
  errorMessage.value = ''
  successMessage.value = ''
  submitting.value = true
  try {
    const scheduledAt = new Date(`${form.date}T${form.time}`).toISOString()
    const created = await bookAppointment({ purpose: form.purpose, scheduledAt })

    successMessage.value = `Appointment booked for ${formatDateTime(created.scheduled_at)}.`
    form.purpose = ''
    form.date = ''
    form.time = ''
    await loadAppointments()
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Could not book that time. Please check the date/time and try again.'
  } finally {
    submitting.value = false
  }
}

async function cancel(appointment) {
  try {
    await cancelAppointment(appointment.id)
    await loadAppointments()
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Could not cancel this appointment.'
  }
}

function formatDateTime(iso) {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const statusMeta = {
  scheduled: { label: 'Scheduled', color: 'processing' },
  completed: { label: 'Completed', color: 'released' },
  cancelled: { label: 'Cancelled', color: 'rejected' },
  no_show: { label: 'No Show', color: 'rejected' },
}

// A sensible earliest-selectable date — today — so the native date input
// doesn't let someone pick a day that's already in the past.
const todayIso = new Date().toISOString().slice(0, 10)

onMounted(loadAppointments)
</script>

<template>
  <div class="appointments-page">
    <v-alert v-if="successMessage" type="success" variant="tonal" class="mb-4" closable>
      {{ successMessage }}
    </v-alert>
    <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
      {{ errorMessage }}
    </v-alert>

    <div class="page-body">
      <section class="panel">
        <h2 class="panel__title">Book an appointment</h2>

        <div class="field-block">
          <label class="field-label">What is this for?</label>
          <v-textarea
            v-model="form.purpose"
            variant="outlined"
            density="compact"
            rows="2"
            placeholder="e.g. Clearance renewal, business permit inquiry"
            hide-details
          />
        </div>

        <div class="field-row">
          <div class="field-block">
            <label class="field-label">Date</label>
            <input v-model="form.date" type="date" class="native-input" :min="todayIso">
          </div>
          <div class="field-block">
            <label class="field-label">Time</label>
            <input v-model="form.time" type="time" class="native-input">
          </div>
        </div>

        <button type="button" class="submit-btn" :disabled="!canSubmit" @click="submit">
          {{ submitting ? 'Booking…' : 'Book appointment' }}
        </button>
      </section>

      <section class="panel">
        <h2 class="panel__title">Your upcoming appointments</h2>

        <div v-if="loading" class="loading-row">
          <v-progress-circular indeterminate size="20" />
        </div>

        <p v-else-if="!appointments.length" class="empty-text">
          No upcoming appointments — book one on the left.
        </p>

        <ul v-else class="appt-list">
          <li v-for="appt in appointments" :key="appt.id" class="appt-list__item">
            <div class="appt-list__main">
              <span class="appt-list__purpose">{{ appt.purpose }}</span>
              <span class="appt-list__time">{{ formatDateTime(appt.scheduled_at) }}</span>
            </div>
            <span class="pill" :class="`pill--${statusMeta[appt.status]?.color}`">
              {{ statusMeta[appt.status]?.label || appt.status }}
            </span>
            <button
              v-if="appt.status === 'scheduled'"
              type="button"
              class="cancel-link"
              @click="cancel(appt)"
            >
              Cancel
            </button>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<style scoped>
.appointments-page {
  font-family: var(--brgy-font-body);
  color: var(--brgy-ink);
}

.page-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: start;
}

.panel {
  background: var(--brgy-paper-raised);
  border-radius: var(--brgy-radius-md);
  box-shadow: var(--brgy-shadow-card);
  padding: 22px 24px;
}

.panel__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 16px;
  color: var(--brgy-ink);
}

.field-block {
  margin-bottom: 16px;
  flex: 1;
}

.field-row {
  display: flex;
  gap: 12px;
}

.field-label {
  display: block;
  font-size: 0.8rem;
  color: var(--brgy-ink-muted);
  margin-bottom: 6px;
}

.native-input {
  width: 100%;
  padding: 9px 10px;
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-sm);
  font-family: var(--brgy-font-body);
  font-size: 0.85rem;
  color: var(--brgy-ink);
  background: var(--brgy-paper-raised);
}

.submit-btn {
  width: 100%;
  padding: 11px 16px;
  background: var(--brgy-navy);
  color: #fff;
  border: none;
  border-radius: var(--brgy-radius-sm);
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}

.submit-btn:disabled {
  background: var(--brgy-line);
  color: var(--brgy-ink-muted);
  cursor: not-allowed;
}

.loading-row {
  display: flex;
  justify-content: center;
  padding: 24px 0;
}

.empty-text {
  font-size: 0.85rem;
  color: var(--brgy-ink-muted);
}

.appt-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.appt-list__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 0;
  border-bottom: 1px solid var(--brgy-line);
}

.appt-list__item:last-child {
  border-bottom: none;
}

.appt-list__main {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.appt-list__purpose {
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--brgy-ink);
}

.appt-list__time {
  font-size: 0.76rem;
  color: var(--brgy-ink-muted);
}

.pill {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 999px;
  white-space: nowrap;
}

.pill--processing {
  background: var(--brgy-status-processing-bg, #eaf1fe);
  color: var(--brgy-status-processing, #2563eb);
}
.pill--released {
  background: var(--brgy-status-released-bg, #e8f6ec);
  color: var(--brgy-status-released, #15803d);
}
.pill--rejected {
  background: var(--brgy-status-rejected-bg, #fbeaea);
  color: var(--brgy-status-rejected, #b91c1c);
}

.cancel-link {
  background: none;
  border: none;
  font-size: 0.76rem;
  color: var(--brgy-status-rejected, #b91c1c);
  cursor: pointer;
  padding: 0;
}

@media (max-width: 900px) {
  .page-body {
    grid-template-columns: 1fr;
  }
}
</style>