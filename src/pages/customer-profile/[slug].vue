<script setup>
import { resolveCrmModule } from '@/utils/crmModuleCatalog';
import ModuleShowcasePage from '@/views/crm/ModuleShowcasePage.vue';

const route = useRoute();
const moduleData = computed(() => resolveCrmModule('customer-profile', String(route.params.slug)));
</script>

<template>
  <ModuleShowcasePage :module="moduleData" />
</template>
