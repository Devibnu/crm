<script setup lang="ts">
import type { LeadIndexResponse, LeadMutationResponse, SalesLeadStatus } from '@/types/sales-enablement'
import { axiosApi, resolveApiErrorMessage } from '@/plugins/axios'
import axios from 'axios'

interface Props {
  embedded?: boolean
}

interface Emit {
  (event: 'created', leadId: number): void
}

const props = withDefaults(defineProps<Props>(), {
  embedded: false,
})

const emit = defineEmits<Emit>()

const { t } = useI18n()
const router = useRouter()

const isLoading = ref(false)
const isSubmitting = ref(false)
const salesUsers = ref<LeadIndexResponse['salesUsers']>([])
const snackbar = ref({ visible: false, color: 'success', text: '' })
const validationErrors = ref<Record<string, string[]>>({})
const form = ref({
  fullName: '',
  company: '',
  status: 'new' as SalesLeadStatus,
  assignedUserId: null as number | null,
})

const assigneeOptions = computed(() => [
  { title: t('crm.sales.shared.unassigned'), value: null },
  ...salesUsers.value.map(user => ({ title: `${user.fullName} • ${user.role}`, value: user.id })),
])

const statusOptions = computed(() => [
  { title: t('crm.sales.shared.leadStatuses.new'), value: 'new' },
  { title: t('crm.sales.shared.leadStatuses.qualified'), value: 'qualified' },
  { title: t('crm.sales.shared.leadStatuses.disqualified'), value: 'disqualified' },
])

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = { visible: true, color, text }
}

const clearErrors = () => {
  validationErrors.value = {}
}

const campaignImportPlaceholder = () => {
  showSnackbar(t('crm.sales.leadForm.snackbar.importPlaceholder'))
}

const fetchOptions = async () => {
  isLoading.value = true

  try {
    const { data } = await axiosApi.get<LeadIndexResponse>('/leads')

    salesUsers.value = data.salesUsers
  }
  catch (error) {
    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.snackbar.loadError')), 'error')
  }
  finally {
    isLoading.value = false
  }
}

const submit = async () => {
  isSubmitting.value = true
  clearErrors()

  try {
    const { data } = await axiosApi.post<LeadMutationResponse>('/leads', {
      fullName: form.value.fullName,
      company: form.value.company,
      status: form.value.status,
      assignedUserId: form.value.assignedUserId,
      source: 'manual',
    })

    showSnackbar(t('crm.sales.leadForm.snackbar.success'))

    form.value = {
      fullName: '',
      company: '',
      status: 'new',
      assignedUserId: null,
    }

    if (props.embedded)
      emit('created', data.data.id)
    else
      router.push({ path: '/sales-enablement/lead-management', query: { lead: String(data.data.id), tab: 'qualification' } })
  }
  catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 422)
      validationErrors.value = (error.response.data?.errors ?? {}) as Record<string, string[]>

    showSnackbar(resolveApiErrorMessage(error, t('crm.sales.leadForm.snackbar.error')), 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  void fetchOptions()
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard v-if="!props.embedded">
      <VCardText class="d-flex flex-column flex-lg-row justify-space-between align-lg-center gap-4">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.sales.leadForm.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.sales.leadForm.subtitle') }}</p>
        </div>

        <VBtn color="secondary" variant="tonal" prepend-icon="tabler-arrow-left" @click="router.push('/sales-enablement/lead-management')">
          {{ t('crm.sales.shared.actions.back') }}
        </VBtn>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" lg="8">
        <VCard>
          <VCardItem :title="t('crm.sales.leadForm.formTitle')" />
          <VCardText>
            <VAlert v-if="validationErrors.general?.length" color="error" variant="tonal" class="mb-4">
              {{ validationErrors.general[0] }}
            </VAlert>

            <VRow>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.fullName" :label="t('crm.sales.leadForm.fields.fullName')" :error-messages="validationErrors.fullName" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.company" :label="t('crm.sales.leadForm.fields.company')" :error-messages="validationErrors.company" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.status" :label="t('crm.sales.leadForm.fields.status')" :items="statusOptions" :error-messages="validationErrors.status" />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect v-model="form.assignedUserId" :label="t('crm.sales.leadForm.fields.assignee')" :items="assigneeOptions" :disabled="isLoading" :error-messages="validationErrors.assignedUserId" />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" lg="4">
        <VAlert color="info" variant="tonal" class="mb-4">
          {{ t('crm.sales.placeholders.leadCapture') }}
        </VAlert>

        <VBtn color="secondary" variant="tonal" block prepend-icon="tabler-speakerphone" class="mb-6" @click="campaignImportPlaceholder">
          {{ t('crm.sales.leadForm.actions.importCampaign') }}
        </VBtn>

        <div class="d-flex flex-column gap-3 mt-6">
          <VBtn color="primary" :loading="isSubmitting" @click="submit">
            {{ t('crm.sales.leadForm.actions.submit') }}
          </VBtn>
          <VBtn v-if="!props.embedded" color="secondary" variant="tonal" @click="router.push('/sales-enablement/lead-management')">
            {{ t('crm.sales.shared.actions.cancel') }}
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <VSnackbar v-model="snackbar.visible" :color="snackbar.color">{{ snackbar.text }}</VSnackbar>
  </section>
</template>