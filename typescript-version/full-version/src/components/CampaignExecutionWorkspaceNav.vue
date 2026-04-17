<script setup lang="ts">
const route = useRoute()
const router = useRouter()

const workspaceItems = [
  {
    title: 'Overview',
    subtitle: 'Campaign Execution',
    icon: 'tabler-layout-grid',
    to: '/marketing-automation/campaign-execution',
  },
  {
    title: 'WhatsApp Broadcast',
    subtitle: 'Outbound campaign',
    icon: 'tabler-send',
    to: '/marketing-automation/campaign-execution/whatsapp-broadcast',
  },
  {
    title: 'WhatsApp Templates',
    subtitle: 'Content asset',
    icon: 'tabler-template',
    to: '/marketing-automation/campaign-execution/whatsapp-templates',
  },
]

const isActive = (path: string) => {
  if (path === '/marketing-automation/campaign-execution')
    return route.path === path

  return route.path.startsWith(path)
}

const openWorkspace = (path: string) => {
  if (route.path !== path)
    router.push(path)
}
</script>

<template>
  <VCard variant="outlined">
    <VCardText class="d-flex flex-column gap-4">
      <div class="d-flex flex-column flex-lg-row align-lg-center justify-space-between gap-3">
        <div>
          <div class="text-h6">Campaign Execution Navigation</div>
          <div class="text-body-2 text-medium-emphasis">Pindah cepat antara overview execution dan child workspace WhatsApp tanpa kembali ke menu utama.</div>
        </div>

        <VChip color="primary" variant="tonal">Execution Workspace</VChip>
      </div>

      <VRow>
        <VCol v-for="item in workspaceItems" :key="item.to" cols="12" md="4">
          <VCard
            class="cursor-pointer h-100 workspace-nav-card"
            :color="isActive(item.to) ? 'primary' : undefined"
            :variant="isActive(item.to) ? 'flat' : 'outlined'"
            @click="openWorkspace(item.to)"
          >
            <VCardText class="d-flex align-center gap-3">
              <VAvatar :color="isActive(item.to) ? 'white' : 'primary'" :variant="isActive(item.to) ? 'flat' : 'tonal'" rounded size="42">
                <VIcon :icon="item.icon" />
              </VAvatar>

              <div>
                <div class="text-body-1 mb-1" :class="isActive(item.to) ? 'text-white' : 'text-high-emphasis'">{{ item.title }}</div>
                <div class="text-body-2" :class="isActive(item.to) ? 'text-white opacity-80' : 'text-medium-emphasis'">{{ item.subtitle }}</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style scoped>
.workspace-nav-card {
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.workspace-nav-card:hover {
  box-shadow: 0 10px 24px rgba(58, 71, 101, 0.08);
  transform: translateY(-2px);
}
</style>