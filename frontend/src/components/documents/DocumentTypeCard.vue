<script setup>
defineProps({
  type: { type: Object, required: true },
  selected: { type: Boolean, default: false },
})

defineEmits(['select'])
</script>

<template>
  <button
    type="button"
    class="doc-type-card"
    :class="{ 'doc-type-card--selected': selected }"
    @click="$emit('select', type.id)"
  >
    <span class="doc-type-card__name">{{ type.name }}</span>
    <span class="doc-type-card__description">{{ type.description }}</span>

    <span class="doc-type-card__meta">
      <span class="doc-type-card__fee">
        {{ type.fee > 0 ? `₱${type.fee.toFixed(2)}` : 'No fee' }}
      </span>
      <span class="doc-type-card__days">
        Ready in {{ type.processing_days }}
        {{ type.processing_days === 1 ? 'business day' : 'business days' }}
      </span>
    </span>

    <span v-if="selected" class="doc-type-card__check" aria-hidden="true">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path
          d="M3 8.5L6.2 11.5L13 4.5"
          stroke="var(--brgy-navy)"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>
  </button>
</template>

<style scoped>
.doc-type-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  text-align: left;
  width: 100%;
  padding: 18px 20px;
  background: var(--brgy-paper-raised);
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-sm);
  cursor: pointer;
  font-family: var(--brgy-font-body);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.doc-type-card:hover {
  border-color: var(--brgy-gold);
}

.doc-type-card:focus-visible {
  outline: 2px solid var(--brgy-navy);
  outline-offset: 2px;
}

.doc-type-card--selected {
  border-color: var(--brgy-gold);
  box-shadow: inset 0 0 0 1px var(--brgy-gold);
  background: linear-gradient(0deg, var(--brgy-gold-soft) 0%, var(--brgy-paper-raised) 55%);
}

.doc-type-card__name {
  font-family: var(--brgy-font-display);
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--brgy-ink);
}

.doc-type-card__description {
  font-size: 0.875rem;
  color: var(--brgy-ink-muted);
  line-height: 1.45;
}

.doc-type-card__meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-top: 8px;
  padding-top: 10px;
  border-top: 1px solid var(--brgy-line);
  font-size: 0.8rem;
}

.doc-type-card__fee {
  font-weight: 600;
  color: var(--brgy-navy);
}

.doc-type-card__days {
  color: var(--brgy-ink-muted);
}

.doc-type-card__check {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--brgy-gold-soft);
}
</style>
