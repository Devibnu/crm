<script setup lang="ts">
import type { LeadIndexResponse, LeadMutationResponse, SalesLeadRecord, SalesLeadStatus } from '@/types/sales-enablement'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'
import axios from 'axios'

interface Props {
  embedded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  embedded: false,
})

const { t } = useI18n()
const router = useRouter()

const selectedStatus = ref<SalesLeadStatus | ''>('')
const leadData = ref<LeadIndexResponse | null>(null)
const isLoading = ref(false)
const isAssignDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const isLoadingSalesUsers = ref(false)
const isAssigning = ref(false)
const isDeleting = ref(false)
const salesUsers = ref<Array<{ id: number, fullName: string, email: string, role: string }>>([])
const selectedLead = ref<SalesLeadRecord | null>(null)
const selectedAssigneeId = ref<number | null>(null)
const assignValidationErrors = ref<Record<string, string[]>>({})
const assignLoadErrorMessage = ref('')
const deleteErrorMessage = ref('')
const page = ref(1)
const itemsPerPage = ref(10)
const snackbar = ref({ visible: false, color: 'success', text: '' })

const headers = computed(() => [
  { title: t('crm.sales.leads.table.company'), key: 'company', sortable: false },
  { title: t('crm.sales.leads.table.fullName'), key: 'fullName', sortable: false },
  { title: t('crm.sales.leads.table.status'), key: 'status', sortable: false },
  { title: t('crm.sales.leads.table.assignedUserId'), key: 'assignedUserId', sortable: false },
  { title: t('crm.sales.leads.table.actions'), key: 'actions', sortable: false },
])

const leads = computed(() => leadData.value?.data ?? [])
const summary = computed(() => leadData.value?.summary ?? {
  total: 0,
  new: 0,
  qualified: 0,
  disqualified: 0,
})

const statusOptions = computed(() => [
  { title: t('crm.sales.shared.statusFilters.all'), value: '' },
  { title: t('crm.sales.shared.leadStatuses.new'), value: 'new' },
  { title: t('crm.sales.shared.leadStatuses.qualified'), value: 'qualified' },
  { title: t('crm.sales.shared.leadStatuses.disqualified'), value: 'disqualified' },
])

const assigneeOptions = computed(() => [
  { title: t('crm.sales.shared.unassigned'), value: null },
  ...salesUsers.value.map(user => ({ title: `${user.fullName} • ${user.role}`, value: user.id })),
])

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = { visible: true, color, text }
}

const resolveLeadStatusLabel = (status: SalesLeadStatus) => {
  if (status === 'qualified')
    return t('crm.sales.shared.leadStatuses.qualified')
  if (status === 'disqualified')
    return t('crm.sales.shared.leadStatuses.disqualified')

  return t('crm.sales.shared.leadStatuses.new')
}

const resolveLeadStatusColor = (status: SalesLeadStatus) => {
  if (status === 'qualified')
    return 'success'
  if (status === 'disqualified')
    return 'error'

  return 'primary'
}

const fetchLeads = async () => {
  isLoading.value = true

  try {
    const { data } = await axiosApi.get<LeadIndexResponse>('/leads', {
      params: {
        status: selectedStatus.value || undefined,
      },
    })

    leadData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.snackbar.loadError')), 'error')
  }
  finally {
    isLoading.value = false
  }
}

const openCreateLeadForm = () => {
  router.push({ path: '/sales-enablement/lead-management', query: { tab: 'capture' } })
}

const fetchSalesUsers = async () => {
  isLoadingSalesUsers.value = true
  assignLoadErrorMessage.value = ''

  try {
    const { data } = await axiosApi.get<{ data: Array<{ id: number, fullName: string, email: string, role: string }> }>('/users', {
      params: {
        role: 'sales',
      },
    })

    salesUsers.value = data.data
  }
  catch (error) {
    assignLoadErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.leads.snackbar.salesUsersError'))
    showSnackbar(assignLoadErrorMessage.value, 'error')
  }
  finally {
    isLoadingSalesUsers.value = false
  }
}

const openAssignDialog = async (lead: SalesLeadRecord) => {
  selectedLead.value = lead
  selectedAssigneeId.value = lead.assignedUser?.id ?? null
  assignValidationErrors.value = {}
  assignLoadErrorMessage.value = ''
  isAssignDialogVisible.value = true

  await fetchSalesUsers()
}

const closeAssignDialog = () => {
  isAssignDialogVisible.value = false
  selectedLead.value = null
  selectedAssigneeId.value = null
  assignValidationErrors.value = {}
  assignLoadErrorMessage.value = ''
}

const submitAssign = async () => {
  if (!selectedLead.value)
    return

  isAssigning.value = true
  assignValidationErrors.value = {}

  try {
    await axiosApi.patch<LeadMutationResponse>(`/leads/${selectedLead.value.id}/assign`, {
      assignedUserId: selectedAssigneeId.value,
    })

    await fetchLeads()
    closeAssignDialog()
    showSnackbar(t('crm.sales.leads.snackbar.assignSuccess'))
  }
  catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422)
      assignValidationErrors.value = (error.response.data?.errors ?? {}) as Record<string, string[]>

    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.leads.snackbar.assignError')), 'error')
  }
  finally {
    isAssigning.value = false
  }
}

const openDeleteDialog = (lead: SalesLeadRecord) => {
  selectedLead.value = lead
  deleteErrorMessage.value = ''
  isDeleteDialogVisible.value = true
}

const closeDeleteDialog = () => {
  isDeleteDialogVisible.value = false
  selectedLead.value = null
  deleteErrorMessage.value = ''
}

const confirmDelete = async () => {
  if (!selectedLead.value)
    return

  isDeleting.value = true

  try {
    await axiosApi.delete(`/leads/${selectedLead.value.id}`)
    await fetchLeads()
    closeDeleteDialog()
    showSnackbar(t('crm.sales.leads.snackbar.deleteSuccess'))
  }
  catch (error) {
    deleteErrorMessage.value = resolveApiErrorMessage(error, t('crm.sales.leads.snackbar.deleteError'))
    showSnackbar(deleteErrorMessage.value, 'error')
  }
  finally {
    isDeleting.value = false
  }
}

onMounted(async () => {
  await fetchLeads()
})

watch(selectedStatus, () => {
  page.value = 1
  void fetchLeads()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard v-if="!props.embedded">
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between gap-4 align-lg-center">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.sales.leadManagement.tabs.qualification') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.sales.leads.subtitle') }}</p>
        </div>

        <VBtn color="primary" prepend-icon="tabler-plus" @click="openCreateLeadForm">
          {{ t('crm.sales.leads.actions.create') }}
        </VBtn>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" sm="6" lg="3">
        <VCard>
          <VCardText>
            <div class="text-body-1 text-high-emphasis mb-1">{{ t('crm.sales.leads.summary.total') }}</div>
            <h4 class="text-h4 mb-0">{{ summary.total }}</h4>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard>
          <VCardText>
            <div class="text-body-1 text-high-emphasis mb-1">{{ t('crm.sales.leads.summary.new') }}</div>
            <h4 class="text-h4 mb-0">{{ summary.new }}</h4>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard>
          <VCardText>
            <div class="text-body-1 text-high-emphasis mb-1">{{ t('crm.sales.leads.summary.qualified') }}</div>
            <h4 class="text-h4 mb-0">{{ summary.qualified }}</h4>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" lg="3">
        <VCard>
          <VCardText>
            <div class="text-body-1 text-high-emphasis mb-1">{{ t('crm.sales.leads.summary.disqualified') }}</div>
            <h4 class="text-h4 mb-0">{{ summary.disqualified }}</h4>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="4">
        <AppSelect v-model="selectedStatus" data-testid="lead-status-filter" :label="t('crm.sales.leads.filters.status')" :items="statusOptions" />
      </VCol>
    </VRow>

    <VCard>
      <VCardItem :title="t('crm.sales.leads.tableTitle')">
        <template #append>
          <VBtn color="secondary" variant="tonal" prepend-icon="tabler-refresh" :loading="isLoading" @click="fetchLeads()">
            {{ t('crm.sales.shared.actions.refresh') }}
          </VBtn>
        </template>
      </VCardItem>

      <VDataTable
        v-model:page="page"
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="leads"
        item-value="id"
        :loading="isLoading"
        class="text-no-wrap"
      >
        <template #item.fullName="{ item }">
          <span class="font-weight-medium text-high-emphasis">{{ item.fullName }}</span>
        </template>

        <template #item.company="{ item }">
          {{ item.company || '-' }}
        </template>

        <template #item.status="{ item }">
          <VChip size="small" :color="resolveLeadStatusColor(item.status)" variant="tonal">
            {{ resolveLeadStatusLabel(item.status) }}
          </VChip>
        </template>

        <template #item.assignedUserId="{ item }">
          {{ item.assignedUser?.fullName || t('crm.sales.shared.unassigned') }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex flex-wrap gap-2 align-center">
            <VBtn size="small" variant="tonal" color="primary" :data-testid="`lead-assign-button-${item.id}`" @click="openAssignDialog(item)">
              {{ t('crm.sales.leads.actions.assign') }}
            </VBtn>
            <VBtn size="small" variant="tonal" color="error" :data-testid="`lead-delete-button-${item.id}`" @click="openDeleteDialog(item)">
              {{ t('crm.sales.leads.actions.delete') }}
            </VBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="leads.length" />
        </template>
      </VDataTable>
    </VCard>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">{{ snackbar.text }}</VSnackbar>

    <VDialog v-model="isAssignDialogVisible" max-width="520">
      <VCard>
        <VCardItem :title="t('crm.sales.leads.dialogs.assign.title')" />
        <VCardText class="d-flex flex-column gap-4">
          <div class="text-body-2 text-medium-emphasis">
            {{ t('crm.sales.leads.dialogs.assign.subtitle', { name: selectedLead?.fullName || '-' }) }}
          </div>

          <AppSelect v-model="selectedAssigneeId" data-testid="lead-assign-dialog-select" :label="t('crm.sales.leads.dialogs.assign.fields.assignee')" :items="assigneeOptions" :disabled="isLoadingSalesUsers" :error-messages="assignValidationErrors.assignedUserId" />

          <VAlert v-if="assignLoadErrorMessage" data-testid="lead-assign-dialog-load-error" color="error" variant="tonal">
            {{ assignLoadErrorMessage }}
          </VAlert>

          <VAlert v-if="assignValidationErrors.assignedUserId?.length" data-testid="lead-assign-dialog-error" color="error" variant="tonal">
            {{ assignValidationErrors.assignedUserId[0] }}
          </VAlert>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 pt-0">
          <VBtn color="secondary" variant="tonal" @click="closeAssignDialog">{{ t('crm.sales.shared.actions.cancel') }}</VBtn>
          <VBtn color="primary" data-testid="lead-assign-dialog-save" :loading="isAssigning || isLoadingSalesUsers" @click="submitAssign">{{ t('crm.sales.leads.dialogs.assign.actions.save') }}</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="isDeleteDialogVisible" max-width="460">
      <VCard>
        <VCardItem :title="t('crm.sales.leads.dialogs.delete.title')" />
        <VCardText class="d-flex flex-column gap-3">
          <div class="text-body-1">{{ t('crm.sales.leads.dialogs.delete.message', { name: selectedLead?.fullName || '-' }) }}</div>
          <div class="text-body-2 text-medium-emphasis">{{ t('crm.sales.leads.dialogs.delete.description') }}</div>
          <VAlert v-if="deleteErrorMessage" data-testid="lead-delete-dialog-error" color="error" variant="tonal">
            {{ deleteErrorMessage }}
          </VAlert>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 pt-0">
          <VBtn color="secondary" variant="tonal" @click="closeDeleteDialog">{{ t('crm.sales.leads.dialogs.delete.actions.cancel') }}</VBtn>
          <VBtn color="error" data-testid="lead-delete-dialog-confirm" :loading="isDeleting" @click="confirmDelete">{{ t('crm.sales.leads.dialogs.delete.actions.confirm') }}</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>