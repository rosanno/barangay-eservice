<script setup>
import { useSurveyOptions } from '@/composables/useSurveyOptions'

defineProps({ survey: { type: Object, required: true } })
const { options, label } = useSurveyOptions()

const yn = (v) => (v === true ? 'Yes' : v === false ? 'No' : '—')
</script>

<template>
  <div v-if="options">
    <h3 class="text-subtitle-1 font-weight-bold mb-2">A. Household information</h3>
    <v-table density="compact" class="mb-6">
      <tbody>
        <tr><td class="text-medium-emphasis" width="40%">Household number</td><td>{{ survey.household_number || '—' }}</td></tr>
        <tr><td class="text-medium-emphasis">Household head</td><td>{{ survey.head_name || '—' }}</td></tr>
        <tr><td class="text-medium-emphasis">Address</td><td>{{ survey.address || '—' }}</td></tr>
        <tr><td class="text-medium-emphasis">Purok / Sitio</td><td>{{ survey.purok || '—' }}</td></tr>
        <tr><td class="text-medium-emphasis">Housing type</td><td>{{ label('housing_type', survey.housing_type) }}</td></tr>
        <tr><td class="text-medium-emphasis">Ownership status</td><td>{{ label('ownership_status', survey.ownership_status) }}</td></tr>
      </tbody>
    </v-table>

    <h3 class="text-subtitle-1 font-weight-bold mb-2">B & C. Family members ({{ survey.members.length }})</h3>
    <v-table density="compact" class="mb-6">
      <thead>
        <tr><th>Name</th><th>Relationship</th><th>Sex</th><th>Birthdate</th><th>Civil status</th><th>Education</th><th>Employment</th></tr>
      </thead>
      <tbody>
        <tr v-for="m in survey.members" :key="m.client_key">
          <td class="text-no-wrap">
            {{ m.last_name }}, {{ m.first_name }} {{ m.middle_name }} {{ m.suffix }}
            <v-chip v-if="m.is_new_resident" size="x-small" color="info" class="ml-1">New</v-chip>
            <v-chip v-else-if="m.resident_id" size="x-small" class="ml-1">Linked</v-chip>
          </td>
          <td>{{ label('relationship', m.relationship) }}</td>
          <td>{{ label('sex', m.sex) }}</td>
          <td class="text-no-wrap">{{ m.birthdate || '—' }}</td>
          <td>{{ label('civil_status', m.civil_status) }}</td>
          <td>{{ label('education', m.education) }}</td>
          <td>{{ label('employment_status', m.employment_status) }}<span v-if="m.occupation"> · {{ m.occupation }}</span></td>
        </tr>
      </tbody>
    </v-table>

    <h3 class="text-subtitle-1 font-weight-bold mb-2">D. Additional information</h3>
    <v-table density="compact">
      <tbody>
        <tr><td class="text-medium-emphasis" width="40%">Housing condition</td><td>{{ label('housing_condition', survey.housing_condition) }}</td></tr>
        <tr><td class="text-medium-emphasis">Water source</td><td>{{ label('water_source', survey.water_source) }}</td></tr>
        <tr><td class="text-medium-emphasis">Electricity</td><td>{{ yn(survey.has_electricity) }}</td></tr>
        <tr><td class="text-medium-emphasis">Sanitation</td><td>{{ label('sanitation', survey.sanitation) }}</td></tr>
        <tr v-for="q in options.extra_questions" :key="q.key">
          <td class="text-medium-emphasis">{{ q.label }}</td>
          <td>{{ q.type === 'yesno' ? yn(survey.extra_answers?.[q.key]) : survey.extra_answers?.[q.key] || '—' }}</td>
        </tr>
      </tbody>
    </v-table>
  </div>
</template>