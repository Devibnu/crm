<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Page Builder',
    description: 'Sediakan editor landing page tanpa coding untuk mempercepat eksekusi campaign dan eksperimen marketing.',
    icon: 'tabler-device-desktop-code',
    color: 'primary',
  },
  {
    title: 'Form Builder',
    description: 'Buat form capture data prospek untuk otomatisasi lead entry ke CRM dan workflow berikutnya.',
    icon: 'tabler-forms',
    color: 'info',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Landing Page & Form Builder"
    description="Template awal untuk builder landing page dan form capture prospek sebagai fondasi eksekusi campaign digital."
    primary-label="Lead Capture"
    secondary-label="Page Builder"
    :modules="modules"
  />
</template>