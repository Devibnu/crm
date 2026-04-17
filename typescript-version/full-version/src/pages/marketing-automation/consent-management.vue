<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const modules = [
  {
    title: 'Consent Capture',
    description: 'Catat persetujuan penggunaan data pelanggan untuk menjaga kepatuhan regulasi dan preference marketing.',
    icon: 'tabler-shield-lock',
    color: 'primary',
  },
  {
    title: 'Consent Audit',
    description: 'Simpan histori persetujuan dan perubahan consent sebagai fondasi audit trail dan kesiapan compliance.',
    icon: 'tabler-history-toggle',
    color: 'warning',
  },
]
</script>

<template>
  <ChannelPlaceholder
    overline="CRM Marketing Automation"
    title="Consent Management"
    description="Template awal Consent Management untuk capture preference pelanggan dan histori audit persetujuan penggunaan data."
    primary-label="Compliance"
    secondary-label="Consent Audit"
    :modules="modules"
  />
</template>