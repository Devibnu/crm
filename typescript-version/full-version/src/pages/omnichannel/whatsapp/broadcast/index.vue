<script setup lang="ts">
import type { BroadcastCampaign } from '@/data/whatsapp-broadcast-campaigns'
import { loadBroadcastCampaigns, saveBroadcastCampaigns } from '@/data/whatsapp-broadcast-campaigns'
import { whatsappTemplateCatalog } from '@/data/whatsapp-template-catalog'

definePage({
  meta: {
    layoutWrapperClasses: 'layout-content-height-fixed',
  },
})

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
})

const campaignSearchQuery = ref('')
const route = useRoute()
const router = useRouter()
const broadcastBasePath = '/marketing-automation/campaign-execution/whatsapp-broadcast'
const legacyBroadcastBasePath = '/omnichannel/whatsapp/broadcast'

const templateOptions = whatsappTemplateCatalog
  .filter(template => template.channels.includes('broadcast') && template.status === 'approved')
  .map(template => ({
    title: template.name,
    value: template.id,
    body: template.body,
  }))

const campaigns = ref<BroadcastCampaign[]>([])

const filteredCampaigns = computed(() => {
  const query = campaignSearchQuery.value.trim().toLowerCase()

  return campaigns.value.filter(campaign => {
    const templateName = templateOptions.find(template => template.value === campaign.templateId)?.title ?? campaign.templateId

    return !query
      || campaign.name.toLowerCase().includes(query)
      || templateName.toLowerCase().includes(query)
  })
})

const campaignStats = computed(() => [
  {
    title: 'Total Kampanye',
    value: campaigns.value.length,
    color: 'primary',
    icon: 'tabler-megaphone',
  },
  {
    title: 'Sedang Berjalan',
    value: campaigns.value.filter(campaign => campaign.status === 'running').length,
    color: 'success',
    icon: 'tabler-player-play-filled',
  },
  {
    title: 'Terjadwal',
    value: campaigns.value.filter(campaign => campaign.status === 'scheduled').length,
    color: 'warning',
    icon: 'tabler-clock-filled',
  },
  {
    title: 'Selesai',
    value: campaigns.value.filter(campaign => campaign.status === 'completed').length,
    color: 'info',
    icon: 'tabler-check',
  },
])

const formatDate = (value: string) => formatter.format(new Date(value))

const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
}).format(value)

const campaignProgress = (campaign: BroadcastCampaign) => {
  if (!campaign.recipientCount)
    return 0

  return Math.round(((campaign.deliveredCount + campaign.failedCount) / campaign.recipientCount) * 100)
}

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

const openCreateCampaignPage = () => {
  router.push(`${broadcastBasePath}/create`)
}

const openCampaignDetail = (campaignId: number) => {
  router.push(`${broadcastBasePath}/${campaignId}`)
}

onMounted(() => {
  if (route.path === legacyBroadcastBasePath) {
    router.replace(broadcastBasePath)
    return
  }

  campaigns.value = loadBroadcastCampaigns()
})

watch(campaigns, value => {
  saveBroadcastCampaigns(value)
}, { deep: true })
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <div>
      <div class="text-overline mb-2">CRM Marketing Automation / Campaign Execution / WhatsApp Broadcast</div>
      <h4 class="text-h4 mb-1">WhatsApp Broadcast</h4>
      <p class="mb-0 text-body-1 text-medium-emphasis">
        Kelola outbound campaign WhatsApp dalam bentuk daftar kampanye yang mudah dipantau. Template yang dipakai di sini hanya template yang sudah Approved Meta.
      </p>
    </div>

    <CampaignExecutionWorkspaceNav />

    <VRow>
      <VCol v-for="card in campaignStats" :key="card.title" cols="12" md="6" lg="3">
        <VCard class="broadcast-stat-card">
          <VCardText class="d-flex align-start justify-space-between gap-3">
            <div>
              <div class="text-body-1 text-high-emphasis mb-1">{{ card.title }}</div>
              <h4 class="text-h4 mb-0">{{ card.value }}</h4>
            </div>

            <VAvatar :color="card.color" class="broadcast-stat-icon" rounded size="46">
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-lg-row align-lg-center justify-space-between gap-3">
          <div>
            <div class="text-h6">Daftar Kampanye</div>
            <div class="text-body-2 text-medium-emphasis">Lihat progres kirim, biaya, dan tanggal kampanye dalam satu tabel yang ringkas.</div>
          </div>

          <div class="d-flex flex-column flex-md-row gap-3 align-stretch align-md-center">
            <AppTextField
              v-model="campaignSearchQuery"
              prepend-inner-icon="tabler-search"
              placeholder="Cari nama kampanye atau template"
              style="min-inline-size: 18rem;"
            />

            <VBtn color="success" prepend-icon="tabler-plus" @click="openCreateCampaignPage">
              Buat Kampanye
            </VBtn>
          </div>
        </div>

        <VTable class="text-no-wrap broadcast-table">
          <thead>
            <tr>
              <th>Kampanye</th>
              <th>Template</th>
              <th>Status</th>
              <th>Progress</th>
              <th>Biaya</th>
              <th>Tanggal</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="campaign in filteredCampaigns" :key="campaign.id">
              <td>
                <div class="d-flex flex-column">
                  <span class="text-high-emphasis font-weight-medium">{{ campaign.name }}</span>
                  <span class="text-xs text-medium-emphasis">{{ campaign.recipientCount }} penerima</span>
                </div>
              </td>
              <td>
                <span class="text-medium-emphasis">{{ templateOptions.find(template => template.value === campaign.templateId)?.title ?? campaign.templateId }}</span>
              </td>
              <td>
                <VChip size="small" :color="resolveStatusColor(campaign.status)" variant="flat" class="status-chip text-uppercase">
                  {{ resolveStatusLabel(campaign.status) }}
                </VChip>
              </td>
              <td style="min-inline-size: 16rem;">
                <div class="d-flex flex-column gap-1">
                  <div class="d-flex justify-space-between gap-3 text-body-2">
                    <span>{{ campaignProgress(campaign) }}%</span>
                    <span class="text-medium-emphasis">{{ campaign.deliveredCount }} terkirim, {{ campaign.failedCount }} gagal</span>
                  </div>
                  <VProgressLinear
                    :model-value="campaignProgress(campaign)"
                    :color="campaign.status === 'scheduled' ? 'warning' : 'success'"
                    rounded
                    height="8"
                  />
                </div>
              </td>
              <td>{{ formatCurrency(campaign.cost) }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ formatDate(campaign.scheduledAt || campaign.createdAt) }}</span>
                  <span class="text-xs text-medium-emphasis">{{ campaign.scheduledAt ? 'Tanggal jadwal' : 'Tanggal dibuat' }}</span>
                </div>
              </td>
              <td class="text-center">
                <VBtn icon size="small" variant="text" color="secondary" @click="openCampaignDetail(campaign.id)">
                  <VIcon icon="tabler-eye" />
                </VBtn>
              </td>
            </tr>

            <tr v-if="!filteredCampaigns.length">
              <td colspan="7" class="text-center text-medium-emphasis py-6">
                Belum ada kampanye yang cocok dengan pencarian ini.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </section>
</template>

<style scoped>
.broadcast-stat-card {
  box-shadow: 0 10px 24px rgba(58, 71, 101, 0.08);
}

.broadcast-stat-icon {
  box-shadow: 0 8px 18px rgba(115, 103, 240, 0.2);
}

.status-chip {
  min-inline-size: 88px;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}
</style>