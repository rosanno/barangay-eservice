<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: null },
  accentColor: { type: String, default: '#0f1e3d' },
  delta: { type: String, default: '' },
  deltaIcon: { type: String, default: '' },
  deltaColor: { type: String, default: '#888' },
  loading: { type: Boolean, default: false },
})
</script>

<template>
  <v-card variant="flat" class="stat-card" :style="{ borderLeftColor: accentColor }">
    <v-card-text class="pa-5">
      <div class="stat-card__label">{{ label }}</div>

      <div v-if="loading" class="stat-card__loading">
        <v-progress-circular indeterminate size="18" width="2" color="#ccc" />
      </div>
      <div v-else class="stat-card__value">{{ value }}</div>

      <div v-if="!loading && delta" class="stat-card__delta" :style="{ color: deltaColor }">
        <v-icon v-if="deltaIcon" :icon="deltaIcon" size="12" />
        {{ delta }}
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.stat-card {
  background: #ffffff;
  border-radius: 10px;
  border-left: 3px solid; /* color comes from the accentColor style binding */
  border-top: none;
  border-right: none;
  border-bottom: none;
  box-shadow: none;
}

.stat-card__label {
  font-size: 0.75rem;
  color: #888;
  font-weight: 500;
  margin-bottom: 8px;
}

.stat-card__value {
  font-size: 28px;
  font-weight: 600;
  color: #1a1a1a;
  line-height: 1;
  margin-bottom: 6px;
}

.stat-card__loading {
  height: 34px;
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}

.stat-card__delta {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
}
</style>