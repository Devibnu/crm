<script setup lang="ts">
import type { BroadcastCampaign } from '@/data/whatsapp-broadcast-campaigns'
import { loadBroadcastCampaigns, saveBroadcastCampaigns } from '@/data/whatsapp-broadcast-campaigns'
import { whatsappTemplateCatalog } from '@/data/whatsapp-template-catalog'

definePage({
  meta: {
    layoutWrapperClasses: 'layout-content-height-fixed',
    navActiveLink: 'marketing-automation-campaign-execution',
  },
})

const route = useRoute()
const router = useRouter()
const broadcastBasePath = '/marketing-automation/campaign-execution/whatsapp-broadcast'
const legacyBroadcastBasePath = '/omnichannel/whatsapp/broadcast'

const campaigns = ref<BroadcastCampaign[]>([])
const campaign = computed(() => campaigns.value.find(item => String(item.id) === String(route.params.id)) ?? null)
const template = computed(() => whatsappTemplateCatalog.find(item => item.id === campaign.value?.templateId) ?? null)

const resolveStatusLabel = (status: BroadcastCampaign['status']) => {
  if (status === 'draft')
    return 'Draft'
  if (status === 'running')
    return 'Sedang Berjalan'
  if (status === 'scheduled')
    return 'Terjadwal'
  if (status === 'cancelled')
    return 'Dibatalkan'

  return 'Selesai'
}

const resolveStatusColor = (status: BroadcastCampaign['status']) => {
  if (status === 'draft')
    return 'secondary'
  if (status === 'running')
    return 'success'
  if (status === 'scheduled')
    return 'warning'
  if (status === 'cancelled')
    return 'error'

  return 'info'
}

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const formatDate = (value: string) => formatter.format(new Date(value))
const audienceLabelMap: Record<string, string> = {
  all: 'Semua kontak eligible',
  loyal: 'Customer loyal',
  warm: 'Warm leads',
  new: 'Customer baru',
}

const updateCampaign = (updater: (campaign: BroadcastCampaign) => BroadcastCampaign) => {
  if (!campaign.value)
    return

  campaigns.value = campaigns.value.map(item => item.id === campaign.value?.id ? updater(item) : item)
  saveBroadcastCampaigns(campaigns.value)
}

const startCampaign = () => {
  updateCampaign(item => ({
    ...item,
    status: 'running',
    deliveredCount: Math.min(item.recipientCount, Math.max(1, Math.floor(item.recipientCount * 0.5))),
    pendingCount: item.recipientCount - Math.min(item.recipientCount, Math.max(1, Math.floor(item.recipientCount * 0.5))),
  }))
}

const finishCampaign = () => {
  updateCampaign(item => ({
    ...item,
    status: 'completed',
    deliveredCount: item.recipientCount,
    failedCount: 0,
    pendingCount: 0,
  }))
}

const cancelCampaign = () => {
  updateCampaign(item => ({
    ...item,
    status: 'cancelled',
  }))
}

const goBack = () => {
  router.push(broadcastBasePath)
}

const completionRate = computed(() => {
  if (!campaign.value?.recipientCount)
    return 0

  return Math.round(((campaign.value.deliveredCount + campaign.value.failedCount) / campaign.value.recipientCount) * 100)
})

const deliveryRate = computed(() => {
  if (!campaign.value?.recipientCount)
    return 0

  return Math.round((campaign.value.deliveredCount / campaign.value.recipientCount) * 100)
})

const failureRate = computed(() => {
  if (!campaign.value?.recipientCount)
    return 0

  return Math.round((campaign.value.failedCount / campaign.value.recipientCount) * 100)
})

const pageBreadcrumb = computed(() => ['CRM Marketing Automation', 'Campaign Execution', 'WhatsApp Broadcast', campaign.value?.name ?? '-'].join(' / '))

onMounted(() => {
  if (route.path.startsWith(`${legacyBroadcastBasePath}/`)) {
    router.replace(route.path.replace(legacyBroadcastBasePath, broadcastBasePath))
    return
  }

  campaigns.value = loadBroadcastCampaigns()
})
</script>

<template>
  <section v-if="campaign" class="d-flex flex-column gap-6">
    <CampaignExecutionWorkspaceNav />

    <div class="d-flex flex-column flex-md-row align-md-start justify-space-between gap-4">
      <div>
        <div class="text-overline mb-2">{{ pageBreadcrumb }}</div>
        <h4 class="text-h4 mb-1">{{ campaign.name }}</h4>
        <VChip :color="resolveStatusColor(campaign.status)" variant="flat" size="small" class="status-chip text-uppercase">
          {{ resolveStatusLabel(campaign.status) }}
        </VChip>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <VBtn v-if="campaign.status === 'draft'" color="success" prepend-icon="tabler-player-play" @click="startCampaign">
          Mulai Kampanye
        </VBtn>
        <VBtn v-if="campaign.status === 'scheduled'" color="success" prepend-icon="tabler-player-play" @click="startCampaign">
          Mulai Sekarang
        </VBtn>
        <VBtn v-if="campaign.status === 'running'" color="info" prepend-icon="tabler-check" @click="finishCampaign">
          Selesaikan
        </VBtn>
        <VBtn color="error" variant="outlined" prepend-icon="tabler-x" @click="cancelCampaign">
          Batalkan
        </VBtn>
        <VBtn color="secondary" variant="outlined" prepend-icon="tabler-arrow-left" @click="goBack">
          Kembali
        </VBtn>
      </div>
    </div>

    <VRow>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1">{{ campaign.recipientCount }}</h3>
            <div class="text-body-2 text-medium-emphasis">Total Penerima</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1 text-info">{{ campaign.deliveredCount }}</h3>
            <div class="text-body-2 text-medium-emphasis">Terkirim</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1 text-success">{{ campaign.deliveredCount }}</h3>
            <div class="text-body-2 text-medium-emphasis">Terdelivery</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1 text-secondary">0</h3>
            <div class="text-body-2 text-medium-emphasis">Dibaca</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1 text-error">{{ campaign.failedCount }}</h3>
            <div class="text-body-2 text-medium-emphasis">Gagal</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6" lg="2">
        <VCard class="metric-card">
          <VCardText class="text-center">
            <h3 class="text-h3 mb-1 text-warning">{{ campaign.pendingCount }}</h3>
            <div class="text-body-2 text-medium-emphasis">Pending</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" lg="8">
        <div class="d-flex flex-column gap-6">
          <VCard>
            <VCardItem>
              <VCardTitle>Progress Pengiriman</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pt-6 d-flex flex-column gap-4">
              <div class="d-flex justify-space-between gap-3 text-body-2">
                <span>{{ completionRate }}% Complete</span>
                <span>{{ campaign.deliveredCount + campaign.failedCount }} / {{ campaign.recipientCount }}</span>
              </div>
              <VProgressLinear :model-value="completionRate" color="info" rounded height="14" />
              <div class="d-flex flex-wrap gap-4 text-body-2">
                <span class="d-flex align-center gap-2"><span class="legend-dot bg-success" /> Delivered</span>
                <span class="d-flex align-center gap-2"><span class="legend-dot bg-info" /> Sent</span>
                <span class="d-flex align-center gap-2"><span class="legend-dot bg-error" /> Failed</span>
              </div>
            </VCardText>
          </VCard>

          <VCard>
            <VCardItem>
              <VCardTitle>Template Pesan</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pt-6 d-flex flex-column gap-3">
              <div class="text-h6">{{ template?.name ?? campaign.templateId }}</div>
              <div class="d-flex gap-2 flex-wrap">
                <VChip size="small" color="secondary" variant="tonal">{{ template?.category ?? 'broadcast' }}</VChip>
                <VChip size="small" color="primary" variant="tonal">ID</VChip>
              </div>
              <div class="campaign-template-preview rounded pa-4 text-body-2 text-medium-emphasis">
                {{ template?.body ?? '-' }}
              </div>
            </VCardText>
          </VCard>
        </div>
      </VCol>

      <VCol cols="12" lg="4">
        <div class="d-flex flex-column gap-6">
          <VCard>
            <VCardItem>
              <VCardTitle>Informasi Kampanye</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pt-6 d-flex flex-column gap-3 text-body-2">
              <div>
                <div class="text-medium-emphasis">Dibuat</div>
                <div class="text-high-emphasis">{{ formatDate(campaign.createdAt) }}</div>
              </div>
              <div>
                <div class="text-medium-emphasis">Rate Limit</div>
                <div class="text-high-emphasis">{{ campaign.rateLimit ?? '10' }} pesan/detik</div>
              </div>
              <div>
                <div class="text-medium-emphasis">Audience</div>
                <div class="text-high-emphasis">{{ audienceLabelMap[campaign.audience ?? 'all'] ?? campaign.audience ?? 'all' }}</div>
              </div>
              <div v-if="campaign.scheduledAt">
                <div class="text-medium-emphasis">Jadwal Kirim</div>
                <div class="text-high-emphasis">{{ formatDate(campaign.scheduledAt) }}</div>
              </div>
            </VCardText>
          </VCard>

          <VCard>
            <VCardItem>
              <VCardTitle>Tingkat Keberhasilan</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pt-6 d-flex flex-column gap-4">
              <div>
                <div class="d-flex justify-space-between gap-3 text-body-2 mb-2">
                  <span>Delivery Rate</span>
                  <span>{{ deliveryRate }}%</span>
                </div>
                <VProgressLinear :model-value="deliveryRate" color="success" rounded height="8" />
              </div>
              <div>
                <div class="d-flex justify-space-between gap-3 text-body-2 mb-2">
                  <span>Read Rate</span>
                  <span>0%</span>
                </div>
                <VProgressLinear :model-value="0" color="secondary" rounded height="8" />
              </div>
              <div>
                <div class="d-flex justify-space-between gap-3 text-body-2 mb-2">
                  <span>Failure Rate</span>
                  <span>{{ failureRate }}%</span>
                </div>
                <VProgressLinear :model-value="failureRate" color="error" rounded height="8" />
              </div>
            </VCardText>
          </VCard>
        </div>
      </VCol>
    </VRow>
  </section>

  <section v-else class="d-flex flex-column gap-4">
    <VAlert color="warning" variant="tonal" title="Kampanye tidak ditemukan" text="Campaign yang dipilih belum tersedia atau sudah dihapus." />
    <VBtn color="primary" @click="goBack">Kembali ke WhatsApp Broadcast</VBtn>
  </section>
</template>

<style scoped>
.legend-dot {
  display: inline-block;
  block-size: 14px;
  inline-size: 14px;
  border-radius: 999px;
}

.campaign-template-preview {
  background: rgba(var(--v-theme-surface-variant), 0.35);
}

.metric-card {
  box-shadow: 0 10px 24px rgba(58, 71, 101, 0.08);
}

.status-chip {
  min-inline-size: 88px;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}
</style>