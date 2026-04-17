<script setup lang="ts">
import type { ServiceTicketAlertState, SlaDashboardResponse, SlaDefinitionRecord, SlaMutationResponse } from '@/types/service-management'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'

const { t, locale } = useI18n()
const snackbar = ref({
  visible: false,
  color: 'success',
  text: '',
})
const slaData = ref<SlaDashboardResponse | null>(null)
const isLoading = ref(false)
const isDialogVisible = ref(false)
const isSubmitting = ref(false)
const editingDefinitionId = ref<number | null>(null)
const definitionForm = ref({
  name: '',
  description: '',
  category: null as string | null,
  priority: 'medium',
  firstResponseMinutes: 60,
  resolutionMinutes: 480,
  warningBeforeMinutes: 60,
  autoEscalate: false,
  escalationPriority: null as string | null,
  isActive: true,
})

const summary = computed(() => slaData.value?.summary ?? {
  activeDefinitions: 0,
  dueSoonTickets: 0,
  overdueTickets: 0,
  resolvedTickets: 0,
})
const alerts = computed(() => slaData.value?.alerts ?? [])
const definitions = computed<SlaDefinitionRecord[]>(() => slaData.value?.definitions ?? [])
const placeholder = computed(() => slaData.value?.placeholder ?? {})

const localeFormatter = computed(() => new Intl.DateTimeFormat(locale.value === 'id' ? 'id-ID' : 'en-GB', {
  dateStyle: 'medium',
  timeStyle: 'short',
}))

const categoryOptions = computed(() => [
  { title: t('crm.slaManagement.form.categoryOptional'), value: null },
  { title: t('crm.tickets.categories.general'), value: 'general' },
  { title: t('crm.tickets.categories.technical'), value: 'technical' },
  { title: t('crm.tickets.categories.billing'), value: 'billing' },
  { title: t('crm.tickets.categories.priorityFollowUp'), value: 'priority-follow-up' },
])

const priorityOptions = computed(() => [
  { title: t('crm.tickets.priorities.low'), value: 'low' },
  { title: t('crm.tickets.priorities.medium'), value: 'medium' },
  { title: t('crm.tickets.priorities.high'), value: 'high' },
  { title: t('crm.tickets.priorities.critical'), value: 'critical' },
])

const summaryCards = computed(() => [
  { title: t('crm.slaManagement.summary.activeDefinitions'), value: summary.value.activeDefinitions, color: 'primary', icon: 'tabler-adjustments-horizontal' },
  { title: t('crm.slaManagement.summary.dueSoonTickets'), value: summary.value.dueSoonTickets, color: 'warning', icon: 'tabler-alarm' },
  { title: t('crm.slaManagement.summary.overdueTickets'), value: summary.value.overdueTickets, color: 'error', icon: 'tabler-alert-octagon' },
  { title: t('crm.slaManagement.summary.resolvedTickets'), value: summary.value.resolvedTickets, color: 'success', icon: 'tabler-circle-check' },
])

const dialogTitle = computed(() => editingDefinitionId.value ? t('crm.slaManagement.form.editTitle') : t('crm.slaManagement.form.createTitle'))

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = {
    visible: true,
    color,
    text,
  }
}

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return localeFormatter.value.format(new Date(value))
}

const resolveAlertLabel = (state: ServiceTicketAlertState) => {
  if (state === 'due_soon')
    return t('crm.tickets.alertStates.dueSoon')
  if (state === 'overdue')
    return t('crm.tickets.alertStates.overdue')
  if (state === 'resolved')
    return t('crm.tickets.alertStates.resolved')

  return t('crm.tickets.alertStates.onTrack')
}

const resolveAlertColor = (state: ServiceTicketAlertState) => {
  if (state === 'overdue')
    return 'error'
  if (state === 'due_soon')
    return 'warning'
  if (state === 'resolved')
    return 'success'

  return 'info'
}

const resetDefinitionForm = () => {
  editingDefinitionId.value = null
  definitionForm.value = {
    name: '',
    description: '',
    category: null,
    priority: 'medium',
    firstResponseMinutes: 60,
    resolutionMinutes: 480,
    warningBeforeMinutes: 60,
    autoEscalate: false,
    escalationPriority: null,
    isActive: true,
  }
}

const openCreateDialog = () => {
  resetDefinitionForm()
  isDialogVisible.value = true
}

const openEditDialog = (definition: SlaDefinitionRecord) => {
  editingDefinitionId.value = definition.id
  definitionForm.value = {
    name: definition.name,
    description: definition.description || '',
    category: definition.category,
    priority: definition.priority,
    firstResponseMinutes: definition.firstResponseMinutes,
    resolutionMinutes: definition.resolutionMinutes,
    warningBeforeMinutes: definition.warningBeforeMinutes,
    autoEscalate: definition.autoEscalate,
    escalationPriority: definition.escalationPriority,
    isActive: definition.isActive,
  }
  isDialogVisible.value = true
}

const refreshSla = async ({ silent = false }: { silent?: boolean } = {}) => {
  if (!silent)
    isLoading.value = true

  try {
    const { data } = await axiosApi.get<SlaDashboardResponse>('/sla')

    slaData.value = data
  }
  catch (error) {
    if (!silent)
      showSnackbar(resolveApiErrorMessage(error, t('crm.slaManagement.snackbar.saveError')), 'error')
  }
  finally {
    if (!silent)
      isLoading.value = false
  }
}

const saveDefinition = async () => {
  isSubmitting.value = true

  try {
    if (editingDefinitionId.value) {
      await axiosApi.put<SlaMutationResponse>(`/sla/${editingDefinitionId.value}`, definitionForm.value)
    }
    else {
      await axiosApi.post<SlaMutationResponse>('/sla', definitionForm.value)
    }

    isDialogVisible.value = false
    await refreshSla()
    showSnackbar(t('crm.slaManagement.snackbar.saveSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.slaManagement.snackbar.saveError')), 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

const { pause: pauseSlaPolling, resume: resumeSlaPolling } = useIntervalFn(() => {
  void refreshSla({ silent: true })
}, 30000, { immediate: false })

onMounted(async () => {
  await refreshSla()
  resumeSlaPolling()
})

onBeforeUnmount(() => {
  pauseSlaPolling()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between align-lg-center gap-4">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.serviceManagement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.slaManagement.dashboard.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.slaManagement.dashboard.subtitle') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-3">
          <VBtn color="secondary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoading" @click="refreshSla()">
            {{ t('crm.slaManagement.actions.refresh') }}
          </VBtn>
          <VBtn color="primary" prepend-icon="tabler-plus" @click="openCreateDialog">
            {{ t('crm.slaManagement.actions.createDefinition') }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VRow>
      <VCol v-for="card in summaryCards" :key="card.title" cols="12" sm="6" lg="3">
        <VCard>
          <VCardText class="d-flex justify-space-between gap-3 align-start">
            <div>
              <div class="text-body-1 text-high-emphasis mb-1">{{ card.title }}</div>
              <h4 class="text-h4 mb-0">{{ card.value }}</h4>
            </div>
            <VAvatar :color="card.color" variant="tonal" size="46">
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VAlert color="info" variant="tonal">
      {{ placeholder.alert || t('crm.slaManagement.placeholders.alert') }}
    </VAlert>

    <VCard>
      <VCardItem :title="t('crm.slaManagement.alerts.title')" />
      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th>{{ t('crm.slaManagement.alerts.table.code') }}</th>
            <th>{{ t('crm.slaManagement.alerts.table.subject') }}</th>
            <th>{{ t('crm.slaManagement.alerts.table.customer') }}</th>
            <th>{{ t('crm.slaManagement.alerts.table.priority') }}</th>
            <th>{{ t('crm.slaManagement.alerts.table.state') }}</th>
            <th>{{ t('crm.slaManagement.alerts.table.deadline') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="alert in alerts" :key="alert.ticketId">
            <td>{{ alert.ticketCode }}</td>
            <td>{{ alert.subject }}</td>
            <td>{{ alert.customer?.name || '-' }}</td>
            <td>{{ alert.priority }}</td>
            <td>
              <VChip size="small" :color="resolveAlertColor(alert.alertState)" variant="tonal">
                {{ resolveAlertLabel(alert.alertState) }}
              </VChip>
            </td>
            <td>{{ formatDate(alert.resolutionDueAt) }}</td>
          </tr>
          <tr v-if="!alerts.length">
            <td colspan="6" class="text-center text-medium-emphasis py-6">{{ t('crm.slaManagement.alerts.empty') }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <VCard>
      <VCardItem :title="t('crm.slaManagement.definitions.title')" />
      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th>{{ t('crm.slaManagement.definitions.table.name') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.category') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.priority') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.firstResponse') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.resolution') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.warning') }}</th>
            <th>{{ t('crm.slaManagement.definitions.table.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="definition in definitions" :key="definition.id">
            <td>
              <div class="font-weight-medium text-high-emphasis">{{ definition.name }}</div>
              <div class="text-sm text-medium-emphasis">{{ definition.description || '-' }}</div>
            </td>
            <td>{{ definition.category || '-' }}</td>
            <td>{{ definition.priority }}</td>
            <td>{{ definition.firstResponseMinutes }}</td>
            <td>{{ definition.resolutionMinutes }}</td>
            <td>{{ definition.warningBeforeMinutes }}</td>
            <td>
              <VBtn size="small" variant="text" color="primary" @click="openEditDialog(definition)">
                {{ t('crm.slaManagement.actions.editDefinition') }}
              </VBtn>
            </td>
          </tr>
          <tr v-if="!definitions.length">
            <td colspan="7" class="text-center text-medium-emphasis py-6">{{ t('crm.slaManagement.definitions.empty') }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <VDialog v-model="isDialogVisible" max-width="720">
      <VCard>
        <VCardItem :title="dialogTitle" />
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField v-model="definitionForm.name" :label="t('crm.slaManagement.form.fields.name')" />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect v-model="definitionForm.category" :label="t('crm.slaManagement.form.fields.category')" :items="categoryOptions" />
            </VCol>
            <VCol cols="12">
              <AppTextarea v-model="definitionForm.description" :label="t('crm.slaManagement.form.fields.description')" rows="3" />
            </VCol>
            <VCol cols="12" md="4">
              <AppSelect v-model="definitionForm.priority" :label="t('crm.slaManagement.form.fields.priority')" :items="priorityOptions" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextField v-model="definitionForm.firstResponseMinutes" type="number" :label="t('crm.slaManagement.form.fields.firstResponseMinutes')" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextField v-model="definitionForm.resolutionMinutes" type="number" :label="t('crm.slaManagement.form.fields.resolutionMinutes')" />
            </VCol>
            <VCol cols="12" md="4">
              <AppTextField v-model="definitionForm.warningBeforeMinutes" type="number" :label="t('crm.slaManagement.form.fields.warningBeforeMinutes')" />
            </VCol>
            <VCol cols="12" md="4">
              <AppSelect v-model="definitionForm.escalationPriority" :label="t('crm.slaManagement.form.fields.escalationPriority')" :items="[{ title: t('crm.slaManagement.form.noEscalationPriority'), value: null }, ...priorityOptions]" />
            </VCol>
            <VCol cols="12" md="2">
              <VSwitch v-model="definitionForm.autoEscalate" :label="t('crm.slaManagement.form.fields.autoEscalate')" />
            </VCol>
            <VCol cols="12" md="2">
              <VSwitch v-model="definitionForm.isActive" :label="t('crm.slaManagement.form.fields.isActive')" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 pt-0">
          <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false">
            {{ t('crm.slaManagement.actions.cancel') }}
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveDefinition">
            {{ t('crm.slaManagement.actions.saveDefinition') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">
      {{ snackbar.text }}
    </VSnackbar>
  </section>
</template>