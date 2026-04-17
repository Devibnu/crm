<script setup lang="ts">
definePage({
  meta: {
    action: 'read',
    subject: 'CrmCustomers',
  },
})

const { t } = useI18n()
const router = useRouter()
const ability = useAbility()

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const { data: dashboardData, execute: refreshDashboard } = await useApi<any>('/crm/dashboard')

const stats = computed(() => dashboardData.value?.stats ?? {})
const recentTickets = computed(() => dashboardData.value?.recentTickets ?? [])

const dashboardQuickLinks = computed(() => [
  {
    title: t('crm.dashboard.quickLinks.myTickets'),
    subtitle: t('crm.dashboard.quickLinks.myTicketsSubtitle'),
    value: stats.value.tiketSaya ?? 0,
    color: 'primary',
    icon: 'tabler-user-check',
    query: { ownership: 'mine' },
    action: 'read',
    subject: 'CrmTickets',
  },
  {
    title: t('crm.dashboard.quickLinks.unassigned'),
    subtitle: t('crm.dashboard.quickLinks.unassignedSubtitle'),
    value: stats.value.tiketBelumAssigned ?? 0,
    color: 'warning',
    icon: 'tabler-user-question',
    query: { ownership: 'unassigned' },
    action: 'read',
    subject: 'CrmTickets',
  },
].filter(link => ability.can(link.action, link.subject)))

const statCards = computed(() => [
  {
    title: t('crm.dashboard.stats.customers'),
    value: stats.value.pelanggan ?? 0,
    icon: 'tabler-users-group',
    color: 'primary',
    subtitle: t('crm.dashboard.stats.customersSubtitle'),
    action: 'read',
    subject: 'CrmCustomers',
  },
  {
    title: t('crm.dashboard.stats.activeTickets'),
    value: stats.value.tiketAktif ?? 0,
    icon: 'tabler-ticket',
    color: 'warning',
    subtitle: t('crm.dashboard.stats.activeTicketsSubtitle'),
    action: 'read',
    subject: 'CrmTickets',
  },
  {
    title: t('crm.dashboard.stats.conversations'),
    value: stats.value.percakapan ?? 0,
    icon: 'tabler-message-circle',
    color: 'info',
    subtitle: t('crm.dashboard.stats.conversationsSubtitle'),
    action: 'read',
    subject: 'CrmInbox',
  },
  {
    title: t('crm.dashboard.stats.users'),
    value: stats.value.pengguna ?? 0,
    icon: 'tabler-user-shield',
    color: 'success',
    subtitle: t('crm.dashboard.stats.usersSubtitle'),
    action: 'manage',
    subject: 'Admin',
  },
].filter(card => ability.can(card.action, card.subject)))

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return formatter.format(new Date(value))
}

const openTicketQueue = (query: Record<string, string>) => {
  router.push({
    name: 'tiket',
    query,
  })
}
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VRow>
      <VCol
        cols="12"
        md="8"
      >
        <VCard>
          <VCardText class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-4">
            <div>
              <div class="text-overline mb-2">
                {{ t('crm.dashboard.overline') }}
              </div>
              <h4 class="text-h4 mb-1">
                {{ t('crm.dashboard.title') }}
              </h4>
              <p class="mb-0 text-body-1">
                {{ t('crm.dashboard.subtitle') }}
              </p>
            </div>

            <VBtn
              color="primary"
              prepend-icon="tabler-refresh"
              @click="refreshDashboard()"
            >
              {{ t('common.refreshData') }}
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <VCard color="surface-variant">
          <VCardText>
            <div class="text-overline mb-2">
              {{ t('crm.dashboard.todayFocus') }}
            </div>
            <div class="text-body-1 mb-2">
              {{ t('crm.dashboard.totalTickets') }}: <strong>{{ stats.tiket ?? 0 }}</strong>
            </div>
            <div class="text-body-1 mb-2">
              {{ t('crm.dashboard.activeTickets') }}: <strong>{{ stats.tiketAktif ?? 0 }}</strong>
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('crm.dashboard.focusHint') }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        v-for="card in statCards"
        :key="card.title"
        cols="12"
        sm="6"
        md="3"
      >
        <VCard>
          <VCardText class="d-flex justify-space-between align-start gap-3">
            <div>
              <div class="text-body-1 text-high-emphasis mb-1">
                {{ card.title }}
              </div>
              <h4 class="text-h4 mb-1">
                {{ card.value }}
              </h4>
              <div class="text-sm text-medium-emphasis">
                {{ card.subtitle }}
              </div>
            </div>

            <VAvatar
              :color="card.color"
              variant="tonal"
              rounded
              size="44"
            >
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        v-for="link in dashboardQuickLinks"
        :key="link.title"
        cols="12"
        md="6"
      >
        <VCard class="cursor-pointer" @click="openTicketQueue(link.query)">
          <VCardText class="d-flex align-center justify-space-between gap-4">
            <div>
              <div class="text-overline mb-1">{{ link.title }}</div>
              <h4 class="text-h4 mb-1">{{ link.value }}</h4>
              <div class="text-body-2 text-medium-emphasis">{{ link.subtitle }}</div>
            </div>

            <VAvatar :color="link.color" variant="tonal" rounded size="48">
              <VIcon :icon="link.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard>
      <VCardItem>
        <VCardTitle>{{ t('crm.dashboard.recentTickets') }}</VCardTitle>
        <template #append>
          <VChip
            color="warning"
            variant="tonal"
          >
            {{ t('crm.dashboard.monitoredTickets', { count: recentTickets.length }) }}
          </VChip>
        </template>
      </VCardItem>

      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th>{{ t('crm.dashboard.table.code') }}</th>
            <th>{{ t('crm.dashboard.table.customer') }}</th>
            <th>{{ t('crm.dashboard.table.status') }}</th>
            <th>{{ t('crm.dashboard.table.priority') }}</th>
            <th>{{ t('crm.dashboard.table.latestMessage') }}</th>
            <th>{{ t('crm.dashboard.table.updatedAt') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="ticket in recentTickets"
            :key="ticket.id"
          >
            <td>{{ ticket.kode }}</td>
            <td>{{ ticket.pelanggan || '-' }}</td>
            <td>
              <VChip
                size="small"
                color="warning"
                variant="tonal"
              >
                {{ ticket.status }}
              </VChip>
            </td>
            <td>{{ ticket.prioritas }}</td>
            <td class="text-wrap" style="max-inline-size: 28rem;">
              {{ ticket.pesanTerbaru || '-' }}
            </td>
            <td>{{ formatDate(ticket.updatedAt) }}</td>
          </tr>
          <tr v-if="!recentTickets.length">
            <td
              colspan="6"
              class="text-center text-medium-emphasis"
            >
              {{ t('crm.dashboard.empty') }}
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </section>
</template>