import api from '@/services/api'

export async function sendChat(messages) {
  const res = await api.post('/ai/chat', { messages })
  return res.data.data.reply
}

export async function translateText(text, target) {
  const res = await api.post('/admin/ai/translate', { text, target })
  return res.data.data.text
}