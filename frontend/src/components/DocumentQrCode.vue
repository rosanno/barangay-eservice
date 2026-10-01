<template>
  <div class="qr-block">
    <img v-if="dataUrl" :src="dataUrl" :alt="`QR code for ${trackingNumber}`" class="qr-image">
    <div v-else class="qr-loading">
      <v-progress-circular indeterminate size="20" />
    </div>

    <a v-if="dataUrl" :href="dataUrl" :download="`${trackingNumber}-qr.png`" class="qr-download">
      <v-icon icon="mdi-download" size="14" />
      Download QR
    </a>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({
  trackingNumber: { type: String, required: true },
})

const dataUrl = ref('')

async function generate() {
  // Points at this SPA's own public verify page — see VerifyDocument.vue.
  // window.location.origin means this encodes whatever domain the app is
  // actually running on (localhost in dev, your real domain in production)
  // rather than a hardcoded URL that would break outside one environment.
  const verifyUrl = `${window.location.origin}/verify/${props.trackingNumber}`

  dataUrl.value = await QRCode.toDataURL(verifyUrl, {
    width: 160,
    margin: 1,
    color: { dark: '#0f1e3d', light: '#ffffff' },
  })
}

onMounted(generate)
watch(() => props.trackingNumber, generate)
</script>

<style scoped>
.qr-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: #fafafa;
  border: 1px dashed #ddd;
  border-radius: 10px;
}

.qr-image {
  width: 160px;
  height: 160px;
  display: block;
}

.qr-loading {
  width: 160px;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qr-download {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #0f1e3d;
  font-weight: 600;
  text-decoration: none;
}
</style>