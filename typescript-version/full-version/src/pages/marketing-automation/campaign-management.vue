<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Campaign Planning',
    description: 'Rancang kampanye multi-channel agar email, WhatsApp, dan sosial media berjalan dalam satu struktur kampanye yang jelas.',
    icon: 'tabler-calendar-event',
    color: 'primary',
  },
  {
    title: 'Campaign Execution',
    description: 'Kelola distribusi konten ke email, WhatsApp, dan sosial media. Workspace WhatsApp Broadcast dan Templates kini diposisikan di domain ini.',
    icon: 'tabler-rocket',
    color: 'success',
  },
  {
    title: 'Campaign Monitoring',
    description: 'Pantau performa kampanye secara real-time untuk melihat delivery, engagement, dan respons per channel.',
    icon: 'tabler-activity-heartbeat',
    color: 'warning',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Campaign Management"
    description="Landing page Campaign Management untuk perencanaan, eksekusi, dan monitoring kampanye multi-channel. CRM Omnichannel sekarang difokuskan untuk inbox handling."
    primary-label="Campaign Ops"
    secondary-label="Outbound Journey"
    :modules="modules"
  />
</template>