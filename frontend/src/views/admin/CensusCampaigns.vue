<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-clipboard-list-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Census campaigns</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">{{ total }} total</p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search campaigns"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input"
          style="width: 280px"
        />
        <v-select
          v-model="statusFilter"
          :items="statusItems"
          item-title="title"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 170px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-btn
          variant="flat"
          color="#0f1e3d"
          prepend-icon="mdi-plus"
          class="new-btn"
          @click="openCreate"
        >
          New campaign
        </v-btn>
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!campaigns.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          {{ emptyText }}
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col || 'actions'"
                class="table-head"
                :class="{ 'table-head--actions': !col }"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in campaigns" :key="c.id" class="table-row">
              <td style="padding: 12px 5px">
                <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">{{ c.name }}</span>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px; white-space: nowrap">
                {{ fmt(c.start_date) }} – {{ fmt(c.end_date) }}
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ c.puroks.length }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ c.enumerators.length }}</td>
              <td style="padding: 12px 8px; min-width: 170px">
                <v-progress-linear
                  :model-value="pct(c)"
                  color="#f5a623"
                  bg-color="#f2f2f2"
                  bg-opacity="1"
                  height="6"
                  rounded
                />
                <div style="font-size: 11px; color: #999; margin-top: 4px">
                  {{ c.surveyed_households }}/{{ c.total_households }} households ({{ pct(c) }}%)
                </div>
              </td>
              <td style="padding: 12px 8px">
                <span class="campaign-chip" :class="`campaign-chip--${c.status}`">
                  {{ statusMeta[c.status]?.label || c.status }}
                </span>
              </td>

              <!-- Actions -->
              <td class="actions-cell">
                <div class="row-actions">
                  <v-tooltip text="View details" location="top">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-eye-outline"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        aria-label="View details"
                        @click="viewing = c"
                      />
                    </template>
                  </v-tooltip>

                  <v-menu>
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-dots-vertical"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        aria-label="More actions"
                      />
                    </template>
                    <v-list density="compact">
                      <v-list-item
                        v-for="a in actionsFor(c)"
                        :key="a.status"
                        :prepend-icon="a.icon"
                        @click="changeStatus(c, a.status)"
                      >
                        <v-list-item-title style="font-size: 13px">{{ a.title }}</v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        v-if="c.status !== 'completed'"
                        prepend-icon="mdi-pencil-outline"
                        @click="openEdit(c)"
                      >
                        <v-list-item-title style="font-size: 13px">Edit</v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        v-if="c.status !== 'active'"
                        prepend-icon="mdi-delete-outline"
                        base-color="error"
                        @click="remove(c)"
                      >
                        <v-list-item-title style="font-size: 13px">Delete</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- ─── View details ─────────────────────────────────────────── -->
    <v-dialog :model-value="!!viewing" max-width="480" @update:model-value="viewing = null">
      <v-card v-if="viewing" style="border-radius: 10px">
        <v-card-title style="font-size: 15px; padding: 18px 24px 4px">{{ viewing.name }}</v-card-title>
        <v-card-text class="px-6 pb-2">
          <div class="mb-3">
            <span class="campaign-chip" :class="`campaign-chip--${viewing.status}`">
              {{ statusMeta[viewing.status]?.label || viewing.status }}
            </span>
          </div>

          <p
            v-if="viewing.description"
            style="font-size: 12.5px; color: #666; margin: 0 0 14px; line-height: 1.5"
          >
            {{ viewing.description }}
          </p>

          <div class="fact-grid">
            <div class="fact">
              <span class="fact__label">Schedule</span>
              <span class="fact__value">{{ fmt(viewing.start_date) }} – {{ fmt(viewing.end_date) }}</span>
            </div>
            <div class="fact">
              <span class="fact__label">Households surveyed</span>
              <span class="fact__value">
                {{ viewing.surveyed_households }}
                <span class="fact__sub">of {{ viewing.total_households }} ({{ pct(viewing) }}%)</span>
              </span>
            </div>
          </div>

          <v-progress-linear
            :model-value="pct(viewing)"
            color="#f5a623"
            bg-color="#f2f2f2"
            bg-opacity="1"
            height="6"
            rounded
            class="mb-5"
          />

          <p class="section-label">Target puroks</p>
          <div class="chip-row">
            <span v-for="p in viewing.puroks" :key="p.id" class="info-chip">{{ p.purok }}</span>
            <span v-if="!viewing.puroks.length" class="empty-note">None assigned</span>
          </div>

          <p class="section-label">Enumerators</p>
          <div class="chip-row">
            <span v-for="e in viewing.enumerators" :key="e.id" class="info-chip">{{ e.name }}</span>
            <span v-if="!viewing.enumerators.length" class="empty-note">None assigned</span>
          </div>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer />
          <v-btn variant="text" @click="viewing = null">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── Delete confirmation ──────────────────────────────────── -->
    <v-dialog v-model="deleteDialog.open" max-width="420" :persistent="deleteDialog.submitting">
      <v-card style="border-radius: 10px">
        <v-card-title style="font-size: 15px; font-weight: 600; padding: 18px 24px 4px">
          Delete campaign
        </v-card-title>
        <v-card-text class="px-6 pb-2">
          <p style="font-size: 12.5px; color: #666; margin: 0; line-height: 1.5">
            Delete
            <strong style="color: #1a1a1a; font-weight: 500">{{ deleteDialog.target?.name }}</strong>?
            This cannot be undone.
          </p>
        </v-card-text>
        <v-card-actions class="px-6 pb-4 pt-2">
          <v-spacer />
          <v-btn
            variant="text"
            class="dialog-btn"
            :disabled="deleteDialog.submitting"
            @click="deleteDialog.open = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            class="dialog-btn"
            :loading="deleteDialog.submitting"
            @click="confirmRemove"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <CampaignFormDialog
      v-model="formOpen"
      :campaign="editing"
      :options="options"
      @saved="onSaved"
    />

    <v-snackbar v-model="toast.show" :color="toast.color" timeout="3000">
      {{ toast.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import CampaignFormDialog from '@/components/dashboard/CampaignFormDialog.vue'
import {
  getCampaigns, getCampaignOptions, updateCampaignStatus, deleteCampaign,
} from '@/api/censusCampaignApi'

const tableColumns = ['Campaign', 'Schedule', 'Puroks', 'Enumerators', 'Progress', 'Status', '']

const campaigns = ref([])
const total = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref('all')
const options = ref({ puroks: [], enumerators: [] })

const formOpen = ref(false)
const editing = ref(null)
const viewing = ref(null)
const toast = ref({ show: false, text: '', color: 'success' })
const deleteDialog = reactive({ open: false, target: null, submitting: false })

const statusMeta = {
  draft: { label: 'Draft' },
  active: { label: 'Active' },
  paused: { label: 'Paused' },
  completed: { label: 'Completed' },
}
const statusItems = [
  { title: 'All statuses', value: 'all' },
  ...Object.entries(statusMeta).map(([value, m]) => ({ title: m.label, value })),
]

const pct = (c) => (c.total_households ? Math.round((c.surveyed_households / c.total_households) * 100) : 0)
const fmt = (d) => new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })

// Which actions each status allows (mirrors the backend transitions)
const actionsFor = (c) => {
  const a = []
  if (c.status === 'draft') a.push({ title: 'Activate', status: 'active', icon: 'mdi-play' })
  if (c.status === 'paused') a.push({ title: 'Resume', status: 'active', icon: 'mdi-play' })
  if (c.status === 'active') a.push({ title: 'Pause', status: 'paused', icon: 'mdi-pause' })
  if (['active', 'paused'].includes(c.status)) {
    a.push({ title: 'Mark as completed', status: 'completed', icon: 'mdi-check-circle-outline' })
  }
  return a
}

const notify = (text, color = 'success') => (toast.value = { show: true, text, color })

async function load() {
  loading.value = true
  try {
    const res = await getCampaigns({
      search: search.value || undefined,
      status: statusFilter.value === 'all' ? undefined : statusFilter.value,
      per_page: 50,
    })
    campaigns.value = res.data
    total.value = res.total
  } catch {
    campaigns.value = []
    notify('Failed to load campaigns.', 'error')
  } finally {
    loading.value = false
  }
}

let t
watch(search, () => { clearTimeout(t); t = setTimeout(load, 300) })
watch(statusFilter, load)

onMounted(async () => {
  load()
  options.value = await getCampaignOptions().catch(() => options.value)
})

const openCreate = () => { editing.value = null; formOpen.value = true }
const openEdit = (c) => { editing.value = c; formOpen.value = true }
const onSaved = () => { notify('Campaign saved.'); load() }

async function changeStatus(c, status) {
  try {
    await updateCampaignStatus(c.id, status)
    notify(`Campaign ${statusMeta[status].label.toLowerCase()}.`)
    load()
  } catch (e) {
    notify(e.response?.data?.message || 'Could not update status.', 'error')
  }
}

function remove(c) {
  deleteDialog.target = c
  deleteDialog.open = true
}

async function confirmRemove() {
  deleteDialog.submitting = true
  try {
    await deleteCampaign(deleteDialog.target.id)
    deleteDialog.open = false
    notify('Campaign deleted.')
    load()
  } catch (e) {
    deleteDialog.open = false
    notify(e.response?.data?.message || 'Could not delete campaign.', 'error')
  } finally {
    deleteDialog.submitting = false
  }
}

const emptyText = computed(() => (search.value || statusFilter.value !== 'all'
  ? 'No campaigns match these filters.'
  : 'No census campaigns yet. Create your first one.'))
</script>

<style scoped src="./DashboardCss.css"></style>
<style scoped src="./RequestsCss.css"></style>

<style scoped>
/* ── New campaign button ── */
.new-btn {
  height: 36px;
  font-size: 12.5px;
  font-weight: 500;
  letter-spacing: 0;
  text-transform: none;
  border-radius: 8px;
}

/* ── Dialog buttons ── */
.dialog-btn {
  height: 34px;
  font-size: 12.5px;
  font-weight: 500;
  letter-spacing: 0;
  text-transform: none;
  border-radius: 8px;
}

/* ── Row layout ── */
.table-row td {
  vertical-align: middle;
  border-bottom: 1px solid #f2f2f2;
}
.table-row:last-child td {
  border-bottom: none;
}
.table-row:hover {
  background: #fafbfc;
}

/* ── Status chips ── */
.campaign-chip {
  display: inline-block;
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 999px;
  white-space: nowrap;
}
.campaign-chip--draft {
  background: #f1f3f7;
  color: #666;
}
.campaign-chip--active {
  background: #e8f5e9;
  color: #27ae60;
}
.campaign-chip--paused {
  background: #fff3e0;
  color: #e67e22;
}
.campaign-chip--completed {
  background: #e8f0fe;
  color: #1a5fd8;
}

/* ── Dialog chips ── */
.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 18px;
}
.info-chip {
  font-size: 11.5px;
  color: #1a1a1a;
  background: #f7f5f0;
  padding: 3px 10px;
  border-radius: 999px;
}
.empty-note {
  font-size: 12px;
  color: #999;
}

/* ── Action buttons ── */
.table-head--actions {
  width: 84px;
}
.actions-cell {
  padding: 8px 5px;
  width: 84px;
}
.row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 2px;
}
.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #8a8f98;
  transition: background 0.15s, color 0.15s;
}
.action-btn :deep(.v-icon) {
  font-size: 18px;
}
.action-btn:hover {
  background: #f1f3f7;
  color: #0f1e3d;
}
</style>