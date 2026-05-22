<script setup>
const props = defineProps({
  module: {
    type: Object,
    required: true,
  },
});

const accentMap = {
  violet: {
    primary: '#6e49ff',
    secondary: '#9d6fff',
    surface: 'rgba(110, 73, 255, 0.08)',
  },
  indigo: {
    primary: '#4f46e5',
    secondary: '#818cf8',
    surface: 'rgba(79, 70, 229, 0.08)',
  },
  pink: {
    primary: '#db2777',
    secondary: '#f472b6',
    surface: 'rgba(219, 39, 119, 0.08)',
  },
  cyan: {
    primary: '#0891b2',
    secondary: '#22d3ee',
    surface: 'rgba(8, 145, 178, 0.08)',
  },
  emerald: {
    primary: '#059669',
    secondary: '#34d399',
    surface: 'rgba(5, 150, 105, 0.08)',
  },
};

const palette = computed(() => accentMap[props.module.accent] ?? accentMap.violet);
</script>

<template>
  <section
    class="crm-module-showcase"
    :style="{
      '--crm-module-primary': palette.primary,
      '--crm-module-secondary': palette.secondary,
      '--crm-module-surface': palette.surface,
    }"
  >
    <VRow class="match-height">
      <VCol
        cols="12"
        lg="8"
      >
        <VCard class="crm-module-showcase__hero">
          <VCardText class="pa-8 pa-md-10">
            <VChip
              class="mb-5"
              color="primary"
              variant="tonal"
              rounded="pill"
            >
              {{ module.eyebrow }}
            </VChip>

            <div class="crm-module-showcase__title-block">
              <h1 class="crm-module-showcase__title">
                {{ module.title }}
              </h1>
              <p class="crm-module-showcase__description">
                {{ module.description }}
              </p>
            </div>

            <div class="crm-module-showcase__highlight-grid">
              <div
                v-for="highlight in module.highlights"
                :key="highlight"
                class="crm-module-showcase__highlight"
              >
                <VIcon
                  icon="tabler-sparkles"
                  size="18"
                />
                <span>{{ highlight }}</span>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="4"
      >
        <VCard class="crm-module-showcase__side-card">
          <VCardText class="pa-8">
            <p class="crm-module-showcase__side-eyebrow">
              Module Signals
            </p>

            <div class="crm-module-showcase__metric-list">
              <div
                v-for="metric in module.metrics"
                :key="metric.label"
                class="crm-module-showcase__metric-item"
              >
                <span>{{ metric.label }}</span>
                <strong>{{ metric.value }}</strong>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss" scoped>
.crm-module-showcase {
  &__hero,
  &__side-card {
    overflow: hidden;
    border: 1px solid rgba(124, 92, 255, 0.1);
    border-radius: 2rem;
    background:
      radial-gradient(circle at top right, color-mix(in srgb, var(--crm-module-secondary) 22%, transparent), transparent 38%),
      linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.94));
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
  }

  &__title-block {
    max-inline-size: 48rem;
  }

  &__title {
    color: rgb(var(--v-theme-on-surface));
    font-size: clamp(2rem, 4vw, 3.25rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.05;
    margin-block-end: 1rem;
  }

  &__description {
    color: rgba(var(--v-theme-on-surface), 0.7);
    font-size: 1rem;
    line-height: 1.8;
    margin: 0;
  }

  &__highlight-grid {
    display: grid;
    gap: 0.9rem;
    margin-block-start: 2rem;
  }

  &__highlight {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    border-radius: 1.2rem;
    background: var(--crm-module-surface);
    color: var(--crm-module-primary);
    font-weight: 700;
    max-inline-size: fit-content;
    padding: 0.85rem 1rem;
  }

  &__side-eyebrow {
    color: rgba(var(--v-theme-on-surface), 0.52);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    margin-block-end: 1rem;
    text-transform: uppercase;
  }

  &__metric-list {
    display: grid;
    gap: 1rem;
  }

  &__metric-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border-radius: 1.1rem;
    background: rgba(255, 255, 255, 0.9);
    padding: 1rem 1.1rem;
  }

  &__metric-item span {
    color: rgba(var(--v-theme-on-surface), 0.56);
    font-size: 0.86rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  &__metric-item strong {
    color: rgb(var(--v-theme-on-surface));
    font-size: 0.98rem;
    font-weight: 800;
  }
}
</style>
