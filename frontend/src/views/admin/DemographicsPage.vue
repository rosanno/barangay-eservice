<template>
  <DemographicsReport />
</template>

<script setup>
import DemographicsReport from '@/components/reports/DemographicsReport.vue'
</script>

<style scoped>
.reports-tabs :deep(.v-tab) {
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0;
  text-transform: none;
}

.reports-tabs :deep(.v-tab .v-icon) {
  font-size: 16px;
}
</style>