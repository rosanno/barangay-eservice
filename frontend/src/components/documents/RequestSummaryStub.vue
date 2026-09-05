<script setup>
defineProps({
  selectedType: { type: Object, default: null },
  purpose: { type: String, default: '' },
  attachmentCount: { type: Number, default: 0 },
  canSubmit: { type: Boolean, default: false },
  submitting: { type: Boolean, default: false },
})

defineEmits(['submit'])
</script>

<template>
  <aside class="stub">
    <div class="stub__header">
      <span class="stub__eyebrow-seal" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="10" stroke="var(--brgy-gold)" stroke-width="1.4" />
          <path
            d="M8 12.5l2.5 2.5L16 9"
            stroke="var(--brgy-gold)"
            stroke-width="1.4"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </span>
      <h2 class="stub__title">Your Request</h2>
    </div>

    <div class="stub__body">
      <template v-if="selectedType">
        <p class="stub__document-name">{{ selectedType.name }}</p>

        <dl class="stub__facts">
          <div class="stub__fact">
            <dt>Fee</dt>
            <dd>{{ selectedType.fee > 0 ? `₱${selectedType.fee.toFixed(2)}` : 'No fee' }}</dd>
          </div>
          <div class="stub__fact">
            <dt>Processing time</dt>
            <dd>
              {{ selectedType.processing_days }}
              {{ selectedType.processing_days === 1 ? 'business day' : 'business days' }}
            </dd>
          </div>
          <div class="stub__fact">
            <dt>Purpose</dt>
            <dd>{{ purpose || '—' }}</dd>
          </div>
          <div class="stub__fact">
            <dt>Attachments</dt>
            <dd>{{ attachmentCount }} file{{ attachmentCount === 1 ? '' : 's' }}</dd>
          </div>
        </dl>
      </template>

      <p v-else class="stub__placeholder">
        Choose a document on the left to see its fee, requirements, and processing time here.
      </p>
    </div>

    <div v-if="selectedType?.requirements?.length" class="stub__perforation" role="presentation" />

    <div v-if="selectedType?.requirements?.length" class="stub__requirements">
      <p class="stub__requirements-title">Bring or attach</p>
      <ul>
        <li v-for="item in selectedType.requirements" :key="item">{{ item }}</li>
      </ul>
    </div>

    <div class="stub__perforation" role="presentation" />

    <div class="stub__footer">
      <button
        type="button"
        class="stub__submit"
        :disabled="!canSubmit"
        @click="$emit('submit')"
      >
        {{ submitting ? 'Submitting…' : 'Submit request' }}
      </button>
      <p class="stub__footer-note">
        You'll get a tracking number once this is submitted.
      </p>
    </div>
  </aside>
</template>

<style scoped>
.stub {
  position: sticky;
  top: 24px;
  background: var(--brgy-paper-raised);
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-md);
  overflow: hidden;
}

.stub__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 18px 20px 14px;
  background: var(--brgy-navy);
}

.stub__title {
  font-family: var(--brgy-font-display);
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--brgy-paper-raised);
  margin: 0;
}

.stub__body {
  padding: 18px 20px 4px;
}

.stub__document-name {
  font-family: var(--brgy-font-display);
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--brgy-ink);
  margin: 0 0 12px;
}

.stub__facts {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 0;
}

.stub__fact {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 0.85rem;
}

.stub__fact dt {
  color: var(--brgy-ink-muted);
}

.stub__fact dd {
  margin: 0;
  color: var(--brgy-ink);
  font-weight: 500;
  text-align: right;
}

.stub__placeholder {
  font-size: 0.85rem;
  color: var(--brgy-ink-muted);
  line-height: 1.5;
}

/* Ticket-style perforation: a dashed line with two notches cut into
   the card edges, as if this section could be torn along it. */
.stub__perforation {
  position: relative;
  height: 0;
  border-top: 2px dashed var(--brgy-line);
  margin: 16px 0;
}

.stub__perforation::before,
.stub__perforation::after {
  content: '';
  position: absolute;
  top: -9px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--brgy-paper);
}

.stub__perforation::before {
  left: -29px;
}

.stub__perforation::after {
  right: -29px;
}

.stub__requirements {
  padding: 0 20px;
}

.stub__requirements-title {
  font-size: 0.78rem;
  color: var(--brgy-ink-muted);
  margin: 0 0 8px;
}

.stub__requirements ul {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.stub__requirements li {
  font-size: 0.85rem;
  color: var(--brgy-ink);
}

.stub__footer {
  padding: 4px 20px 20px;
}

.stub__submit {
  width: 100%;
  padding: 12px 16px;
  background: var(--brgy-navy);
  color: var(--brgy-paper-raised);
  border: none;
  border-radius: var(--brgy-radius-sm);
  font-family: var(--brgy-font-body);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease;
}

.stub__submit:hover:not(:disabled) {
  background: var(--brgy-navy-light);
}

.stub__submit:disabled {
  background: var(--brgy-line);
  color: var(--brgy-ink-muted);
  cursor: not-allowed;
}

.stub__footer-note {
  margin: 10px 0 0;
  font-size: 0.75rem;
  color: var(--brgy-ink-muted);
  text-align: center;
}

@media (max-width: 960px) {
  .stub {
    position: static;
  }

  /* On narrow viewports the notches would clip against the page edge. */
  .stub__perforation::before,
  .stub__perforation::after {
    display: none;
  }
}
</style>
