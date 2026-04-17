<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Operational Reports',
    description: 'Susun laporan periodik untuk performa service, sales, marketing, dan transaksi secara konsisten.',
    icon: 'tabler-report',
    color: 'primary',
  },
  {
    title: 'Export & Distribution',
    description: 'Siapkan kanal distribusi laporan untuk stakeholder internal tanpa mengubah struktur navigasi utama.',
    icon: 'tabler-file-export',
    color: 'secondary',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="Pelaporan & Dashboard"
    title="Reporting"
    description="Placeholder Reporting untuk memperjelas area pelaporan sesuai struktur modul PRD PDF pada sidebar admin."
    primary-label="Reports"
    secondary-label="Distribution"
    :modules="modules"
  />
</template>