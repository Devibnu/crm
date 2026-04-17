<script setup lang="ts">
import TicketList from '@/views/crm/service-management/TicketList.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'CrmTickets',
  },
})
</script>

<template>
  <TicketList />
</template>