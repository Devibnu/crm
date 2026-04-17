<script setup lang="ts">
import type { QuotationIndexResponse, QuotationMutationResponse, QuotationRecord, QuotationStatus } from '@/types/sales-enablement'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'

const { t } = useI18n()

const quotationData = ref<QuotationIndexResponse | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const isSaving = ref(false)
const selectedQuotationId = ref<number | null>(null)
const selectedStatus = ref<QuotationStatus>('draft')
const selectedApprovalNotes = ref('')
const snackbar = ref({ visible: false, color: 'success', text: '' })
const page = ref(1)
const itemsPerPage = ref(10)
const form = ref({
  opportunityId: null as number | null,
  title: '',
  amount: 0,
  currency: 'IDR',
  validUntil: '',
  status: 'draft' as QuotationStatus,
  approvalNotes: '',
})

const summary = computed(() => quotationData.value?.summary ?? {
  draft: 0,
  submitted: 0,
  approved: 0,
  rejected: 0,
})
const quotations = computed(() => quotationData.value?.data ?? [])
const opportunityOptions = computed(() => quotationData.value?.opportunities.map(opportunity => ({
  title: `${opportunity.name}${opportunity.lead?.company ? ` • ${opportunity.lead.company}` : ''}`,
  value: opportunity.id,
})) ?? [])
const selectedQuotation = computed(() => quotations.value.find(item => item.id === selectedQuotationId.value) ?? null)

const headers = computed(() => [
  { title: t('crm.sales.quotations.table.number'), key: 'quoteNumber', sortable: false },
  { title: t('crm.sales.quotations.table.title'), key: 'title', sortable: false },
  { title: t('crm.sales.quotations.table.opportunity'), key: 'opportunity', sortable: false },
  { title: t('crm.sales.quotations.table.status'), key: 'status', sortable: false },
  { title: t('crm.sales.quotations.table.validUntil'), key: 'validUntil', sortable: false },
  { title: t('crm.sales.quotations.table.amount'), key: 'amount', sortable: false },
])

const statusOptions = computed(() => [
  { title: t('crm.sales.shared.quotationStatuses.draft'), value: 'draft' },
  { title: t('crm.sales.shared.quotationStatuses.submitted'), value: 'submitted' },
  { title: t('crm.sales.shared.quotationStatuses.approved'), value: 'approved' },
  { title: t('crm.sales.shared.quotationStatuses.rejected'), value: 'rejected' },
])

const currencyFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
})

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = { visible: true, color, text }
}

const resolveQuotationStatusLabel = (status: QuotationStatus) => {
  if (status === 'submitted')
    return t('crm.sales.shared.quotationStatuses.submitted')
  if (status === 'approved')
    return t('crm.sales.shared.quotationStatuses.approved')
  if (status === 'rejected')
    return t('crm.sales.shared.quotationStatuses.rejected')

  return t('crm.sales.shared.quotationStatuses.draft')
}

const resolveQuotationStatusColor = (status: QuotationStatus) => {
  if (status === 'submitted')
    return 'warning'
  if (status === 'approved')
    return 'success'
  if (status === 'rejected')
    return 'error'

  return 'secondary'
}

const fetchQuotations = async () => {
  isLoading.value = true

  try {
    const { data } = await axiosApi.get<QuotationIndexResponse>('/quotations')

    quotationData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.snackbar.loadError')), 'error')
  }
  finally {
    isLoading.value = false
  }
}

const submitQuotation = async () => {
  isSubmitting.value = true

  try {
    await axiosApi.post<QuotationMutationResponse>('/quotations', {
      ...form.value,
      validUntil: form.value.validUntil || null,
    })

    form.value = {
      opportunityId: null,
      title: '',
      amount: 0,
      currency: 'IDR',
      validUntil: '',
      status: 'draft',
      approvalNotes: '',
    }
    await fetchQuotations()
    showSnackbar(t('crm.sales.quotations.snackbar.createSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.quotations.snackbar.createError')), 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

const selectQuotation = (quotation: QuotationRecord) => {
  selectedQuotationId.value = quotation.id
  selectedStatus.value = quotation.status
  selectedApprovalNotes.value = quotation.approvalNotes || ''
}

const updateQuotation = async () => {
  if (!selectedQuotation.value)
    return

  isSaving.value = true

  try {
    await axiosApi.patch<QuotationMutationResponse>(`/quotations/${selectedQuotation.value.id}`, {
      status: selectedStatus.value,
      approvalNotes: selectedApprovalNotes.value,
    })

    await fetchQuotations()
    showSnackbar(t('crm.sales.quotations.snackbar.updateSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.quotations.snackbar.updateError')), 'error')
  }
  finally {
    isSaving.value = false
  }
}

onMounted(() => {
  void fetchQuotations()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText>
        <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
        <h4 class="text-h4 mb-1">{{ t('crm.sales.quotations.title') }}</h4>
        <p class="mb-0 text-body-1">{{ t('crm.sales.quotations.subtitle') }}</p>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.quotations.summary.draft') }}</div><h4 class="text-h4 mb-0">{{ summary.draft }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.quotations.summary.submitted') }}</div><h4 class="text-h4 mb-0">{{ summary.submitted }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.quotations.summary.approved') }}</div><h4 class="text-h4 mb-0">{{ summary.approved }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.quotations.summary.rejected') }}</div><h4 class="text-h4 mb-0">{{ summary.rejected }}</h4></VCardText></VCard></VCol>
    </VRow>

    <VRow>
      <VCol cols="12" lg="5">
        <VCard>
          <VCardItem :title="t('crm.sales.quotations.formTitle')" />
          <VCardText>
            <VRow>
              <VCol cols="12"><AppSelect v-model="form.opportunityId" :label="t('crm.sales.quotations.form.opportunity')" :items="opportunityOptions" /></VCol>
              <VCol cols="12"><AppTextField v-model="form.title" :label="t('crm.sales.quotations.form.title')" /></VCol>
              <VCol cols="12" md="6"><AppTextField v-model="form.amount" type="number" :label="t('crm.sales.quotations.form.amount')" /></VCol>
              <VCol cols="12" md="6"><AppTextField v-model="form.currency" :label="t('crm.sales.quotations.form.currency')" /></VCol>
              <VCol cols="12" md="6"><AppTextField v-model="form.validUntil" type="date" :label="t('crm.sales.quotations.form.validUntil')" /></VCol>
              <VCol cols="12" md="6"><AppSelect v-model="form.status" :label="t('crm.sales.quotations.form.status')" :items="statusOptions" /></VCol>
              <VCol cols="12"><AppTextarea v-model="form.approvalNotes" :label="t('crm.sales.quotations.form.approvalNotes')" rows="4" /></VCol>
            </VRow>
          </VCardText>
          <VCardText class="pt-0">
            <VBtn color="primary" block :loading="isSubmitting" @click="submitQuotation">{{ t('crm.sales.quotations.form.submit') }}</VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" lg="7">
        <VAlert color="info" variant="tonal" class="mb-6">{{ quotationData?.placeholder?.approval || t('crm.sales.placeholders.quotation') }}</VAlert>

        <VCard>
          <VCardItem :title="t('crm.sales.quotations.tableTitle')">
            <template #append>
              <VBtn color="secondary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoading" @click="fetchQuotations()">{{ t('crm.sales.shared.actions.refresh') }}</VBtn>
            </template>
          </VCardItem>
          <VDataTable v-model:page="page" v-model:items-per-page="itemsPerPage" :headers="headers" :items="quotations" item-value="id" class="text-no-wrap">
            <template #item.quoteNumber="{ item }"><button type="button" class="sales-link" @click="selectQuotation(item)">{{ item.quoteNumber }}</button></template>
            <template #item.title="{ item }"><button type="button" class="sales-subject" @click="selectQuotation(item)"><span class="font-weight-medium text-high-emphasis">{{ item.title }}</span><span class="text-sm text-medium-emphasis">{{ item.opportunity?.name || '-' }}</span></button></template>
            <template #item.opportunity="{ item }">{{ item.opportunity?.lead?.company || item.opportunity?.name || '-' }}</template>
            <template #item.status="{ item }"><VChip size="small" :color="resolveQuotationStatusColor(item.status)" variant="tonal">{{ resolveQuotationStatusLabel(item.status) }}</VChip></template>
            <template #item.amount="{ item }">{{ currencyFormatter.format(item.amount) }}</template>
            <template #bottom><TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="quotations.length" /></template>
          </VDataTable>
        </VCard>
      </VCol>
    </VRow>

    <VRow v-if="selectedQuotation">
      <VCol cols="12" lg="7">
        <VCard>
          <VCardItem :title="selectedQuotation.title" :subtitle="selectedQuotation.quoteNumber" />
          <VCardText class="d-flex flex-column gap-4">
            <div><div class="text-sm text-disabled mb-1">{{ t('crm.sales.quotations.detail.opportunity') }}</div><div>{{ selectedQuotation.opportunity?.name || '-' }}</div></div>
            <div><div class="text-sm text-disabled mb-1">{{ t('crm.sales.quotations.detail.customer') }}</div><div>{{ selectedQuotation.opportunity?.lead?.company || '-' }}</div></div>
            <div><div class="text-sm text-disabled mb-1">{{ t('crm.sales.quotations.detail.amount') }}</div><div>{{ currencyFormatter.format(selectedQuotation.amount) }}</div></div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" lg="5">
        <VCard>
          <VCardItem :title="t('crm.sales.quotations.actions.manageTitle')" />
          <VCardText class="d-flex flex-column gap-4">
            <AppSelect v-model="selectedStatus" :label="t('crm.sales.quotations.actions.status')" :items="statusOptions" />
            <AppTextarea v-model="selectedApprovalNotes" :label="t('crm.sales.quotations.actions.notes')" rows="4" />
            <VBtn color="primary" :loading="isSaving" @click="updateQuotation">{{ t('crm.sales.quotations.actions.save') }}</VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">{{ snackbar.text }}</VSnackbar>
  </section>
</template>

<style scoped lang="scss">
.sales-link,
.sales-subject {
  display: flex;
  flex-direction: column;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  inline-size: 100%;
  text-align: start;
}

.sales-link {
  color: rgb(var(--v-theme-primary));
  font-weight: 500;
}
</style>