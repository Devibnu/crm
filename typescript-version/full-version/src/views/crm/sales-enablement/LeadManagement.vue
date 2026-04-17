<script setup lang="ts">
import LeadForm from '@/views/crm/sales-enablement/LeadForm.vue'
import LeadList from '@/views/crm/sales-enablement/LeadList.vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

type LeadManagementTab = 'capture' | 'qualification'

const activeTab = ref<LeadManagementTab>('capture')

const syncTabFromRoute = () => {
  const requestedTab = route.query.tab

  if (requestedTab === 'qualification' || requestedTab === 'capture')
    activeTab.value = requestedTab
}

const updateRouteQuery = (tab: LeadManagementTab, leadId?: number) => {
  const nextQuery: Record<string, string> = { tab }

  if (leadId)
    nextQuery.lead = String(leadId)
  else if (typeof route.query.lead === 'string')
    nextQuery.lead = route.query.lead

  router.replace({ path: '/sales-enablement/lead-management', query: nextQuery })
}

const handleLeadCreated = (leadId: number) => {
  activeTab.value = 'qualification'
  updateRouteQuery('qualification', leadId)
}

watch(() => route.query.tab, syncTabFromRoute, { immediate: true })

watch(activeTab, tab => {
  updateRouteQuery(tab)
})
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div>
          <div class="text-overline mb-2">{{ t('crm.nav.salesEnablement') }}</div>
          <h4 class="text-h4 mb-1">{{ t('crm.sales.leadManagement.title') }}</h4>
          <p class="mb-0 text-body-1">{{ t('crm.sales.leadManagement.subtitle') }}</p>
        </div>

        <VTabs v-model="activeTab" color="primary" grow>
          <VTab value="capture">{{ t('crm.sales.leadManagement.tabs.capture') }}</VTab>
          <VTab value="qualification">{{ t('crm.sales.leadManagement.tabs.qualification') }}</VTab>
        </VTabs>
      </VCardText>
    </VCard>

    <VWindow v-model="activeTab">
      <VWindowItem value="capture">
        <LeadForm embedded @created="handleLeadCreated" />
      </VWindowItem>

      <VWindowItem value="qualification">
        <LeadList embedded />
      </VWindowItem>
    </VWindow>
  </section>
</template>