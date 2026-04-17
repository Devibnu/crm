<script setup lang="ts">
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
const legacyBroadcastCreatePath = '/omnichannel/whatsapp/broadcast/create'

interface CustomerContact {
  id: number
  nama: string
  email: string
  noHp: string | null
  jumlahTiket: number
  createdAt: string | null
}

const campaignForm = reactive({
  name: '',
  description: '',
  template: '',
  audience: 'all',
  sendMode: 'draft',
  scheduledAt: '',
  rateLimit: '10',
})

const templateOptions = whatsappTemplateCatalog
  .filter(template => template.channels.includes('broadcast') && template.status === 'approved')
  .map(template => ({
    title: template.name,
    value: template.id,
    body: template.body,
  }))

campaignForm.template = templateOptions[0]?.value ?? ''

const audienceOptions = [
  { title: 'Semua kontak eligible', value: 'all' },
  { title: 'Customer loyal', value: 'loyal' },
  { title: 'Warm leads', value: 'warm' },
  { title: 'Customer baru', value: 'new' },
]

const scheduleOptions = [
  { title: 'Simpan sebagai Draft', value: 'draft' },
  { title: 'Jadwalkan', value: 'scheduled' },
]

const rateLimitOptions = [
  { title: '10 (Default)', value: '10' },
  { title: '20', value: '20' },
  { title: '30', value: '30' },
]

const { data: pelangganData } = await useApi<{ pelanggan: CustomerContact[] }>(createUrl('/crm/pelanggan'))

const allCustomers = computed(() => pelangganData.value?.pelanggan ?? [])
const customersWithWhatsapp = computed(() => allCustomers.value.filter(customer => Boolean(customer.noHp)))

const getAudienceBucket = (customer: CustomerContact) => {
  if (customer.jumlahTiket >= 3)
    return 'loyal'
  if (customer.jumlahTiket >= 1)
    return 'warm'

  const createdAt = customer.createdAt ? new Date(customer.createdAt).getTime() : 0

  if (createdAt >= Date.now() - 1000 * 60 * 60 * 24 * 30)
    return 'new'

  return 'all'
}

const selectedAudienceContacts = computed(() => customersWithWhatsapp.value.filter(customer => {
  if (campaignForm.audience === 'all')
    return true

  return getAudienceBucket(customer) === campaignForm.audience
}))

const estimatedCost = computed(() => selectedAudienceContacts.value.length * 350)
const selectedTemplate = computed(() => templateOptions.find(template => template.value === campaignForm.template))
const selectedTemplatePreview = computed(() => selectedTemplate.value?.body.replace('{{nama}}', 'Budi') ?? '')
const canCreateCampaign = computed(() => Boolean(campaignForm.name.trim() && campaignForm.template && selectedAudienceContacts.value.length))
const canScheduleCampaign = computed(() => Boolean(canCreateCampaign.value && campaignForm.scheduledAt))
const canSubmitCampaign = computed(() => campaignForm.sendMode === 'scheduled' ? canScheduleCampaign.value : canCreateCampaign.value)
const schedulePreview = computed(() => {
  if (!campaignForm.scheduledAt)
    return '-'

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(campaignForm.scheduledAt))
})

const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
}).format(value)

const audienceFootnote = computed(() => {
  if (!selectedAudienceContacts.value.length)
    return 'Belum ada kontak? Import kontak'

  return `${selectedAudienceContacts.value.length} kontak eligible untuk segment ini.`
})

const persistCampaign = (status: 'draft' | 'scheduled') => {
  if (status === 'scheduled' && !canScheduleCampaign.value)
    return

  if (status === 'draft' && !canCreateCampaign.value)
    return

  const campaigns = loadBroadcastCampaigns()
  const campaignId = Date.now()

  campaigns.unshift({
    id: campaignId,
    name: campaignForm.name.trim(),
    templateId: campaignForm.template,
    status,
    recipientCount: selectedAudienceContacts.value.length,
    deliveredCount: 0,
    failedCount: 0,
    pendingCount: selectedAudienceContacts.value.length,
    cost: estimatedCost.value,
    createdAt: new Date().toISOString(),
    scheduledAt: status === 'scheduled' ? new Date(campaignForm.scheduledAt).toISOString() : null,
    description: campaignForm.description.trim(),
    audience: campaignForm.audience,
    rateLimit: campaignForm.rateLimit,
  })

  saveBroadcastCampaigns(campaigns)
  router.push(broadcastBasePath)
}

const saveDraft = () => {
  persistCampaign('draft')
}

const scheduleCampaign = () => {
  persistCampaign('scheduled')
}

const submitCampaign = () => {
  if (campaignForm.sendMode === 'scheduled') {
    scheduleCampaign()
    return
  }

  saveDraft()
}

const cancel = () => {
  router.push(broadcastBasePath)
}

onMounted(() => {
  if (route.path === legacyBroadcastCreatePath)
    router.replace(`${broadcastBasePath}/create`)
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <div>
      <div class="text-overline mb-2">CRM Marketing Automation / Campaign Execution / WhatsApp Broadcast / Create</div>
      <h4 class="text-h4 mb-1">Buat Kampanye WhatsApp Broadcast</h4>
      <p class="mb-0 text-body-1 text-medium-emphasis">
        Siapkan detail outbound campaign, pilih template Approved Meta, lalu tentukan target audience dan mode pengirimannya.
      </p>
    </div>

    <CampaignExecutionWorkspaceNav />

    <VRow>
      <VCol cols="12" lg="8">
        <div class="d-flex flex-column gap-6">
          <VCard>
            <VCardItem>
              <VCardTitle>Detail Kampanye</VCardTitle>
            </VCardItem>

            <VDivider />

            <VCardText class="pt-6">
              <VRow>
                <VCol cols="12">
                  <AppTextField
                    v-model="campaignForm.name"
                    label="Nama Kampanye *"
                    placeholder="Contoh: Promo Akhir Tahun 2026"
                  />
                </VCol>

                <VCol cols="12">
                  <AppTextarea
                    v-model="campaignForm.description"
                    label="Deskripsi"
                    placeholder="Deskripsi singkat kampanye (opsional)"
                    rows="3"
                  />
                </VCol>

                <VCol cols="12">
                  <AppSelect
                    v-model="campaignForm.template"
                    :items="templateOptions"
                    item-title="title"
                    item-value="value"
                    label="Template Pesan *"
                    placeholder="Pilih Template"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard>
            <VCardItem>
              <VCardTitle>Target Penerima</VCardTitle>
              <template #append>
                <span class="text-body-2 text-medium-emphasis">Hanya kontak yang sudah opt-in yang akan menerima pesan</span>
              </template>
            </VCardItem>

            <VDivider />

            <VCardText class="pt-6">
              <AppSelect
                v-model="campaignForm.audience"
                :items="audienceOptions"
                item-title="title"
                item-value="value"
                label="Target Audience *"
                placeholder="Pilih target..."
              />

              <div class="text-body-2 text-medium-emphasis mt-3">
                {{ audienceFootnote }}
              </div>

              <div class="d-flex flex-wrap gap-2 mt-4">
                <VChip v-for="item in selectedAudienceContacts.slice(0, 6)" :key="item.id" size="small" color="primary" variant="tonal">
                  {{ item.nama }}
                </VChip>
                <VChip v-if="selectedAudienceContacts.length > 6" size="small" variant="outlined">
                  +{{ selectedAudienceContacts.length - 6 }} lainnya
                </VChip>
              </div>
            </VCardText>
          </VCard>
        </div>
      </VCol>

      <VCol cols="12" lg="4">
        <div class="d-flex flex-column gap-6">
          <VCard>
            <VCardItem>
              <VCardTitle>Jadwal Pengiriman</VCardTitle>
            </VCardItem>

            <VDivider />

            <VCardText class="pt-6 d-flex flex-column gap-4">
              <VRadioGroup v-model="campaignForm.sendMode" class="schedule-radio-group">
                <VRadio v-for="option in scheduleOptions" :key="option.value" :value="option.value" color="primary">
                  <template #label>
                    <span class="text-body-1 text-high-emphasis">{{ option.title }}</span>
                  </template>
                </VRadio>
              </VRadioGroup>

              <AppDateTimePicker
                v-if="campaignForm.sendMode === 'scheduled'"
                v-model="campaignForm.scheduledAt"
                label="Waktu Pengiriman"
                placeholder="Pilih tanggal dan jam"
                :config="{ enableTime: true, dateFormat: 'Y-m-d H:i', minDate: 'today', time_24hr: true }"
              />

              <div v-if="campaignForm.sendMode === 'scheduled'" class="text-body-2 text-medium-emphasis">
                Pengiriman akan dijalankan pada {{ schedulePreview }}.
              </div>

              <div v-else class="text-body-2 text-medium-emphasis">
                Campaign akan disimpan ke status draft dan bisa dijalankan dari halaman detail kampanye.
              </div>
            </VCardText>
          </VCard>

          <VCard>
            <VCardItem>
              <VCardTitle>Pengaturan</VCardTitle>
            </VCardItem>

            <VDivider />

            <VCardText class="pt-6 d-flex flex-column gap-4">
              <AppSelect
                v-model="campaignForm.rateLimit"
                :items="rateLimitOptions"
                item-title="title"
                item-value="value"
                label="Rate Limit (pesan/detik)"
              />

              <div class="text-body-2 text-medium-emphasis">
                Rate limit lebih tinggi = pengiriman lebih cepat, tapi risiko throttling.
              </div>

              <div v-if="selectedTemplate" class="template-preview-box rounded pa-4">
                <div class="text-body-1 text-high-emphasis mb-2">Preview Template</div>
                <div class="text-body-2 text-medium-emphasis">
                  {{ selectedTemplatePreview }}
                </div>
              </div>
            </VCardText>
          </VCard>

          <VCard class="campaign-estimate-card">
            <VCardText class="text-white d-flex flex-column gap-2">
              <div class="text-body-1 opacity-90">Estimasi Biaya</div>
              <h3 class="text-h3 text-white">{{ formatCurrency(estimatedCost) }}</h3>
              <div class="text-body-2 opacity-90">{{ selectedAudienceContacts.length }} penerima × Rp 350</div>
            </VCardText>
          </VCard>

          <div class="d-flex flex-column gap-3">
            <VBtn block color="success" prepend-icon="tabler-lock" :disabled="!canSubmitCampaign" @click="submitCampaign">
              Buat Kampanye
            </VBtn>
            <VBtn block color="secondary" variant="outlined" @click="cancel">
              Batal
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.campaign-estimate-card {
  background: linear-gradient(135deg, #53d122 0%, #2cb40a 100%);
}

.schedule-radio-group :deep(.v-selection-control) {
  min-block-size: 34px;
  padding-block: 0;
}

.schedule-radio-group :deep(.v-label) {
  opacity: 1;
}

.template-preview-box {
  background: rgba(var(--v-theme-info), 0.08);
  border: 1px solid rgba(var(--v-theme-info), 0.16);
}
</style>