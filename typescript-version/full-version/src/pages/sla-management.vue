<script setup lang="ts">
definePage({
  meta: {
    action: 'read',
    subject: 'CrmTickets',
  },
})

const { t } = useI18n()
const router = useRouter()

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const { data: tiketData, execute: refreshSlaData } = await useApi<any>('/crm/tiket')

const allTickets = computed(() => tiketData.value?.tiket ?? [])

const resolveSlaState = (ticket: any) => {
  if (!ticket.batasSla)
    return 'on-track'
  if (ticket.status === 'selesai')
    return 'resolved'

  const slaTime = new Date(ticket.batasSla).getTime()
  const now = Date.now()

  if (slaTime < now)
    return 'overdue'
  if (slaTime <= now + 1000 * 60 * 60 * 24)
    return 'due-soon'

  return 'on-track'
}

const resolveStatusLabel = (status: string) => {
  if (status === 'baru')
    return t('crm.shared.statuses.new')
  if (status === 'diproses')
    return t('crm.shared.statuses.inProgress')
  if (status === 'selesai')
    return t('crm.shared.statuses.done')

  return status
}

const resolvePriorityLabel = (priority: string) => {
  if (priority === 'tinggi')
    return t('crm.shared.priorities.high')
  if (priority === 'sedang')
    return t('crm.shared.priorities.medium')
  if (priority === 'rendah')
    return t('crm.shared.priorities.low')

  return priority
}

const resolveSlaColor = (ticket: any) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return 'error'
  if (state === 'due-soon')
    return 'warning'
  if (state === 'resolved')
    return 'success'

  return 'info'
}

const resolveSlaLabel = (ticket: any) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return t('crm.shared.sla.overdue')
  if (state === 'due-soon')
    return t('crm.shared.sla.dueSoon')
  if (state === 'resolved')
    return t('crm.shared.statuses.done')

  return t('crm.shared.sla.onTrack')
}

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return formatter.format(new Date(value))
}

const statCards = computed(() => {
  const tickets = allTickets.value

  return [
    {
      title: t('crm.slaManagement.cards.overdue'),
      value: tickets.filter((ticket: any) => resolveSlaState(ticket) === 'overdue').length,
      color: 'error',
      icon: 'tabler-alert-octagon',
    },
    {
      title: t('crm.slaManagement.cards.dueSoon'),
      value: tickets.filter((ticket: any) => resolveSlaState(ticket) === 'due-soon').length,
      color: 'warning',
      icon: 'tabler-alarm',
    },
    {
      title: t('crm.slaManagement.cards.onTrack'),
      value: tickets.filter((ticket: any) => resolveSlaState(ticket) === 'on-track').length,
      color: 'info',
      icon: 'tabler-progress-check',
    },
    {
      title: t('crm.slaManagement.cards.resolved'),
      value: tickets.filter((ticket: any) => resolveSlaState(ticket) === 'resolved').length,
      color: 'success',
      icon: 'tabler-circle-check',
    },
  ]
})

const watchlistTickets = computed(() => [...allTickets.value]
  .filter((ticket: any) => ['overdue', 'due-soon'].includes(resolveSlaState(ticket)))
  .sort((left: any, right: any) => new Date(left.batasSla || 0).getTime() - new Date(right.batasSla || 0).getTime()))

const openTicket = (ticketId: number) => {
  router.push({
    name: 'tiket',
    query: {
      ticket: String(ticketId),
    },
  })
}
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VRow>
      <VCol cols="12" md="8">
        <VCard>
          <VCardText class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-4">
            <div>
              <div class="text-overline mb-2">Layanan Pelanggan</div>
              <h4 class="text-h4 mb-1">{{ t('crm.slaManagement.title') }}</h4>
              <p class="mb-0 text-body-1">{{ t('crm.slaManagement.subtitle') }}</p>
            </div>

            <VBtn color="primary" prepend-icon="tabler-refresh" @click="refreshSlaData()">
              {{ t('crm.slaManagement.refreshButton') }}
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol v-for="card in statCards" :key="card.title" cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-start justify-space-between gap-3">
            <div>
              <div class="text-body-1 text-high-emphasis mb-1">{{ card.title }}</div>
              <h4 class="text-h4 mb-0">{{ card.value }}</h4>
            </div>

            <VAvatar :color="card.color" variant="tonal" rounded size="46">
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard>
      <VCardItem :title="t('crm.slaManagement.table.title')" />

      <VTable class="text-no-wrap">
        <thead>
          <tr>
            <th>{{ t('crm.slaManagement.table.code') }}</th>
            <th>{{ t('crm.slaManagement.table.customer') }}</th>
            <th>{{ t('crm.slaManagement.table.status') }}</th>
            <th>{{ t('crm.slaManagement.table.priority') }}</th>
            <th>{{ t('crm.slaManagement.table.sla') }}</th>
            <th>{{ t('crm.slaManagement.table.owner') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ticket in watchlistTickets" :key="ticket.id" class="cursor-pointer" @click="openTicket(ticket.id)">
            <td>{{ ticket.kode }}</td>
            <td>{{ ticket.pelanggan?.nama || '-' }}</td>
            <td>
              <VChip size="small" :color="resolveSlaColor(ticket)" variant="tonal">
                {{ resolveStatusLabel(ticket.status) }}
              </VChip>
            </td>
            <td>{{ resolvePriorityLabel(ticket.prioritas) }}</td>
            <td>
              <div class="d-flex flex-column gap-1">
                <span>{{ formatDate(ticket.batasSla) }}</span>
                <span class="text-xs text-medium-emphasis">{{ resolveSlaLabel(ticket) }}</span>
              </div>
            </td>
            <td>{{ ticket.assignedUser?.fullName || t('crm.conversations.assignee.unassigned') }}</td>
          </tr>
          <tr v-if="!watchlistTickets.length">
            <td colspan="6" class="text-center text-medium-emphasis py-6">{{ t('crm.slaManagement.empty') }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </section>
</template>
