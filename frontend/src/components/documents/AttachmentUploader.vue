<script setup>
import { ref } from 'vue'

const props = defineProps({
  attachments: { type: Array, required: true }, // [{ file, label }]
})

const emit = defineEmits(['add', 'remove', 'update-label'])

const isDragging = ref(false)
const fileInput = ref(null)

function onDrop(event) {
  isDragging.value = false
  emit('add', event.dataTransfer.files)
}

function onBrowse(event) {
  emit('add', event.target.files)
  event.target.value = ''
}

function formatSize(bytes) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
  <div>
    <div
      class="dropzone"
      :class="{ 'dropzone--active': isDragging }"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="fileInput.click()"
    >
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
          d="M12 4v11m0-11l-4 4m4-4l4 4M5 17v1a2 2 0 002 2h10a2 2 0 002-2v-1"
          stroke="var(--brgy-gold)"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
      <p class="dropzone__label">
        <strong>Drop files here</strong> or click to browse
      </p>
      <p class="dropzone__hint">JPG, PNG, or PDF · up to 5 MB each</p>
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".jpg,.jpeg,.png,.pdf"
        class="dropzone__input"
        @change="onBrowse"
      >
    </div>

    <ul v-if="attachments.length" class="attachment-list">
      <li v-for="(attachment, index) in attachments" :key="index" class="attachment-list__item">
        <div class="attachment-list__info">
          <span class="attachment-list__name">{{ attachment.file.name }}</span>
          <span class="attachment-list__size">{{ formatSize(attachment.file.size) }}</span>
        </div>

        <input
          class="attachment-list__label-input"
          type="text"
          placeholder="What is this? (e.g. Valid ID)"
          :value="attachment.label"
          @input="$emit('update-label', index, $event.target.value)"
        >

        <button
          type="button"
          class="attachment-list__remove"
          aria-label="Remove attachment"
          @click="$emit('remove', index)"
        >
          &times;
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 28px 20px;
  text-align: center;
  border: 1.5px dashed var(--brgy-gold-soft);
  border-radius: var(--brgy-radius-sm);
  background: var(--brgy-paper-raised);
  cursor: pointer;
  transition: border-color 0.15s ease, background 0.15s ease;
}

.dropzone--active,
.dropzone:hover {
  border-color: var(--brgy-gold);
  background: color-mix(in srgb, var(--brgy-gold-soft) 25%, var(--brgy-paper-raised));
}

.dropzone__label {
  font-family: var(--brgy-font-body);
  font-size: 0.9rem;
  color: var(--brgy-ink);
  margin: 4px 0 0;
}

.dropzone__hint {
  font-size: 0.78rem;
  color: var(--brgy-ink-muted);
  margin: 0;
}

.dropzone__input {
  display: none;
}

.attachment-list {
  list-style: none;
  margin: 14px 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.attachment-list__item {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: var(--brgy-paper-raised);
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-sm);
}

.attachment-list__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.attachment-list__name {
  font-size: 0.85rem;
  color: var(--brgy-ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attachment-list__size {
  font-size: 0.72rem;
  color: var(--brgy-ink-muted);
}

.attachment-list__label-input {
  font-size: 0.82rem;
  padding: 6px 8px;
  border: 1px solid var(--brgy-line);
  border-radius: var(--brgy-radius-sm);
  background: var(--brgy-paper);
  font-family: var(--brgy-font-body);
}

.attachment-list__remove {
  background: none;
  border: none;
  font-size: 1.1rem;
  line-height: 1;
  color: var(--brgy-status-rejected);
  cursor: pointer;
  padding: 4px 8px;
}
</style>
