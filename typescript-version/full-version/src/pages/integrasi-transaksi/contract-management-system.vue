<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Contract Repository',
    description: 'Kelola dokumen kontrak, masa aktif, dan struktur lampiran untuk transaksi yang membutuhkan jejak legal terpusat.',
    icon: 'tabler-file-certificate',
    color: 'primary',
  },
  {
    title: 'Renewal Control',
    description: 'Siapkan pipeline monitoring renewal, alert termin, dan sinkronisasi transaksi untuk kebutuhan operasional backoffice.',
    icon: 'tabler-refresh-dot',
    color: 'success',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="Integrasi & Transaksi"
    title="Contract Management System"
    description="Placeholder awal Contract Management System untuk mengonsolidasikan siklus dokumen kontrak dan integrasinya dengan transaksi backoffice."
    primary-label="Contracts"
    secondary-label="Renewal"
    :modules="modules"
  />
</template>