<script setup lang="ts">
import type { OpportunityRecord } from '@/types/sales-enablement'

interface Props {
  opportunity: OpportunityRecord
  currencyLabel: string
  assigneeFallback: string
  selected?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
})

const emit = defineEmits<{
  view: [opportunity: OpportunityRecord]
  delete: [opportunity: OpportunityRecord]
  dragstart: [opportunity: OpportunityRecord]
}>()

const onView = () => emit('view', props.opportunity)
const onDelete = () => emit('delete', props.opportunity)
const onDragStart = () => emit('dragstart', props.opportunity)
</script>

<template>
  <article
    class="opportunity-card"
    :class="{ 'opportunity-card--selected': selected }"
    draggable="true"
    :data-opportunity-id="opportunity.id"
    @dragstart="onDragStart"
  >
    <div class="d-flex justify-space-between align-start gap-3 mb-3">
      <div>
        <div class="font-weight-medium text-high-emphasis">{{ opportunity.lead?.company || '-' }}</div>
        <div class="text-body-2 text-high-emphasis mt-1">{{ opportunity.name }}</div>
      </div>

      <VChip size="small" variant="tonal" color="primary">{{ opportunity.code }}</VChip>
    </div>

    <div class="d-flex flex-column gap-1 text-body-2 mb-4">
      <div>{{ currencyLabel }}</div>
      <div class="text-medium-emphasis">{{ opportunity.assignedUser?.fullName || assigneeFallback }}</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <VBtn size="small" variant="tonal" color="primary" :data-testid="`opportunity-view-button-${opportunity.id}`" @click.stop="onView">
        {{ $t('crm.sales.opportunities.card.actions.view') }}
      </VBtn>
      <VBtn size="small" variant="tonal" color="error" :data-testid="`opportunity-delete-button-${opportunity.id}`" @click.stop="onDelete">
        {{ $t('crm.sales.opportunities.card.actions.delete') }}
      </VBtn>
    </div>
  </article>
</template>

<style scoped lang="scss">
.opportunity-card {
  padding: 1rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 18px;
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-primary), 0.08), transparent 42%),
    rgb(var(--v-theme-surface));
  cursor: grab;
  text-align: start;
  transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.opportunity-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
}

.opportunity-card--selected {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgba(var(--v-theme-primary), 0.25);
}
</style>