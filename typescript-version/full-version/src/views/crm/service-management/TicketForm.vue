<script setup lang="ts">
import type { ServiceTicketCategory, ServiceTicketMutationResponse, ServiceTicketOptionsResponse, ServiceTicketPriority, SlaDefinitionRecord } from '@/types/service-management'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const snackbar = ref({
  visible: false,
  color: 'success',
  text: '',
})
const isSubmitting = ref(false)
const isLoadingOptions = ref(false)
const optionData = ref<ServiceTicketOptionsResponse | null>(null)

const form = ref({
  customerId: null as number | null,
  assignedUserId: null as number | null,
  slaDefinitionId: null as number | null,
  category: 'general' as ServiceTicketCategory,
  priority: 'medium' as ServiceTicketPriority,
  subject: '',
  description: '',
})

const customers = computed(() => optionData.value?.customers ?? [])
const agents = computed(() => optionData.value?.agents ?? [])
const slaDefinitions = computed<SlaDefinitionRecord[]>(() => optionData.value?.slaDefinitions ?? [])

const customerOptions = computed(() => customers.value.map((customer: any) => ({
  title: `${customer.name}${customer.email ? ` • ${customer.email}` : ''}`,
  value: customer.id,
})))

const assigneeOptions = computed(() => [
  { title: t('crm.tickets.assignee.unassigned'), value: null },
  ...agents.value.map((agent: any) => ({
    title: `${agent.fullName} • ${agent.role}`,
    value: agent.id,
  })),
])

const categoryOptions = computed(() => [
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

const slaOptions = computed(() => [
  { title: t('crm.ticketForm.fields.slaDefinitionOptional'), value: null },
  ...slaDefinitions.value.map(definition => ({
    title: `${definition.name} • ${definition.resolutionMinutes}m`,
    value: definition.id,
  })),
])

const selectedSla = computed(() => slaDefinitions.value.find(definition => definition.id === form.value.slaDefinitionId) ?? null)

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = {
    visible: true,
    color,
    text,
  }
}

const goBack = () => {
  router.push('/service-management/ticket-list')
}

const loadOptions = async () => {
  isLoadingOptions.value = true

  try {
    const { data } = await axiosApi.get<ServiceTicketOptionsResponse>('/tickets/options')

    optionData.value = data
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.ticketForm.snackbar.error')), 'error')
  }
  finally {
    isLoadingOptions.value = false
  }
}

const submit = async () => {
  isSubmitting.value = true

  try {
    const { data: response } = await axiosApi.post<ServiceTicketMutationResponse>('/tickets', form.value)

    showSnackbar(t('crm.ticketForm.snackbar.success'))

    router.push({
      path: '/service-management/ticket-list',
      query: response?.data?.id ? { ticket: String(response.data.id) } : undefined,
    })
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.ticketForm.snackbar.error')), 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  void loadOptions()
})

watch(customers, customerList => {
  const preselectedCustomerId = Number(route.query.customer ?? 0)

  if (preselectedCustomerId && customerList.some((customer: any) => customer.id === preselectedCustomerId))
    form.value.customerId = preselectedCustomerId
}, { immediate: true })
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between align-lg-center gap-4">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.serviceManagement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.ticketForm.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.ticketForm.subtitle') }}</p>
        </div>

        <VBtn color="secondary" variant="tonal" prepend-icon="tabler-arrow-left" @click="goBack">
          {{ t('crm.ticketForm.actions.back') }}
        </VBtn>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" lg="8">
        <VCard>
          <VCardItem :title="t('crm.ticketForm.formTitle')" />
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.customerId" :label="t('crm.ticketForm.fields.customer')" :items="customerOptions" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.assignedUserId" :label="t('crm.ticketForm.fields.assignee')" :items="assigneeOptions" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.category" :label="t('crm.ticketForm.fields.category')" :items="categoryOptions" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.priority" :label="t('crm.ticketForm.fields.priority')" :items="priorityOptions" />
              </VCol>
              <VCol cols="12">
                <AppTextField v-model="form.subject" :label="t('crm.ticketForm.fields.subject')" :placeholder="t('crm.ticketForm.fields.subjectPlaceholder')" />
              </VCol>
              <VCol cols="12">
                <AppSelect v-model="form.slaDefinitionId" :label="t('crm.ticketForm.fields.slaDefinition')" :items="slaOptions" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="form.description" :label="t('crm.ticketForm.fields.description')" :placeholder="t('crm.ticketForm.fields.descriptionPlaceholder')" rows="6" />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" lg="4">
        <VCard class="mb-6">
          <VCardItem :title="t('crm.ticketForm.sidebar.selectedSlaTitle')" />
          <VCardText class="d-flex flex-column gap-3">
            <template v-if="selectedSla">
              <div>
                <div class="font-weight-medium text-high-emphasis">{{ selectedSla.name }}</div>
                <div class="text-sm text-medium-emphasis">{{ selectedSla.description || '-' }}</div>
              </div>
              <div class="text-sm text-medium-emphasis">{{ t('crm.ticketForm.sidebar.firstResponse', { value: selectedSla.firstResponseMinutes }) }}</div>
              <div class="text-sm text-medium-emphasis">{{ t('crm.ticketForm.sidebar.resolution', { value: selectedSla.resolutionMinutes }) }}</div>
              <div class="text-sm text-medium-emphasis">{{ t('crm.ticketForm.sidebar.warning', { value: selectedSla.warningBeforeMinutes }) }}</div>
              <VAlert v-if="selectedSla.autoEscalate" color="warning" variant="tonal">
                {{ t('crm.ticketForm.sidebar.autoEscalate', { priority: selectedSla.escalationPriority || t('crm.tickets.priorities.high') }) }}
              </VAlert>
            </template>
            <div v-else class="text-medium-emphasis">{{ t('crm.ticketForm.sidebar.noSlaSelected') }}</div>
          </VCardText>
        </VCard>

        <VAlert color="info" variant="tonal" class="mb-6">
          {{ t('crm.ticketForm.sidebar.placeholderNote') }}
        </VAlert>

        <div class="d-flex flex-column gap-3">
          <VBtn color="primary" :loading="isSubmitting" @click="submit">
            {{ t('crm.ticketForm.actions.submit') }}
          </VBtn>
          <VBtn color="secondary" variant="tonal" :disabled="isLoadingOptions" @click="goBack">
            {{ t('crm.ticketForm.actions.cancel') }}
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">
      {{ snackbar.text }}
    </VSnackbar>
  </section>
</template>