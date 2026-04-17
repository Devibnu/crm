<script setup lang="ts">
import type { OpportunityStage } from '@/types/sales-enablement'

interface FormState {
  leadId: number | null
  assignedUserId: number | null
  name: string
  stage: OpportunityStage
  amount: number
  currency: string
  probability: number
  expectedCloseDate: string
  statusNotes: string
}

interface Props {
  modelValue: boolean
  leadOptions: Array<{ title: string, value: number | null }>
  assigneeOptions: Array<{ title: string, value: number | null }>
  stageOptions: Array<{ title: string, value: OpportunityStage }>
  submitErrorMessage?: string
  isSubmitting?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  submitErrorMessage: '',
  isSubmitting: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [payload: FormState]
}>()

const createInitialForm = (): FormState => ({
  leadId: null,
  assignedUserId: null,
  name: '',
  stage: 'new',
  amount: 0,
  currency: 'IDR',
  probability: 10,
  expectedCloseDate: '',
  statusNotes: '',
})

const form = ref<FormState>(createInitialForm())

watch(() => props.modelValue, value => {
  if (value)
    form.value = createInitialForm()
})

const closeDialog = () => emit('update:modelValue', false)
const submit = () => emit('submit', { ...form.value })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="760" @update:model-value="emit('update:modelValue', $event)">
    <VCard>
      <VCardItem :title="$t('crm.sales.opportunities.dialog.title')" />
      <VCardText class="d-flex flex-column gap-4">
        <VAlert v-if="submitErrorMessage" data-testid="opportunity-form-error" color="error" variant="tonal">
          {{ submitErrorMessage }}
        </VAlert>

        <VRow>
          <VCol cols="12" md="6"><AppSelect v-model="form.leadId" :label="$t('crm.sales.opportunities.form.lead')" :items="leadOptions" /></VCol>
          <VCol cols="12" md="6"><AppSelect v-model="form.assignedUserId" :label="$t('crm.sales.opportunities.form.assignee')" :items="assigneeOptions" /></VCol>
          <VCol cols="12" md="6"><AppTextField v-model="form.name" :label="$t('crm.sales.opportunities.form.name')" /></VCol>
          <VCol cols="12" md="6"><AppSelect v-model="form.stage" :label="$t('crm.sales.opportunities.form.stage')" :items="stageOptions" /></VCol>
          <VCol cols="12" md="4"><AppTextField v-model="form.amount" type="number" :label="$t('crm.sales.opportunities.form.amount')" /></VCol>
          <VCol cols="12" md="4"><AppTextField v-model="form.currency" :label="$t('crm.sales.opportunities.form.currency')" /></VCol>
          <VCol cols="12" md="4"><AppTextField v-model="form.probability" type="number" :label="$t('crm.sales.opportunities.form.probability')" /></VCol>
          <VCol cols="12" md="6"><AppTextField v-model="form.expectedCloseDate" type="date" :label="$t('crm.sales.opportunities.form.expectedCloseDate')" /></VCol>
          <VCol cols="12"><AppTextarea v-model="form.statusNotes" :label="$t('crm.sales.opportunities.form.statusNotes')" rows="4" /></VCol>
        </VRow>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 pt-0">
        <VBtn color="secondary" variant="tonal" @click="closeDialog">{{ $t('crm.sales.shared.actions.cancel') }}</VBtn>
        <VBtn color="primary" data-testid="opportunity-form-submit" :loading="isSubmitting" @click="submit">{{ $t('crm.sales.opportunities.form.submit') }}</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>