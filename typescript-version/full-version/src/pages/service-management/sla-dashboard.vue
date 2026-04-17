<script setup lang="ts">
import SLADashboard from '@/views/crm/service-management/SLADashboard.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'CrmTickets',
  },
})
</script>

<template>
  <SLADashboard />
</template>