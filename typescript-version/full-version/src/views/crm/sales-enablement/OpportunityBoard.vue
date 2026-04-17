<script setup lang="ts">
import type { OpportunityIndexResponse, OpportunityMutationResponse, OpportunityRecord, OpportunityStage } from '@/types/sales-enablement'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'
import OpportunityCard from '@/views/crm/sales-enablement/OpportunityCard.vue'
import OpportunityForm from '@/views/crm/sales-enablement/OpportunityForm.vue'

type CanonicalOpportunityStage = Exclude<OpportunityStage, 'prospecting'>

const { t } = useI18n()

const opportunityData = ref<OpportunityIndexResponse | null>(null)
const isLoading = ref(false)
const isFormVisible = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const isStageUpdating = ref(false)
const selectedOpportunityId = ref<number | null>(null)
const selectedAssignedUserId = ref<number | ''>('')
const selectedStageFilter = ref<CanonicalOpportunityStage | ''>('')
const draggedOpportunityId = ref<number | null>(null)
const boardErrorMessage = ref('')
const formErrorMessage = ref('')
const deleteErrorMessage = ref('')
const isDeleteDialogVisible = ref(false)
const snackbar = ref({ visible: false, color: 'success', text: '' })

const boardColumns = computed<Array<{ stage: CanonicalOpportunityStage, title: string }>>(() => [
  { stage: 'new', title: t('crm.sales.opportunities.board.stages.new') },
  { stage: 'qualified', title: t('crm.sales.opportunities.board.stages.qualified') },
  { stage: 'proposal', title: t('crm.sales.opportunities.board.stages.proposal') },
  { stage: 'negotiation', title: t('crm.sales.opportunities.board.stages.negotiation') },
  { stage: 'closed_won', title: t('crm.sales.opportunities.board.stages.closedWon') },
  { stage: 'closed_lost', title: t('crm.sales.opportunities.board.stages.closedLost') },
])

const summary = computed(() => opportunityData.value?.summary ?? {
  pipelineValue: 0,
  weightedForecast: 0,
  closedWonValue: 0,
  totalOpenDeals: 0,
})

const salesUsers = computed(() => opportunityData.value?.salesUsers ?? [])
const qualifiedLeads = computed(() => opportunityData.value?.qualifiedLeads ?? [])
const selectedOpportunity = computed(() => opportunityData.value?.data.find(item => item.id === selectedOpportunityId.value) ?? null)

const currencyFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
})

const normalizeStage = (stage: OpportunityStage): CanonicalOpportunityStage => stage === 'prospecting' ? 'new' : stage

const resolveStageTitle = (stage: OpportunityStage) => {
  const normalized = normalizeStage(stage)

  if (normalized === 'closed_won')
    return t('crm.sales.opportunities.board.stages.closedWon')
  if (normalized === 'closed_lost')
    return t('crm.sales.opportunities.board.stages.closedLost')

  return t(`crm.sales.opportunities.board.stages.${normalized}`)
}

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = { visible: true, color, text }
}

const leadOptions = computed(() => qualifiedLeads.value.map(lead => ({
  title: `${lead.fullName}${lead.company ? ` • ${lead.company}` : ''}`,
  value: lead.id,
})))

const assigneeOptions = computed(() => [
  { title: t('crm.sales.shared.unassigned'), value: null },
  ...salesUsers.value.map(user => ({ title: `${user.fullName} • ${user.role}`, value: user.id })),
])

const assigneeFilterOptions = computed(() => [
  { title: t('crm.sales.shared.filters.allAssignees'), value: '' },
  ...salesUsers.value.map(user => ({ title: user.fullName, value: user.id })),
])

const stageOptions = computed<Array<{ title: string, value: CanonicalOpportunityStage }>>(() => [
  { title: t('crm.sales.opportunities.board.stages.new'), value: 'new' },
  { title: t('crm.sales.opportunities.board.stages.qualified'), value: 'qualified' },
  { title: t('crm.sales.opportunities.board.stages.proposal'), value: 'proposal' },
  { title: t('crm.sales.opportunities.board.stages.negotiation'), value: 'negotiation' },
  { title: t('crm.sales.opportunities.board.stages.closedWon'), value: 'closed_won' },
  { title: t('crm.sales.opportunities.board.stages.closedLost'), value: 'closed_lost' },
])

const stageFilterOptions = computed(() => [
  { title: t('crm.sales.shared.statusFilters.all'), value: '' },
  ...stageOptions.value,
])

const boardByStage = computed<Record<CanonicalOpportunityStage, OpportunityRecord[]>>(() => {
  const grouped: Record<CanonicalOpportunityStage, OpportunityRecord[]> = {
    new: [],
    qualified: [],
    proposal: [],
    negotiation: [],
    closed_won: [],
    closed_lost: [],
  }

  for (const item of opportunityData.value?.data ?? [])
    grouped[normalizeStage(item.stage)].push(item)

  return grouped
})

const visibleColumns = computed(() => boardColumns.value.filter(column => !selectedStageFilter.value || column.stage === selectedStageFilter.value))

const fetchOpportunities = async () => {
  isLoading.value = true

  try {
    const { data } = await axiosApi.get<OpportunityIndexResponse>('/opportunities', {
      params: {
        assignedUserId: selectedAssignedUserId.value || undefined,
        stage: selectedStageFilter.value || undefined,
      },
    })

    opportunityData.value = data
    boardErrorMessage.value = ''
  }
  catch (error) {
    boardErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.snackbar.loadError'))
    showSnackbar(boardErrorMessage.value, 'error')
  }
  finally {
    isLoading.value = false
  }
}

const openForm = () => {
  formErrorMessage.value = ''
  isFormVisible.value = true
}

const submitOpportunity = async (payload: {
  leadId: number | null
  assignedUserId: number | null
  name: string
  stage: OpportunityStage
  amount: number
  currency: string
  probability: number
  expectedCloseDate: string
  statusNotes: string
}) => {
  isSubmitting.value = true
  formErrorMessage.value = ''

  try {
    await axiosApi.post<OpportunityMutationResponse>('/opportunities', {
      ...payload,
      expectedCloseDate: payload.expectedCloseDate || null,
    })

    isFormVisible.value = false
    await fetchOpportunities()
    showSnackbar(t('crm.sales.opportunities.snackbar.createSuccess'))
  }
  catch (error) {
    formErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.opportunities.snackbar.createError'))
    showSnackbar(formErrorMessage.value, 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

const selectOpportunity = (opportunity: OpportunityRecord) => {
  selectedOpportunityId.value = opportunity.id
}

const openDeleteDialog = (opportunity: OpportunityRecord) => {
  selectedOpportunityId.value = opportunity.id
  deleteErrorMessage.value = ''
  isDeleteDialogVisible.value = true
}

const closeDeleteDialog = () => {
  isDeleteDialogVisible.value = false
  deleteErrorMessage.value = ''
}

const deleteOpportunity = async () => {
  if (!selectedOpportunity.value)
    return

  isDeleting.value = true
  deleteErrorMessage.value = ''

  try {
    const deletedId = selectedOpportunity.value.id

    await axiosApi.delete(`/opportunities/${deletedId}`)
    await fetchOpportunities()
    closeDeleteDialog()

    if (selectedOpportunityId.value === deletedId)
      selectedOpportunityId.value = null

    showSnackbar(t('crm.sales.opportunities.snackbar.deleteSuccess'))
  }
  catch (error) {
    deleteErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.opportunities.snackbar.deleteError'))
    showSnackbar(deleteErrorMessage.value, 'error')
  }
  finally {
    isDeleting.value = false
  }
}

const startDraggingOpportunity = (opportunity: OpportunityRecord) => {
  draggedOpportunityId.value = opportunity.id
}

const allowStageDrop = (event: DragEvent) => {
  event.preventDefault()
}

const moveOpportunityStage = async (stage: CanonicalOpportunityStage) => {
  if (!draggedOpportunityId.value)
    return

  const currentOpportunity = opportunityData.value?.data.find(item => item.id === draggedOpportunityId.value)

  if (!currentOpportunity || normalizeStage(currentOpportunity.stage) === stage) {
    draggedOpportunityId.value = null
    return
  }

  isStageUpdating.value = true
  boardErrorMessage.value = ''

  try {
    await axiosApi.patch<OpportunityMutationResponse>(`/opportunities/${draggedOpportunityId.value}/stage`, {
      stage,
    })

    selectedOpportunityId.value = draggedOpportunityId.value
    await fetchOpportunities()
    showSnackbar(t('crm.sales.opportunities.snackbar.updateSuccess'))
  }
  catch (error) {
    boardErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.opportunities.snackbar.stageError'))
    showSnackbar(boardErrorMessage.value, 'error')
  }
  finally {
    draggedOpportunityId.value = null
    isStageUpdating.value = false
  }
}

watch([selectedAssignedUserId, selectedStageFilter], () => {
  void fetchOpportunities()
})

onMounted(() => {
  void fetchOpportunities()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-xl-row justify-space-between gap-4 align-xl-center">
          <div>
            <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
            <h4 class="text-h4 mb-1">{{ t('crm.sales.opportunities.title') }}</h4>
            <p class="mb-0 text-body-1">{{ t('crm.sales.opportunities.subtitle') }}</p>
          </div>

          <div class="d-flex gap-3 flex-wrap">
            <VBtn color="secondary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoading" @click="fetchOpportunities()">
              {{ t('crm.sales.shared.actions.refresh') }}
            </VBtn>
            <VBtn color="primary" prepend-icon="tabler-plus" @click="openForm">
              {{ t('crm.sales.opportunities.actions.create') }}
            </VBtn>
          </div>
        </div>

        <VRow>
          <VCol cols="12" md="6" lg="4">
            <AppSelect v-model="selectedAssignedUserId" data-testid="opportunity-assignee-filter" :label="t('crm.sales.opportunities.toolbar.assigneeFilter')" :items="assigneeFilterOptions" />
          </VCol>
          <VCol cols="12" md="6" lg="4">
            <AppSelect v-model="selectedStageFilter" data-testid="opportunity-stage-filter" :label="t('crm.sales.opportunities.toolbar.stageFilter')" :items="stageFilterOptions" />
          </VCol>
        </VRow>

        <VAlert v-if="boardErrorMessage" data-testid="opportunity-board-error" color="error" variant="tonal">
          {{ boardErrorMessage }}
        </VAlert>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" sm="6" lg="3">
        <VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.opportunities.summary.pipelineValue') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.pipelineValue) }}</h4></VCardText></VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.opportunities.summary.weightedForecast') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.weightedForecast) }}</h4></VCardText></VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.opportunities.summary.closedWonValue') }}</div><h4 class="text-h4 mb-0">{{ currencyFormatter.format(summary.closedWonValue) }}</h4></VCardText></VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard><VCardText><div class="text-body-1 mb-1">{{ t('crm.sales.opportunities.summary.totalOpenDeals') }}</div><h4 class="text-h4 mb-0">{{ summary.totalOpenDeals }}</h4></VCardText></VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol v-for="column in visibleColumns" :key="column.stage" cols="12" md="6" xl="2">
        <VCard class="kanban-column h-100">
          <div class="kanban-dropzone" :data-stage="column.stage" @dragover="allowStageDrop" @drop="moveOpportunityStage(column.stage)">
            <VCardItem>
              <template #title>
                <div class="d-flex justify-space-between align-center gap-2">
                  <span>{{ column.title }}</span>
                  <VChip size="small" color="primary" variant="tonal">{{ boardByStage[column.stage].length }}</VChip>
                </div>
              </template>
            </VCardItem>
            <VCardText class="d-flex flex-column gap-3">
              <OpportunityCard
                v-for="item in boardByStage[column.stage]"
                :key="item.id"
                :opportunity="item"
                :currency-label="currencyFormatter.format(item.amount)"
                :assignee-fallback="t('crm.sales.shared.unassigned')"
                :selected="selectedOpportunityId === item.id"
                @view="selectOpportunity"
                @delete="openDeleteDialog"
                @dragstart="startDraggingOpportunity"
              />

              <div v-if="!boardByStage[column.stage].length" class="text-medium-emphasis text-sm">{{ t('crm.sales.opportunities.board.empty') }}</div>
            </VCardText>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <VRow v-if="selectedOpportunity">
      <VCol cols="12" lg="8">
        <VCard>
          <VCardItem :title="selectedOpportunity.name" :subtitle="selectedOpportunity.code" />
          <VCardText class="d-flex flex-column gap-4">
            <VRow>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.company') }}</div><div>{{ selectedOpportunity.lead?.company || '-' }}</div></VCol>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.assignee') }}</div><div>{{ selectedOpportunity.assignedUser?.fullName || t('crm.sales.shared.unassigned') }}</div></VCol>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.amount') }}</div><div>{{ currencyFormatter.format(selectedOpportunity.amount) }}</div></VCol>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.stage') }}</div><div>{{ resolveStageTitle(selectedOpportunity.stage) }}</div></VCol>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.probability') }}</div><div>{{ selectedOpportunity.probability }}%</div></VCol>
              <VCol cols="12" md="6"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.expectedCloseDate') }}</div><div>{{ selectedOpportunity.expectedCloseDate || '-' }}</div></VCol>
              <VCol cols="12"><div class="text-sm text-disabled mb-1">{{ t('crm.sales.opportunities.detail.notes') }}</div><div>{{ selectedOpportunity.statusNotes || '-' }}</div></VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" lg="4">
        <VCard>
          <VCardItem :title="t('crm.sales.opportunities.actions.manageTitle')" />
          <VCardText>
            <VAlert color="info" variant="tonal">{{ t('crm.sales.opportunities.detail.help') }}</VAlert>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <OpportunityForm
      v-model="isFormVisible"
      :lead-options="leadOptions"
      :assignee-options="assigneeOptions"
      :stage-options="stageOptions"
      :submit-error-message="formErrorMessage"
      :is-submitting="isSubmitting"
      @submit="submitOpportunity"
    />

    <VDialog v-model="isDeleteDialogVisible" max-width="460">
      <VCard>
        <VCardItem :title="t('crm.sales.opportunities.dialogs.delete.title')" />
        <VCardText class="d-flex flex-column gap-3">
          <div class="text-body-1">{{ t('crm.sales.opportunities.dialogs.delete.message', { name: selectedOpportunity?.name || '-' }) }}</div>
          <div class="text-body-2 text-medium-emphasis">{{ t('crm.sales.opportunities.dialogs.delete.description') }}</div>
          <VAlert v-if="deleteErrorMessage" data-testid="opportunity-delete-error" color="error" variant="tonal">
            {{ deleteErrorMessage }}
          </VAlert>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 pt-0">
          <VBtn color="secondary" variant="tonal" @click="closeDeleteDialog">{{ t('crm.sales.opportunities.dialogs.delete.actions.cancel') }}</VBtn>
          <VBtn color="error" data-testid="opportunity-delete-confirm" :loading="isDeleting" @click="deleteOpportunity">{{ t('crm.sales.opportunities.dialogs.delete.actions.confirm') }}</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">{{ snackbar.text }}</VSnackbar>
  </section>
</template>

<style scoped lang="scss">
.kanban-column {
  background:
    linear-gradient(180deg, rgba(var(--v-theme-surface-variant), 0.45), rgba(var(--v-theme-surface), 1));
}

.kanban-dropzone {
  min-block-size: 100%;
}
</style>