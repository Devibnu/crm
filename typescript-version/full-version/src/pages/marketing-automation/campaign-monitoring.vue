<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Real-time Performance View',
    description: 'Pantau delivery, open, click, reply, dan conversion secara real-time untuk tiap campaign dan tiap channel.',
    icon: 'tabler-chart-line',
    color: 'primary',
  },
  {
    title: 'Alert & Optimization Loop',
    description: 'Gunakan insight performa untuk follow-up optimasi konten, audience, dan jadwal pengiriman kampanye.',
    icon: 'tabler-bell-ringing',
    color: 'error',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Campaign Monitoring"
    description="Functional requirement: sistem harus memantau performa kampanye secara real-time."
    primary-label="Monitoring"
    secondary-label="Performance"
    :modules="modules"
  />
</template>
