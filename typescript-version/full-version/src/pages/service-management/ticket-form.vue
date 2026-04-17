<script setup lang="ts">
import TicketForm from '@/views/crm/service-management/TicketForm.vue'

definePage({
  meta: {
    action: 'create',
    subject: 'CrmTickets',
  },
})
</script>

<template>
  <TicketForm />
</template>