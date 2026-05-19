<script setup>
import { resolveCrmModule } from '@/utils/crmModuleCatalog';
import ModuleShowcasePage from '@/views/crm/ModuleShowcasePage.vue';

const route = useRoute();

const slug = computed(() => {
  const value = route.params.slug;

  return Array.isArray(value) ? value.join('/') : String(value || '');
});

const moduleData = computed(() => resolveCrmModule('marketing', slug.value));
</script>

<template>
  <ModuleShowcasePage :module="moduleData" />
</template>
