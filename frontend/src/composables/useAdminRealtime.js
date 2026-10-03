import { onMounted, onBeforeUnmount } from 'vue'
import { initEcho } from '@/plugins/echo'
import { useAuthStore } from '@/store/auth'

export function useAdminRealtime({ onSubmitted, onStatusChanged }) {
  const auth = useAuthStore()
  let channel = null

  onMounted(() => {
    const echo = initEcho(auth.token)
    channel = echo.private('admin.dashboard')
      .listen('.clearance.submitted', onSubmitted)
      .listen('.clearance.status-changed', onStatusChanged)
  })

  onBeforeUnmount(() => {
    if (channel) {
      channel.stopListening('.clearance.submitted')
      channel.stopListening('.clearance.status-changed')
      window.Echo?.leave?.('admin.dashboard')
    }
  })
}