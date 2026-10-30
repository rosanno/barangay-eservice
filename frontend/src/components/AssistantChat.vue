<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { sendChat } from '../api/ai'

const { t } = useI18n()

const open = ref(false)
const messages = ref([])
const input = ref('')
const loading = ref(false)
const box = ref(null)
const field = ref(null)

async function scrollToBottom() {
  await nextTick()
  if (box.value) box.value.scrollTo({ top: box.value.scrollHeight, behavior: 'smooth' })
}

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await nextTick()
    scrollToBottom()
    field.value?.focus()
  }
}

function close() {
  open.value = false
}

function onKey(e) {
  if (e.key === 'Escape' && open.value) close()
}
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

async function send() {
  const text = input.value.trim()
  if (!text || loading.value) return

  messages.value.push({ role: 'user', content: text })
  input.value = ''
  loading.value = true
  scrollToBottom()

  try {
    const reply = await sendChat(messages.value)
    messages.value.push({ role: 'assistant', content: reply })
  } catch {
    messages.value.push({ role: 'assistant', content: t('assistant.error') })
  } finally {
    loading.value = false
    scrollToBottom()
  }
}
</script>

<template>
  <div class="assistant">
    <!-- ─── Chat panel ───────────────────────────────────────────── -->
    <Transition name="chat-pop">
      <section
        v-show="open"
        class="chat"
        role="dialog"
        :aria-label="t('assistant.title')"
      >
        <header class="chat__head">
          <span class="chat__icon">
            <v-icon icon="mdi-robot-happy-outline" size="18" />
          </span>
          <span class="chat__title">{{ t('assistant.title') }}</span>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            class="chat__close"
            :aria-label="t('assistant.close', 'Close')"
            @click="close"
          />
        </header>

        <div ref="box" class="chat__body" aria-live="polite">
          <div v-if="!messages.length && !loading" class="chat__empty">
            <v-icon icon="mdi-chat-processing-outline" size="40" />
          </div>

          <div
            v-for="(m, i) in messages"
            :key="i"
            class="msg"
            :class="m.role === 'user' ? 'msg--user' : 'msg--assistant'"
          >
            <span v-if="m.role !== 'user'" class="msg__avatar">
              <v-icon icon="mdi-robot-happy-outline" size="14" />
            </span>
            <div class="msg__bubble">{{ m.content }}</div>
          </div>

          <div v-if="loading" class="msg msg--assistant">
            <span class="msg__avatar">
              <v-icon icon="mdi-robot-happy-outline" size="14" />
            </span>
            <div class="msg__bubble msg__bubble--typing" aria-hidden="true">
              <span /><span /><span />
            </div>
          </div>
        </div>

        <footer class="chat__footer">
          <div class="chat__input-row">
            <v-text-field
              ref="field"
              v-model="input"
              :placeholder="t('assistant.placeholder')"
              variant="outlined"
              density="comfortable"
              hide-details
              autocomplete="off"
              class="chat__input"
              @keyup.enter="send"
            />
            <v-btn
              icon="mdi-send"
              class="chat__send"
              :disabled="!input.trim() || loading"
              @click="send"
            />
          </div>
          <p class="chat__disclaimer">{{ t('assistant.disclaimer') }}</p>
        </footer>
      </section>
    </Transition>

    <!-- ─── Floating launcher ────────────────────────────────────── -->
    <button
      type="button"
      class="fab"
      :class="{ 'fab--open': open }"
      :aria-label="t('assistant.title')"
      :aria-expanded="open"
      @click="toggle"
    >
      <v-icon :icon="open ? 'mdi-chevron-down' : 'mdi-robot-happy-outline'" size="26" />
    </button>
  </div>
</template>

<style scoped src="./AssistantChatCss.css">
</style>