<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getHousehold, createHousehold, updateHousehold } from '@/api/household'
import HouseholdFormFields from '@/components/household/HouseholdFormFields.vue'
import { emptyForm, fillForm } from '@/utils/householdForm'

const LIST_ROUTE = { name: 'admin-households' } // match your census route name

const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id)
const isEdit = computed(() => !!id.value)

const loading = ref(isEdit.value)
const saving = ref(false)
const existing = ref(null)
const errors = ref({})
const form = reactive(emptyForm())
const snackbar = reactive({ show: false, text: '', color: 'success' })
const notify = (text, color = 'success') => Object.assign(snackbar, { show: true, text, color })

const errorList = computed(() => Object.values(errors.value).flat())

async function submit(addAnother = false) {
  saving.value = true
  errors.value = {}
  try {
    const payload = JSON.parse(JSON.stringify(form))
    if (isEdit.value) {
      await updateHousehold(id.value, payload)
      router.push(LIST_ROUTE)
    } else {
      await createHousehold(payload)
      if (addAnother) {
        Object.assign(form, emptyForm())
        notify('Household saved. Ready for the next one.')
        window.scrollTo({ top: 0, behavior: 'smooth' })
      } else {
        router.push(LIST_ROUTE)
      }
    }
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
      notify('Please fix the highlighted fields', 'error')
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      notify(e.response?.data?.message ?? 'Something went wrong.', 'error')
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (!isEdit.value) return
  try {
    existing.value = await getHousehold(id.value)
    fillForm(form, existing.value)
  } catch {
    notify('Failed to load household', 'error')
    router.push(LIST_ROUTE)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div style="max-width: 920px; margin: 0 auto">
    <div class="d-flex align-center mb-4 ga-2">
      <v-btn icon="mdi-arrow-left" variant="text" density="comfortable" aria-label="Back" @click="router.push(LIST_ROUTE)" />
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-home-group" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">
            {{ isEdit ? `Edit ${existing?.household_number ?? 'household'}` : 'Add household survey' }}
          </span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ isEdit ? 'Saving keeps the current verification status.' : 'Saved as verified under your name.' }}
        </p>
      </div>
    </div>

    <div v-if="loading" class="d-flex justify-center py-10">
      <v-progress-circular indeterminate size="24" color="#0f1e3d" />
    </div>

    <template v-else>
      <v-alert v-if="errorList.length" type="error" variant="tonal" density="compact" class="mb-4">
        <div v-for="(m, i) in errorList.slice(0, 5)" :key="i">{{ m }}</div>
      </v-alert>

      <v-form @submit.prevent="submit(false)">
        <HouseholdFormFields :form="form" :errors="errors" />

        <section class="attest">
          <v-checkbox v-model="form.consent" color="#0f1e3d" hide-details>
            <template #label>
              <span style="font-size: 12.5px; color: #555; line-height: 1.5">
                I confirm the resident has given consent to collect and process this information under the
                Data Privacy Act of 2012 (RA 10173).
              </span>
            </template>
          </v-checkbox>
        </section>

        <div class="d-flex justify-end ga-2">
          <v-btn variant="text" style="text-transform: none" @click="router.push(LIST_ROUTE)">Cancel</v-btn>
          <v-btn
            v-if="!isEdit"
            variant="outlined"
            style="text-transform: none"
            :loading="saving"
            :disabled="!form.consent"
            @click="submit(true)"
          >
            Save &amp; add another
          </v-btn>
          <v-btn
            type="submit"
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="saving"
            :disabled="!form.consent"
          >
            {{ isEdit ? 'Save changes' : 'Save household' }}
          </v-btn>
        </div>
      </v-form>
    </template>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<style scoped>
.attest { background: #fff; border-radius: 10px; padding: 12px 20px; margin-bottom: 16px; }
</style>