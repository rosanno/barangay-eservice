import { ref } from 'vue'
import { getSurveyOptions } from '@/api/householdSurveyApi'

const options = ref(null) // cached across pages
let pending = null

export function useSurveyOptions() {
  const load = () => {
    if (options.value) return Promise.resolve(options.value)
    pending ??= getSurveyOptions().then((o) => (options.value = o)).finally(() => (pending = null))
    return pending
  }
  const label = (key, value) =>
    options.value?.[key]?.find((o) => o.value === value)?.title ?? value ?? '—'
  return { options, load, label }
}

export const surveyStatusMeta = {
  not_started:      { label: 'Not started',      bg: '#E5E7EB', fg: '#374151' },
  draft:            { label: 'Draft',            bg: '#FEF3C7', fg: '#92400E' },
  for_verification: { label: 'For verification', bg: '#DBEAFE', fg: '#1E40AF' },
  needs_revision:   { label: 'Needs revision',   bg: '#FEE2E2', fg: '#991B1B' },
  approved:         { label: 'Approved',         bg: '#DCFCE7', fg: '#166534' },
}