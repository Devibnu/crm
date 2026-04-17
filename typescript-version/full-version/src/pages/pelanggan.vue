<script setup lang="ts">
definePage({
  meta: {
    action: 'read',
    subject: 'CrmCustomers',
  },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const ability = useAbility()
const profileSectionRef = ref()

interface TimelineSection {
  key: string
  label: string
  items: OmnichannelTimelineEntry[]
}

interface OmnichannelTimelineEntry {
  id: string
  source: 'customer' | 'ticket' | 'whatsapp' | 'email'
  title: string
  description: string
  eventAt: string | null
  actor: string
  targetType: 'ticket' | 'whatsapp' | 'email' | null
  targetValue?: number | string | null
  meta?: Record<string, unknown> | null
}

interface CustomerSummary {
  id: number
  nama: string
  name?: string
  email: string | null
  noHp: string | null
  status: 'active' | 'inactive'
  source: 'manual' | 'inbox' | 'import' | 'form' | 'campaign' | null
  notes?: string | null
  jumlahTiket: number
  activeTicketCount?: number
  lastActivityAt?: string | null
  createdAt: string | null
}

interface CustomerIdentity {
  id: number
  type: 'email' | 'phone' | 'whatsapp'
  value: string
  label: string | null
  isPrimary: boolean
  isVerified: boolean
  createdAt: string | null
  updatedAt: string | null
}

interface CustomerTimelineEvent {
  id: number
  type: string
  title: string
  description: string | null
  eventAt: string | null
  meta: Record<string, unknown> | null
  user: {
    id: number
    fullName: string
    email: string
  } | null
}

interface CustomerDetail extends CustomerSummary {
  ticketSummary: {
    total: number
    active: number
    lastActivityAt: string | null
  }
  identities: CustomerIdentity[]
  recentTickets: Array<{
    id: number
    kode: string
    kategori: string
    subjek: string | null
    status: string
    prioritas: string
    batasSla: string | null
    lastMessage: string | null
    lastMessageAt: string | null
  }>
  timeline: CustomerTimelineEvent[]
}

interface TicketSummary {
  id: number
  kode: string
  kategori: string
  subjek: string | null
  status: string
  prioritas: string
  batasSla: string | null
  pelanggan: {
    id: number | null
    nama: string | null
    email: string | null
  }
  pesan: Array<{
    id: number
    channel: string
    isiPesan: string
    pengirim: string
    createdAt: string | null
  }>
}

const searchQuery = ref('')
const selectedStatus = ref<'all' | 'active' | 'inactive'>('all')
const selectedSource = ref<'all' | 'manual' | 'inbox' | 'import' | 'form' | 'campaign'>('all')
const activeDirectorySegment = ref<'all' | 'active' | 'followUp'>('all')
const activeWorkspaceTab = ref<'activity' | 'tickets'>('activity')
const activeTimelineSource = ref<'all' | OmnichannelTimelineEntry['source']>('all')
const isDialogVisible = ref(false)
const isIdentityDialogVisible = ref(false)
const isSaving = ref(false)
const isIdentitySaving = ref(false)
const isCustomerDetailLoading = ref(false)
const customerDetail = ref<CustomerDetail | null>(null)
const customerFormMode = ref<'create' | 'edit'>('create')
const formError = ref('')
const identityError = ref('')
const selectedCustomerId = ref<number | null>(null)
const form = ref({
  nama: '',
  email: '',
  noHp: '',
  status: 'active' as 'active' | 'inactive',
  source: 'manual' as 'manual' | 'inbox' | 'import' | 'form' | 'campaign',
  notes: '',
})
const identityForm = ref({
  type: 'whatsapp' as 'email' | 'phone' | 'whatsapp',
  value: '',
  label: '',
  isPrimary: false,
  isVerified: false,
})

const getQueryValue = (value: unknown) => typeof value === 'string' ? value : ''

const getCustomerFromQuery = () => {
  const value = Number(getQueryValue(route.query.customer))

  return Number.isFinite(value) && value > 0 ? value : null
}

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const statusOptions = [
  { title: t('crm.customers.filters.allStatus'), value: 'all' },
  { title: t('crm.customers.status.active'), value: 'active' },
  { title: t('crm.customers.status.inactive'), value: 'inactive' },
]

const sourceOptions = [
  { title: t('crm.customers.filters.allSource'), value: 'all' },
  { title: t('crm.customers.source.manual'), value: 'manual' },
  { title: t('crm.customers.source.inbox'), value: 'inbox' },
  { title: t('crm.customers.source.import'), value: 'import' },
  { title: t('crm.customers.source.form'), value: 'form' },
  { title: t('crm.customers.source.campaign'), value: 'campaign' },
]

const identityTypeOptions = [
  { title: t('crm.customers.identity.types.email'), value: 'email' },
  { title: t('crm.customers.identity.types.phone'), value: 'phone' },
  { title: t('crm.customers.identity.types.whatsapp'), value: 'whatsapp' },
]

const { data: pelangganData, execute: fetchPelanggan } = await useApi<{ pelanggan: CustomerSummary[] }>(createUrl('/crm/pelanggan', {
  query: {
    q: searchQuery,
    status: () => selectedStatus.value === 'all' ? undefined : selectedStatus.value,
    source: () => selectedSource.value === 'all' ? undefined : selectedSource.value,
  },
}))

const pelanggan = computed(() => pelangganData.value?.pelanggan ?? [])
const highlightedCustomerId = computed(() => getCustomerFromQuery())

const followUpCustomers = computed(() => pelanggan.value.filter(customer => (customer.activeTicketCount ?? 0) > 0))

const directorySegments = computed(() => ([
  {
    title: t('crm.customers.directory.segments.all'),
    value: 'all',
    count: pelanggan.value.length,
  },
  {
    title: t('crm.customers.directory.segments.active'),
    value: 'active',
    count: pelanggan.value.filter(customer => customer.status === 'active').length,
  },
  {
    title: t('crm.customers.directory.segments.followUp'),
    value: 'followUp',
    count: followUpCustomers.value.length,
  },
]))

const filteredCustomers = computed(() => {
  if (activeDirectorySegment.value === 'active')
    return pelanggan.value.filter(customer => customer.status === 'active')

  if (activeDirectorySegment.value === 'followUp')
    return followUpCustomers.value

  return pelanggan.value
})

const selectedCustomer = computed(() => {
  const id = selectedCustomerId.value ?? highlightedCustomerId.value

  if (id) {
    const matched = filteredCustomers.value.find(customer => customer.id === id)
    if (matched)
      return matched
  }

  return filteredCustomers.value[0] ?? null
})

const selectedCustomerTickets = computed(() => customerDetail.value?.recentTickets ?? [])
const selectedCustomerActiveTickets = computed(() => customerDetail.value?.ticketSummary.active ?? selectedCustomer.value?.activeTicketCount ?? 0)
const selectedCustomerLastActivity = computed(() => customerDetail.value?.ticketSummary.lastActivityAt ?? selectedCustomer.value?.lastActivityAt ?? null)
const selectedCustomerIdentityCount = computed(() => customerDetail.value?.identities.length ?? 0)
const selectedCustomerPrimaryIdentity = computed(() => customerDetail.value?.identities.find(identity => identity.isPrimary) ?? customerDetail.value?.identities[0] ?? null)
const isCustomerDataPlatformRoute = computed(() => route.path === '/marketing-automation/customer-data-platform')
const customerProfile360HeaderTitle = computed(() => t('crm.customers.profile360.headerTitle'))
const customerProfile360Subtitle = computed(() => t('crm.customers.profile360.subtitle'))
const customerStats = computed(() => {
  const total = pelanggan.value.length
  const active = pelanggan.value.filter(customer => customer.status === 'active').length
  const inactive = total - active
  const tickets = pelanggan.value.reduce((sum, customer) => sum + customer.jumlahTiket, 0)

  return {
    total,
    active,
    inactive,
    tickets,
  }
})

const omnichannelTimeline = computed<OmnichannelTimelineEntry[]>(() => {
  const customerEvents: OmnichannelTimelineEntry[] = (customerDetail.value?.timeline ?? []).map(event => ({
    id: `customer-${event.id}`,
    source: event.meta?.source === 'email'
      ? 'email'
      : (event.meta?.source === 'whatsapp' ? 'whatsapp' : 'customer'),
    title: event.title,
    description: event.description || t('crm.customers.timeline.noDescription'),
    eventAt: event.eventAt,
    actor: event.user?.fullName || String(event.meta?.contactName || t('crm.customers.timeline.system')),
    targetType: event.meta?.source === 'email'
      ? 'email'
      : (event.meta?.source === 'whatsapp' ? 'whatsapp' : null),
    targetValue: event.meta?.source === 'email'
      ? String(event.meta?.emailAddress || '')
      : (event.meta?.source === 'whatsapp' ? String(event.meta?.phone || '') : null),
    meta: event.meta,
  }))

  const ticketEvents: OmnichannelTimelineEntry[] = selectedCustomerTickets.value.map(ticket => ({
    id: `ticket-${ticket.id}`,
    source: 'ticket',
    title: `${ticket.kode} • ${resolveStatusLabel(ticket.status)}`,
    description: ticket.subjek || ticket.lastMessage || t('crm.tickets.noSubject'),
    eventAt: ticket.lastMessageAt || ticket.batasSla || selectedCustomerLastActivity.value,
    actor: t('crm.customers.timeline.sources.ticket'),
    targetType: 'ticket',
    targetValue: ticket.id,
    meta: {
      ticketCode: ticket.kode,
      status: ticket.status,
    },
  }))

  return [...customerEvents, ...ticketEvents]
    .sort((left, right) => new Date(right.eventAt || 0).getTime() - new Date(left.eventAt || 0).getTime())
})

const timelineSourceFilters = computed(() => {
  const options: Array<{ title: string, value: 'all' | OmnichannelTimelineEntry['source'], count: number }> = [
    {
      title: t('crm.customers.timeline.filters.all'),
      value: 'all',
      count: omnichannelTimeline.value.length,
    },
    {
      title: t('crm.customers.timeline.sources.whatsapp'),
      value: 'whatsapp',
      count: omnichannelTimeline.value.filter(item => item.source === 'whatsapp').length,
    },
    {
      title: t('crm.customers.timeline.sources.email'),
      value: 'email',
      count: omnichannelTimeline.value.filter(item => item.source === 'email').length,
    },
    {
      title: t('crm.customers.timeline.sources.ticket'),
      value: 'ticket',
      count: omnichannelTimeline.value.filter(item => item.source === 'ticket').length,
    },
    {
      title: t('crm.customers.timeline.sources.customer'),
      value: 'customer',
      count: omnichannelTimeline.value.filter(item => item.source === 'customer').length,
    },
  ]

  return options.filter(option => option.value === 'all' || option.count > 0)
})

const filteredOmnichannelTimeline = computed(() => {
  if (activeTimelineSource.value === 'all')
    return omnichannelTimeline.value

  return omnichannelTimeline.value.filter(item => item.source === activeTimelineSource.value)
})

const timelineSections = computed<TimelineSection[]>(() => {
  const sections: TimelineSection[] = []
  const sectionMap = new Map<string, TimelineSection>()

  filteredOmnichannelTimeline.value.forEach(item => {
    const dateKey = item.eventAt ? item.eventAt.slice(0, 10) : 'undated'
    const existing = sectionMap.get(dateKey)

    if (existing) {
      existing.items.push(item)

      return
    }

    const section: TimelineSection = {
      key: dateKey,
      label: resolveTimelineSectionLabel(item.eventAt),
      items: [item],
    }

    sectionMap.set(dateKey, section)
    sections.push(section)
  })

  return sections
})

const customerProfile360Cards = computed(() => {
  const preferredIdentityType = selectedCustomerPrimaryIdentity.value?.type
  const preferredChannel = preferredIdentityType === 'email'
    ? t('crm.customers.identity.types.email')
    : preferredIdentityType === 'phone'
      ? t('crm.customers.identity.types.phone')
      : preferredIdentityType === 'whatsapp'
        ? t('crm.customers.identity.types.whatsapp')
        : t('crm.customers.profile360.fallback')

  return [
    {
      key: 'identities',
      title: t('crm.customers.profile360.identities.title'),
      value: String(selectedCustomerIdentityCount.value),
      description: selectedCustomerPrimaryIdentity.value?.value || t('crm.customers.profile360.identities.empty'),
      icon: 'tabler-id-badge-2',
      color: 'primary',
    },
    {
      key: 'interactions',
      title: t('crm.customers.profile360.interactions.title'),
      value: String(omnichannelTimeline.value.length),
      description: selectedCustomerLastActivity.value
        ? t('crm.customers.profile360.interactions.description', { date: formatDate(selectedCustomerLastActivity.value) })
        : t('crm.customers.profile360.interactions.empty'),
      icon: 'tabler-activity-heartbeat',
      color: 'info',
    },
    {
      key: 'transactions',
      title: t('crm.customers.profile360.transactions.title'),
      value: String(customerDetail.value?.ticketSummary.total ?? selectedCustomer.value?.jumlahTiket ?? 0),
      description: t('crm.customers.profile360.transactions.description'),
      icon: 'tabler-receipt-2',
      color: 'warning',
    },
    {
      key: 'preferences',
      title: t('crm.customers.profile360.preferences.title'),
      value: preferredChannel,
      description: t('crm.customers.profile360.preferences.description', {
        source: selectedCustomer.value ? resolveCustomerSourceLabel(selectedCustomer.value.source) : t('crm.customers.profile360.fallback'),
      }),
      icon: 'tabler-adjustments-horizontal',
      color: 'success',
    },
  ]
})

const resolveCustomerRisk = (customer: CustomerSummary) => {
  if ((customer.activeTicketCount ?? 0) > 0) {
    return {
      label: t('crm.customers.risk.followUp'),
      color: 'warning',
    }
  }

  if (customer.status === 'inactive') {
    return {
      label: t('crm.customers.risk.inactive'),
      color: 'secondary',
    }
  }

  return {
    label: t('crm.customers.risk.healthy'),
    color: 'success',
  }
}

const openCustomerSegment = (segment: 'all' | 'active' | 'followUp') => {
  activeDirectorySegment.value = segment
}

const resetForm = () => {
  form.value = {
    nama: '',
    email: '',
    noHp: '',
    status: 'active',
    source: 'manual',
    notes: '',
  }
  formError.value = ''
}

const resetIdentityForm = () => {
  identityForm.value = {
    type: 'whatsapp',
    value: '',
    label: '',
    isPrimary: false,
    isVerified: false,
  }
  identityError.value = ''
}

const resolveStatusColor = (status: string) => {
  if (status === 'baru')
    return 'warning'
  if (status === 'diproses')
    return 'info'
  if (status === 'selesai')
    return 'success'

  return 'secondary'
}

const resolvePriorityColor = (priority: string) => {
  if (priority === 'tinggi')
    return 'error'
  if (priority === 'sedang')
    return 'warning'

  return 'success'
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

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return formatter.format(new Date(value))
}

const formatTimelineSectionDate = (value: Date) => {
  const sectionFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })

  return sectionFormatter.format(value)
}

const normalizePhoneValue = (value?: string | null) => value?.replace(/\D/g, '') ?? ''
const normalizeEmailValue = (value?: string | null) => value?.trim().toLowerCase() ?? ''

const getCustomerInitials = (name?: string | null) => {
  const segments = name?.trim().split(/\s+/).filter(Boolean) ?? []

  if (!segments.length)
    return 'C'

  return segments.slice(0, 2).map(segment => segment[0]?.toUpperCase() ?? '').join('')
}

const getTicketPreview = (ticket: CustomerDetail['recentTickets'][number]) => ticket.subjek || ticket.lastMessage || t('crm.tickets.noSubject')

const resolveCustomerStatusColor = (status: CustomerSummary['status']) => status === 'active' ? 'success' : 'secondary'
const resolveCustomerStatusLabel = (status: CustomerSummary['status']) => status === 'active' ? t('crm.customers.status.active') : t('crm.customers.status.inactive')
const resolveCustomerSourceLabel = (source: CustomerSummary['source']) => {
  if (source === 'inbox')
    return t('crm.customers.source.inbox')
  if (source === 'import')
    return t('crm.customers.source.import')
  if (source === 'form')
    return t('crm.customers.source.form')
  if (source === 'campaign')
    return t('crm.customers.source.campaign')

  return t('crm.customers.source.manual')
}

const resolveIdentityTypeLabel = (type: CustomerIdentity['type']) => {
  if (type === 'email')
    return t('crm.customers.identity.types.email')
  if (type === 'phone')
    return t('crm.customers.identity.types.phone')

  return t('crm.customers.identity.types.whatsapp')
}

const fetchCustomerDetail = async (customerId: number) => {
  isCustomerDetailLoading.value = true

  try {
    const response = await $api<{ data: CustomerDetail }>(`/crm/pelanggan/${customerId}`)
    customerDetail.value = response.data
  }
  finally {
    isCustomerDetailLoading.value = false
  }
}

const syncCustomerQuery = (customerId?: number | null) => {
  router.replace({
    query: {
      ...route.query,
      customer: customerId ? String(customerId) : undefined,
    },
  })
}

const openCustomer = (customerId: number) => {
  selectedCustomerId.value = customerId
  syncCustomerQuery(customerId)
  fetchCustomerDetail(customerId)
  nextTick(() => {
    profileSectionRef.value?.$el?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
  })
}

const openTicket = (ticketId: number, reply = false) => {
  if (!ability.can('read', 'CrmTickets'))
    return

  router.push({
    name: 'tiket',
    query: {
      ticket: String(ticketId),
      from: 'customers',
      reply: reply ? '1' : undefined,
    },
  })
}

const openTicketWorkspace = () => {
  if (!ability.can('read', 'CrmTickets'))
    return

  router.push({
    name: 'tiket',
    query: {
      customer: selectedCustomerId.value ? String(selectedCustomerId.value) : undefined,
      from: 'customers',
      create: '1',
    },
  })
}

const openInboxWorkspace = () => {
  if (!canOpenPreferredInbox.value)
    return

  const preferredIdentity = selectedCustomerPrimaryIdentity.value?.type
  const path = preferredIdentity === 'email'
    ? '/omnichannel/email-inbox'
    : '/omnichannel/whatsapp/inbox'
  const identityValue = preferredIdentity === 'email'
    ? normalizeEmailValue(selectedCustomerPrimaryIdentity.value?.value || selectedCustomer.value?.email)
    : normalizePhoneValue(selectedCustomerPrimaryIdentity.value?.value || selectedCustomer.value?.noHp)

  router.push({
    path,
    query: {
      customer: selectedCustomerId.value ? String(selectedCustomerId.value) : undefined,
      from: 'customers',
      customerName: selectedCustomer.value?.nama || undefined,
      identityType: preferredIdentity || undefined,
      identityValue: identityValue || undefined,
    },
  })
}

const openWhatsAppConversationWorkspace = () => {
  if (!ability.can('read', 'CrmWhatsapp'))
    return

  const identityValue = normalizePhoneValue(selectedCustomerPrimaryIdentity.value?.value || selectedCustomer.value?.noHp)

  router.push({
    path: '/omnichannel/whatsapp/inbox',
    query: {
      customer: selectedCustomerId.value ? String(selectedCustomerId.value) : undefined,
      from: 'customers',
      create: '1',
      customerName: selectedCustomer.value?.nama || undefined,
      identityType: 'whatsapp',
      identityValue: identityValue || undefined,
    },
  })
}

const resolveTimelineSourceColor = (source: OmnichannelTimelineEntry['source']) => {
  if (source === 'ticket')
    return 'warning'
  if (source === 'whatsapp')
    return 'success'
  if (source === 'email')
    return 'info'

  return 'primary'
}

const resolveTimelineSourceLabel = (source: OmnichannelTimelineEntry['source']) => {
  if (source === 'ticket')
    return t('crm.customers.timeline.sources.ticket')
  if (source === 'whatsapp')
    return t('crm.customers.timeline.sources.whatsapp')
  if (source === 'email')
    return t('crm.customers.timeline.sources.email')

  return t('crm.customers.timeline.sources.customer')
}

const resolveTimelineDirectionLabel = (entry: OmnichannelTimelineEntry) => {
  if (entry.source !== 'email' && entry.source !== 'whatsapp')
    return null

  return entry.meta?.direction === 'outgoing'
    ? t('crm.customers.timeline.directions.outgoing')
    : t('crm.customers.timeline.directions.incoming')
}

const resolveTimelineDirectionColor = (entry: OmnichannelTimelineEntry) => {
  if (entry.source !== 'email' && entry.source !== 'whatsapp')
    return 'secondary'

  return entry.meta?.direction === 'outgoing' ? 'primary' : 'success'
}

const resolveTimelineContext = (entry: OmnichannelTimelineEntry) => {
  if (entry.source === 'email') {
    const emailAddress = String(entry.meta?.emailAddress || '')

    return emailAddress ? `${t('crm.customers.timeline.context.contact')} • ${emailAddress}` : null
  }

  if (entry.source === 'whatsapp') {
    const phone = String(entry.meta?.phone || '')
    const ticketId = entry.meta?.ticketId
    const ticketLabel = ticketId ? `${t('crm.customers.timeline.context.ticket')} #${ticketId}` : null

    if (phone && ticketLabel)
      return `${ticketLabel} • ${phone}`

    return phone || ticketLabel
  }

  if (entry.source === 'ticket') {
    const ticketCode = String(entry.meta?.ticketCode || '')

    return ticketCode ? `${t('crm.customers.timeline.context.ticket')} • ${ticketCode}` : null
  }

  return null
}

const resolveTimelineSectionLabel = (value?: string | null) => {
  if (!value)
    return t('crm.customers.timeline.sections.undated')

  const eventDate = new Date(value)

  if (Number.isNaN(eventDate.getTime()))
    return t('crm.customers.timeline.sections.undated')

  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)

  if (eventDate.toDateString() === today.toDateString())
    return t('crm.customers.timeline.sections.today')

  if (eventDate.toDateString() === yesterday.toDateString())
    return t('crm.customers.timeline.sections.yesterday')

  return formatTimelineSectionDate(eventDate)
}

const resolveTimelineDotStyle = (source: OmnichannelTimelineEntry['source']) => {
  if (source === 'ticket') {
    return {
      background: 'rgb(var(--v-theme-warning))',
      boxShadow: '0 0 0 6px rgba(var(--v-theme-warning), 0.12)',
    }
  }

  if (source === 'whatsapp') {
    return {
      background: 'rgb(var(--v-theme-success))',
      boxShadow: '0 0 0 6px rgba(var(--v-theme-success), 0.12)',
    }
  }

  if (source === 'email') {
    return {
      background: 'rgb(var(--v-theme-info))',
      boxShadow: '0 0 0 6px rgba(var(--v-theme-info), 0.12)',
    }
  }

  return {
    background: 'rgb(var(--v-theme-primary))',
    boxShadow: '0 0 0 6px rgba(var(--v-theme-primary), 0.12)',
  }
}

const openTimelineEntry = (entry: OmnichannelTimelineEntry) => {
  if (entry.targetType === 'ticket' && !ability.can('read', 'CrmTickets'))
    return

  if (entry.targetType === 'whatsapp' && !ability.can('read', 'CrmWhatsapp'))
    return

  if (entry.targetType === 'email' && !ability.can('read', 'CrmInbox'))
    return

  if (entry.targetType === 'ticket' && typeof entry.targetValue === 'number') {
    openTicket(entry.targetValue)

    return
  }

  if (entry.targetType === 'whatsapp') {
    router.push({
      path: '/omnichannel/whatsapp/inbox',
      query: {
        customer: selectedCustomerId.value ? String(selectedCustomerId.value) : undefined,
        from: 'customers',
        customerName: selectedCustomer.value?.nama || undefined,
        identityValue: String(entry.targetValue || ''),
      },
    })

    return
  }

  if (entry.targetType === 'email') {
    router.push({
      path: '/omnichannel/email-inbox',
      query: {
        customer: selectedCustomerId.value ? String(selectedCustomerId.value) : undefined,
        from: 'customers',
        customerName: selectedCustomer.value?.nama || undefined,
        identityType: 'email',
        identityValue: String(entry.targetValue || ''),
      },
    })
  }
}

const openCreateDialog = () => {
  if (!ability.can('create', 'CrmCustomers'))
    return

  customerFormMode.value = 'create'
  resetForm()
  isDialogVisible.value = true
}

const openEditDialog = () => {
  if (!customerDetail.value || !ability.can('update', 'CrmCustomers'))
    return

  customerFormMode.value = 'edit'
  form.value = {
    nama: customerDetail.value.nama,
    email: customerDetail.value.email ?? '',
    noHp: customerDetail.value.noHp ?? '',
    status: customerDetail.value.status,
    source: customerDetail.value.source ?? 'manual',
    notes: customerDetail.value.notes ?? '',
  }
  formError.value = ''
  isDialogVisible.value = true
}

const canViewTickets = computed(() => ability.can('read', 'CrmTickets'))
const canViewEmailInbox = computed(() => ability.can('read', 'CrmInbox'))
const canViewWhatsAppInbox = computed(() => ability.can('read', 'CrmWhatsapp'))
const canCreateCustomers = computed(() => ability.can('create', 'CrmCustomers'))
const canUpdateCustomers = computed(() => ability.can('update', 'CrmCustomers'))
const canCreateTickets = computed(() => ability.can('create', 'CrmTickets'))
const canUpdateTickets = computed(() => ability.can('update', 'CrmTickets'))
const customerWorkspaceAccessMessage = computed(() => {
  if (!canCreateCustomers.value && !canUpdateCustomers.value && !canCreateTickets.value && !canUpdateTickets.value && !canViewEmailInbox.value && !canViewWhatsAppInbox.value)
    return 'Mode baca saja. Anda dapat melihat Customer 360 dan timeline lintas kanal, tetapi tidak bisa mengubah profil, menambah identitas, atau membuat tindak lanjut baru.'

  if (!canCreateCustomers.value && !canUpdateCustomers.value)
    return 'Akses profil terbatas. Informasi customer tetap dapat dipantau, tetapi perubahan profil dan penambahan identity dinonaktifkan.'

  if (!canCreateTickets.value || !canUpdateTickets.value)
    return 'Akses ticket terbatas. Customer 360 tetap bisa dipakai sebagai konteks utama, tetapi pembuatan tiket baru atau aksi reply ticket mengikuti permission Anda.'

  return ''
})
const canOpenPreferredInbox = computed(() => {
  const preferredIdentity = selectedCustomerPrimaryIdentity.value?.type

  if (preferredIdentity === 'email')
    return canViewEmailInbox.value

  return canViewWhatsAppInbox.value
})

const canOpenTimelineEntry = (entry: OmnichannelTimelineEntry) => {
  if (entry.targetType === 'ticket')
    return canViewTickets.value
  if (entry.targetType === 'whatsapp')
    return canViewWhatsAppInbox.value
  if (entry.targetType === 'email')
    return canViewEmailInbox.value

  return false
}

const openIdentityDialog = () => {
  if (!canUpdateCustomers.value)
    return

  resetIdentityForm()
  isIdentityDialogVisible.value = true
}

const savePelanggan = async () => {
  if (customerFormMode.value === 'create' && !canCreateCustomers.value)
    return

  if (customerFormMode.value === 'edit' && !canUpdateCustomers.value)
    return

  isSaving.value = true
  formError.value = ''

  try {
    const url = customerFormMode.value === 'create'
      ? '/crm/pelanggan'
      : `/crm/pelanggan/${selectedCustomerId.value}`

    const response = await $api<{ data: CustomerSummary }> (url, {
      method: customerFormMode.value === 'create' ? 'POST' : 'PUT',
      body: form.value,
    })

    isDialogVisible.value = false
    resetForm()
    await fetchPelanggan()
    if (response?.data?.id)
      openCustomer(response.data.id)
  }
  catch (error: any) {
    formError.value = error?.data?.message || t('crm.customers.messages.saveError')
  }
  finally {
    isSaving.value = false
  }
}

const saveIdentity = async () => {
  if (!selectedCustomerId.value || !canUpdateCustomers.value)
    return

  isIdentitySaving.value = true
  identityError.value = ''

  try {
    await $api(`/crm/pelanggan/${selectedCustomerId.value}/identities`, {
      method: 'POST',
      body: identityForm.value,
    })

    isIdentityDialogVisible.value = false
    resetIdentityForm()
    await Promise.all([fetchPelanggan(), fetchCustomerDetail(selectedCustomerId.value)])
  }
  catch (error: any) {
    identityError.value = error?.data?.message || t('crm.customers.messages.identityError')
  }
  finally {
    isIdentitySaving.value = false
  }
}

watch(filteredCustomers, customers => {
  if (!customers.length)
    return

  const currentId = selectedCustomerId.value ?? highlightedCustomerId.value
  if (!currentId || !customers.some(customer => customer.id === currentId))
    selectedCustomerId.value = customers[0].id
}, { immediate: true })

watch(selectedCustomerId, customerId => {
  if (customerId)
    fetchCustomerDetail(customerId)
}, { immediate: true })
</script>

<template>
  <section class="customer-page d-flex flex-column gap-6">
    <VAlert
      v-if="customerWorkspaceAccessMessage"
      color="warning"
      variant="tonal"
    >
      {{ customerWorkspaceAccessMessage }}
    </VAlert>

    <VCard class="customer-hero-card">
      <VCardText class="pa-6 pa-md-8">
        <VRow class="align-center">
          <VCol cols="12" lg="7">
            <div class="text-sm text-primary font-weight-medium mb-2">{{ t('crm.customers.overline') }}</div>
            <h3 class="text-h3 text-high-emphasis mb-3">
              {{ isCustomerDataPlatformRoute ? customerProfile360HeaderTitle : t('crm.customers.title') }}
            </h3>
            <p class="text-body-1 text-medium-emphasis mb-6 customer-hero-copy">
              {{ isCustomerDataPlatformRoute ? customerProfile360Subtitle : t('crm.customers.subtitle') }}
            </p>

            <div class="d-flex flex-wrap gap-3">
              <div class="customer-stat-chip">
                <div class="text-h5 text-high-emphasis">{{ customerStats.total }}</div>
                <div class="text-sm text-medium-emphasis">{{ t('crm.customers.metrics.total') }}</div>
              </div>
              <div class="customer-stat-chip">
                <div class="text-h5 text-high-emphasis">{{ customerStats.active }}</div>
                <div class="text-sm text-medium-emphasis">{{ t('crm.customers.metrics.active') }}</div>
              </div>
              <div class="customer-stat-chip">
                <div class="text-h5 text-high-emphasis">{{ customerStats.tickets }}</div>
                <div class="text-sm text-medium-emphasis">{{ t('crm.customers.metrics.tickets') }}</div>
              </div>
            </div>
          </VCol>

          <VCol cols="12" lg="5">
            <div class="customer-command-card">
              <div class="d-flex flex-column flex-sm-row gap-3">
                <AppTextField
                  v-model="searchQuery"
                  prepend-inner-icon="tabler-search"
                  :placeholder="t('crm.customers.searchPlaceholder')"
                  class="flex-grow-1"
                />
                <VBtn
                  v-if="canCreateCustomers"
                  color="primary"
                  size="large"
                  prepend-icon="tabler-plus"
                  @click="openCreateDialog"
                >
                  {{ t('crm.customers.addButton') }}
                </VBtn>
              </div>

              <div class="d-flex flex-column flex-sm-row gap-3 mt-3">
                <AppSelect
                  v-model="selectedStatus"
                  :items="statusOptions"
                  class="flex-grow-1"
                />
                <AppSelect
                  v-model="selectedSource"
                  :items="sourceOptions"
                  class="flex-grow-1"
                />
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" lg="4">
        <VCard class="customer-directory-card">
          <VCardItem>
            <template #title>
              <div class="d-flex align-center justify-space-between gap-3 flex-wrap">
                <div>
                  <div class="text-h6">{{ t('crm.customers.directory.title') }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.directory.subtitle') }}</div>
                </div>
                <VChip size="small" color="primary" variant="tonal">
                  {{ customerStats.total }}
                </VChip>
              </div>
            </template>
          </VCardItem>

          <VCardText class="pt-0">
            <div class="d-flex flex-wrap gap-2 mb-4">
              <button
                v-for="segment in directorySegments"
                :key="segment.value"
                type="button"
                class="customer-segment-chip"
                :class="{ 'customer-segment-chip-active': activeDirectorySegment === segment.value }"
                @click="openCustomerSegment(segment.value as 'all' | 'active' | 'followUp')"
              >
                <span>{{ segment.title }}</span>
                <span class="customer-segment-count">{{ segment.count }}</span>
              </button>
            </div>

            <div v-if="filteredCustomers.length" class="d-flex flex-column gap-3 customer-directory-list">
              <button
                v-for="item in filteredCustomers"
                :key="item.id"
                type="button"
                class="customer-list-item text-start"
                :class="{ 'customer-list-item-active': selectedCustomer?.id === item.id }"
                @click="openCustomer(item.id)"
              >
                <div class="d-flex align-start gap-3">
                  <VAvatar size="46" color="primary" variant="tonal">
                    {{ getCustomerInitials(item.nama) }}
                  </VAvatar>

                  <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-start justify-space-between gap-3 flex-wrap mb-1">
                      <div>
                        <div class="font-weight-medium text-high-emphasis text-body-1 text-truncate">
                          {{ item.nama }}
                        </div>
                        <div class="text-body-2 text-medium-emphasis text-truncate">
                          {{ item.email || item.noHp || '-' }}
                        </div>
                      </div>

                      <VChip size="x-small" :color="resolveCustomerStatusColor(item.status)" variant="tonal">
                        {{ resolveCustomerStatusLabel(item.status) }}
                      </VChip>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <VChip size="x-small" color="info" variant="tonal">
                        {{ t('crm.customers.ticketCountChip', { count: item.jumlahTiket }) }}
                      </VChip>
                      <VChip size="x-small" color="secondary" variant="tonal">
                        {{ resolveCustomerSourceLabel(item.source) }}
                      </VChip>
                      <VChip size="x-small" :color="resolveCustomerRisk(item).color" variant="tonal">
                        {{ resolveCustomerRisk(item).label }}
                      </VChip>
                    </div>

                    <div class="d-flex flex-wrap gap-x-4 gap-y-2 text-caption text-medium-emphasis">
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-phone" size="14" />
                        <span>{{ item.noHp || '-' }}</span>
                      </div>
                      <div class="d-flex align-center gap-1">
                        <VIcon icon="tabler-clock-hour-4" size="14" />
                        <span>{{ formatDate(item.lastActivityAt || item.createdAt) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </button>
            </div>

            <VAlert
              v-else
              variant="tonal"
              color="secondary"
              icon="tabler-users"
              :text="t('crm.customers.empty')"
            />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" lg="8">
        <template v-if="selectedCustomer">
          <VCard ref="profileSectionRef" class="customer-profile-hero mb-6">
            <VCardText class="pa-6">
              <div class="d-flex flex-column flex-lg-row justify-space-between gap-6">
                <div class="d-flex align-start gap-4">
                  <VAvatar size="72" color="primary" variant="tonal">
                    {{ getCustomerInitials(selectedCustomer.nama) }}
                  </VAvatar>

                  <div>
                    <div class="text-overline text-primary mb-1">{{ t('crm.customers.profile.contactInfo') }}</div>
                    <div class="text-h4 mb-2">{{ selectedCustomer.nama }}</div>
                    <div class="text-body-1 text-medium-emphasis mb-3">
                      {{ selectedCustomerPrimaryIdentity?.value || selectedCustomer.email || selectedCustomer.noHp || '-' }}
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <VChip size="small" :color="resolveCustomerStatusColor(selectedCustomer.status)" variant="tonal">
                        {{ resolveCustomerStatusLabel(selectedCustomer.status) }}
                      </VChip>
                      <VChip size="small" color="secondary" variant="tonal">
                        {{ resolveCustomerSourceLabel(selectedCustomer.source) }}
                      </VChip>
                      <VChip
                        v-if="highlightedCustomerId === selectedCustomer.id && route.query.from === 'inbox'"
                        size="small"
                        color="success"
                        variant="tonal"
                      >
                        {{ t('crm.customers.fromInbox') }}
                      </VChip>
                    </div>

                    <div class="d-flex flex-wrap gap-x-6 gap-y-2 text-body-2 text-medium-emphasis">
                      <div class="d-flex align-center gap-2">
                        <VIcon icon="tabler-phone" size="18" />
                        <span>{{ selectedCustomer.noHp || '-' }}</span>
                      </div>
                      <div class="d-flex align-center gap-2">
                        <VIcon icon="tabler-calendar-time" size="18" />
                        <span>{{ t('crm.customers.profile.registeredAt', { date: formatDate(selectedCustomer.createdAt) }) }}</span>
                      </div>
                      <div class="d-flex align-center gap-2">
                        <VIcon icon="tabler-clock-hour-4" size="18" />
                        <span>{{ t('crm.customers.profile.lastActivity', { date: formatDate(selectedCustomerLastActivity) }) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex flex-column align-stretch gap-3 customer-profile-actions">
                  <VBtn v-if="canUpdateCustomers" color="secondary" variant="tonal" prepend-icon="tabler-edit" @click="openEditDialog">
                    {{ t('crm.customers.editButton') }}
                  </VBtn>
                  <VBtn v-if="canUpdateCustomers" color="primary" prepend-icon="tabler-plus" @click="openIdentityDialog">
                    {{ t('crm.customers.identity.addButton') }}
                  </VBtn>
                </div>
              </div>

              <div class="customer-quick-actions mt-6">
                <button v-if="canCreateTickets" type="button" class="customer-quick-action" @click="openTicketWorkspace">
                  <VIcon icon="tabler-ticket" size="20" />
                  <div>
                    <div class="font-weight-medium text-high-emphasis">{{ t('crm.customers.quickActions.ticket') }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.quickActions.ticketHint') }}</div>
                  </div>
                </button>

                <button v-if="canOpenPreferredInbox" type="button" class="customer-quick-action" @click="openInboxWorkspace">
                  <VIcon icon="tabler-message-circle" size="20" />
                  <div>
                    <div class="font-weight-medium text-high-emphasis">{{ t('crm.customers.quickActions.inbox') }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.quickActions.inboxHint') }}</div>
                  </div>
                </button>

                <button v-if="canViewWhatsAppInbox" type="button" class="customer-quick-action" @click="openWhatsAppConversationWorkspace">
                  <VIcon icon="tabler-brand-whatsapp" size="20" />
                  <div>
                    <div class="font-weight-medium text-high-emphasis">{{ t('crm.customers.quickActions.startWhatsapp') }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.quickActions.startWhatsappHint') }}</div>
                  </div>
                </button>
              </div>

              <div v-if="customerDetail?.notes" class="customer-notes-panel mt-6">
                <div class="text-sm text-medium-emphasis mb-1">{{ t('crm.customers.fields.notes') }}</div>
                <div class="text-body-2 text-high-emphasis">{{ customerDetail.notes }}</div>
              </div>
            </VCardText>
          </VCard>

          <VRow class="mb-6">
            <VCol cols="12" md="4">
              <VCard class="customer-metric-card h-100">
                <VCardText>
                  <div class="text-sm text-medium-emphasis mb-2">{{ t('crm.customers.metrics.totalTickets') }}</div>
                  <div class="text-h4 mb-2">{{ customerDetail?.ticketSummary.total ?? selectedCustomer.jumlahTiket }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.profile.totalTickets', { count: customerDetail?.ticketSummary.total ?? selectedCustomer.jumlahTiket }) }}</div>
                </VCardText>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard class="customer-metric-card h-100">
                <VCardText>
                  <div class="text-sm text-medium-emphasis mb-2">{{ t('crm.customers.metrics.activeTickets') }}</div>
                  <div class="text-h4 mb-2">{{ selectedCustomerActiveTickets }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ t('crm.customers.profile.activeTickets', { count: selectedCustomerActiveTickets }) }}</div>
                </VCardText>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard class="customer-metric-card h-100">
                <VCardText>
                  <div class="text-sm text-medium-emphasis mb-2">{{ t('crm.customers.metrics.identities') }}</div>
                  <div class="text-h4 mb-2">{{ selectedCustomerIdentityCount }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ selectedCustomerPrimaryIdentity?.value || '-' }}</div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <VCard class="mb-6 customer-360-overview-card">
            <VCardItem
              :title="isCustomerDataPlatformRoute ? customerProfile360HeaderTitle : t('crm.customers.title')"
              :subtitle="isCustomerDataPlatformRoute ? customerProfile360Subtitle : t('crm.customers.subtitle')"
            />
            <VCardText>
              <VRow>
                <VCol
                  v-for="card in customerProfile360Cards"
                  :key="card.key"
                  cols="12"
                  md="6"
                  xl="3"
                >
                  <VCard variant="tonal" class="h-100">
                    <VCardText>
                      <div class="d-flex justify-space-between align-start gap-3 mb-4">
                        <div>
                          <div class="text-sm text-medium-emphasis mb-1">{{ card.title }}</div>
                          <div class="text-h5 text-high-emphasis">{{ card.value }}</div>
                        </div>
                        <VAvatar :color="card.color" variant="tonal" rounded size="42">
                          <VIcon :icon="card.icon" size="22" />
                        </VAvatar>
                      </div>
                      <div class="text-body-2 text-medium-emphasis">{{ card.description }}</div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard class="mt-6 overflow-hidden">
            <VTabs
              v-model="activeWorkspaceTab"
              class="customer-workspace-tabs px-4 pt-3"
            >
              <VTab value="activity">{{ t('crm.customers.workspaceTabs.activity') }}</VTab>
              <VTab value="tickets">{{ t('crm.customers.workspaceTabs.tickets') }}</VTab>
            </VTabs>

            <VWindow v-model="activeWorkspaceTab" class="disable-tab-transition">
              <VWindowItem value="activity">
                <VCardText>
                  <VRow>
                    <VCol cols="12" xl="6">
                      <VCard class="h-100" variant="text">
                        <VCardItem :title="t('crm.customers.identity.title')" />
                        <VCardText>
                          <div v-if="isCustomerDetailLoading" class="text-body-2 text-medium-emphasis">
                            {{ t('crm.customers.messages.loadingDetail') }}
                          </div>
                          <div v-else-if="customerDetail?.identities?.length" class="d-flex flex-column gap-3">
                            <div v-for="identity in customerDetail.identities" :key="identity.id" class="customer-identity-card">
                              <div class="d-flex align-start justify-space-between gap-3 flex-wrap">
                                <div>
                                  <div class="text-body-1 font-weight-medium text-high-emphasis">{{ identity.value }}</div>
                                  <div class="text-body-2 text-medium-emphasis">{{ resolveIdentityTypeLabel(identity.type) }}<span v-if="identity.label"> • {{ identity.label }}</span></div>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                  <VChip v-if="identity.isPrimary" size="small" color="primary" variant="tonal">{{ t('crm.customers.identity.primary') }}</VChip>
                                  <VChip size="small" :color="identity.isVerified ? 'success' : 'warning'" variant="tonal">
                                    {{ identity.isVerified ? t('crm.customers.identity.verified') : t('crm.customers.identity.unverified') }}
                                  </VChip>
                                </div>
                              </div>
                            </div>
                          </div>
                          <VAlert v-else variant="tonal" color="secondary" :text="t('crm.customers.identity.empty')" />
                        </VCardText>
                      </VCard>
                    </VCol>

                    <VCol cols="12" xl="6">
                      <VCard class="h-100" variant="text">
                        <VCardItem :title="t('crm.customers.timeline.title')" />
                        <VCardText>
                          <div v-if="isCustomerDetailLoading" class="text-body-2 text-medium-emphasis">
                            {{ t('crm.customers.messages.loadingDetail') }}
                          </div>
                          <div v-else-if="omnichannelTimeline.length">
                            <div class="customer-timeline-toolbar mb-4">
                              <button
                                v-for="filter in timelineSourceFilters"
                                :key="filter.value"
                                type="button"
                                class="customer-timeline-filter"
                                :class="{ 'customer-timeline-filter-active': activeTimelineSource === filter.value }"
                                @click="activeTimelineSource = filter.value"
                              >
                                <span>{{ filter.title }}</span>
                                <span class="customer-timeline-filter-count">{{ filter.count }}</span>
                              </button>
                            </div>

                            <div v-if="timelineSections.length" class="customer-timeline-list">
                              <section v-for="section in timelineSections" :key="section.key" class="customer-timeline-section">
                                <div class="customer-timeline-section-label">{{ section.label }}</div>

                                <div class="customer-timeline-section-items">
                                  <div v-for="event in section.items" :key="event.id" class="customer-timeline-item">
                                    <div class="customer-timeline-dot" :style="resolveTimelineDotStyle(event.source)" />
                                    <div class="flex-grow-1">
                                      <div class="d-flex align-start justify-space-between gap-3 flex-wrap mb-1">
                                        <div>
                                          <div class="font-weight-medium text-high-emphasis">{{ event.title }}</div>
                                          <div class="d-flex flex-wrap gap-2 mt-2">
                                            <VChip size="x-small" :color="resolveTimelineSourceColor(event.source)" variant="tonal">
                                              {{ resolveTimelineSourceLabel(event.source) }}
                                            </VChip>
                                            <VChip v-if="resolveTimelineDirectionLabel(event)" size="x-small" :color="resolveTimelineDirectionColor(event)" variant="outlined">
                                              {{ resolveTimelineDirectionLabel(event) }}
                                            </VChip>
                                            <VBtn
                                              v-if="event.targetType && canOpenTimelineEntry(event)"
                                              size="x-small"
                                              variant="text"
                                              color="primary"
                                              @click="openTimelineEntry(event)"
                                            >
                                              {{ t('crm.customers.timeline.openAction') }}
                                            </VBtn>
                                          </div>
                                        </div>
                                        <div class="text-caption text-medium-emphasis">{{ formatDate(event.eventAt) }}</div>
                                      </div>
                                      <div class="text-body-2 text-medium-emphasis mb-2">{{ event.description || t('crm.customers.timeline.noDescription') }}</div>
                                      <div v-if="resolveTimelineContext(event)" class="text-caption text-medium-emphasis mb-1">{{ resolveTimelineContext(event) }}</div>
                                      <div class="text-caption text-medium-emphasis">{{ event.actor }}</div>
                                    </div>
                                  </div>
                                </div>
                              </section>
                            </div>
                            <VAlert v-else variant="tonal" color="secondary" :text="t('crm.customers.timeline.emptyFiltered')" />
                          </div>
                          <VAlert v-else variant="tonal" color="secondary" :text="t('crm.customers.timeline.empty')" />
                        </VCardText>
                      </VCard>
                    </VCol>
                  </VRow>
                </VCardText>
              </VWindowItem>

              <VWindowItem value="tickets">
                <VCardItem :title="t('crm.customers.profile.recentTickets')" />
                <VCardText>
                  <div v-if="isCustomerDetailLoading" class="text-body-2 text-medium-emphasis">
                    {{ t('crm.customers.messages.loadingDetail') }}
                  </div>
                  <div v-else-if="selectedCustomerTickets.length" class="d-flex flex-column gap-3">
                    <div
                      v-for="ticket in selectedCustomerTickets.slice(0, 4)"
                      :key="ticket.id"
                      class="customer-ticket-card"
                    >
                      <div class="d-flex align-start justify-space-between gap-3 flex-wrap mb-3">
                        <div>
                          <div class="font-weight-medium text-high-emphasis mb-1">{{ ticket.kode }}</div>
                          <div class="text-body-2 text-medium-emphasis">{{ getTicketPreview(ticket) }}</div>
                        </div>
                        <VChip size="small" :color="resolveStatusColor(ticket.status)" variant="tonal">
                          {{ resolveStatusLabel(ticket.status) }}
                        </VChip>
                      </div>

                      <div class="d-flex align-center justify-space-between gap-3 flex-wrap">
                        <div class="d-flex gap-2 flex-wrap">
                          <VChip size="x-small" color="secondary" variant="tonal">{{ ticket.kategori }}</VChip>
                          <VChip size="x-small" :color="resolvePriorityColor(ticket.prioritas)" variant="tonal">
                            {{ resolvePriorityLabel(ticket.prioritas) }}
                          </VChip>
                        </div>

                        <div class="d-flex gap-2">
                          <VBtn v-if="canViewTickets" size="small" variant="text" color="primary" @click="openTicket(ticket.id)">
                            {{ t('crm.customers.profile.openTicket') }}
                          </VBtn>
                          <VBtn v-if="canUpdateTickets" size="small" variant="text" color="secondary" @click="openTicket(ticket.id, true)">
                            {{ t('crm.customers.profile.replyTicket') }}
                          </VBtn>
                        </div>
                      </div>
                    </div>
                  </div>

                  <VAlert
                    v-else
                    variant="tonal"
                    color="secondary"
                    icon="tabler-ticket-off"
                    :text="t('crm.customers.profile.emptyTickets')"
                  />
                </VCardText>
              </VWindowItem>
            </VWindow>
          </VCard>
        </template>
      </VCol>
    </VRow>

    <VDialog v-model="isDialogVisible" max-width="520">
      <VCard>
        <VCardItem :title="customerFormMode === 'create' ? t('crm.customers.dialogTitle') : t('crm.customers.editDialogTitle')" />
        <VCardText>
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="form.nama"
                :label="t('common.name')"
                :placeholder="t('crm.customers.namePlaceholder')"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.email"
                :label="t('common.email')"
                type="email"
                :placeholder="t('crm.customers.emailPlaceholder')"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.noHp"
                :label="t('common.phone')"
                :placeholder="t('crm.customers.phonePlaceholder')"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect v-model="form.status" :items="statusOptions.slice(1)" :label="t('common.status')" />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect v-model="form.source" :items="sourceOptions.slice(1)" :label="t('crm.customers.fields.source')" />
            </VCol>
            <VCol cols="12">
              <AppTextarea v-model="form.notes" :label="t('crm.customers.fields.notes')" rows="3" />
            </VCol>
            <VCol v-if="formError" cols="12">
              <VAlert color="error" variant="tonal" :text="formError" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false; resetForm()">
            {{ t('common.cancel') }}
          </VBtn>
          <VBtn color="primary" :loading="isSaving" :disabled="customerFormMode === 'create' ? !canCreateCustomers : !canUpdateCustomers" @click="savePelanggan">
            {{ t('common.save') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="isIdentityDialogVisible" max-width="520">
      <VCard>
        <VCardItem :title="t('crm.customers.identity.dialogTitle')" />
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <AppSelect v-model="identityForm.type" :items="identityTypeOptions" :label="t('crm.customers.identity.fields.type')" />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField v-model="identityForm.label" :label="t('crm.customers.identity.fields.label')" />
            </VCol>
            <VCol cols="12">
              <AppTextField v-model="identityForm.value" :label="t('crm.customers.identity.fields.value')" />
            </VCol>
            <VCol cols="12" md="6">
              <VSwitch v-model="identityForm.isPrimary" :label="t('crm.customers.identity.fields.primary')" />
            </VCol>
            <VCol cols="12" md="6">
              <VSwitch v-model="identityForm.isVerified" :label="t('crm.customers.identity.fields.verified')" />
            </VCol>
            <VCol v-if="identityError" cols="12">
              <VAlert color="error" variant="tonal" :text="identityError" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn color="secondary" variant="tonal" @click="isIdentityDialogVisible = false; resetIdentityForm()">
            {{ t('common.cancel') }}
          </VBtn>
          <VBtn color="primary" :loading="isIdentitySaving" :disabled="!canUpdateCustomers" @click="saveIdentity">
            {{ t('common.save') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
.customer-hero-card {
  overflow: hidden;
  background:
    radial-gradient(circle at top left, rgba(var(--v-theme-primary), 0.18), transparent 36%),
    linear-gradient(135deg, rgba(var(--v-theme-surface), 1) 0%, rgba(var(--v-theme-surface), 0.96) 100%);
}

.customer-hero-copy {
  max-inline-size: 42rem;
}

.customer-command-card,
.customer-stat-chip,
.customer-metric-card,
.customer-notes-panel,
.customer-identity-card,
.customer-ticket-card,
.customer-list-item {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.customer-command-card {
  border-radius: 20px;
  background: rgba(var(--v-theme-surface), 0.88);
  padding: 1rem;
  backdrop-filter: blur(8px);
}

.customer-stat-chip {
  border-radius: 18px;
  min-inline-size: 8.5rem;
  padding: 1rem 1.1rem;
  background: rgba(var(--v-theme-surface), 0.82);
}

.customer-directory-card,
.customer-profile-hero {
  overflow: hidden;
}

.customer-directory-list {
  max-block-size: 78vh;
  overflow: auto;
  padding-inline-end: 0.25rem;
}

.customer-segment-chip,
.customer-quick-action {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 999px;
  background: rgba(var(--v-theme-surface), 1);
  color: rgb(var(--v-theme-on-surface));
  padding: 0.625rem 0.9rem;
  transition: all 0.2s ease;
}

.customer-segment-chip-active,
.customer-segment-chip:hover {
  border-color: rgba(var(--v-theme-primary), 0.4);
  background: rgba(var(--v-theme-primary), 0.08);
}

.customer-segment-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-inline-size: 1.75rem;
  block-size: 1.75rem;
  border-radius: 999px;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
  font-size: 0.75rem;
  font-weight: 600;
}

.customer-list-item {
  inline-size: 100%;
  border-radius: 18px;
  background: rgba(var(--v-theme-surface), 1);
  padding: 1rem;
  transition: all 0.2s ease;
}

.customer-list-item:hover,
.customer-list-item-active {
  border-color: rgba(var(--v-theme-primary), 0.4);
  background: rgba(var(--v-theme-primary), 0.05);
  box-shadow: 0 16px 32px rgba(var(--v-theme-on-surface), 0.06);
}

.customer-profile-hero {
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-primary), 0.16), transparent 30%),
    linear-gradient(180deg, rgba(var(--v-theme-surface), 1) 0%, rgba(var(--v-theme-surface), 0.98) 100%);
}

.customer-profile-actions {
  min-inline-size: 12rem;
}

.customer-quick-actions {
  display: grid;
  gap: 0.875rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.customer-quick-action {
  justify-content: flex-start;
  border-radius: 18px;
  padding: 1rem;
  text-align: start;
}

.customer-quick-action:hover {
  border-color: rgba(var(--v-theme-primary), 0.4);
  background: rgba(var(--v-theme-primary), 0.05);
  transform: translateY(-1px);
}

.customer-notes-panel,
.customer-identity-card,
.customer-ticket-card {
  border-radius: 18px;
  background: rgba(var(--v-theme-surface), 0.72);
  padding: 1rem;
}

.customer-metric-card {
  border-radius: 18px;
}

.customer-workspace-tabs {
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.customer-timeline-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.customer-timeline-filter {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 999px;
  background: rgba(var(--v-theme-surface), 1);
  color: rgb(var(--v-theme-on-surface));
  padding: 0.55rem 0.85rem;
  transition: all 0.2s ease;
}

.customer-timeline-filter:hover,
.customer-timeline-filter-active {
  border-color: rgba(var(--v-theme-primary), 0.4);
  background: rgba(var(--v-theme-primary), 0.08);
}

.customer-timeline-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-inline-size: 1.5rem;
  block-size: 1.5rem;
  border-radius: 999px;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
  font-size: 0.72rem;
  font-weight: 600;
}

.customer-timeline-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.customer-timeline-section {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.customer-timeline-section-label {
  position: sticky;
  inset-block-start: 0;
  z-index: 1;
  inline-size: fit-content;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 999px;
  background: rgba(var(--v-theme-surface), 0.92);
  color: rgb(var(--v-theme-on-surface));
  padding: 0.375rem 0.75rem;
  backdrop-filter: blur(8px);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.customer-timeline-section-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.customer-timeline-item {
  position: relative;
  display: flex;
  gap: 0.875rem;
  padding-inline-start: 0.25rem;
}

.customer-timeline-item:not(:last-child)::after {
  position: absolute;
  inset-block-start: 1.25rem;
  inset-inline-start: 0.54rem;
  block-size: calc(100% + 0.75rem);
  border-inline-start: 1px dashed rgba(var(--v-theme-primary), 0.3);
  content: '';
}

.customer-timeline-dot {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  inline-size: 0.875rem;
  block-size: 0.875rem;
  border-radius: 999px;
  margin-block-start: 0.35rem;
  background: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 6px rgba(var(--v-theme-primary), 0.12);
}

@media (max-width: 1279px) {
  .customer-directory-list {
    max-block-size: none;
  }

  .customer-quick-actions {
    grid-template-columns: 1fr;
  }
}
</style>