<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Lead Overview',
    description: 'Ringkasan prospek masuk, lead aktif, dan prioritas follow-up untuk tim sales dalam satu panel utama.',
    icon: 'tabler-users-plus',
    color: 'primary',
  },
  {
    title: 'Pipeline Snapshot',
    description: 'Pantau peluang per stage, nilai pipeline, dan forecast supaya keputusan sales lebih cepat.',
    icon: 'tabler-chart-funnel',
    color: 'success',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Sales Enablement"
    title="Sales Dashboard"
    description="Template awal untuk dashboard Sales Enablement. Halaman ini nanti menjadi ringkasan lead, opportunity, aktivitas sales, dan forecast revenue."
    primary-label="Sales Overview"
    secondary-label="Executive Snapshot"
    :modules="modules"
  />
</template>