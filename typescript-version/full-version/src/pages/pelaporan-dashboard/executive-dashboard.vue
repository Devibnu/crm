<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Leadership Snapshot',
    description: 'Ringkasan KPI strategis lintas CRM dan backoffice untuk konsumsi manajemen eksekutif.',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
  },
  {
    title: 'Cross-Module Signals',
    description: 'Sorot tren utama service, sales, marketing, governance, dan transaksi dalam satu tampilan eksekutif.',
    icon: 'tabler-presentation-analytics',
    color: 'info',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="Pelaporan & Dashboard"
    title="Executive Dashboard"
    description="Placeholder Executive Dashboard untuk penyelarasan KPI dan ringkasan manajemen sesuai struktur modul PRD."
    primary-label="Executive"
    secondary-label="KPIs"
    :modules="modules"
  />
</template>