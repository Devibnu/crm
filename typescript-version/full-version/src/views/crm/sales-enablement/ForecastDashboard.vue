<script setup lang="ts">
import type { ForecastResponse, OpportunityStage } from '@/types/sales-enablement'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'

const { t } = useI18n()

const forecastData = ref<ForecastResponse | null>(null)
const isLoading = ref(false)
const snackbar = ref({ visible: false, color: 'success', text: '' })
const page = ref(1)
const itemsPerPage = ref(10)

const summary = computed(() => forecastData.value?.summary ?? {
  pipelineValue: 0,
  weightedForecast: 0,
  committedForecast: 0,
  closedWonValue: 0,
})

const byStage = computed(() => forecastData.value?.byStage ?? [])
const openDeals = computed(() => forecastData.value?.openDeals ?? [])
const snapshots = computed(() => forecastData.value?.snapshots ?? [])
const placeholder = computed(() => forecastData.value?.placeholder ?? {})

const currencyFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
})

const stageHeaders = computed(() => [
  { title: t('crm.sales.forecast.stage.table.stage'), key: 'stage', sortable: false },
  { title: t('crm.sales.forecast.stage.table.count'), key: 'count', sortable: false },
  { title: t('crm.sales.forecast.stage.table.amount'), key: 'amount', sortable: false },
  { title: t('crm.sales.forecast.stage.table.weighted'), key: 'weightedAmount', sortable: false },
])

const dealHeaders = computed(() => [
  { title: t('crm.sales.forecast.openDeals.table.code'), key: 'code', sortable: false },
  { title: t('crm.sales.forecast.openDeals.table.name'), key: 'name', sortable: false },
  { title: t('crm.sales.forecast.openDeals.table.stage'), key: 'stage', sortable: false },
  { title: t('crm.sales.forecast.openDeals.table.amount'), key: 'amount', sortable: false },
  { title: t('crm.sales.forecast.openDeals.table.probability'), key: 'probability', sortable: false },
  { title: t('crm.sales.forecast.openDeals.table.closeDate'), key: 'expectedCloseDate', sortable: false },
])

const resolveStageLabel = (stage: OpportunityStage) => {
  if (stage === 'proposal')
    return t('crm.sales.shared.opportunityStages.proposal')
  if (stage === 'negotiation')
    return t('crm.sales.shared.opportunityStages.negotiation')
  if (stage === 'closed_won')
    return t('crm.sales.shared.opportunityStages.closedWon')
  if (stage === 'closed_lost')
    return t('crm.sales.shared.opportunityStages.closedLost')

  return t('crm.sales.shared.opportunityStages.prospecting')
}

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = { visible: true, color, text }
}

const fetchForecast = async () => {
  isLoading.value = true

  try {
    const { data } = await axiosApi.get<ForecastResponse>('/forecast')

    forecastData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.snackbar.loadError')), 'error')
  }
  finally {
    isLoading.value = false
  }
}

onMounted(() => {
  void fetchForecast()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between gap-4 align-lg-center">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.sales.forecast.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.sales.forecast.subtitle') }}</p>
        </div>
        <VBtn color="secondary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoading" @click="fetchForecast()">{{ t('crm.sales.shared.actions.refresh') }}</VBtn>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.forecast.summary.pipelineValue') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.pipelineValue) }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.forecast.summary.weightedForecast') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.weightedForecast) }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.forecast.summary.committedForecast') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.committedForecast) }}</h4></VCardText></VCard></VCol>
      <VCol cols="12" sm="6" lg="3"><VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.forecast.summary.closedWonValue') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.closedWonValue) }}</h4></VCardText></VCard></VCol>
    </VRow>

    <VAlert color="info" variant="tonal">{{ placeholder.forecastEngine || t('crm.sales.placeholders.forecast') }}</VAlert>

    <VCard>
      <VCardItem :title="t('crm.sales.forecast.stage.title')" />
      <VDataTable :headers="stageHeaders" :items="byStage" item-value="stage" class="text-no-wrap">
        <template #item.stage="{ item }">{{ resolveStageLabel(item.stage) }}</template>
        <template #item.amount="{ item }">{{ currencyFormatter.format(item.amount) }}</template>
        <template #item.weightedAmount="{ item }">{{ currencyFormatter.format(item.weightedAmount) }}</template>
      </VDataTable>
    </VCard>

    <VCard>
      <VCardItem :title="t('crm.sales.forecast.openDeals.title')" />
      <VDataTable v-model:page="page" v-model:items-per-page="itemsPerPage" :headers="dealHeaders" :items="openDeals" item-value="id" class="text-no-wrap">
        <template #item.stage="{ item }">{{ resolveStageLabel(item.stage) }}</template>
        <template #item.amount="{ item }">{{ currencyFormatter.format(item.amount) }}</template>
        <template #bottom><TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="openDeals.length" /></template>
      </VDataTable>
    </VCard>

    <VCard>
      <VCardItem :title="t('crm.sales.forecast.snapshots.title')" />
      <VCardText v-if="!snapshots.length" class="text-medium-emphasis">{{ t('crm.sales.forecast.snapshots.empty') }}</VCardText>
      <VCardText v-else>
        <div class="d-flex flex-column gap-3">
          <div v-for="snapshot in snapshots" :key="snapshot.id" class="rounded border pa-4">
            <div class="d-flex justify-space-between gap-3 flex-wrap mb-2">
              <div class="font-weight-medium text-high-emphasis">{{ snapshot.periodLabel }}</div>
              <VChip size="small" variant="tonal">{{ snapshot.status }}</VChip>
            </div>
            <div class="text-body-2 text-medium-emphasis mb-2">{{ snapshot.snapshotDate || '-' }}</div>
            <div class="text-body-2">{{ currencyFormatter.format(snapshot.forecastAmount) }} / {{ currencyFormatter.format(snapshot.weightedAmount) }} / {{ currencyFormatter.format(snapshot.committedAmount) }}</div>
          </div>
        </div>
      </VCardText>
    </VCard>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">{{ snackbar.text }}</VSnackbar>
  </section>
</template>