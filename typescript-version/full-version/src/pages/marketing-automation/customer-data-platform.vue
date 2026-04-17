<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Data Consolidation',
    description: 'Gabungkan data pelanggan dari berbagai modul agar marketing punya basis data terpadu untuk segmentasi dan targeting.',
    icon: 'tabler-database-import',
    color: 'primary',
  },
  {
    title: 'Segmentation Engine',
    description: 'Dukung segmentasi pelanggan berbasis perilaku untuk kampanye yang lebih tepat sasaran dan terukur.',
    icon: 'tabler-filter-bolt',
    color: 'success',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Customer Data Platform"
    description="Template awal Customer Data Platform untuk konsolidasi data pelanggan dan pengelolaan segmentasi marketing."
    primary-label="Unified Customer Data"
    secondary-label="Segmentation Engine"
    :modules="modules"
  />
</template>