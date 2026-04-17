<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Audience & Goal Setup',
    description: 'Tentukan objective, target audience, channel mix, dan timeline kampanye agar eksekusi lintas channel tetap terarah.',
    icon: 'tabler-target-arrow',
    color: 'primary',
  },
  {
    title: 'Content & Journey Blueprint',
    description: 'Siapkan brief konten, template pesan, dan alur campaign sebelum didistribusikan ke email, WhatsApp, atau sosial media.',
    icon: 'tabler-route-alt-left',
    color: 'info',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Campaign Planning"
    description="Functional requirement: sistem harus mendukung perencanaan kampanye multi-channel."
    primary-label="Planning"
    secondary-label="Multi-channel"
    :modules="modules"
  />
</template>
