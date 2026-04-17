<script setup lang="ts">
import ChannelPlaceholder from '@/views/service-management/ChannelPlaceholder.vue'

const router = useRouter()

const modules = [
  {
    title: 'Outbound Distribution',
    description: 'Kelola distribusi konten ke email, WhatsApp, dan sosial media dari satu domain campaign execution.',
    icon: 'tabler-send',
    color: 'success',
  },
  {
    title: 'Existing WhatsApp Workspaces',
    description: 'WhatsApp Broadcast dan WhatsApp Templates sekarang diakses langsung dari domain Campaign Execution agar struktur marketing tetap konsisten.',
    icon: 'tabler-brand-whatsapp',
    color: 'warning',
  },
]

const executionWorkspaces = [
  {
    title: 'WhatsApp Broadcast',
    description: 'Kelola daftar kampanye, penjadwalan, detail performa, dan eksekusi WA blast sebagai workspace outbound WhatsApp.',
    icon: 'tabler-send',
    color: 'success',
    actionLabel: 'Buka Broadcast',
    to: '/marketing-automation/campaign-execution/whatsapp-broadcast',
  },
  {
    title: 'WhatsApp Templates',
    description: 'Kelola template Approved Meta dan template internal yang dipakai sebagai aset konten campaign WhatsApp.',
    icon: 'tabler-template',
    color: 'primary',
    actionLabel: 'Buka Templates',
    to: '/marketing-automation/campaign-execution/whatsapp-templates',
  },
]

const openWorkspace = (path: string) => {
  router.push(path)
}

const channelReadiness = [
  {
    title: 'WhatsApp Channel',
    subtitle: 'Live workspace',
    description: 'Sudah punya child workspace aktif untuk broadcast campaign dan template management.',
    color: 'success',
  },
  {
    title: 'Email Channel',
    subtitle: 'Planned',
    description: 'Disiapkan untuk email campaign execution setelah struktur campaign core stabil.',
    color: 'info',
  },
  {
    title: 'Social Media Channel',
    subtitle: 'Planned',
    description: 'Akan menjadi workspace distribusi konten sosial media dalam domain execution yang sama.',
    color: 'warning',
  },
]
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <ChannelPlaceholder
      overline="CRM Marketing Automation"
      title="Campaign Execution"
      description="Functional requirement: sistem harus mengelola distribusi konten ke email, WhatsApp, dan sosial media."
      primary-label="Execution"
      secondary-label="Outbound Distribution"
      :modules="modules"
    />

    <CampaignExecutionWorkspaceNav />

    <VCard variant="outlined">
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-lg-row align-lg-center justify-space-between gap-3">
          <div>
            <div class="text-h6">Execution Workspaces</div>
            <div class="text-body-2 text-medium-emphasis">Campaign Execution menjadi parent operasional untuk workspace distribusi per channel. WhatsApp adalah channel pertama yang sudah live.</div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <VChip color="success" variant="tonal">WhatsApp Live</VChip>
            <VChip color="info" variant="tonal">Email Planned</VChip>
            <VChip color="warning" variant="tonal">Social Planned</VChip>
          </div>
        </div>

        <VRow>
          <VCol v-for="channel in channelReadiness" :key="channel.title" cols="12" md="4">
            <div class="execution-channel-item rounded border-sm pa-4 h-100">
              <div class="d-flex align-center justify-space-between gap-3 mb-2">
                <div class="text-body-1 text-high-emphasis">{{ channel.title }}</div>
                <VChip size="small" :color="channel.color" variant="tonal">{{ channel.subtitle }}</VChip>
              </div>
              <div class="text-body-2 text-medium-emphasis">{{ channel.description }}</div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow>
      <VCol v-for="workspace in executionWorkspaces" :key="workspace.title" cols="12" md="6">
        <VCard class="h-100">
          <VCardText class="d-flex flex-column gap-4">
            <div class="d-flex align-start gap-4">
              <VAvatar :color="workspace.color" variant="tonal" rounded size="46">
                <VIcon :icon="workspace.icon" />
              </VAvatar>

              <div>
                <div class="text-body-1 text-high-emphasis mb-1">{{ workspace.title }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ workspace.description }}</div>
              </div>
            </div>

            <div>
              <VBtn :color="workspace.color" variant="tonal" @click="openWorkspace(workspace.to)">
                {{ workspace.actionLabel }}
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.execution-channel-item {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
