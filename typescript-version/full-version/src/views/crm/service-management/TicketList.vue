<script setup lang="ts">
import type { ServiceAgentOption, ServiceTicketAlertState, ServiceTicketCategory, ServiceTicketListResponse, ServiceTicketMutationResponse, ServiceTicketOptionsResponse, ServiceTicketPriority, ServiceTicketRecord, ServiceTicketStatus } from '@/types/service-management'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'

const { t, locale } = useI18n()
const router = useRouter()
const route = useRoute()
const ability = useAbility()

const search = ref('')
const selectedStatus = ref<ServiceTicketStatus | ''>('')
const selectedCategory = ref<ServiceTicketCategory | ''>('')
const selectedPriority = ref<ServiceTicketPriority | ''>('')
const selectedAlertState = ref<ServiceTicketAlertState | ''>('')
const selectedTicketId = ref<number | null>(null)
const selectedAssigneeId = ref<number | null>(null)
const selectedStatusDraft = ref<ServiceTicketStatus>('open')
const snackbar = ref({
  visible: false,
  color: 'success',
  text: '',
})
const ticketListData = ref<ServiceTicketListResponse | null>(null)
const optionData = ref<ServiceTicketOptionsResponse | null>(null)
const isLoadingTickets = ref(false)
const isLoadingOptions = ref(false)
const isAssigning = ref(false)
const isUpdatingStatus = ref(false)
const isEscalating = ref(false)
const page = ref(1)
const itemsPerPage = ref(10)

const canCreateTickets = computed(() => ability.can('create', 'CrmTickets'))
const canUpdateTickets = computed(() => ability.can('update', 'CrmTickets'))

const tickets = computed<ServiceTicketRecord[]>(() => ticketListData.value?.data ?? [])
const agents = computed<ServiceAgentOption[]>(() => optionData.value?.agents ?? [])
const selectedTicket = computed(() => tickets.value.find(ticket => ticket.id === selectedTicketId.value) ?? null)
const headers = computed(() => [
  { title: t('crm.tickets.table.code'), key: 'code', sortable: false },
  { title: t('crm.tickets.table.subject'), key: 'subject', sortable: false },
  { title: t('crm.tickets.table.customer'), key: 'customer', sortable: false },
  { title: t('crm.tickets.table.category'), key: 'category', sortable: false },
  { title: t('crm.tickets.table.priority'), key: 'priority', sortable: false },
  { title: t('crm.tickets.table.status'), key: 'status', sortable: false },
  { title: t('crm.tickets.table.sla'), key: 'resolutionDueAt', sortable: false },
  { title: t('crm.tickets.table.alert'), key: 'alertState', sortable: false },
])

const localeFormatter = computed(() => new Intl.DateTimeFormat(locale.value === 'id' ? 'id-ID' : 'en-GB', {
  dateStyle: 'medium',
  timeStyle: 'short',
}))

const searchPlaceholder = computed(() => t('crm.tickets.list.searchPlaceholder'))

const statusOptions = computed(() => [
  { title: t('crm.tickets.list.filters.allStatuses'), value: '' },
  { title: t('crm.tickets.statuses.open'), value: 'open' },
  { title: t('crm.tickets.statuses.inProgress'), value: 'in_progress' },
  { title: t('crm.tickets.statuses.resolved'), value: 'resolved' },
])

const categoryOptions = computed(() => [
  { title: t('crm.tickets.list.filters.allCategories'), value: '' },
  { title: t('crm.tickets.categories.general'), value: 'general' },
  { title: t('crm.tickets.categories.technical'), value: 'technical' },
  { title: t('crm.tickets.categories.billing'), value: 'billing' },
  { title: t('crm.tickets.categories.priorityFollowUp'), value: 'priority-follow-up' },
])

const priorityOptions = computed(() => [
  { title: t('crm.tickets.list.filters.allPriorities'), value: '' },
  { title: t('crm.tickets.priorities.low'), value: 'low' },
  { title: t('crm.tickets.priorities.medium'), value: 'medium' },
  { title: t('crm.tickets.priorities.high'), value: 'high' },
  { title: t('crm.tickets.priorities.critical'), value: 'critical' },
])

const alertStateOptions = computed(() => [
  { title: t('crm.tickets.list.filters.allAlerts'), value: '' },
  { title: t('crm.tickets.alertStates.onTrack'), value: 'on_track' },
  { title: t('crm.tickets.alertStates.dueSoon'), value: 'due_soon' },
  { title: t('crm.tickets.alertStates.overdue'), value: 'overdue' },
  { title: t('crm.tickets.alertStates.resolved'), value: 'resolved' },
])

const assigneeOptions = computed(() => [
  { title: t('crm.tickets.assignee.unassigned'), value: null },
  ...agents.value.map(agent => ({ title: agent.fullName, value: agent.id })),
])

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = {
    visible: true,
    color,
    text,
  }
}

const loadOptions = async () => {
  isLoadingOptions.value = true

  try {
    const { data } = await axiosApi.get<ServiceTicketOptionsResponse>('/tickets/options')

    optionData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.tickets.snackbar.assignError')), 'error')
  }
  finally {
    isLoadingOptions.value = false
  }
}

const refreshTickets = async () => {
  isLoadingTickets.value = true

  try {
    const { data } = await axiosApi.get<ServiceTicketListResponse>('/tickets', {
      params: {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        category: selectedCategory.value || undefined,
        priority: selectedPriority.value || undefined,
        alertState: selectedAlertState.value || undefined,
      },
    })

    ticketListData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.tickets.snackbar.statusError')), 'error')
  }
  finally {
    isLoadingTickets.value = false
  }
}

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return localeFormatter.value.format(new Date(value))
}

const resolveCategoryLabel = (category: ServiceTicketCategory) => {
  if (category === 'technical')
    return t('crm.tickets.categories.technical')
  if (category === 'billing')
    return t('crm.tickets.categories.billing')
  if (category === 'priority-follow-up')
    return t('crm.tickets.categories.priorityFollowUp')

  return t('crm.tickets.categories.general')
}

const resolvePriorityLabel = (priority: ServiceTicketPriority) => {
  if (priority === 'low')
    return t('crm.tickets.priorities.low')
  if (priority === 'high')
    return t('crm.tickets.priorities.high')
  if (priority === 'critical')
    return t('crm.tickets.priorities.critical')

  return t('crm.tickets.priorities.medium')
}

const resolveStatusLabel = (status: ServiceTicketStatus) => {
  if (status === 'in_progress')
    return t('crm.tickets.statuses.inProgress')
  if (status === 'resolved')
    return t('crm.tickets.statuses.resolved')

  return t('crm.tickets.statuses.open')
}

const resolveAlertStateLabel = (state: ServiceTicketAlertState) => {
  if (state === 'due_soon')
    return t('crm.tickets.alertStates.dueSoon')
  if (state === 'overdue')
    return t('crm.tickets.alertStates.overdue')
  if (state === 'resolved')
    return t('crm.tickets.alertStates.resolved')

  return t('crm.tickets.alertStates.onTrack')
}

const resolvePriorityColor = (priority: ServiceTicketPriority) => {
  if (priority === 'critical')
    return 'error'
  if (priority === 'high')
    return 'warning'
  if (priority === 'medium')
    return 'primary'

  return 'secondary'
}

const resolveStatusColor = (status: ServiceTicketStatus) => {
  if (status === 'resolved')
    return 'success'
  if (status === 'in_progress')
    return 'warning'

  return 'secondary'
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

const syncSelectedTicket = () => {
  if (!tickets.value.length) {
    selectedTicketId.value = null
    return
  }

  if (!selectedTicket.value)
    selectedTicketId.value = tickets.value[0].id
}

const hydrateSelectedDrafts = () => {
  if (!selectedTicket.value)
    return

  selectedAssigneeId.value = selectedTicket.value.assignedUser?.id ?? null
  selectedStatusDraft.value = selectedTicket.value.status
}

const openCreateForm = () => {
  router.push('/service-management/ticket-form')
}

const openSlaDashboard = () => {
  router.push('/service-management/sla-dashboard')
}

const selectTicket = (ticketId: number) => {
  selectedTicketId.value = ticketId
}

const assignTicket = async () => {
  if (!selectedTicket.value || !canUpdateTickets.value)
    return

  isAssigning.value = true

  try {
    await axiosApi.patch<ServiceTicketMutationResponse>(`/tickets/${selectedTicket.value.id}/assign`, {
      assignedUserId: selectedAssigneeId.value,
    })

    await refreshTickets()
    showSnackbar(t('crm.tickets.snackbar.assignSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.tickets.snackbar.assignError')), 'error')
  }
  finally {
    isAssigning.value = false
  }
}

const updateTicketStatus = async () => {
  if (!selectedTicket.value || !canUpdateTickets.value)
    return

  isUpdatingStatus.value = true

  try {
    await axiosApi.patch<ServiceTicketMutationResponse>(`/tickets/${selectedTicket.value.id}/status`, {
      status: selectedStatusDraft.value,
    })

    await refreshTickets()
    showSnackbar(t('crm.tickets.snackbar.statusSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.tickets.snackbar.statusError')), 'error')
  }
  finally {
    isUpdatingStatus.value = false
  }
}

const escalateTicket = async () => {
  if (!selectedTicket.value || !canUpdateTickets.value)
    return

  isEscalating.value = true

  try {
    await axiosApi.patch<ServiceTicketMutationResponse>(`/tickets/${selectedTicket.value.id}/escalate`, {
      reason: t('crm.tickets.placeholders.escalationReason'),
    })

    await refreshTickets()
    showSnackbar(t('crm.tickets.snackbar.escalateSuccess'))
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.tickets.snackbar.escalateError')), 'error')
  }
  finally {
    isEscalating.value = false
  }
}

onMounted(async () => {
  const ticketIdFromQuery = Number(route.query.ticket ?? 0)

  if (ticketIdFromQuery)
    selectedTicketId.value = ticketIdFromQuery

  await Promise.all([loadOptions(), refreshTickets()])
})

watch([search, selectedStatus, selectedCategory, selectedPriority, selectedAlertState], () => {
  page.value = 1
  void refreshTickets()
})

watch(tickets, () => {
  syncSelectedTicket()
  hydrateSelectedDrafts()
}, { immediate: true })

watch(selectedTicketId, () => {
  hydrateSelectedDrafts()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between gap-4 align-lg-center">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.serviceManagement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.tickets.list.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.tickets.list.subtitle') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-3">
          <VBtn color="secondary" variant="tonal" prepend-icon="tabler-clock-hour-4" @click="openSlaDashboard">
            {{ t('crm.tickets.list.openSlaButton') }}
          </VBtn>
          <VBtn color="primary" prepend-icon="tabler-plus" :disabled="!canCreateTickets" @click="openCreateForm">
            {{ t('crm.tickets.list.openFormButton') }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" md="4">
        <AppTextField v-model="search" prepend-inner-icon="tabler-search" :label="t('crm.tickets.list.searchLabel')" :placeholder="searchPlaceholder" />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect v-model="selectedStatus" :label="t('crm.tickets.list.filters.status')" :items="statusOptions" />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect v-model="selectedCategory" :label="t('crm.tickets.list.filters.category')" :items="categoryOptions" />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect v-model="selectedPriority" :label="t('crm.tickets.list.filters.priority')" :items="priorityOptions" />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect v-model="selectedAlertState" :label="t('crm.tickets.list.filters.alert')" :items="alertStateOptions" />
      </VCol>
    </VRow>

    <VCard>
      <VCardItem :title="t('crm.tickets.list.tableTitle')">
        <template #append>
          <VBtn color="primary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoadingTickets" @click="refreshTickets()">
            {{ t('crm.tickets.actions.refresh') }}
          </VBtn>
        </template>
      </VCardItem>

      <VDataTable
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="tickets"
        item-value="id"
        :loading="isLoadingTickets || isLoadingOptions"
        class="text-no-wrap"
      >
        <template #item.code="{ item }">
          <button type="button" class="ticket-link" @click="selectTicket(item.id)">
            {{ item.code }}
          </button>
        </template>

        <template #item.subject="{ item }">
          <button type="button" class="ticket-subject" @click="selectTicket(item.id)">
            <span class="font-weight-medium text-high-emphasis">{{ item.subject }}</span>
            <span class="text-sm text-medium-emphasis">{{ item.description }}</span>
          </button>
        </template>

        <template #item.customer="{ item }">
          {{ item.customer?.name || '-' }}
        </template>

        <template #item.category="{ item }">
          {{ resolveCategoryLabel(item.category) }}
        </template>

        <template #item.priority="{ item }">
          <VChip size="small" :color="resolvePriorityColor(item.priority)" variant="tonal">
            {{ resolvePriorityLabel(item.priority) }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip size="small" :color="resolveStatusColor(item.status)" variant="tonal">
            {{ resolveStatusLabel(item.status) }}
          </VChip>
        </template>

        <template #item.resolutionDueAt="{ item }">
          {{ formatDate(item.resolutionDueAt) }}
        </template>

        <template #item.alertState="{ item }">
          <VChip size="small" :color="resolveAlertColor(item.alertState)" variant="tonal">
            {{ resolveAlertStateLabel(item.alertState) }}
          </VChip>
        </template>

        <template #no-data>
          <div class="py-6 text-center text-medium-emphasis">
            {{ t('crm.tickets.list.empty') }}
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="tickets.length" />
        </template>
      </VDataTable>
    </VCard>

    <VRow v-if="selectedTicket">
      <VCol cols="12" lg="8">
        <VCard>
          <VCardItem :title="t('crm.tickets.detail.title', { code: selectedTicket.code })" />
          <VCardText class="d-flex flex-column gap-4">
            <div>
              <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.descriptionLabel') }}</div>
              <div class="text-body-1">{{ selectedTicket.description }}</div>
            </div>

            <VRow>
              <VCol cols="12" md="6">
                <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.customerLabel') }}</div>
                <div class="font-weight-medium text-high-emphasis">{{ selectedTicket.customer?.name || '-' }}</div>
                <div class="text-sm text-medium-emphasis">{{ selectedTicket.customer?.email || '-' }}</div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.slaDefinitionLabel') }}</div>
                <div class="font-weight-medium text-high-emphasis">{{ selectedTicket.slaDefinition?.name || '-' }}</div>
                <div class="text-sm text-medium-emphasis">{{ selectedTicket.slaDefinition?.description || t('crm.tickets.detail.slaDefinitionEmpty') }}</div>
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.firstResponseDueLabel') }}</div>
                <div>{{ formatDate(selectedTicket.firstResponseDueAt) }}</div>
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.resolutionDueLabel') }}</div>
                <div>{{ formatDate(selectedTicket.resolutionDueAt) }}</div>
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-sm text-disabled mb-1">{{ t('crm.tickets.detail.escalationLevelLabel') }}</div>
                <div>{{ selectedTicket.escalationLevel }}</div>
              </VCol>
            </VRow>

            <VAlert color="info" variant="tonal">
              {{ t('crm.tickets.placeholders.alert') }}
            </VAlert>

            <div>
              <div class="text-sm text-disabled mb-3">{{ t('crm.tickets.detail.activityTitle') }}</div>
              <div class="d-flex flex-column gap-3">
                <div v-for="activity in selectedTicket.activities" :key="activity.id" class="rounded border pa-4">
                  <div class="d-flex justify-space-between gap-3 flex-wrap">
                    <div class="font-weight-medium text-high-emphasis">{{ activity.title }}</div>
                    <div class="text-sm text-medium-emphasis">{{ formatDate(activity.createdAt) }}</div>
                  </div>
                  <div v-if="activity.description" class="text-body-2 text-medium-emphasis mt-2">{{ activity.description }}</div>
                  <div class="text-xs text-disabled mt-2">{{ activity.user?.fullName || t('crm.tickets.activity.system') }}</div>
                </div>
                <div v-if="!selectedTicket.activities.length" class="text-medium-emphasis">{{ t('crm.tickets.activity.empty') }}</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" lg="4">
        <VCard>
          <VCardItem :title="t('crm.tickets.detail.actionsTitle')" />
          <VCardText class="d-flex flex-column gap-4">
            <AppSelect v-model="selectedAssigneeId" :label="t('crm.tickets.assignee.label')" :items="assigneeOptions" :disabled="!canUpdateTickets || isLoadingOptions" />
            <VBtn color="primary" :loading="isAssigning" :disabled="!canUpdateTickets" @click="assignTicket">
              {{ t('crm.tickets.actions.assign') }}
            </VBtn>

            <AppSelect v-model="selectedStatusDraft" :label="t('crm.tickets.detail.statusLabel')" :items="statusOptions.filter(option => option.value)" :disabled="!canUpdateTickets" />
            <VBtn color="secondary" variant="tonal" :loading="isUpdatingStatus" :disabled="!canUpdateTickets" @click="updateTicketStatus">
              {{ t('crm.tickets.actions.updateStatus') }}
            </VBtn>

            <VBtn color="error" variant="tonal" :loading="isEscalating" :disabled="!canUpdateTickets" @click="escalateTicket">
              {{ t('crm.tickets.actions.escalate') }}
            </VBtn>

            <VAlert color="warning" variant="tonal">
              {{ t('crm.tickets.placeholders.escalation') }}
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">
      {{ snackbar.text }}
    </VSnackbar>
  </section>
</template>

<style scoped lang="scss">
.ticket-link,
.ticket-subject {
  display: flex;
  flex-direction: column;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  inline-size: 100%;
  text-align: start;
}

.ticket-link {
  color: rgb(var(--v-theme-primary));
  font-weight: 500;
}
</style>