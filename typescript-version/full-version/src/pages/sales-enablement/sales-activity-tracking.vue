<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Call & Meeting Log',
    description: 'Catat aktivitas sales seperti call, meeting, dan touchpoint lain untuk menjaga akuntabilitas kinerja.',
    icon: 'tabler-phone-call',
    color: 'primary',
  },
  {
    title: 'Reminder & Task',
    description: 'Sediakan reminder follow-up dan task sales agar tidak ada lead atau opportunity yang terlewat.',
    icon: 'tabler-calendar-clock',
    color: 'warning',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Sales Enablement"
    title="Sales Activity Tracking"
    description="Template awal Sales Activity Tracking untuk pencatatan call, meeting, reminder, dan follow-up task tim sales."
    primary-label="Activity Log"
    secondary-label="Follow-up Control"
    :modules="modules"
  />
</template>