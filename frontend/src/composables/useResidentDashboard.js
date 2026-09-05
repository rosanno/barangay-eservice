import { ref } from 'vue'
import { fetchMyDocumentRequests } from '@/api/documentRequests'

export function useResidentDashboard() {
  const loading = ref(true)
  const recentRequests = ref([])
  const stats = ref({
    total: 0,
    pending: 0,
    readyForPickup: 0,
    rejected: 0,
  })

  async function load() {
    loading.value = true
    try {
      // A dedicated GET /api/dashboard-stats endpoint would be cheaper than
      // computing this client-side, but until that exists, pull a page of
      // this resident's own requests and derive counts from it.
      const { data, meta } = await fetchMyDocumentRequests({ per_page: 50 })

      recentRequests.value = data.slice(0, 5)
      stats.value = {
        total: meta?.total ?? data.length,
        pending: data.filter((r) => r.status === 'pending').length,
        readyForPickup: data.filter((r) => r.status === 'ready_for_pickup').length,
        rejected: data.filter((r) => r.status === 'rejected').length,
      }
    } catch {
      recentRequests.value = []
    } finally {
      loading.value = false
    }
  }

  return { loading, recentRequests, stats, load }
}
