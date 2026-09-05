import { computed, reactive, ref } from 'vue'
import {
  fetchDocumentTypes,
  fetchMyDocumentRequests,
  submitDocumentRequest,
} from '@/api/documentRequests'

export function useDocumentRequestForm() {
  const documentTypes = ref([])
  const recentRequests = ref([])
  const loadingTypes = ref(false)
  const submitting = ref(false)
  const submitError = ref('')
  const submittedRequest = ref(null)

  const form = reactive({
    documentTypeId: null,
    purpose: '',
    details: {},
    attachments: [], // [{ file, label }]
  })

  const selectedType = computed(
    () => documentTypes.value.find((type) => type.id === form.documentTypeId) || null
  )

  const isPurposeValid = computed(() => form.purpose.trim().length >= 5)
  const isTypeSelected = computed(() => form.documentTypeId !== null)
  const canSubmit = computed(
    () => isTypeSelected.value && isPurposeValid.value && !submitting.value
  )

  async function loadDocumentTypes() {
    loadingTypes.value = true
    try {
      documentTypes.value = await fetchDocumentTypes()
    } finally {
      loadingTypes.value = false
    }
  }

  async function loadRecentRequests() {
    try {
      const { data } = await fetchMyDocumentRequests({ per_page: 5 })
      recentRequests.value = data
    } catch {
      // Non-critical for the request flow itself; fail quietly.
      recentRequests.value = []
    }
  }

  function selectType(typeId) {
    form.documentTypeId = typeId
    form.details = {}
  }

  function addAttachments(files) {
    Array.from(files).forEach((file) => {
      form.attachments.push({ file, label: '' })
    })
  }

  function removeAttachment(index) {
    form.attachments.splice(index, 1)
  }

  async function submit() {
    submitError.value = ''
    submitting.value = true
    try {
      submittedRequest.value = await submitDocumentRequest(form)
      await loadRecentRequests()
      resetForm()
    } catch (error) {
      submitError.value =
        error?.response?.data?.message ||
        'We could not submit your request. Please check the form and try again.'
      throw error
    } finally {
      submitting.value = false
    }
  }

  function resetForm() {
    form.documentTypeId = null
    form.purpose = ''
    form.details = {}
    form.attachments = []
  }

  return {
    documentTypes,
    recentRequests,
    loadingTypes,
    submitting,
    submitError,
    submittedRequest,
    form,
    selectedType,
    isPurposeValid,
    isTypeSelected,
    canSubmit,
    loadDocumentTypes,
    loadRecentRequests,
    selectType,
    addAttachments,
    removeAttachment,
    submit,
  }
}
