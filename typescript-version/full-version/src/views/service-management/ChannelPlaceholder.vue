<script setup lang="ts">
const props = defineProps<{
  overline: string
  title: string
  description: string
  primaryLabel: string
  secondaryLabel: string
  modules: Array<{ title: string; description: string; icon: string; color: string }>
}>()
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-4">
        <div>
          <div class="text-overline mb-2">{{ props.overline }}</div>
          <h4 class="text-h4 mb-1">{{ props.title }}</h4>
          <p class="mb-0 text-body-1">{{ props.description }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VChip color="primary" variant="tonal">{{ props.primaryLabel }}</VChip>
          <VChip color="secondary" variant="tonal">{{ props.secondaryLabel }}</VChip>
        </div>
      </VCardText>
    </VCard>

    <VRow>
      <VCol v-for="module in props.modules" :key="module.title" cols="12" md="6">
        <VCard class="h-100">
          <VCardText class="d-flex align-start gap-4">
            <VAvatar :color="module.color" variant="tonal" rounded size="46">
              <VIcon :icon="module.icon" />
            </VAvatar>

            <div>
              <div class="text-body-1 text-high-emphasis mb-1">{{ module.title }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ module.description }}</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>