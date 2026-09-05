<script setup>
import { onMounted } from 'vue'
import { useDocumentRequestForm } from '@/composables/useDocumentRequestForm'
import DocumentTypeCard from '@/components/documents/DocumentTypeCard.vue'
import AttachmentUploader from '@/components/documents/AttachmentUploader.vue'
import RequestSummaryStub from '@/components/documents/RequestSummaryStub.vue'

const {
  documentTypes,
  recentRequests,
  loadingTypes,
  submitting,
  submitError,
  submittedRequest,
  form,
  selectedType,
  isTypeSelected,
  canSubmit,
  loadDocumentTypes,
  loadRecentRequests,
  selectType,
  addAttachments,
  removeAttachment,
  submit,
} = useDocumentRequestForm()

onMounted(() => {
  loadDocumentTypes()
  loadRecentRequests()
})

function updateAttachmentLabel(index, value) {
  form.attachments[index].label = value
}

async function handleSubmit() {
  try {
    await submit()
  } catch {
    // submitError is already set by the composable; nothing further to do here.
  }
}

const statusStyles = {
  pending: 'status--processing',
  processing: 'status--processing',
  ready_for_pickup: 'status--ready',
  released: 'status--ready',
  rejected: 'status--rejected',
  cancelled: 'status--rejected',
}
</script>

<template>
  <div class="request-page">
    <header class="request-page__header">
      <p class="request-page__eyebrow">Documents</p>
      <h1 class="request-page__title">Request a document</h1>
      <p class="request-page__intro">
        Choose the document you need, tell us what it's for, and attach any
        requirements. We'll queue it for processing and notify you as it moves along.
      </p>
    </header>

    <v-alert
      v-if="submittedRequest"
      type="success"
      variant="tonal"
      class="request-page__success"
      closable
    >
      Request submitted. Your tracking number is
      <strong>{{ submittedRequest.tracking_number }}</strong> — we'll email you as its status changes.
    </v-alert>

    <v-alert
      v-if="submitError"
      type="error"
      variant="tonal"
      class="request-page__success"
    >
      {{ submitError }}
    </v-alert>

    <v-row class="request-page__layout">
      <v-col cols="12" md="8">
        <section class="request-section">
          <h2 class="request-section__title">
            <span class="request-section__number">1</span>
            Choose a document
          </h2>

          <div v-if="loadingTypes" class="request-section__loading">
            <v-progress-circular indeterminate size="22" color="var(--brgy-navy)" />
          </div>

          <div v-else class="doc-type-grid">
            <DocumentTypeCard
              v-for="type in documentTypes"
              :key="type.id"
              :type="type"
              :selected="type.id === form.documentTypeId"
              @select="selectType"
            />
          </div>
        </section>

        <section class="request-section" :class="{ 'request-section--disabled': !isTypeSelected }">
          <h2 class="request-section__title">
            <span class="request-section__number">2</span>
            Purpose &amp; details
          </h2>

          <v-textarea
            v-model="form.purpose"
            :disabled="!isTypeSelected"
            variant="outlined"
            label="What is this document for?"
            placeholder="e.g. Employment requirement, school enrollment, loan application"
            rows="3"
            hide-details="auto"
          />
        </section>

        <section class="request-section" :class="{ 'request-section--disabled': !isTypeSelected }">
          <h2 class="request-section__title">
            <span class="request-section__number">3</span>
            Attachments
          </h2>
          <p class="request-section__hint">
            Optional, but attaching requirements now speeds up processing.
          </p>

          <AttachmentUploader
            :attachments="form.attachments"
            @add="addAttachments"
            @remove="removeAttachment"
            @update-label="updateAttachmentLabel"
          />
        </section>

        <section v-if="recentRequests.length" class="request-section">
          <h2 class="request-section__title request-section__title--no-number">
            Your recent requests
          </h2>

          <ul class="recent-list">
            <li v-for="item in recentRequests" :key="item.id" class="recent-list__item">
              <div class="recent-list__main">
                <span class="recent-list__name">{{ item.document_type.name }}</span>
                <span class="recent-list__tracking">{{ item.tracking_number }}</span>
              </div>
              <span class="recent-list__status" :class="statusStyles[item.status]">
                {{ item.status_label }}
              </span>
            </li>
          </ul>
        </section>
      </v-col>

      <v-col cols="12" md="4">
        <RequestSummaryStub
          :selected-type="selectedType"
          :purpose="form.purpose"
          :attachment-count="form.attachments.length"
          :can-submit="canSubmit"
          :submitting="submitting"
          @submit="handleSubmit"
        />
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.request-page {
  background: var(--brgy-paper);
  min-height: 100%;
  padding: 32px clamp(16px, 4vw, 48px) 64px;
  font-family: var(--brgy-font-body);
  color: var(--brgy-ink);
}

.request-page__header {
  max-width: 640px;
  margin-bottom: 28px;
}

.request-page__eyebrow {
  font-size: 0.8rem;
  color: var(--brgy-gold);
  font-weight: 600;
  margin: 0 0 4px;
}

.request-page__title {
  font-family: var(--brgy-font-display);
  font-size: clamp(1.6rem, 2.4vw, 2.1rem);
  font-weight: 600;
  color: var(--brgy-navy);
  margin: 0 0 10px;
}

.request-page__intro {
  font-size: 0.95rem;
  line-height: 1.6;
  color: var(--brgy-ink-muted);
  max-width: 58ch;
}

.request-page__success {
  max-width: 900px;
  margin-bottom: 20px;
}

.request-page__layout {
  max-width: 1200px;
}

.request-section {
  margin-bottom: 36px;
}

.request-section--disabled {
  opacity: 0.55;
  pointer-events: none;
}

.request-section__title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--brgy-font-display);
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--brgy-ink);
  margin: 0 0 6px;
}

.request-section__title--no-number {
  margin-bottom: 14px;
}

.request-section__number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--brgy-navy);
  color: var(--brgy-paper-raised);
  font-family: var(--brgy-font-body);
  font-size: 0.85rem;
  font-weight: 600;
}

.request-section__hint {
  font-size: 0.85rem;
  color: var(--brgy-ink-muted);
  margin: 0 0 14px;
}

.request-section__loading {
  display: flex;
  justify-content: center;
  padding: 32px 0;
}

.doc-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 14px;
  margin-top: 14px;
}

.recent-list {
  list-style: none;
  margin: 0;
  padding: 0;
  border-top: 1px solid var(--brgy-line);
}

.recent-list__item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--brgy-line);
}

.recent-list__main {
  display: flex;
  flex-direction: column;
}

.recent-list__name {
  font-size: 0.9rem;
  color: var(--brgy-ink);
}

.recent-list__tracking {
  font-size: 0.75rem;
  color: var(--brgy-ink-muted);
}

.recent-list__status {
  font-size: 0.78rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 999px;
}

.status--processing {
  color: var(--brgy-status-processing);
  background: color-mix(in srgb, var(--brgy-status-processing) 12%, transparent);
}

.status--ready {
  color: var(--brgy-status-ready);
  background: color-mix(in srgb, var(--brgy-status-ready) 12%, transparent);
}

.status--rejected {
  color: var(--brgy-status-rejected);
  background: color-mix(in srgb, var(--brgy-status-rejected) 12%, transparent);
}
</style>
