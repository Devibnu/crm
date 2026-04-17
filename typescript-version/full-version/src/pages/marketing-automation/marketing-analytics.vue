<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Funnel Analytics',
    description: 'Analisis perjalanan pelanggan dari awareness hingga conversion agar funnel marketing bisa dioptimalkan.',
    icon: 'tabler-chart-funnel',
    color: 'primary',
  },
  {
    title: 'ROI Measurement',
    description: 'Hitung efektivitas biaya kampanye untuk mengukur return dan membantu optimasi anggaran marketing.',
    icon: 'tabler-chart-arrows-vertical',
    color: 'success',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Marketing Analytics"
    description="Template awal Marketing Analytics untuk funnel analysis, pengukuran ROI, dan evaluasi efektivitas kampanye."
    primary-label="Performance Analytics"
    secondary-label="ROI Measurement"
    :modules="modules"
  />
</template>