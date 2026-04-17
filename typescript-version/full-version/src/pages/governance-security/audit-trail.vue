<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Activity Chronicle',
    description: 'Pantau jejak perubahan akses, konfigurasi, dan aktivitas admin untuk kebutuhan governance dan investigasi.',
    icon: 'tabler-history',
    color: 'primary',
  },
  {
    title: 'Security Review',
    description: 'Siapkan jalur audit terstruktur untuk peninjauan kebijakan keamanan, approval, dan exception log.',
    icon: 'tabler-shield-checkered',
    color: 'warning',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="Governance & Security"
    title="Audit Trail"
    description="Ruang awal untuk konsolidasi log aktivitas, histori perubahan hak akses, dan penelusuran kejadian penting sesuai kebutuhan audit PRD."
    primary-label="Governance"
    secondary-label="Security Review"
    :modules="modules"
  />
</template>