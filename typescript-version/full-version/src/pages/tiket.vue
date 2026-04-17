<script setup lang="ts">
definePage({
  meta: {
    action: 'read',
    subject: 'CrmTickets',
  },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const ability = useAbility()
const userData = useCookie<any>('userData')
const selectedStatus = useCookie<string>('crmTiketStatus', { default: () => '' })
const selectedCategory = useCookie<string>('crmTiketCategory', { default: () => '' })
const selectedOwnership = useCookie<string>('crmTiketOwnership', { default: () => '' })
const selectedSort = useCookie<string>('crmTiketSort', { default: () => 'latest' })
const debouncedSelectedStatus = ref('')
const debouncedSelectedCategory = ref('')
const isDialogVisible = ref(false)
const isCreateDialogVisible = ref(false)
const isSubmitting = ref(false)
const isCreatingTicket = ref(false)
const isAssigningTicketId = ref<number | null>(null)
const activeTicket = ref<any>(null)
const createError = ref('')
const snackbar = ref({
  visible: false,
  color: 'success',
  text: '',
})
const replyForm = ref({
  status: 'diproses',
  isiPesan: '',
})
const createTicketForm = ref({
  pelangganId: null as number | null,
  assignedUserId: null as number | null,
  kategori: 'general',
  subjek: '',
  prioritas: 'sedang',
  isiPesan: '',
})

const ticketCategoryOptions = computed(() => [
  { title: t('crm.shared.categories.all'), value: '' },
  { title: t('crm.shared.categories.general'), value: 'general' },
  { title: t('crm.shared.categories.billing'), value: 'billing' },
  { title: t('crm.shared.categories.technical'), value: 'technical' },
  { title: t('crm.shared.categories.priorityFollowUp'), value: 'priority-follow-up' },
])

const ticketStatusOptions = computed(() => [
  { title: t('crm.shared.statuses.all'), value: '' },
  { title: t('crm.shared.statuses.new'), value: 'baru' },
  { title: t('crm.shared.statuses.inProgress'), value: 'diproses' },
  { title: t('crm.shared.statuses.done'), value: 'selesai' },
])

const ticketSortOptions = computed(() => [
  { title: t('crm.shared.sort.latest'), value: 'latest' },
  { title: t('crm.shared.sort.priority'), value: 'priority' },
  { title: t('crm.shared.sort.sla'), value: 'sla' },
])

const ownershipOptions = computed(() => [
  { title: t('crm.shared.ownership.all'), value: '' },
  { title: t('crm.shared.ownership.mine'), value: 'mine' },
  { title: t('crm.shared.ownership.unassigned'), value: 'unassigned' },
])

const getQueryValue = (value: unknown) => typeof value === 'string' ? value : ''

const getTicketFromQuery = () => {
  const value = Number(getQueryValue(route.query.ticket))

  return Number.isFinite(value) && value > 0 ? value : null
}

const getCustomerFromQuery = () => {
  const value = Number(getQueryValue(route.query.customer))

  return Number.isFinite(value) && value > 0 ? value : null
}

selectedStatus.value = getQueryValue(route.query.status) || selectedStatus.value
selectedCategory.value = getQueryValue(route.query.category) || selectedCategory.value
selectedOwnership.value = getQueryValue(route.query.ownership) || selectedOwnership.value
selectedSort.value = getQueryValue(route.query.sort) || selectedSort.value

const syncTicketFilters = useDebounceFn(() => {
  debouncedSelectedStatus.value = selectedStatus.value
  debouncedSelectedCategory.value = selectedCategory.value
}, 220)

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const { data: usersData } = await useApi<any>(createUrl('/apps/users', {
  query: {
    itemsPerPage: 100,
    page: 1,
  },
}))

const { data: pelangganData } = await useApi<any>(createUrl('/crm/pelanggan'))

const { data: tiketData, execute: fetchTiket } = await useApi<any>(createUrl('/crm/tiket', {
  query: {
    status: debouncedSelectedStatus,
    kategori: debouncedSelectedCategory,
  },
}))

const allTiket = computed(() => tiketData.value?.tiket ?? [])
const highlightedTicketId = computed(() => getTicketFromQuery())
const highlightedCustomerId = computed(() => getCustomerFromQuery())
const pelangganOptions = computed(() => (pelangganData.value?.pelanggan ?? []).map((customer: any) => ({
  title: `${customer.nama} ${customer.email ? `• ${customer.email}` : ''}`.trim(),
  value: customer.id,
})))
const selectedCreateCustomer = computed(() => (pelangganData.value?.pelanggan ?? []).find((customer: any) => customer.id === createTicketForm.value.pelangganId) ?? null)
const selectedCreateCustomerOpenTickets = computed(() => allTiket.value.filter((ticket: any) => ticket.pelanggan?.id === createTicketForm.value.pelangganId && ticket.status !== 'selesai'))
const selectedCreateCustomerRecentAssignedTicket = computed(() => allTiket.value.find((ticket: any) => ticket.pelanggan?.id === createTicketForm.value.pelangganId && ticket.assignedUser?.id) ?? null)
const assigneeOptions = computed(() => [
  { title: t('crm.tickets.assignee.unassigned'), value: null },
  ...((usersData.value?.users ?? []).map((user: any) => ({
    title: user.fullName,
    value: user.id,
  }))),
])
const currentUserAssigneeOption = computed(() => assigneeOptions.value.find(option => option.value === userData.value?.id) ?? null)
const canCreateTickets = computed(() => ability.can('create', 'CrmTickets'))
const canUpdateTickets = computed(() => ability.can('update', 'CrmTickets'))
const ticketWorkspaceAccessMessage = computed(() => {
  if (!canCreateTickets.value && !canUpdateTickets.value)
    return 'Mode baca saja. Daftar tiket dan histori aktivitas tetap terlihat, tetapi Anda tidak bisa membuat tiket baru, mengubah assignment, atau menambahkan balasan.'

  if (!canCreateTickets.value)
    return 'Akses terbatas. Anda masih bisa menangani tiket yang ada, tetapi pembuatan tiket baru dinonaktifkan.'

  if (!canUpdateTickets.value)
    return 'Akses terbatas. Anda masih bisa melihat daftar tiket, tetapi assignment, status, dan balasan ticket dinonaktifkan.'

  return ''
})

const ticketPriorityOptions = computed(() => [
  { title: t('crm.shared.priorities.low'), value: 'rendah' },
  { title: t('crm.shared.priorities.medium'), value: 'sedang' },
  { title: t('crm.shared.priorities.high'), value: 'tinggi' },
])

const createCategoryOptions = computed(() => ticketCategoryOptions.value.filter(option => option.value))
const ticketRecommendation = computed(() => {
  const customer = selectedCreateCustomer.value
  const openTickets = selectedCreateCustomerOpenTickets.value

  if (!customer) {
    return {
      kategori: 'general',
      prioritas: 'sedang',
      subjek: '',
      reason: '',
      assignedUserId: null,
      assigneeLabel: t('crm.tickets.assignee.unassigned'),
      assigneeReason: '',
    }
  }

  const historicalAssignee = selectedCreateCustomerOpenTickets.value.find(ticket => ticket.assignedUser?.id)?.assignedUser
    ?? selectedCreateCustomerRecentAssignedTicket.value?.assignedUser

  const recommendedAssignee = historicalAssignee
    ? {
        assignedUserId: historicalAssignee.id,
        assigneeLabel: historicalAssignee.fullName,
        assigneeReason: t('crm.tickets.recommendation.assigneeHistory', { name: historicalAssignee.fullName }),
      }
    : currentUserAssigneeOption.value
      ? {
          assignedUserId: currentUserAssigneeOption.value.value,
          assigneeLabel: currentUserAssigneeOption.value.title,
          assigneeReason: t('crm.tickets.recommendation.assigneeCurrentUser', { name: currentUserAssigneeOption.value.title }),
        }
      : {
          assignedUserId: null,
          assigneeLabel: t('crm.tickets.assignee.unassigned'),
          assigneeReason: t('crm.tickets.recommendation.assigneeUnassigned'),
        }

  if (openTickets.length) {
    return {
      kategori: 'priority-follow-up',
      prioritas: 'tinggi',
      subjek: `Follow-up ${customer.nama}`,
      reason: t('crm.tickets.recommendation.activeTickets', { count: openTickets.length }),
      ...recommendedAssignee,
    }
  }

  if (customer.source === 'inbox') {
    return {
      kategori: 'technical',
      prioritas: 'sedang',
      subjek: `Permintaan dari inbox ${customer.nama}`,
      reason: t('crm.tickets.recommendation.inboxSource'),
      ...recommendedAssignee,
    }
  }

  if (customer.source === 'campaign' || customer.source === 'form') {
    return {
      kategori: 'general',
      prioritas: 'sedang',
      subjek: `Tindak lanjut lead ${customer.nama}`,
      reason: t('crm.tickets.recommendation.leadSource'),
      ...recommendedAssignee,
    }
  }

  return {
    kategori: 'general',
    prioritas: customer.status === 'inactive' ? 'rendah' : 'sedang',
    subjek: `Permintaan customer ${customer.nama}`,
    reason: t('crm.tickets.recommendation.default'),
    ...recommendedAssignee,
  }
})

const getPriorityRank = (priority: string) => {
  if (priority === 'tinggi')
    return 3
  if (priority === 'sedang')
    return 2

  return 1
}

const getTicketActivityTime = (ticket: any) => new Date(ticket.pesan?.[0]?.createdAt || ticket.batasSla || 0).getTime()

const matchesOwnershipFilter = (ticket: any, ownership: string) => {
  if (!ownership)
    return true
  if (ownership === 'mine')
    return ticket.assignedUser?.id === userData.value?.id
  if (ownership === 'unassigned')
    return !ticket.assignedUser

  return true
}

const tiket = computed(() => [...allTiket.value]
  .filter(ticket => matchesOwnershipFilter(ticket, selectedOwnership.value))
  .sort((left, right) => {
  if (selectedSort.value === 'priority') {
    const priorityDelta = getPriorityRank(right.prioritas) - getPriorityRank(left.prioritas)

    if (priorityDelta !== 0)
      return priorityDelta
  }

  if (selectedSort.value === 'sla') {
    const leftSla = new Date(left.batasSla || 0).getTime()
    const rightSla = new Date(right.batasSla || 0).getTime()

    if (leftSla !== rightSla)
      return leftSla - rightSla
  }

  return getTicketActivityTime(right) - getTicketActivityTime(left)
}))

const resolveCategoryLabel = (category?: string | null) => ticketCategoryOptions.value.find(option => option.value === category)?.title || t('crm.shared.categories.general')

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

const getTicketStatusCount = (status: string) => status
  ? allTiket.value.filter((ticket: any) => ticket.status === status).length
  : allTiket.value.length

const getTicketCategoryCount = (category: string) => category
  ? allTiket.value.filter((ticket: any) => ticket.kategori === category).length
  : allTiket.value.length

const getTicketOwnershipCount = (ownership: string) => ownership
  ? allTiket.value.filter((ticket: any) => matchesOwnershipFilter(ticket, ownership)).length
  : allTiket.value.length

const resolveStatusFilterColor = (status: string) => {
  const count = getTicketStatusCount(status)

  if (!count)
    return undefined
  if (status === 'baru')
    return 'error'
  if (status === 'diproses')
    return 'warning'
  if (status === 'selesai')
    return 'success'

  return 'primary'
}

const resolveCategoryFilterColor = (category: string) => {
  const count = getTicketCategoryCount(category)

  if (!count)
    return undefined
  if (category === 'priority-follow-up')
    return 'error'
  if (category === 'technical')
    return 'warning'
  if (category === 'billing')
    return 'info'
  if (category === 'general')
    return 'secondary'

  return 'primary'
}

const resolveSortChipColor = (sort: string) => {
  if (sort === 'sla') {
    const hasUrgentSla = allTiket.value.some((ticket: any) => ticket.status !== 'selesai' && new Date(ticket.batasSla || 0).getTime() <= Date.now() + 1000 * 60 * 60 * 24)

    return hasUrgentSla ? 'warning' : undefined
  }

  if (sort === 'priority') {
    const hasHighPriority = allTiket.value.some((ticket: any) => ticket.prioritas === 'tinggi' && ticket.status !== 'selesai')

    return hasHighPriority ? 'error' : undefined
  }

  return undefined
}

const resolveSlaState = (ticket: any) => {
  if (!ticket.batasSla || ticket.status === 'selesai')
    return null

  const slaTime = new Date(ticket.batasSla).getTime()
  const now = Date.now()

  if (slaTime < now)
    return 'overdue'
  if (slaTime <= now + 1000 * 60 * 60 * 24)
    return 'due-soon'

  return null
}

const resolveSlaChipColor = (ticket: any) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return 'error'
  if (state === 'due-soon')
    return 'warning'

  return 'secondary'
}

const resolveSlaChipLabel = (ticket: any) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return t('crm.shared.sla.overdue')
  if (state === 'due-soon')
    return t('crm.shared.sla.dueSoon')

  return t('crm.shared.sla.onTrack')
}

const syncTiketQuery = useDebounceFn(() => {
  const nextQuery = {
    ...route.query,
    status: selectedStatus.value || undefined,
    category: selectedCategory.value || undefined,
    ownership: selectedOwnership.value || undefined,
    sort: selectedSort.value !== 'latest' ? selectedSort.value : undefined,
  }

  router.replace({ query: nextQuery })
}, 180)

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbar.value = {
    visible: true,
    text,
    color,
  }
}

const applyRecommendedTicketDefaults = () => {
  if (!createTicketForm.value.pelangganId)
    return

  createTicketForm.value = {
    ...createTicketForm.value,
    assignedUserId: ticketRecommendation.value.assignedUserId,
    kategori: ticketRecommendation.value.kategori,
    prioritas: ticketRecommendation.value.prioritas,
    subjek: ticketRecommendation.value.subjek,
  }
}

const resetCreateTicketForm = () => {
  createTicketForm.value = {
    pelangganId: highlightedCustomerId.value,
    assignedUserId: null,
    kategori: 'general',
    subjek: '',
    prioritas: 'sedang',
    isiPesan: '',
  }
  createError.value = ''
  applyRecommendedTicketDefaults()
}

const openCreateTicketDialog = () => {
  if (!canCreateTickets.value)
    return

  resetCreateTicketForm()
  isCreateDialogVisible.value = true
}

const getErrorMessage = (error: unknown, fallback: string) => {
  if (typeof error === 'object' && error && 'data' in error) {
    const message = (error as any).data?.message
    if (typeof message === 'string' && message)
      return message
  }

  if (error instanceof Error && error.message)
    return error.message

  return fallback
}

const openReplyDialog = (ticket: any) => {
  if (!canUpdateTickets.value)
    return

  activeTicket.value = ticket
  replyForm.value = {
    status: ticket.status,
    isiPesan: '',
  }
  isDialogVisible.value = true
}

const isHighlightedTicket = (ticketId: number) => highlightedTicketId.value === ticketId

const syncTicketContextFromQuery = () => {
  if (getQueryValue(route.query.create) === '1' && canCreateTickets.value && !isCreateDialogVisible.value) {
    openCreateTicketDialog()

    router.replace({
      query: {
        ...route.query,
        create: undefined,
      },
    })
  }

  const ticketId = highlightedTicketId.value

  if (!ticketId)
    return

  const matchedTicket = tiket.value.find((ticket: any) => ticket.id === ticketId)

  if (!matchedTicket)
    return

  if (getQueryValue(route.query.reply) === '1' && canUpdateTickets.value && !isDialogVisible.value) {
    openReplyDialog(matchedTicket)

    router.replace({
      query: {
        ...route.query,
        reply: undefined,
      },
    })
  }
}

const createTicket = async () => {
  if (!canCreateTickets.value)
    return

  isCreatingTicket.value = true
  createError.value = ''

  try {
    const response = await $api<any>('/crm/tiket', {
      method: 'POST',
      body: createTicketForm.value,
    })

    isCreateDialogVisible.value = false
    await fetchTiket()
    showSnackbar(t('crm.tickets.snackbar.createSuccess'))

    if (response?.tiket?.id) {
      router.replace({
        query: {
          ...route.query,
          ticket: String(response.tiket.id),
          customer: undefined,
        },
      })
    }
  }
  catch (error) {
    createError.value = getErrorMessage(error, t('crm.tickets.snackbar.createError'))
  }
  finally {
    isCreatingTicket.value = false
  }
}

const submitReply = async () => {
  if (!activeTicket.value || !canUpdateTickets.value)
    return

  isSubmitting.value = true

  try {
    await $api(`/crm/tiket/${activeTicket.value.id}/balas`, {
      method: 'POST',
      body: replyForm.value,
    })

    isDialogVisible.value = false
    await fetchTiket()
    showSnackbar(t('crm.tickets.snackbar.replySuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.tickets.snackbar.replyError')), 'error')
  }
  finally {
    isSubmitting.value = false
  }
}

const assignTicket = async (ticketId: number, assignedUserId: number | null) => {
  if (!canUpdateTickets.value)
    return

  isAssigningTicketId.value = ticketId

  try {
    await $api(`/crm/tiket/${ticketId}/assign`, {
      method: 'PATCH',
      body: { assignedUserId },
    })

    await fetchTiket()
    showSnackbar(t('crm.tickets.snackbar.assignSuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.tickets.snackbar.assignError')), 'error')
  }
  finally {
    isAssigningTicketId.value = null
  }
}

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return formatter.format(new Date(value))
}

const resolveActivityIcon = (type: string) => {
  if (type === 'ticket_created')
    return 'tabler-ticket'
  if (type === 'status_changed')
    return 'tabler-refresh'
  if (type === 'assignment_changed')
    return 'tabler-user-check'
  if (type === 'customer_reply')
    return 'tabler-send'
  if (type === 'internal_note')
    return 'tabler-notes'

  return 'tabler-bolt'
}

const resolveActivityColor = (type: string) => {
  if (type === 'ticket_created')
    return 'primary'
  if (type === 'status_changed')
    return 'warning'
  if (type === 'assignment_changed')
    return 'info'
  if (type === 'customer_reply')
    return 'success'
  if (type === 'internal_note')
    return 'secondary'

  return 'secondary'
}

watch([selectedStatus, selectedCategory], () => {
  syncTicketFilters()
}, { immediate: true })

watch(() => createTicketForm.value.pelangganId, customerId => {
  if (!customerId || !isCreateDialogVisible.value)
    return

  applyRecommendedTicketDefaults()
})

watch([selectedStatus, selectedCategory, selectedOwnership, selectedSort], () => {
  syncTiketQuery()
}, { immediate: true })

watch(tiket, () => {
  syncTicketContextFromQuery()
}, { immediate: true })
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VAlert
      v-if="ticketWorkspaceAccessMessage"
      color="warning"
      variant="tonal"
    >
      {{ ticketWorkspaceAccessMessage }}
    </VAlert>

    <VCard>
      <VCardText class="d-flex flex-column flex-md-row gap-4 justify-space-between align-md-center">
        <div>
          <h4 class="text-h4 mb-1">
            {{ t('crm.tickets.title') }}
          </h4>
          <p class="mb-0 text-body-1">
            {{ t('crm.tickets.subtitle') }}
          </p>
        </div>

        <div class="d-flex flex-column gap-3 w-100 w-md-auto">
          <VBtn
            v-if="canCreateTickets"
            color="primary"
            prepend-icon="tabler-plus"
            @click="openCreateTicketDialog"
          >
            {{ t('crm.tickets.createButton') }}
          </VBtn>

          <div>
            <div class="text-xs text-disabled mb-2">{{ t('crm.tickets.sections.sort') }}</div>
            <VChipGroup v-model="selectedSort" selected-class="text-primary" mandatory>
              <VChip
                v-for="option in ticketSortOptions"
                :key="`sort-${option.value}`"
                :value="option.value"
                :color="resolveSortChipColor(option.value)"
                size="small"
                label
                filter
                variant="tonal"
              >
                {{ option.title }}
              </VChip>
            </VChipGroup>
          </div>

          <div>
            <div class="text-xs text-disabled mb-2">{{ t('crm.tickets.sections.status') }}</div>
            <VChipGroup v-model="selectedStatus" selected-class="text-primary" mandatory>
              <VChip
                v-for="option in ticketStatusOptions"
                :key="`status-${option.value || 'all'}`"
                :value="option.value"
                :color="resolveStatusFilterColor(option.value)"
                size="small"
                label
                filter
                variant="tonal"
              >
                {{ option.title }} ({{ getTicketStatusCount(option.value) }})
              </VChip>
            </VChipGroup>
          </div>

          <div>
            <div class="text-xs text-disabled mb-2">{{ t('crm.tickets.sections.category') }}</div>
            <VChipGroup v-model="selectedCategory" selected-class="text-primary" mandatory>
              <VChip
                v-for="option in ticketCategoryOptions"
                :key="`category-${option.value || 'all'}`"
                :value="option.value"
                :color="resolveCategoryFilterColor(option.value)"
                size="small"
                label
                filter
                variant="tonal"
              >
                {{ option.title }} ({{ getTicketCategoryCount(option.value) }})
              </VChip>
            </VChipGroup>
          </div>

          <div>
            <div class="text-xs text-disabled mb-2">{{ t('crm.tickets.sections.ownership') }}</div>
            <VChipGroup v-model="selectedOwnership" selected-class="text-primary" mandatory>
              <VChip
                v-for="option in ownershipOptions"
                :key="`ownership-${option.value || 'all'}`"
                :value="option.value"
                size="small"
                label
                filter
                variant="tonal"
              >
                {{ option.title }} ({{ getTicketOwnershipCount(option.value) }})
              </VChip>
            </VChipGroup>
          </div>

          <VBtn
            color="primary"
            variant="tonal"
            prepend-icon="tabler-refresh"
            @click="fetchTiket()"
          >
            {{ t('crm.tickets.refreshButton') }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VRow>
      <VCol
        v-for="item in tiket"
        :key="item.id"
        cols="12"
      >
        <VCard>
          <VCardText class="d-flex flex-column gap-4" :class="{ 'ticket-card-highlight': isHighlightedTicket(item.id) }">
            <div class="d-flex flex-column flex-lg-row justify-space-between gap-4">
              <div>
                <div class="d-flex align-center flex-wrap gap-2 mb-2">
                  <VChip color="primary" variant="tonal" size="small">
                    {{ item.kode }}
                  </VChip>
                  <VChip v-if="isHighlightedTicket(item.id)" color="success" variant="tonal" size="small">
                    {{ t('crm.tickets.fromInbox') }}
                  </VChip>
                  <VChip color="secondary" variant="tonal" size="small">
                    {{ resolveCategoryLabel(item.kategori) }}
                  </VChip>
                  <VChip v-if="resolveSlaState(item)" :color="resolveSlaChipColor(item)" variant="tonal" size="small">
                    {{ resolveSlaChipLabel(item) }}
                  </VChip>
                  <VChip color="warning" variant="tonal" size="small">
                    {{ resolveStatusLabel(item.status) }}
                  </VChip>
                  <VChip color="error" variant="tonal" size="small">
                    {{ resolvePriorityLabel(item.prioritas) }}
                  </VChip>
                </div>
                <div class="font-weight-medium text-high-emphasis mb-1">
                  {{ item.subjek || t('crm.tickets.noSubject') }}
                </div>
                <div class="font-weight-medium text-high-emphasis">
                  {{ item.pelanggan?.nama || '-' }}
                </div>
                <div class="text-sm text-medium-emphasis">
                  {{ item.pelanggan?.email || '-' }}
                </div>
                <div class="text-sm text-medium-emphasis mt-2">
                  {{ t('crm.tickets.assignee.label') }}: {{ item.assignedUser?.fullName || t('crm.tickets.assignee.unassigned') }}
                </div>
              </div>

              <div class="text-sm text-medium-emphasis">
                {{ t('crm.tickets.slaLabel', { date: formatDate(item.batasSla) }) }}
              </div>
            </div>

            <VDivider />

            <div class="d-flex flex-column gap-3">
              <div class="d-flex flex-column flex-md-row align-md-center gap-3">
                <div class="text-sm text-medium-emphasis" style="min-inline-size: 8rem;">
                  {{ t('crm.tickets.assignee.label') }}
                </div>
                <AppSelect
                  v-if="canUpdateTickets"
                  :model-value="item.assignedUser?.id ?? null"
                  :items="assigneeOptions"
                  :placeholder="t('crm.tickets.assignee.placeholder')"
                  :loading="isAssigningTicketId === item.id"
                  @update:model-value="assignTicket(item.id, $event as number | null)"
                />
                <div v-else class="text-body-2 text-medium-emphasis">
                  {{ item.assignedUser?.fullName || t('crm.tickets.assignee.unassigned') }}
                </div>
              </div>

              <VCard variant="tonal" color="secondary">
                <VCardItem :title="t('crm.tickets.activity.title')" />
                <VCardText>
                  <div v-if="item.activities?.length" class="ticket-activity-list d-flex flex-column gap-3">
                    <div
                      v-for="activity in item.activities"
                      :key="`ticket-activity-${item.id}-${activity.id}`"
                      class="ticket-activity-item d-flex align-start gap-3"
                    >
                      <VAvatar size="32" :color="resolveActivityColor(activity.type)" variant="tonal">
                        <VIcon size="16" :icon="resolveActivityIcon(activity.type)" />
                      </VAvatar>

                      <div class="flex-grow-1 min-w-0">
                        <div class="text-body-2 text-high-emphasis font-weight-medium">{{ activity.title }}</div>
                        <div v-if="activity.description" class="text-body-2 text-medium-emphasis mt-1">{{ activity.description }}</div>
                        <div class="text-xs text-disabled mt-1">{{ activity.user?.fullName || t('crm.tickets.activity.system') }} • {{ formatDate(activity.createdAt) }}</div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-body-2 text-medium-emphasis">
                    {{ t('crm.tickets.activity.empty') }}
                  </div>
                </VCardText>
              </VCard>

              <div
                v-for="message in item.pesan"
                :key="message.id"
                class="rounded border pa-4"
              >
                <div class="d-flex flex-column flex-sm-row justify-space-between gap-2 mb-2">
                  <div class="font-weight-medium text-high-emphasis">
                    {{ message.pengirim }}
                  </div>
                  <div class="text-sm text-medium-emphasis">
                    {{ formatDate(message.createdAt) }}
                  </div>
                </div>
                <div class="text-sm text-medium-emphasis mb-2">
                  {{ message.channel }}
                </div>
                <div class="text-body-1">
                  {{ message.isiPesan }}
                </div>
              </div>
            </div>

            <div class="d-flex justify-end">
              <VBtn
                v-if="canUpdateTickets"
                color="primary"
                prepend-icon="tabler-message-reply"
                @click="openReplyDialog(item)"
              >
                {{ t('crm.tickets.replyButton') }}
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        v-if="!tiket.length"
        cols="12"
      >
        <VCard>
          <VCardText class="text-center text-medium-emphasis">
            {{ t('crm.tickets.empty') }}
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VDialog
      v-model="isDialogVisible"
      max-width="640"
    >
      <VCard>
        <VCardItem :title="t('crm.tickets.replyDialog.title', { code: activeTicket?.kode || t('crm.tickets.replyDialog.fallbackTitle') })" />
        <VCardText>
          <VRow>
            <VCol cols="12">
              <AppSelect
                v-model="replyForm.status"
                :label="t('crm.tickets.replyDialog.statusLabel')"
                :items="[
                  { title: t('crm.shared.statuses.new'), value: 'baru' },
                  { title: t('crm.shared.statuses.inProgress'), value: 'diproses' },
                  { title: t('crm.shared.statuses.done'), value: 'selesai' },
                ]"
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea
                v-model="replyForm.isiPesan"
                :label="t('crm.tickets.replyDialog.messageLabel')"
                :placeholder="t('crm.tickets.replyDialog.messagePlaceholder')"
                rows="5"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isDialogVisible = false"
          >
            {{ t('common.cancel') }}
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitting"
            :disabled="!canUpdateTickets"
            @click="submitReply"
          >
            {{ t('crm.tickets.replyDialog.submit') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isCreateDialogVisible"
      max-width="720"
    >
      <VCard>
        <VCardItem :title="t('crm.tickets.createDialog.title')" />
        <VCardText>
          <VAlert
            v-if="createTicketForm.pelangganId && ticketRecommendation.reason"
            color="primary"
            variant="tonal"
            class="mb-4"
          >
            <div class="d-flex flex-column gap-2">
              <div class="font-weight-medium">{{ t('crm.tickets.recommendation.title') }}</div>
              <div class="text-body-2">{{ ticketRecommendation.reason }}</div>
              <div class="d-flex flex-wrap gap-2">
                <VChip size="small" color="secondary" variant="tonal">
                  {{ resolveCategoryLabel(ticketRecommendation.kategori) }}
                </VChip>
                <VChip size="small" color="warning" variant="tonal">
                  {{ resolvePriorityLabel(ticketRecommendation.prioritas) }}
                </VChip>
                <VChip size="small" color="info" variant="tonal">
                  {{ ticketRecommendation.assigneeLabel }}
                </VChip>
              </div>
              <div class="text-body-2 text-medium-emphasis">{{ ticketRecommendation.assigneeReason }}</div>
            </div>
          </VAlert>

          <VRow>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="createTicketForm.pelangganId"
                :label="t('crm.tickets.createDialog.customerLabel')"
                :items="pelangganOptions"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="createTicketForm.assignedUserId"
                :label="t('crm.tickets.assignee.label')"
                :items="assigneeOptions"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="createTicketForm.kategori"
                :label="t('crm.tickets.createDialog.categoryLabel')"
                :items="createCategoryOptions"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="createTicketForm.prioritas"
                :label="t('crm.tickets.createDialog.priorityLabel')"
                :items="ticketPriorityOptions"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="createTicketForm.subjek"
                :label="t('crm.tickets.createDialog.subjectLabel')"
                :placeholder="t('crm.tickets.createDialog.subjectPlaceholder')"
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea
                v-model="createTicketForm.isiPesan"
                :label="t('crm.tickets.createDialog.messageLabel')"
                :placeholder="t('crm.tickets.createDialog.messagePlaceholder')"
                rows="5"
              />
            </VCol>
            <VCol v-if="createError" cols="12">
              <VAlert color="error" variant="tonal" :text="createError" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isCreateDialogVisible = false"
          >
            {{ t('common.cancel') }}
          </VBtn>
          <VBtn
            color="primary"
            :loading="isCreatingTicket"
            :disabled="!canCreateTickets"
            @click="createTicket"
          >
            {{ t('crm.tickets.createDialog.submit') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar.visible"
      :color="snackbar.color"
      location="top end"
      timeout="2600"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </section>
</template>

<style lang="scss">
.v-slide-group__content {
  gap: 0.5rem;
}

.ticket-card-highlight {
  border: 1px solid rgba(var(--v-theme-primary), 0.32);
  border-radius: 1rem;
  background: rgba(var(--v-theme-primary), 0.04);
}

.ticket-activity-list {
  .ticket-activity-item + .ticket-activity-item {
    padding-block-start: 0.25rem;
    border-top: 1px dashed rgba(var(--v-border-color), 0.6);
  }
}
</style>