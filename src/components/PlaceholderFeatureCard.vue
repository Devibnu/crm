<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <VCard>
    <VCardText class="d-flex align-start gap-5 pa-8">
      <VAvatar
        color="primary"
        variant="tonal"
        rounded
        size="58"
      >
        <VIcon
          :icon="icon"
          size="30"
        />
      </VAvatar>

      <div>
        <VChip
          color="success"
          variant="tonal"
          size="small"
          class="mb-4"
        >
          Coming Soon
        </VChip>

        <h1 class="text-h4 mb-2">
          {{ title }}
        </h1>

        <p class="text-body-1 mb-0">
          {{ description }}
        </p>
      </div>
    </VCardText>
  </VCard>
</template>
