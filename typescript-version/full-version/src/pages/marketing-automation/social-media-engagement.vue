<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Social Listening',
    description: 'Pantau mention, komentar, dan pesan sosial media untuk membantu respons marketing yang lebih cepat.',
    icon: 'tabler-ear',
    color: 'primary',
  },
  {
    title: 'Engagement Log',
    description: 'Catat interaksi sosial media agar histori engagement tersimpan dan bisa dihubungkan ke customer journey.',
    icon: 'tabler-message-dots',
    color: 'warning',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Social Media Engagement"
    description="Template awal Social Media Engagement untuk monitoring social listening dan pencatatan interaksi marketing di kanal sosial."
    primary-label="Social Listening"
    secondary-label="Engagement Monitoring"
    :modules="modules"
  />
</template>