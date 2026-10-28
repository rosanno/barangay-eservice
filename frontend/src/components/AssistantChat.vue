<script setup>
import { ref, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { sendChat } from '../api/ai'

const { t } = useI18n()

const messages = ref([])
const input = ref('')
const loading = ref(false)
const box = ref(null)

async function scrollToBottom() {
  await nextTick()
  if (box.value) box.value.scrollTo({ top: box.value.scrollHeight, behavior: 'smooth' })
}

async function send() {
  const text = input.value.trim()
  if (!text || loading.value) return

  messages.value.push({ role: 'user', content: text })
  input.value = ''
  loading.value = true
  scrollToBottom() // show the resident's own message right away, not only after the reply

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
  <section class="chat" :aria-label="t('assistant.title')">
    <!-- ─── Header ───────────────────────────────────────────────── -->
    <header class="chat__head">
      <span class="chat__icon">
        <v-icon icon="mdi-robot-happy-outline" size="18" />
      </span>
      <span class="chat__title">{{ t('assistant.title') }}</span>
    </header>

    <!-- ─── Messages ─────────────────────────────────────────────── -->
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

    <!-- ─── Input ────────────────────────────────────────────────── -->
    <footer class="chat__footer">
      <div class="chat__input-row">
        <v-text-field
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
</template>

<style scoped>
.chat {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: min(620px, calc(100vh - 200px));
  min-height: 420px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  overflow: hidden;
}

/* Header: same icon + small title pattern as the other pages */
.chat__head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  border-bottom: 1px solid #f0ede3;
}

.chat__icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #fff9ed;
  color: #f5a623;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat__title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
}

/* Messages */
.chat__body {
  flex: 1;
  overflow-y: auto;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: #fafafa;
}

.chat__empty {
  margin: auto;
  color: #d4d4d4;
}

.msg {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}

.msg--user {
  justify-content: flex-end;
}

.msg__avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #0f1e3d;
  color: #f5a623;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.msg__bubble {
  max-width: 80%;
  padding: 10px 14px;
  font-size: 13.5px;
  line-height: 1.5;
  white-space: pre-wrap; /* keeps the line breaks in the assistant's reply */
  word-break: break-word;
}

.msg--user .msg__bubble {
  background: #0f1e3d;
  color: #ffffff;
  border-radius: 14px 14px 4px 14px;
}

.msg--assistant .msg__bubble {
  background: #ffffff;
  color: #1a1a1a;
  border: 1px solid #ebe8e2;
  border-radius: 14px 14px 14px 4px;
}

/* Typing indicator */
.msg__bubble--typing {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 14px 16px;
}

.msg__bubble--typing span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #b8b8b8;
  animation: chat-dot 1.2s infinite ease-in-out;
}

.msg__bubble--typing span:nth-child(2) {
  animation-delay: 0.15s;
}

.msg__bubble--typing span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes chat-dot {
  0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
  30% { opacity: 1; transform: translateY(-3px); }
}

/* Input */
.chat__footer {
  padding: 12px 16px 14px;
  border-top: 1px solid #f0ede3;
  background: #ffffff;
}

.chat__input-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chat__input {
  flex: 1;
}

.chat__input :deep(.v-field__input) {
  font-size: 13.5px;
}

.chat__send {
  background: #0f1e3d !important;
  color: #ffffff !important;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
}

.chat__send:disabled {
  background: #e5e7eb !important;
  color: #9ca3af !important;
}

.chat__disclaimer {
  font-size: 11px;
  color: #999;
  margin: 8px 2px 0;
}
</style>