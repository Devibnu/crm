<script setup lang="ts">
import type { WhatsAppTemplate } from '@/data/whatsapp-template-catalog'
import { whatsappTemplateCatalog } from '@/data/whatsapp-template-catalog'

definePage({
  meta: {
    navActiveLink: 'marketing-automation-campaign-execution',
  },
})

type TemplateCategoryFilter = 'all' | WhatsAppTemplate['category']
type TemplateStatusFilter = 'all' | WhatsAppTemplate['status']

interface WorkspaceTemplate extends WhatsAppTemplate {
  createdSource: 'catalog' | 'custom'
}

interface TemplateFormState {
  id: string | null
  sourceTemplateId: string | null
  name: string
  category: WhatsAppTemplate['category'] | null
  body: string
  status: WhatsAppTemplate['status']
}

const route = useRoute()
const router = useRouter()
const broadcastBasePath = '/marketing-automation/campaign-execution/whatsapp-broadcast'
const templateBasePath = '/marketing-automation/campaign-execution/whatsapp-templates'
const legacyTemplatePath = '/omnichannel/whatsapp/templates'
const searchQuery = ref('')
const selectedCategory = ref<TemplateCategoryFilter>('all')
const selectedStatus = ref<TemplateStatusFilter>('all')
const isDialogVisible = ref(false)
const isEditing = ref(false)

const templateLibrary = ref<WorkspaceTemplate[]>(
  whatsappTemplateCatalog.map(template => ({
    ...template,
    createdSource: 'catalog',
  })),
)

const form = reactive<TemplateFormState>({
  id: null,
  sourceTemplateId: null,
  name: '',
  category: null,
  body: '',
  status: 'draft',
})

const variableSuggestions = [
  { title: 'Nama Penerima', value: 'nama' },
  { title: 'No HP', value: 'no_hp' },
  { title: 'Email', value: 'email' },
  { title: 'Periode', value: 'periode' },
  { title: 'Jatuh Tempo', value: 'jatuh_tempo' },
]

const categoryOptions = [
  { title: 'Semua Kategori', value: 'all' },
  { title: 'Marketing', value: 'marketing' },
  { title: 'Utility', value: 'utility' },
  { title: 'Service', value: 'service' },
]

const statusOptions = [
  { title: 'Semua Status', value: 'all' },
  { title: 'Draft Internal', value: 'draft' },
  { title: 'Menunggu Review Meta', value: 'pending-meta' },
  { title: 'Approved Meta', value: 'approved' },
  { title: 'Ditolak', value: 'rejected' },
]

const templateStarterOptions = [
  { title: '-- Tulis sendiri --', value: null },
  ...whatsappTemplateCatalog.map(template => ({
    title: template.name,
    value: template.id,
  })),
]

const filteredTemplates = computed(() => templateLibrary.value.filter(template => {
  const query = searchQuery.value.trim().toLowerCase()
  const matchesSearch = !query
    || template.name.toLowerCase().includes(query)
    || template.body.toLowerCase().includes(query)

  const matchesCategory = selectedCategory.value === 'all' || template.category === selectedCategory.value
  const matchesStatus = selectedStatus.value === 'all' || template.status === selectedStatus.value

  return matchesSearch && matchesCategory && matchesStatus
}))

const stats = computed(() => ({
  approved: templateLibrary.value.filter(template => template.status === 'approved').length,
  pendingMeta: templateLibrary.value.filter(template => template.status === 'pending-meta').length,
  draft: templateLibrary.value.filter(template => template.status === 'draft').length,
  total: templateLibrary.value.length,
}))

const canSubmit = computed(() => Boolean(form.name.trim() && form.category && form.body.trim()))

const metaStatusLabel = (status: WhatsAppTemplate['status']) => {
  if (status === 'draft')
    return 'Draft Internal'
  if (status === 'pending-meta')
    return 'Menunggu Review Meta'
  if (status === 'approved')
    return 'Approved Meta'

  return 'Ditolak'
}

const metaStatusColor = (status: WhatsAppTemplate['status']) => {
  if (status === 'draft')
    return 'secondary'
  if (status === 'pending-meta')
    return 'warning'
  if (status === 'approved')
    return 'success'

  return 'error'
}

const categoryLabel = (category: WhatsAppTemplate['category']) => {
  if (category === 'marketing')
    return 'Marketing'
  if (category === 'utility')
    return 'Utility'

  return 'Service'
}

const extractVariables = (body: string) => Array.from(new Set([...body.matchAll(/\{\{\s*([^}]+?)\s*\}\}/g)].map(match => match[1].trim())))

const previewMessage = (body: string) => body
  .replaceAll('{{nama}}', 'Budi')
  .replaceAll('{{no_hp}}', '0812xxxxxxx')
  .replaceAll('{{email}}', 'budi@email.com')
  .replaceAll('{{periode}}', 'April 2026')
  .replaceAll('{{jatuh_tempo}}', '18 April 2026')

const formatVariableToken = (variable: string) => `{{${variable}}}`

const resetForm = () => {
  form.id = null
  form.sourceTemplateId = null
  form.name = ''
  form.category = null
  form.body = ''
  form.status = 'draft'
}

const openCreateDialog = () => {
  isEditing.value = false
  resetForm()
  isDialogVisible.value = true
}

const openEditDialog = (template: WorkspaceTemplate) => {
  isEditing.value = true
  form.id = template.id
  form.sourceTemplateId = null
  form.name = template.name
  form.category = template.category
  form.body = template.body
  form.status = template.status
  isDialogVisible.value = true
}

const removeTemplate = (templateId: string) => {
  templateLibrary.value = templateLibrary.value.filter(template => template.id !== templateId)
}

const applyStarterTemplate = (templateId: string | null) => {
  form.sourceTemplateId = templateId

  if (!templateId)
    return

  const selectedTemplate = whatsappTemplateCatalog.find(template => template.id === templateId)

  if (!selectedTemplate)
    return

  form.name = selectedTemplate.name
  form.category = selectedTemplate.category
  form.body = selectedTemplate.body
  form.status = 'draft'
}

const sendToMeta = (templateId: string) => {
  templateLibrary.value = templateLibrary.value.map(template => {
    if (template.id !== templateId || template.status !== 'draft')
      return template

    return {
      ...template,
      status: 'pending-meta',
    }
  })
}

const insertVariable = (variable: string) => {
  form.body = `${form.body}${form.body ? ' ' : ''}{{${variable}}}`
}

const saveTemplate = () => {
  if (!canSubmit.value || !form.category)
    return

  const templatePayload: WorkspaceTemplate = {
    id: form.id ?? `${form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${Date.now()}`,
    name: form.name.trim(),
    category: form.category,
    status: isEditing.value ? form.status : 'draft',
    language: 'ID',
    tone: 'Operasional',
    body: form.body.trim(),
    variables: extractVariables(form.body),
    channels: ['broadcast'],
    createdSource: 'custom',
  }

  if (isEditing.value && form.id) {
    templateLibrary.value = templateLibrary.value.map(template => template.id === form.id ? templatePayload : template)
  }
  else {
    templateLibrary.value = [templatePayload, ...templateLibrary.value]
  }

  isDialogVisible.value = false
  resetForm()
}

const openBroadcast = () => {
  router.push(broadcastBasePath)
}

onMounted(() => {
  if (route.path === legacyTemplatePath)
    router.replace(templateBasePath)
})

watch(isDialogVisible, value => {
  if (!value)
    resetForm()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <div class="d-flex flex-column flex-md-row align-md-start justify-space-between gap-4">
      <div>
        <div class="text-overline mb-2">Marketing Automation / Campaign Execution</div>
        <h4 class="text-h4 mb-1">WhatsApp Templates</h4>
        <p class="mb-0 text-body-1 text-medium-emphasis">
          Kelola template WhatsApp agar mudah dipahami tim, siap direview Meta, dan langsung bisa dipakai ke Broadcast maupun workflow outbound campaign.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <VChip color="success" variant="tonal">Approved Meta {{ stats.approved }}</VChip>
        <VChip color="warning" variant="tonal">Menunggu Review {{ stats.pendingMeta }}</VChip>
        <VChip color="secondary" variant="tonal">Draft {{ stats.draft }}</VChip>
        <VBtn color="primary" prepend-icon="tabler-plus" @click="openCreateDialog">
          Tambah Template
        </VBtn>
      </div>
    </div>

    <CampaignExecutionWorkspaceNav />

    <VCard class="template-guide-card">
      <VCardText class="pa-6 text-white d-flex flex-column gap-3">
        <div class="d-flex align-center gap-2 text-h6">
          <VIcon icon="tabler-info-circle" />
          <span>Cara Menggunakan Template untuk WA Blast</span>
        </div>

        <p class="mb-0 text-white opacity-90">
          Template yang dibuat di sini perlu masuk proses review Meta sebelum dipakai penuh di WhatsApp Broadcast. Alur ini dibuat agar tim campaign ops langsung paham langkah operasionalnya.
        </p>

        <ol class="template-guide-list mb-0 ps-5">
          <li>Buat template baru dari tombol <strong>Tambah Template</strong>.</li>
          <li>Pilih kategori dan tulis isi pesan dengan variable pelanggan bila diperlukan, lalu simpan sebagai <strong>Draft Internal</strong>.</li>
          <li>Cek isi template dulu dari daftar sebelum mengirim ke review.</li>
          <li>Jika sudah siap, klik <strong>Kirim ke Meta</strong> agar status berubah menjadi <strong>Menunggu Review Meta</strong>.</li>
          <li>Hasil review Meta akan muncul sebagai <strong>Approved Meta</strong> atau <strong>Ditolak</strong> di daftar template.</li>
        </ol>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-lg-row align-lg-center justify-space-between gap-3">
          <div>
            <div class="text-h6">Daftar Template</div>
            <div class="text-body-2 text-medium-emphasis">Struktur halaman ini dibuat supaya tim bisa cepat scan status review Meta, kategori, dan isi template.</div>
          </div>

          <div class="d-flex flex-column flex-md-row gap-3 align-stretch align-md-center">
            <AppTextField
              v-model="searchQuery"
              prepend-inner-icon="tabler-search"
              placeholder="Cari nama atau isi template"
              style="min-inline-size: 18rem;"
            />

            <AppSelect
              v-model="selectedCategory"
              :items="categoryOptions"
              style="min-inline-size: 11rem;"
            />

            <AppSelect
              v-model="selectedStatus"
              :items="statusOptions"
              style="min-inline-size: 13rem;"
            />
          </div>
        </div>

        <div class="d-flex justify-space-between align-center gap-3 flex-wrap">
          <div class="d-flex flex-wrap gap-2">
            <VChip color="primary" variant="tonal">{{ stats.total }} Template</VChip>
            <VChip color="info" variant="tonal">Vuexy CRM Style</VChip>
          </div>

          <VBtn color="primary" variant="tonal" prepend-icon="tabler-send" @click="openBroadcast">
            Buka Broadcast
          </VBtn>
        </div>

        <VRow>
          <VCol v-for="template in filteredTemplates" :key="template.id" cols="12" md="6" xl="4">
            <VCard class="h-100" variant="outlined">
              <VCardText class="d-flex flex-column gap-4 h-100">
                <div class="d-flex align-start justify-space-between gap-3">
                  <div>
                    <div class="text-h6 mb-1">{{ template.name }}</div>
                    <div class="text-body-2 text-medium-emphasis">
                      Bahasa {{ template.language }} • {{ template.createdSource === 'catalog' ? 'Template dasar' : 'Template custom' }}
                    </div>
                  </div>

                  <VChip size="small" :color="template.category === 'marketing' ? 'primary' : template.category === 'utility' ? 'info' : 'secondary'" variant="tonal">
                    {{ categoryLabel(template.category) }}
                  </VChip>
                </div>

                <div class="template-body-preview rounded pa-4">
                  <div class="text-body-2 text-medium-emphasis">{{ template.body }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <VChip
                    v-for="variable in template.variables"
                    :key="variable"
                    size="small"
                    color="success"
                    variant="tonal"
                  >
                    {{ formatVariableToken(variable) }}
                  </VChip>
                </div>

                <div class="d-flex flex-column gap-3 mt-auto">
                  <VChip :color="metaStatusColor(template.status)" variant="flat" size="small">
                    {{ metaStatusLabel(template.status) }}
                  </VChip>

                  <div class="d-flex gap-2 flex-wrap">
                    <VBtn
                      v-if="template.status === 'draft'"
                      size="small"
                      color="warning"
                      variant="tonal"
                      prepend-icon="tabler-send"
                      @click="sendToMeta(template.id)"
                    >
                      Kirim ke Meta
                    </VBtn>
                    <VBtn size="small" color="secondary" variant="tonal" prepend-icon="tabler-edit" @click="openEditDialog(template)">
                      Edit
                    </VBtn>
                    <VBtn size="small" color="error" variant="tonal" prepend-icon="tabler-trash" @click="removeTemplate(template.id)">
                      Hapus
                    </VBtn>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <VAlert
          v-if="!filteredTemplates.length"
          color="warning"
          variant="tonal"
          icon="tabler-search-off"
          title="Belum ada template sesuai filter"
          text="Coba ubah pencarian atau tambahkan template baru dari dialog di kanan atas."
        />
      </VCardText>
    </VCard>

    <VDialog v-model="isDialogVisible" max-width="760">
      <VCard>
        <VCardItem>
          <VCardTitle>{{ isEditing ? 'Edit Template' : 'Tambah Template Baru' }}</VCardTitle>
        </VCardItem>

        <VDivider />

        <VCardText class="pt-6">
          <VRow>
            <VCol cols="12">
              <AppSelect
                :model-value="form.sourceTemplateId"
                :items="templateStarterOptions"
                label="Mulai dari Template Siap Pakai (opsional)"
                @update:model-value="applyStarterTemplate"
              />
            </VCol>

            <VCol cols="12">
              <AppTextField
                v-model="form.name"
                label="Nama Template"
                placeholder="Contoh: Promo Akhir Tahun"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect
                v-model="form.category"
                :items="categoryOptions.filter(item => item.value !== 'all')"
                label="Kategori"
                placeholder="Pilih kategori"
              />
            </VCol>

            <VCol cols="12">
              <AppTextarea
                v-model="form.body"
                label="Isi Pesan"
                placeholder="Tulis isi template di sini"
                rows="5"
              />
              <div class="text-body-2 text-medium-emphasis mt-2">
                Setelah disimpan, template baru tetap berada di Draft Internal sampai Anda klik Kirim ke Meta dari daftar template. Gunakan variable di bawah agar pesan otomatis disesuaikan dengan data pelanggan.
              </div>
            </VCol>

            <VCol cols="12">
              <div class="variable-helper rounded pa-4">
                <div class="d-flex align-center gap-2 text-body-1 text-high-emphasis mb-3">
                  <VIcon icon="tabler-bulb" color="warning" />
                  <span>Sisipkan variable ke pesan</span>
                </div>

                <VAlert
                  color="success"
                  variant="tonal"
                  icon="tabler-sparkles"
                  class="mb-4"
                  :text="previewMessage(form.body || 'Halo {{nama}}, kami siap membantu kebutuhan Anda.')"
                />

                <div class="d-flex flex-wrap gap-2">
                  <VChip
                    v-for="item in variableSuggestions"
                    :key="item.value"
                    color="success"
                    variant="outlined"
                    @click="insertVariable(item.value)"
                  >
                    + {{ item.title }}
                  </VChip>
                </div>
              </div>
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn color="secondary" variant="text" @click="isDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="primary" prepend-icon="tabler-device-floppy" :disabled="!canSubmit" @click="saveTemplate">
            Simpan Template
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
.template-guide-card {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, #c2185b 100%);
}

.template-guide-list li + li {
  margin-block-start: 0.5rem;
}

.template-body-preview {
  background: rgba(var(--v-theme-surface-variant), 0.35);
  min-block-size: 7.5rem;
}

.variable-helper {
  background: rgba(var(--v-theme-surface-variant), 0.28);
}
</style>