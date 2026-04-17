<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useDisplay, useTheme } from 'vuetify'
import { themes } from '@/plugins/vuetify/theme'

definePage({
  meta: {
    action: 'read',
    layoutWrapperClasses: 'layout-content-height-fixed',
    subject: 'CrmInbox',
  },
})

const { t } = useI18n()
const ability = useAbility()

interface CustomerContact {
  id: number
  nama: string
  email: string
  noHp: string | null
  jumlahTiket: number
  jumlahTiketAktif?: number
  createdAt: string | null
  lastActivityAt?: string | null
}

interface ConversationMessage {
  id: number
  channel: string
  isiPesan: string
  pengirim: string
  createdAt: string | null
  senderType: 'customer' | 'agent' | 'internal'
}

interface ConversationActivity {
  id: number
  type: string
  title: string
  description: string | null
  createdAt: string | null
  user?: {
    id: number
    fullName: string
    email: string
  } | null
}

interface ConversationTicket {
  id: number
  kode: string
  kategori: string
  subjek: string | null
  status: string
  prioritas: string
  batasSla: string | null
  activityAt?: string | null
  assignedUser?: {
    id: number
    fullName: string
    email: string
    role: string
  } | null
  pelanggan: {
    id: number | null
    nama: string | null
    email: string | null
    noHp?: string | null
  }
  activities?: ConversationActivity[]
  pesan: ConversationMessage[]
}

interface InboxOverviewResponse {
  conversations: ConversationTicket[]
  customers: CustomerContact[]
  summary: {
    totalConversations: number
    activeConversations: number
    overdueSla: number
    dueSoonSla: number
  }
}

interface MessageGroup {
  senderType: ConversationMessage['senderType']
  pengirim: string
  messages: ConversationMessage[]
}

const relativeTimeFormatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' })
const route = useRoute()
const router = useRouter()

const display = useDisplay()
const { name } = useTheme()

const isLeftSidebarOpen = ref(display.mdAndUp.value)
const isUserProfileSidebarOpen = ref(false)
const isConversationSidebarOpen = ref(false)
const isFilterMenuOpen = ref(false)
const searchQuery = useCookie<string>('crmPercakapanSearch', { default: () => '' })
const debouncedSearchQuery = ref('')
const selectedStatus = useCookie<string>('crmPercakapanStatus', { default: () => '' })
const selectedCategory = useCookie<string>('crmPercakapanCategory', { default: () => '' })
const selectedOwnership = useCookie<string>('crmPercakapanOwnership', { default: () => '' })
const selectedQueue = useCookie<string>('crmPercakapanQueue', { default: () => '' })
const selectedSort = useCookie<string>('crmPercakapanSort', { default: () => 'latest' })
const activeConversationId = ref<number | null>(null)
const selectedCustomerId = ref<number | null>(null)
const draftMessage = ref('')
const replyMode = ref<'internal' | 'customer'>('internal')
const isSending = ref(false)
const isUpdatingStatus = ref(false)
const isAssigningConversation = ref(false)
const isCreateTicketDialogVisible = ref(false)
const isCreatingTicket = ref(false)
const isRefreshingChats = ref(false)
const chatLogPS = ref<any>()
const createTicketForm = ref({
  kategori: 'general',
  subjek: '',
  prioritas: 'sedang',
  isiPesan: '',
})
const snackbar = ref({
  visible: false,
  color: 'success',
  text: '',
})

const ticketCategoryOptions = computed(() => [
  { title: t('crm.shared.categories.general'), value: 'general' },
  { title: t('crm.shared.categories.billing'), value: 'billing' },
  { title: t('crm.shared.categories.technical'), value: 'technical' },
  { title: t('crm.shared.categories.priorityFollowUp'), value: 'priority-follow-up' },
])

const ticketCategoryFilterOptions = computed(() => [
  { title: t('crm.shared.categories.all'), value: '' },
  ...ticketCategoryOptions.value,
])

const ticketStatusFilterOptions = computed(() => [
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

const ticketStatusActionOptions = computed(() => [
  { title: t('crm.shared.statuses.new'), value: 'baru' },
  { title: t('crm.shared.statuses.inProgress'), value: 'diproses' },
  { title: t('crm.shared.statuses.done'), value: 'selesai' },
])

const ticketPriorityOptions = computed(() => [
  { title: t('crm.shared.priorities.low'), value: 'rendah' },
  { title: t('crm.shared.priorities.medium'), value: 'sedang' },
  { title: t('crm.shared.priorities.high'), value: 'tinggi' },
])

const ownershipFilterOptions = computed(() => [
  { title: t('crm.shared.ownership.all'), value: '' },
  { title: t('crm.shared.ownership.mine'), value: 'mine' },
  { title: t('crm.shared.ownership.unassigned'), value: 'unassigned' },
])

const queueFilterOptions = computed(() => [
  { title: t('crm.conversations.queues.all'), value: '' },
  { title: t('crm.conversations.queues.unread'), value: 'unread' },
  { title: t('crm.conversations.queues.waitingAgent'), value: 'waiting-agent' },
  { title: t('crm.conversations.queues.waitingCustomer'), value: 'waiting-customer' },
  { title: t('crm.conversations.queues.resolved'), value: 'resolved' },
])

const assigneeOptions = computed(() => [
  { title: t('crm.conversations.assignee.unassigned'), value: null },
  ...((usersData.value?.users ?? []).map((user: any) => ({
    title: user.fullName,
    value: user.id,
  }))),
])

const userData = useCookie<any>('userData')

const getQueryValue = (value: unknown) => typeof value === 'string' ? value : ''

selectedStatus.value = getQueryValue(route.query.status) || selectedStatus.value
selectedCategory.value = getQueryValue(route.query.category) || selectedCategory.value
selectedOwnership.value = getQueryValue(route.query.ownership) || selectedOwnership.value
selectedQueue.value = getQueryValue(route.query.queue) || selectedQueue.value
selectedSort.value = getQueryValue(route.query.sort) || selectedSort.value
searchQuery.value = getQueryValue(route.query.q) || searchQuery.value

const syncConversationSearch = useDebounceFn(() => {
  debouncedSearchQuery.value = searchQuery.value
}, 220)

const { data: inboxData, execute: fetchInbox } = await useApi<InboxOverviewResponse>('/crm/inbox/overview')
const { data: usersData } = await useApi<any>(createUrl('/apps/users', {
  query: {
    itemsPerPage: 100,
    page: 1,
  },
}))

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const listDateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
})

const timeFormatter = new Intl.DateTimeFormat('id-ID', {
  hour: '2-digit',
  minute: '2-digit',
})

const containerBg = computed(() => {
  let color = 'transparent'

  if (themes)
    color = themes?.[name.value].colors?.background as string

  return color
})

const normalizeMessages = (messages: ConversationTicket['pesan'] = []) => [...messages]
  .sort((left, right) => new Date(left.createdAt || '').getTime() - new Date(right.createdAt || '').getTime())
  .map(message => ({
    ...message,
    senderType: message.senderType || (message.channel === 'balasan-internal'
      ? 'internal'
      : (message.channel === 'balasan-pelanggan' ? 'agent' : 'customer')),
  }))

const conversations = computed<ConversationTicket[]>(() => (inboxData.value?.conversations ?? []).map(ticket => ({
  ...ticket,
  activities: [...(ticket.activities ?? [])].sort((left, right) => new Date(right.createdAt || 0).getTime() - new Date(left.createdAt || 0).getTime()),
  pesan: normalizeMessages(ticket.pesan),
})))

const getConversationActivityTime = (ticket: ConversationTicket) => new Date(ticket.pesan.at(-1)?.createdAt || ticket.batasSla || 0).getTime()

const getPriorityRank = (priority: string) => {
  if (priority === 'tinggi')
    return 3
  if (priority === 'sedang')
    return 2

  return 1
}

const getConversationStatusCount = (status: string) => status
  ? conversations.value.filter(ticket => ticket.status === status).length
  : conversations.value.length

const getConversationCategoryCount = (category: string) => category
  ? conversations.value.filter(ticket => ticket.kategori === category).length
  : conversations.value.length

const matchesOwnershipFilter = (ticket: ConversationTicket, ownership: string) => {
  if (!ownership)
    return true
  if (ownership === 'mine')
    return ticket.assignedUser?.id === userData.value?.id
  if (ownership === 'unassigned')
    return !ticket.assignedUser

  return true
}

const getConversationOwnershipCount = (ownership: string) => ownership
  ? conversations.value.filter(ticket => matchesOwnershipFilter(ticket, ownership)).length
  : conversations.value.length

const getChannelKey = (channel?: string | null) => {
  const normalizedChannel = String(channel || '').toLowerCase()

  if (!normalizedChannel || normalizedChannel === 'balasan-internal' || normalizedChannel === 'internal')
    return 'internal'
  if (normalizedChannel === 'balasan-pelanggan' || normalizedChannel === 'agent-reply')
    return 'agent'
  if (normalizedChannel.includes('whatsapp') || normalizedChannel === 'wa')
    return 'whatsapp'
  if (normalizedChannel.includes('instagram') || normalizedChannel === 'ig')
    return 'instagram'
  if (normalizedChannel.includes('facebook') || normalizedChannel.includes('messenger'))
    return 'facebook'
  if (normalizedChannel.includes('mail') || normalizedChannel.includes('email'))
    return 'email'
  if (normalizedChannel.includes('web') || normalizedChannel.includes('chat'))
    return 'webchat'

  return 'other'
}

const getConversationChannel = (ticket: ConversationTicket) => {
  const externalMessage = [...ticket.pesan].reverse().find(message => message.senderType === 'customer')

  return getChannelKey(externalMessage?.channel || ticket.pesan.at(-1)?.channel)
}

const resolveChannelMeta = (channel: string) => {
  const key = getChannelKey(channel)

  if (key === 'whatsapp')
    return { key, label: t('crm.conversations.channels.whatsapp'), color: 'success', icon: 'tabler-brand-whatsapp' }
  if (key === 'instagram')
    return { key, label: t('crm.conversations.channels.instagram'), color: 'error', icon: 'tabler-brand-instagram' }
  if (key === 'facebook')
    return { key, label: t('crm.conversations.channels.facebook'), color: 'info', icon: 'tabler-brand-facebook' }
  if (key === 'email')
    return { key, label: t('crm.conversations.channels.email'), color: 'secondary', icon: 'tabler-mail' }
  if (key === 'webchat')
    return { key, label: t('crm.conversations.channels.webchat'), color: 'primary', icon: 'tabler-message-circle' }
  if (key === 'agent')
    return { key, label: t('crm.conversations.channels.agentReply'), color: 'info', icon: 'tabler-send' }
  if (key === 'internal')
    return { key, label: t('crm.conversations.channels.internal'), color: 'primary', icon: 'tabler-lock' }

  return { key, label: t('crm.conversations.channels.other'), color: 'secondary', icon: 'tabler-message-2' }
}

const getConversationChannelMeta = (ticket: ConversationTicket) => resolveChannelMeta(getConversationChannel(ticket))

const getConversationQueueState = (ticket: ConversationTicket) => {
  if (ticket.status === 'selesai')
    return 'resolved'

  const lastMessage = [...ticket.pesan].reverse().find(message => message.senderType !== 'internal') || getLastMessage(ticket)

  if (!lastMessage)
    return 'waiting-agent'

  return lastMessage.senderType === 'customer' ? 'waiting-agent' : 'waiting-customer'
}

const matchesQueueFilter = (ticket: ConversationTicket, queue: string) => {
  if (!queue)
    return true
  if (queue === 'unread')
    return getUnreadCount(ticket) > 0

  return getConversationQueueState(ticket) === queue
}

const getConversationQueueCount = (queue: string) => queue
  ? conversations.value.filter(ticket => matchesQueueFilter(ticket, queue)).length
  : conversations.value.length

const resolveQueueStateColor = (state: string) => {
  if (state === 'unread')
    return 'error'
  if (state === 'waiting-agent')
    return 'warning'
  if (state === 'waiting-customer')
    return 'info'
  if (state === 'resolved')
    return 'success'

  return 'secondary'
}

const resolveQueueStateLabel = (state: string) => {
  if (state === 'unread')
    return t('crm.conversations.queues.unread')
  if (state === 'waiting-agent')
    return t('crm.conversations.queues.waitingAgent')
  if (state === 'waiting-customer')
    return t('crm.conversations.queues.waitingCustomer')
  if (state === 'resolved')
    return t('crm.conversations.queues.resolved')

  return t('crm.conversations.queues.all')
}

const resolveStatusFilterColor = (status: string) => {
  const count = getConversationStatusCount(status)

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
  const count = getConversationCategoryCount(category)

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
    const hasUrgentSla = conversations.value.some(ticket => ticket.status !== 'selesai' && new Date(ticket.batasSla || 0).getTime() <= Date.now() + 1000 * 60 * 60 * 24)

    return hasUrgentSla ? 'warning' : undefined
  }

  if (sort === 'priority') {
    const hasHighPriority = conversations.value.some(ticket => ticket.prioritas === 'tinggi' && ticket.status !== 'selesai')

    return hasHighPriority ? 'error' : undefined
  }

  return undefined
}

const resolveSlaState = (ticket: ConversationTicket) => {
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

const resolveSlaChipColor = (ticket: ConversationTicket) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return 'error'
  if (state === 'due-soon')
    return 'warning'

  return 'secondary'
}

const resolveSlaChipLabel = (ticket: ConversationTicket) => {
  const state = resolveSlaState(ticket)

  if (state === 'overdue')
    return t('crm.shared.sla.overdue')
  if (state === 'due-soon')
    return t('crm.shared.sla.dueSoon')

  return t('crm.shared.sla.onTrack')
}

const isSelectedFilter = (currentValue: string, optionValue: string) => currentValue === optionValue

const getOptionTitle = (options: { title: string; value: string }[], value: string, fallback: string) => options.find(option => option.value === value)?.title || fallback

const activeFilterCount = computed(() => Number(selectedSort.value !== 'latest') + Number(Boolean(selectedStatus.value)) + Number(Boolean(selectedCategory.value)) + Number(Boolean(selectedOwnership.value)) + Number(Boolean(selectedQueue.value)))

const selectedSortLabel = computed(() => getOptionTitle(ticketSortOptions.value, selectedSort.value, t('crm.shared.sort.latest')))

const selectedStatusLabel = computed(() => getOptionTitle(ticketStatusFilterOptions.value, selectedStatus.value, t('crm.shared.statuses.all')))

const selectedCategoryLabel = computed(() => getOptionTitle(ticketCategoryFilterOptions.value, selectedCategory.value, t('crm.shared.categories.all')))

const selectedOwnershipLabel = computed(() => getOptionTitle(ownershipFilterOptions.value, selectedOwnership.value, t('crm.shared.ownership.all')))

const selectedQueueLabel = computed(() => getOptionTitle(queueFilterOptions.value, selectedQueue.value, t('crm.conversations.queues.all')))

const visibleConversationCountLabel = computed(() => t('crm.conversations.countLabel', { count: filteredConversations.value.length }))

const clearConversationFilters = () => {
  selectedSort.value = 'latest'
  selectedStatus.value = ''
  selectedCategory.value = ''
  selectedOwnership.value = ''
  selectedQueue.value = ''
}

const resolveFilterChipColor = (isSelected: boolean, preferredColor?: string) => {
  if (!isSelected)
    return undefined

  return preferredColor || 'primary'
}

const syncPercakapanQuery = useDebounceFn(() => {
  const nextQuery = {
    ...route.query,
    q: searchQuery.value || undefined,
    status: selectedStatus.value || undefined,
    category: selectedCategory.value || undefined,
    ownership: selectedOwnership.value || undefined,
    queue: selectedQueue.value || undefined,
    sort: selectedSort.value !== 'latest' ? selectedSort.value : undefined,
  }

  router.replace({ query: nextQuery })
}, 180)

const sortedConversations = computed<ConversationTicket[]>(() => [...conversations.value].sort((left, right) => {
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

  return getConversationActivityTime(right) - getConversationActivityTime(left)
}))

const contacts = computed<CustomerContact[]>(() => inboxData.value?.customers ?? [])

const filteredConversations = computed(() => {
  const query = debouncedSearchQuery.value.trim().toLowerCase()

  return sortedConversations.value.filter(ticket => {
    const matchesStatus = !selectedStatus.value || ticket.status === selectedStatus.value
    const matchesCategory = !selectedCategory.value || ticket.kategori === selectedCategory.value
    const matchesOwnership = matchesOwnershipFilter(ticket, selectedOwnership.value)
    const matchesQueue = matchesQueueFilter(ticket, selectedQueue.value)

    if (!matchesStatus || !matchesCategory || !matchesOwnership || !matchesQueue)
      return false

    if (!query)
      return true

    const lastMessage = ticket.pesan.at(-1)?.isiPesan?.toLowerCase() || ''

    return [
      ticket.kode,
      ticket.status,
      ticket.prioritas,
      ticket.pelanggan.nama,
      ticket.pelanggan.email,
      lastMessage,
    ].some(value => String(value || '').toLowerCase().includes(query))
  })
})

const filteredContacts = computed(() => {
  const query = debouncedSearchQuery.value.trim().toLowerCase()

  if (!query)
    return contacts.value

  return contacts.value.filter(contact => [
    contact.nama,
    contact.email,
    contact.noHp,
  ].some(value => String(value || '').toLowerCase().includes(query)))
})

const activeConversation = computed(() => {
  if (!filteredConversations.value.length)
    return null

  return filteredConversations.value.find(ticket => ticket.id === activeConversationId.value) || filteredConversations.value[0]
})

const selectedCustomer = computed(() => {
  if (selectedCustomerId.value) {
    const matched = contacts.value.find(contact => contact.id === selectedCustomerId.value)
    if (matched)
      return matched
  }

  if (activeConversation.value?.pelanggan.id)
    return contacts.value.find(contact => contact.id === activeConversation.value?.pelanggan.id) || null

  return null
})

const customerTickets = computed(() => {
  const customer = selectedCustomer.value

  if (!customer)
    return []

  return sortedConversations.value.filter(ticket => ticket.pelanggan.id === customer.id)
})

const showSidebarSkeleton = computed(() => isRefreshingChats.value && !filteredConversations.value.length && !filteredContacts.value.length)
const showConversationSkeleton = computed(() => isRefreshingChats.value && !activeConversation.value)

const activeConversationGroups = computed<MessageGroup[]>(() => {
  const messages = activeConversation.value?.pesan ?? []
  const groups: MessageGroup[] = []

  messages.forEach(message => {
    const previousGroup = groups.at(-1)

    if (previousGroup && previousGroup.senderType === message.senderType && previousGroup.pengirim === message.pengirim) {
      previousGroup.messages.push(message)
      return
    }

    groups.push({
      senderType: message.senderType,
      pengirim: message.pengirim,
      messages: [message],
    })
  })

  return groups
})

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

const showSnackbar = (text: string, color: 'success' | 'error' | 'warning' | 'info' = 'success') => {
  snackbar.value = {
    visible: true,
    text,
    color,
  }
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

const resolveContactPresence = (contactId: number) => {
  const latestTicket = conversations.value.find(ticket => ticket.pelanggan.id === contactId)

  if (!latestTicket)
    return 'secondary'
  if (latestTicket.status === 'baru' && latestTicket.prioritas === 'tinggi')
    return 'error'
  if (latestTicket.status === 'diproses')
    return 'warning'

  const lastMessageTime = latestTicket.pesan.at(-1)?.createdAt
  if (lastMessageTime && Date.now() - new Date(lastMessageTime).getTime() < 1000 * 60 * 60 * 24)
    return 'success'

  return 'secondary'
}

const getLatestTicketForContact = (contactId: number) => conversations.value.find(ticket => ticket.pelanggan.id === contactId) || null

const getUnresolvedTicketsForContact = (contactId: number) => conversations.value.filter(ticket => ticket.pelanggan.id === contactId && ticket.status !== 'selesai').length

const getLastMessage = (ticket: ConversationTicket) => ticket.pesan.at(-1) || null

const getTicketPreview = (ticket: ConversationTicket) => {
  if (ticket.subjek)
    return ticket.subjek

  const lastMessage = getLastMessage(ticket)

  if (!lastMessage)
    return t('crm.conversations.empty.messages')

  if (lastMessage.senderType === 'internal')
    return t('crm.conversations.preview.internalPrefix', { message: lastMessage.isiPesan })

  if (lastMessage.senderType === 'agent')
    return t('crm.conversations.preview.agentPrefix', { message: lastMessage.isiPesan })

  return lastMessage.isiPesan
}

const getUnreadCount = (ticket: ConversationTicket) => {
  const lastMessage = getLastMessage(ticket)

  if (!lastMessage || ticket.status === 'selesai')
    return 0

  return lastMessage.senderType === 'customer' ? 1 : 0
}

const hasSelectedContact = (contactId: number) => selectedCustomer.value?.id === contactId || activeConversation.value?.pelanggan.id === contactId

const hasConversationForContact = (contactId: number) => conversations.value.some(ticket => ticket.pelanggan.id === contactId)

const scrollToBottom = () => {
  const element = chatLogPS.value?.$el || chatLogPS.value

  if (!element)
    return

  element.scrollTop = element.scrollHeight
}

const formatDate = (value?: string | null) => {
  if (!value)
    return '-'

  return formatter.format(new Date(value))
}

const formatTime = (value?: string | null) => {
  if (!value)
    return '-'

  return timeFormatter.format(new Date(value))
}

const formatChatListTime = (value?: string | null) => {
  if (!value)
    return '-'

  const date = new Date(value)
  const now = new Date()
  const isSameDay = date.toDateString() === now.toDateString()

  return isSameDay ? formatRelativeTime(value) : listDateFormatter.format(date)
}

const formatRelativeTime = (value?: string | null) => {
  if (!value)
    return '-'

  const timestamp = new Date(value).getTime()
  const deltaMs = timestamp - Date.now()
  const minute = 1000 * 60
  const hour = minute * 60
  const day = hour * 24

  if (Math.abs(deltaMs) < hour)
    return relativeTimeFormatter.format(Math.round(deltaMs / minute), 'minute')
  if (Math.abs(deltaMs) < day)
    return relativeTimeFormatter.format(Math.round(deltaMs / hour), 'hour')

  return relativeTimeFormatter.format(Math.round(deltaMs / day), 'day')
}

const isSameCalendarDay = (left?: string | null, right?: string | null) => {
  if (!left || !right)
    return false

  return new Date(left).toDateString() === new Date(right).toDateString()
}

const shouldShowDateSeparator = (groupIndex: number) => {
  const currentGroup = activeConversationGroups.value[groupIndex]
  const previousGroup = activeConversationGroups.value[groupIndex - 1]
  const currentDate = currentGroup?.messages[0]?.createdAt
  const previousDate = previousGroup?.messages.at(-1)?.createdAt

  if (!currentDate)
    return false
  if (!previousGroup)
    return true

  return !isSameCalendarDay(currentDate, previousDate)
}

const getDateSeparatorLabel = (value?: string | null) => {
  if (!value)
    return '-'

  const date = new Date(value)
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)

  if (date.toDateString() === today.toDateString())
    return t('crm.conversations.date.today')
  if (date.toDateString() === yesterday.toDateString())
    return t('crm.conversations.date.yesterday')

  return formatter.format(date)
}

const openConversation = (id: number) => {
  const ticket = conversations.value.find(item => item.id === id)

  activeConversationId.value = id
  selectedCustomerId.value = ticket?.pelanggan.id ?? null
  draftMessage.value = ''
  replyMode.value = 'internal'

  if (display.smAndDown.value)
    isLeftSidebarOpen.value = false

  nextTick(() => {
    scrollToBottom()
  })
}

const openActiveTicket = (reply = false) => {
  if (!activeConversation.value || !ability.can('read', 'CrmTickets'))
    return

  router.push({
    name: 'tiket',
    query: {
      ticket: String(activeConversation.value.id),
      from: 'inbox',
      reply: reply ? '1' : undefined,
    },
  })
}

const openCustomerProfile = () => {
  if (!selectedCustomer.value || !ability.can('read', 'CrmCustomers'))
    return

  router.push({
    name: 'marketing-automation-customer-data-platform',
    query: {
      customer: String(selectedCustomer.value.id),
      from: 'inbox',
    },
  })
}

const resetCreateTicketForm = () => {
  createTicketForm.value = {
    kategori: 'general',
    subjek: '',
    prioritas: 'sedang',
    isiPesan: '',
  }
}

const openCreateTicketDialog = () => {
  if (!selectedCustomer.value || !ability.can('create', 'CrmTickets'))
    return

  resetCreateTicketForm()
  isCreateTicketDialogVisible.value = true
}

const openContact = (contactId: number) => {
  selectedCustomerId.value = contactId

  const latestTicket = getLatestTicketForContact(contactId)

  if (latestTicket)
    openConversation(latestTicket.id)
  else {
    if (display.smAndDown.value)
      isLeftSidebarOpen.value = false

    isConversationSidebarOpen.value = true
  }
}

const refreshChatData = async () => {
  isRefreshingChats.value = true

  try {
    await fetchInbox()
  }
  finally {
    isRefreshingChats.value = false
  }
}

const updateConversationStatus = async (status: string) => {
  if (!activeConversation.value || !ability.can('update', 'CrmTickets') || status === activeConversation.value.status)
    return

  isUpdatingStatus.value = true

  try {
    const activeId = activeConversation.value.id

    await $api(`/crm/tiket/${activeConversation.value.id}/status`, {
      method: 'PATCH',
      body: { status },
    })

    await refreshChatData()
    activeConversationId.value = activeId
    showSnackbar(t('crm.conversations.snackbar.statusSuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.conversations.snackbar.statusError')), 'error')
  }
  finally {
    isUpdatingStatus.value = false
  }
}

const assignConversation = async (assignedUserId: number | null) => {
  if (!activeConversation.value || !ability.can('update', 'CrmTickets'))
    return

  isAssigningConversation.value = true

  try {
    const activeId = activeConversation.value.id

    await $api(`/crm/tiket/${activeConversation.value.id}/assign`, {
      method: 'PATCH',
      body: { assignedUserId },
    })

    await refreshChatData()
    activeConversationId.value = activeId
    showSnackbar(t('crm.conversations.snackbar.assignSuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.conversations.snackbar.assignError')), 'error')
  }
  finally {
    isAssigningConversation.value = false
  }
}

const createTicketFromContact = async () => {
  if (!selectedCustomer.value || !ability.can('create', 'CrmTickets'))
    return

  isCreatingTicket.value = true

  try {
    const response = await $api<{ tiket?: { id?: number } }>('/crm/tiket', {
      method: 'POST',
      body: {
        pelangganId: selectedCustomer.value.id,
        kategori: createTicketForm.value.kategori,
        subjek: createTicketForm.value.subjek || undefined,
        prioritas: createTicketForm.value.prioritas,
        isiPesan: createTicketForm.value.isiPesan,
      },
    })

    const createdTicketId = response?.tiket?.id

    isCreateTicketDialogVisible.value = false
    isConversationSidebarOpen.value = false
    resetCreateTicketForm()
    await refreshChatData()

    if (createdTicketId)
      openConversation(createdTicketId)

    showSnackbar(t('crm.conversations.snackbar.createTicketSuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.conversations.snackbar.createTicketError')), 'error')
  }
  finally {
    isCreatingTicket.value = false
  }
}

const sendMessage = async () => {
  if (!activeConversation.value || !ability.can('update', 'CrmInbox') || !draftMessage.value.trim())
    return

  isSending.value = true

  try {
    await $api(`/crm/inbox/conversations/${activeConversation.value.id}/reply`, {
      method: 'POST',
      body: {
        isiPesan: draftMessage.value,
        mode: replyMode.value,
        status: activeConversation.value.status,
      },
    })

    draftMessage.value = ''
    await refreshChatData()
    activeConversationId.value = activeConversation.value.id

    nextTick(() => {
      scrollToBottom()
    })
    showSnackbar(replyMode.value === 'customer' ? t('crm.conversations.snackbar.customerReplySuccess') : t('crm.conversations.snackbar.replySuccess'))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, replyMode.value === 'customer' ? t('crm.conversations.snackbar.customerReplyError') : t('crm.conversations.snackbar.replyError')), 'error')
  }
  finally {
    isSending.value = false
  }
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

const canViewCustomers = computed(() => ability.can('read', 'CrmCustomers'))
const canViewTickets = computed(() => ability.can('read', 'CrmTickets'))
const canCreateTickets = computed(() => ability.can('create', 'CrmTickets'))
const canUpdateTickets = computed(() => ability.can('update', 'CrmTickets'))
const canUpdateInbox = computed(() => ability.can('update', 'CrmInbox'))
const conversationWorkspaceAccessMessage = computed(() => {
  if (!canUpdateInbox.value && !canCreateTickets.value && !canUpdateTickets.value)
    return 'Mode baca saja. Anda dapat memantau percakapan dan histori customer, tetapi tidak bisa membalas, membuat ticket, atau mengubah assignment.'

  if (!canUpdateInbox.value)
    return 'Akses terbatas. Anda masih bisa melihat konteks percakapan, tetapi composer balasan dinonaktifkan.'

  if (!canCreateTickets.value || !canUpdateTickets.value)
    return 'Akses ticket terbatas. Balasan inbox tetap aktif, tetapi pembuatan ticket baru atau perubahan status/assignment mengikuti permission Anda.'

  return ''
})

watch(filteredConversations, conversationsList => {
  if (!conversationsList.length) {
    activeConversationId.value = null
    return
  }

  if (!conversationsList.some(ticket => ticket.id === activeConversationId.value))
    activeConversationId.value = conversationsList[0].id
}, { immediate: true })

watch(searchQuery, () => {
  syncConversationSearch()
}, { immediate: true })

watch([searchQuery, selectedStatus, selectedCategory, selectedOwnership, selectedQueue, selectedSort], () => {
  syncPercakapanQuery()
}, { immediate: true })

watch(() => activeConversation.value?.id, () => {
  if (activeConversation.value?.pelanggan.id)
    selectedCustomerId.value = activeConversation.value.pelanggan.id

  replyMode.value = 'internal'

  nextTick(() => {
    scrollToBottom()
  })
})
</script>

<template>
  <VLayout
    class="chat-app-layout"
    style="z-index: 0;"
  >
    <VAlert
      v-if="conversationWorkspaceAccessMessage"
      color="warning"
      variant="tonal"
      class="ma-4 mb-0"
    >
      {{ conversationWorkspaceAccessMessage }}
    </VAlert>

    <VNavigationDrawer
      v-model="isUserProfileSidebarOpen"
      temporary
      touchless
      absolute
      class="user-profile-sidebar"
      location="start"
      width="370"
    >
      <div class="pt-2 me-2 text-end">
        <IconBtn @click="isUserProfileSidebarOpen = false">
          <VIcon class="text-medium-emphasis" color="disabled" icon="tabler-x" />
        </IconBtn>
      </div>

      <div class="text-center px-6">
        <VAvatar size="84" color="primary" variant="tonal" class="chat-user-profile-badge mb-3">
          {{ avatarText(userData?.fullName || userData?.username || 'A') }}
        </VAvatar>
        <h5 class="text-h5">
          {{ userData?.fullName || userData?.username || 'Admin KitCRM' }}
        </h5>
        <p class="text-capitalize text-medium-emphasis mb-0">
          {{ userData?.role || 'admin' }}
        </p>
      </div>

      <PerfectScrollbar class="ps-chat-user-profile-sidebar-content pb-5 px-6" :options="{ wheelPropagation: false }">
        <div class="my-6 text-medium-emphasis">
          <div class="text-base text-disabled">{{ t('crm.conversations.profile.activeAccount') }}</div>
          <p class="mt-2 mb-0">
            {{ t('crm.conversations.profile.activeAccountDescription') }}
          </p>
        </div>

        <div class="mb-6">
          <div class="text-base text-disabled mb-2">{{ t('crm.conversations.profile.info') }}</div>
          <div class="d-flex align-center pa-2">
            <VIcon class="me-2 text-high-emphasis" icon="tabler-mail" size="22" />
            <div class="text-body-1 text-high-emphasis">{{ userData?.email || '-' }}</div>
          </div>
          <div class="d-flex align-center pa-2">
            <VIcon class="me-2 text-high-emphasis" icon="tabler-shield-check" size="22" />
            <div class="text-body-1 text-high-emphasis">{{ t('crm.conversations.profile.internalAccess') }}</div>
          </div>
          <div class="d-flex align-center pa-2">
            <VIcon class="me-2 text-high-emphasis" icon="tabler-message-circle" size="22" />
            <div class="text-body-1 text-high-emphasis">{{ t('crm.conversations.profile.monitoredConversations', { count: filteredConversations.length }) }}</div>
          </div>
        </div>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <VNavigationDrawer
      v-model="isConversationSidebarOpen"
      width="374"
      absolute
      temporary
      location="end"
      touchless
      class="active-chat-user-profile-sidebar"
    >
      <template v-if="selectedCustomer">
        <div class="pt-6 px-6" :class="$vuetify.locale.isRtl ? 'text-left' : 'text-right'">
          <IconBtn @click="isConversationSidebarOpen = false">
            <VIcon icon="tabler-x" class="text-medium-emphasis" />
          </IconBtn>
        </div>

        <div class="text-center px-6">
          <VBadge
            location="bottom right"
            offset-x="7"
            offset-y="4"
            bordered
            :color="resolveContactPresence(selectedCustomer.id)"
            class="chat-user-profile-badge mb-5"
          >
            <VAvatar size="84" color="primary" variant="tonal">
              {{ avatarText(selectedCustomer.nama) }}
            </VAvatar>
          </VBadge>
          <h5 class="text-h5">{{ selectedCustomer.nama }}</h5>
          <p class="text-capitalize text-body-1 mb-0">{{ selectedCustomer.email }}</p>
        </div>

        <PerfectScrollbar class="ps-chat-user-profile-sidebar-content text-medium-emphasis pb-6 px-6" :options="{ wheelPropagation: false }">
          <div class="my-6">
            <div class="text-sm text-disabled">{{ t('crm.conversations.contactDrawer.about') }}</div>
            <p class="mt-1 mb-0">
              {{ t('crm.conversations.contactDrawer.aboutDescription', { date: formatDate(selectedCustomer.createdAt), count: selectedCustomer.jumlahTiket }) }}
            </p>
          </div>

          <div class="mb-6">
            <div class="text-sm text-disabled mb-1">{{ t('crm.conversations.contactDrawer.contactInfo') }}</div>
            <div class="d-flex align-center text-high-emphasis pa-2">
              <VIcon class="me-2" icon="tabler-mail" size="22" />
              <div class="text-base">{{ selectedCustomer.email }}</div>
            </div>
            <div class="d-flex align-center text-high-emphasis pa-2">
              <VIcon class="me-2" icon="tabler-phone" size="22" />
              <div class="text-base">{{ selectedCustomer.noHp || t('crm.conversations.empty.phone') }}</div>
            </div>
            <div class="d-flex align-center text-high-emphasis pa-2">
              <VIcon class="me-2" icon="tabler-ticket" size="22" />
              <div class="text-base">{{ t('crm.conversations.contactDrawer.totalTickets', { count: selectedCustomer.jumlahTiket }) }}</div>
            </div>
          </div>

          <div class="mb-6">
            <div class="text-sm text-disabled mb-1">{{ t('crm.conversations.contactDrawer.summary') }}</div>
            <div class="d-flex align-center text-high-emphasis pa-2">
              <VIcon class="me-2" icon="tabler-alert-circle" size="22" />
              <div class="text-base">{{ t('crm.conversations.contactDrawer.openTickets', { count: getUnresolvedTicketsForContact(selectedCustomer.id) }) }}</div>
            </div>
            <div v-if="customerTickets[0]" class="d-flex align-center text-high-emphasis pa-2">
              <VIcon class="me-2" icon="tabler-clock" size="22" />
              <div class="text-base">{{ t('crm.conversations.contactDrawer.lastActivity', { date: formatDate(customerTickets[0].pesan.at(-1)?.createdAt) }) }}</div>
            </div>
          </div>

          <div v-if="customerTickets.length">
            <div class="text-sm text-disabled mb-1">{{ t('crm.conversations.contactDrawer.latestTickets') }}</div>
            <div
              v-for="ticket in customerTickets.slice(0, 3)"
              :key="ticket.id"
              class="d-flex align-center justify-space-between pa-2 rounded border-sm mb-2 cursor-pointer recent-ticket-card"
              @click="openConversation(ticket.id); isConversationSidebarOpen = false"
            >
              <div>
                <div class="text-body-1 text-high-emphasis">{{ ticket.kode }}</div>
                <div class="text-sm text-medium-emphasis">{{ getTicketPreview(ticket) }}</div>
                <div class="text-xs text-disabled mt-1 text-uppercase">{{ resolveCategoryLabel(ticket.kategori) }}</div>
              </div>
              <VChip :color="resolveStatusColor(ticket.status)" variant="tonal" size="small">
                {{ resolveStatusLabel(ticket.status) }}
              </VChip>
            </div>
          </div>

          <div v-if="activeConversation?.activities?.length" class="mb-6 mt-6">
            <div class="text-sm text-disabled mb-2">{{ t('crm.conversations.activity.title') }}</div>
            <div class="activity-timeline d-flex flex-column gap-3">
              <div
                v-for="activity in activeConversation.activities.slice(0, 8)"
                :key="`activity-${activity.id}`"
                class="activity-item d-flex align-start gap-3"
              >
                <VAvatar size="30" :color="resolveActivityColor(activity.type)" variant="tonal">
                  <VIcon size="16" :icon="resolveActivityIcon(activity.type)" />
                </VAvatar>

                <div class="flex-grow-1 min-w-0">
                  <div class="text-body-2 text-high-emphasis font-weight-medium">{{ activity.title }}</div>
                  <div v-if="activity.description" class="text-body-2 text-medium-emphasis mt-1">{{ activity.description }}</div>
                  <div class="text-xs text-disabled mt-1">
                    {{ activity.user?.fullName || t('crm.conversations.activity.system') }} • {{ formatDate(activity.createdAt) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <VAlert
            v-else
            variant="tonal"
            color="secondary"
            icon="tabler-message-off"
            :text="t('crm.conversations.empty.thread')"
          />

          <VBtn
            v-if="canViewCustomers"
            block
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-id-badge-2"
            class="mt-4"
            @click="openCustomerProfile"
          >
            {{ t('crm.conversations.contactDrawer.openCustomer') }}
          </VBtn>

          <VBtn
            v-if="!customerTickets.length && canCreateTickets"
            block
            color="primary"
            prepend-icon="tabler-ticket"
            class="mt-4"
            @click="openCreateTicketDialog"
          >
            {{ t('crm.conversations.contactDrawer.createTicket') }}
          </VBtn>
        </PerfectScrollbar>
      </template>
    </VNavigationDrawer>

    <VNavigationDrawer
      v-model="isLeftSidebarOpen"
      absolute
      touchless
      location="start"
      width="370"
      :temporary="$vuetify.display.smAndDown"
      class="chat-list-sidebar"
      :permanent="$vuetify.display.mdAndUp"
    >
      <div class="chat-list-header flex-column align-stretch pa-4">
        <div class="inbox-sidebar-header">
          <div class="d-flex align-start justify-space-between gap-3">
            <div>
              <div class="workspace-kicker">Email Channel</div>
              <h4 class="workspace-title">Email Inbox</h4>
              <p class="workspace-subtitle mb-0">Fokus ke email masuk pelanggan, triage cepat, dan eskalasi ke ticket management saat dibutuhkan.</p>
            </div>

            <div class="d-flex align-center gap-2">
              <VBadge
                dot
                location="bottom right"
                offset-x="3"
                offset-y="0"
                color="success"
                bordered
              >
                <VAvatar
                  size="42"
                  class="cursor-pointer"
                  color="primary"
                  variant="tonal"
                  @click="isUserProfileSidebarOpen = true"
                >
                  {{ avatarText(userData?.fullName || userData?.username || 'A') }}
                </VAvatar>
              </VBadge>

              <IconBtn v-if="$vuetify.display.smAndDown" @click="isLeftSidebarOpen = false">
                <VIcon icon="tabler-x" class="text-medium-emphasis" />
              </IconBtn>
            </div>
          </div>

          <AppTextField
            id="search"
            v-model="searchQuery"
            :placeholder="t('crm.conversations.searchPlaceholder')"
            prepend-inner-icon="tabler-search"
            class="chat-list-search mt-4"
          />
        </div>

        <div class="chat-filter-toolbar mt-4 px-4 pt-4 pb-4">
          <div class="d-flex align-center justify-space-between gap-3 flex-wrap">
            <div class="chat-filter-meta">
              <div class="text-body-2 font-weight-medium">Filter workspace</div>
              <div class="text-xs text-medium-emphasis">Atur prioritas, ownership, dan urutan antrian aktif.</div>
            </div>

            <VMenu
              v-model="isFilterMenuOpen"
              :close-on-content-click="false"
              location="bottom end"
              offset="12"
            >
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  variant="flat"
                  color="primary"
                  prepend-icon="tabler-adjustments-horizontal"
                  class="chat-filter-trigger"
                >
                  {{ t('crm.conversations.filterButton') }}
                  <VBadge
                    v-if="activeFilterCount"
                    inline
                    color="primary"
                    :content="activeFilterCount"
                    class="ms-2"
                  />
                </VBtn>
              </template>

              <VCard class="chat-filter-menu" rounded="xl" elevation="8">
                <div class="d-flex align-start justify-space-between gap-3 px-4 pt-4 pb-2">
                  <div>
                    <div class="text-subtitle-1 font-weight-medium">{{ t('crm.conversations.filterTitle') }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ t('crm.conversations.filterSubtitle') }}</div>
                  </div>

                  <VBtn
                    v-if="activeFilterCount"
                    size="small"
                    variant="text"
                    color="secondary"
                    @click="clearConversationFilters"
                  >
                    {{ t('crm.conversations.reset') }}
                  </VBtn>
                </div>

                <VDivider />

                <div class="chat-filter-panel px-4 py-4 d-flex flex-column gap-4">
                  <div>
                    <div class="filter-section-header mb-2">
                      <span class="text-xs text-disabled">{{ t('crm.conversations.sections.sort') }}</span>
                    </div>
                    <div class="filter-chip-set">
                      <VChip
                        v-for="option in ticketSortOptions"
                        :key="`sort-${option.value}`"
                        class="filter-chip"
                        :class="{ 'filter-chip-active': isSelectedFilter(selectedSort, option.value) }"
                        :color="resolveFilterChipColor(isSelectedFilter(selectedSort, option.value), resolveSortChipColor(option.value))"
                        :variant="isSelectedFilter(selectedSort, option.value) ? 'flat' : 'tonal'"
                        @click="selectedSort = option.value"
                      >
                        {{ option.title }}
                      </VChip>
                    </div>
                  </div>

                  <div>
                    <div class="filter-section-header mb-2">
                      <span class="text-xs text-disabled">{{ t('crm.conversations.sections.status') }}</span>
                    </div>
                    <div class="filter-chip-set">
                      <VChip
                        v-for="option in ticketStatusFilterOptions"
                        :key="`status-${option.value || 'all'}`"
                        class="filter-chip"
                        :class="{ 'filter-chip-active': isSelectedFilter(selectedStatus, option.value) }"
                        :color="resolveFilterChipColor(isSelectedFilter(selectedStatus, option.value), resolveStatusFilterColor(option.value))"
                        :variant="isSelectedFilter(selectedStatus, option.value) ? 'flat' : 'tonal'"
                        @click="selectedStatus = option.value"
                      >
                        <span>{{ option.title }}</span>
                        <span class="filter-chip-count">{{ getConversationStatusCount(option.value) }}</span>
                      </VChip>
                    </div>
                  </div>

                  <div>
                    <div class="filter-section-header mb-2">
                      <span class="text-xs text-disabled">{{ t('crm.conversations.sections.category') }}</span>
                    </div>
                    <div class="filter-chip-set">
                      <VChip
                        v-for="option in ticketCategoryFilterOptions"
                        :key="`category-${option.value || 'all'}`"
                        class="filter-chip"
                        :class="{ 'filter-chip-active': isSelectedFilter(selectedCategory, option.value) }"
                        :color="resolveFilterChipColor(isSelectedFilter(selectedCategory, option.value), resolveCategoryFilterColor(option.value))"
                        :variant="isSelectedFilter(selectedCategory, option.value) ? 'flat' : 'tonal'"
                        @click="selectedCategory = option.value"
                      >
                        <span>{{ option.title }}</span>
                        <span class="filter-chip-count">{{ getConversationCategoryCount(option.value) }}</span>
                      </VChip>
                    </div>
                  </div>

                  <div>
                    <div class="filter-section-header mb-2">
                      <span class="text-xs text-disabled">{{ t('crm.conversations.sections.queue') }}</span>
                    </div>
                    <div class="filter-chip-set">
                      <VChip
                        v-for="option in queueFilterOptions"
                        :key="`queue-${option.value || 'all'}`"
                        class="filter-chip"
                        :class="{ 'filter-chip-active': isSelectedFilter(selectedQueue, option.value) }"
                        :color="resolveFilterChipColor(isSelectedFilter(selectedQueue, option.value), resolveQueueStateColor(option.value || 'all'))"
                        :variant="isSelectedFilter(selectedQueue, option.value) ? 'flat' : 'tonal'"
                        @click="selectedQueue = option.value"
                      >
                        <span>{{ option.title }}</span>
                        <span class="filter-chip-count">{{ getConversationQueueCount(option.value) }}</span>
                      </VChip>
                    </div>
                  </div>

                  <div>
                    <div class="filter-section-header mb-2">
                      <span class="text-xs text-disabled">{{ t('crm.conversations.sections.ownership') }}</span>
                    </div>
                    <div class="filter-chip-set">
                      <VChip
                        v-for="option in ownershipFilterOptions"
                        :key="`ownership-${option.value || 'all'}`"
                        class="filter-chip"
                        :class="{ 'filter-chip-active': isSelectedFilter(selectedOwnership, option.value) }"
                        :color="resolveFilterChipColor(isSelectedFilter(selectedOwnership, option.value))"
                        :variant="isSelectedFilter(selectedOwnership, option.value) ? 'flat' : 'tonal'"
                        @click="selectedOwnership = option.value"
                      >
                        <span>{{ option.title }}</span>
                        <span class="filter-chip-count">{{ getConversationOwnershipCount(option.value) }}</span>
                      </VChip>
                    </div>
                  </div>
                </div>
              </VCard>
            </VMenu>
          </div>

          <div class="filter-summary-row mt-3">
            <div class="filter-summary-pill">
              <VIcon size="16" icon="tabler-arrows-sort" />
              <span>{{ selectedSortLabel }}</span>
            </div>
            <div v-if="activeFilterCount" class="filter-summary-pill filter-summary-pill-active">
              <VIcon size="16" icon="tabler-adjustments-horizontal" />
              <span>{{ activeFilterCount }} filter aktif</span>
            </div>
            <VBtn
              v-if="activeFilterCount"
              size="small"
              variant="text"
              color="secondary"
              class="chat-filter-reset"
              @click="clearConversationFilters"
            >
              {{ t('crm.conversations.resetFilter') }}
            </VBtn>
          </div>

          <div class="queue-chip-row mt-3">
            <VChip
              v-for="option in queueFilterOptions"
              :key="`queue-quick-${option.value || 'all'}`"
              size="small"
              class="queue-chip"
              :color="isSelectedFilter(selectedQueue, option.value) ? resolveQueueStateColor(option.value || 'all') : undefined"
              :variant="isSelectedFilter(selectedQueue, option.value) ? 'flat' : 'tonal'"
              @click="selectedQueue = option.value"
            >
              <span>{{ option.title }}</span>
              <span class="queue-chip-count">{{ getConversationQueueCount(option.value) }}</span>
            </VChip>
          </div>
        </div>
      </div>
      <VProgressLinear
        v-if="isRefreshingChats"
        indeterminate
        color="primary"
      />
      <VDivider />

      <PerfectScrollbar
        tag="ul"
        class="d-flex flex-column gap-y-1 chat-contacts-list px-3 py-2 list-none"
        :options="{ wheelPropagation: false }"
      >
        <template v-if="showSidebarSkeleton">
          <div class="px-4 py-3 d-flex flex-column gap-4">
            <VSkeletonLoader type="heading" />
            <VSkeletonLoader v-for="item in 4" :key="`chat-skeleton-${item}`" type="list-item-avatar-two-line" />
            <VSkeletonLoader type="heading" />
            <VSkeletonLoader v-for="item in 3" :key="`contact-skeleton-${item}`" type="list-item-avatar-two-line" />
          </div>
        </template>

        <template v-else>
        <li class="list-none">
          <div class="conversation-section-header px-4 pt-2 pb-3">
            <div>
              <h5 class="chat-contact-header text-primary text-h6 mb-1">{{ t('crm.conversations.sections.conversations') }}</h5>
              <div class="text-xs text-medium-emphasis">{{ filteredConversations.length }} thread aktif di inbox</div>
            </div>
          </div>
        </li>

        <li
          v-for="ticket in filteredConversations"
          :key="ticket.id"
          class="chat-contact conversation-card cursor-pointer d-flex align-start"
          :class="{ 'chat-contact-active': ticket.id === activeConversation?.id }"
          @click="openConversation(ticket.id)"
        >
          <VBadge
            dot
            location="bottom right"
            offset-x="3"
            offset-y="0"
            :color="ticket.pelanggan.id ? resolveContactPresence(ticket.pelanggan.id) : 'secondary'"
            bordered
            model-value
          >
            <VAvatar size="44" color="primary" variant="tonal">
              {{ avatarText(ticket.pelanggan.nama || 'P') }}
            </VAvatar>
          </VBadge>

          <div class="conversation-card-body flex-grow-1 ms-4 overflow-hidden">
            <div class="d-flex align-center justify-space-between gap-3 mb-1">
              <div class="min-w-0">
                <p class="text-base text-high-emphasis mb-0 font-weight-medium text-truncate">{{ ticket.pelanggan.nama || ticket.kode }}</p>
                <div class="text-xs text-medium-emphasis conversation-card-code">{{ ticket.kode }} • {{ resolveCategoryLabel(ticket.kategori) }}</div>
              </div>

              <div class="conversation-card-time text-body-2 text-disabled whitespace-no-wrap">{{ formatChatListTime(ticket.pesan.at(-1)?.createdAt) }}</div>
            </div>

            <p class="mb-0 text-body-2 conversation-card-preview">{{ getTicketPreview(ticket) }}</p>

            <div class="conversation-card-footer mt-3">
              <div class="conversation-card-meta-line text-xs text-medium-emphasis">
                {{ getConversationChannelMeta(ticket).label }}
                <span class="conversation-meta-separator">•</span>
                {{ resolveQueueStateLabel(getConversationQueueState(ticket)) }}
                <span class="conversation-meta-separator">•</span>
                {{ ticket.assignedUser?.fullName || t('crm.conversations.assignee.unassigned') }}
              </div>

              <div class="conversation-card-summary">
                <div class="conversation-side-note text-xs text-medium-emphasis">
                  {{ resolveStatusLabel(ticket.status) }} • {{ ticket.activities?.length || 0 }} activity
                </div>
              </div>
            </div>
          </div>

          <div class="conversation-card-side d-flex flex-column align-end justify-space-between gap-2 ms-3">
            <VChip
              v-if="resolveSlaState(ticket)"
              size="x-small"
              :color="resolveSlaChipColor(ticket)"
              variant="flat"
            >
              {{ resolveSlaChipLabel(ticket) }}
            </VChip>
            <VBadge
              v-else-if="getUnreadCount(ticket)"
              color="error"
              inline
              :content="getUnreadCount(ticket)"
            />
            <VChip
              v-else
              size="x-small"
              :color="resolvePriorityColor(ticket.prioritas)"
              variant="tonal"
            >
              {{ resolvePriorityLabel(ticket.prioritas) }}
            </VChip>
          </div>
        </li>

        <span v-show="!filteredConversations.length" class="no-chat-items-text text-disabled">{{ t('crm.conversations.empty.conversations') }}</span>

        <li class="list-none pt-2">
          <div class="conversation-section-header px-4 pt-1 pb-3">
            <div>
              <h5 class="chat-contact-header text-primary text-h6 mb-1">{{ t('crm.conversations.sections.contacts') }}</h5>
              <div class="text-xs text-medium-emphasis">Pelanggan yang bisa dibuka cepat dari inbox</div>
            </div>
          </div>
        </li>

        <li
          v-for="contact in filteredContacts"
          :key="`contact-${contact.id}`"
          class="chat-contact cursor-pointer d-flex align-center"
          :class="{ 'chat-contact-active': hasSelectedContact(contact.id) }"
          @click="openContact(contact.id)"
        >
          <VBadge
            dot
            location="bottom right"
            offset-x="3"
            offset-y="0"
            :color="resolveContactPresence(contact.id)"
            bordered
            model-value
          >
            <VAvatar size="40" color="primary" variant="tonal">
              {{ avatarText(contact.nama) }}
            </VAvatar>
          </VBadge>

          <div class="flex-grow-1 ms-4 overflow-hidden">
            <p class="text-base text-high-emphasis mb-0">{{ contact.nama }}</p>
            <p class="mb-0 text-truncate text-body-2">{{ t('crm.conversations.contactMeta.activeTickets', { count: getUnresolvedTicketsForContact(contact.id) }) }} • {{ contact.noHp || contact.email }}</p>
          </div>

          <VChip size="x-small" :color="hasConversationForContact(contact.id) ? 'secondary' : 'default'" variant="tonal">
            {{ hasConversationForContact(contact.id) ? t('crm.conversations.contactMeta.ticketCount', { count: contact.jumlahTiket }) : t('crm.conversations.contactMeta.noConversation') }}
          </VChip>
        </li>

        <span v-show="!filteredContacts.length" class="no-chat-items-text text-disabled">{{ t('crm.conversations.empty.contacts') }}</span>
        </template>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <VMain class="chat-content-container">
      <div v-if="showConversationSkeleton" class="chat-empty-state d-flex h-100 flex-column justify-center px-6 py-8">
        <VSkeletonLoader type="article, actions" />
      </div>

      <div v-if="activeConversation" class="d-flex flex-column h-100">
        <VProgressLinear
          v-if="isRefreshingChats"
          indeterminate
          color="primary"
        />
        <div class="active-chat-header text-medium-emphasis bg-surface">
          <div class="conversation-topbar d-flex align-start justify-space-between gap-4 flex-wrap w-100">
            <div class="d-flex align-start gap-3 cursor-pointer conversation-topbar-main" @click="isConversationSidebarOpen = true">
              <IconBtn class="d-md-none mt-1" @click.stop="isLeftSidebarOpen = true">
                <VIcon icon="tabler-menu-2" />
              </IconBtn>

              <VBadge
                dot
                location="bottom right"
                offset-x="3"
                offset-y="0"
                :color="activeConversation.pelanggan.id ? resolveContactPresence(activeConversation.pelanggan.id) : 'secondary'"
                bordered
              >
                <VAvatar size="52" color="primary" variant="tonal" class="cursor-pointer">
                  {{ avatarText(activeConversation.pelanggan.nama || 'P') }}
                </VAvatar>
              </VBadge>

              <div class="flex-grow-1 min-w-0">
                <div class="conversation-kicker">Conversation Workspace</div>
                <div class="text-h5 mb-1 font-weight-medium text-high-emphasis">{{ activeConversation.pelanggan.nama || activeConversation.kode }}</div>
                <p class="text-truncate mb-0 text-body-2 conversation-topbar-description">{{ activeConversation.kode }} • {{ activeConversation.pelanggan.email || t('crm.conversations.empty.email') }}</p>
                <div class="conversation-header-meta d-flex flex-wrap align-center gap-2 mt-3">
                  <VChip :color="resolveStatusColor(activeConversation.status)" size="small" variant="tonal">
                    {{ resolveStatusLabel(activeConversation.status) }}
                  </VChip>
                  <VChip :color="getConversationChannelMeta(activeConversation).color" size="small" variant="tonal">
                    <VIcon start size="14" :icon="getConversationChannelMeta(activeConversation).icon" />
                    {{ getConversationChannelMeta(activeConversation).label }}
                  </VChip>
                  <VChip :color="resolveQueueStateColor(getConversationQueueState(activeConversation))" size="small" variant="tonal">
                    {{ resolveQueueStateLabel(getConversationQueueState(activeConversation)) }}
                  </VChip>
                  <VChip size="small" color="secondary" variant="tonal">
                    {{ activeConversation.assignedUser?.fullName || t('crm.conversations.assignee.unassigned') }}
                  </VChip>
                </div>
              </div>
            </div>

            <div class="d-flex flex-column align-stretch conversation-topbar-actions">
              <div class="d-sm-flex align-center flex-wrap justify-end d-none text-medium-emphasis gap-2">
            <VMenu v-if="canUpdateTickets" location="bottom end">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="small"
                  variant="flat"
                  :color="resolveStatusColor(activeConversation.status)"
                  :loading="isUpdatingStatus"
                >
                  {{ resolveStatusLabel(activeConversation.status) }}
                </VBtn>
              </template>

              <VList density="compact">
                <VListItem
                  v-for="option in ticketStatusActionOptions"
                  :key="`status-action-${option.value}`"
                  :active="option.value === activeConversation.status"
                  @click="updateConversationStatus(option.value)"
                >
                  <template #prepend>
                    <VIcon
                      :icon="option.value === activeConversation.status ? 'tabler-check' : 'tabler-circle'"
                      size="16"
                    />
                  </template>
                  <VListItemTitle>{{ option.title }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
            <VChip
              v-else
              size="small"
              :color="resolveStatusColor(activeConversation.status)"
              variant="tonal"
            >
              {{ resolveStatusLabel(activeConversation.status) }}
            </VChip>
            <AppSelect
              v-if="canUpdateTickets"
              :model-value="activeConversation.assignedUser?.id ?? null"
              :items="assigneeOptions"
              density="compact"
              style="min-inline-size: 12rem;"
              :placeholder="t('crm.conversations.assignee.placeholder')"
              :loading="isAssigningConversation"
              @update:model-value="assignConversation($event as number | null)"
            />
            <VChip v-else size="small" color="secondary" variant="tonal">
              {{ activeConversation.assignedUser?.fullName || t('crm.conversations.assignee.unassigned') }}
            </VChip>
            <IconBtn class="conversation-action-icon">
              <VIcon icon="tabler-phone" />
            </IconBtn>
            <IconBtn class="conversation-action-icon">
              <VIcon icon="tabler-mail" />
            </IconBtn>
            <IconBtn class="conversation-action-icon" @click="refreshChatData">
              <VIcon icon="tabler-refresh" />
            </IconBtn>
            <IconBtn class="conversation-action-icon" @click="isConversationSidebarOpen = true">
              <VIcon icon="tabler-dots-vertical" />
            </IconBtn>
              </div>
            </div>
          </div>
        </div>

        <VDivider />

        <PerfectScrollbar ref="chatLogPS" tag="ul" :options="{ wheelPropagation: false }" class="flex-grow-1">
          <div class="chat-log chat-log-surface pa-6">
            <div
              v-for="(group, index) in activeConversationGroups"
              :key="`${group.pengirim}-${index}`"
              class="chat-group-wrapper"
            >
              <div v-if="shouldShowDateSeparator(index)" class="chat-date-separator">
                <span>{{ getDateSeparatorLabel(group.messages[0]?.createdAt) }}</span>
              </div>

              <div
                class="chat-group d-flex align-start"
                :class="[{ 'flex-row-reverse': group.senderType !== 'customer', 'mb-6': activeConversationGroups.length - 1 !== index }]"
              >
                <div class="chat-avatar" :class="group.senderType !== 'customer' ? 'ms-4' : 'me-4'">
                  <VAvatar size="32" :color="group.senderType === 'internal' ? 'primary' : (group.senderType === 'agent' ? 'info' : undefined)" :variant="group.senderType !== 'customer' ? 'tonal' : undefined">
                    <span v-if="group.senderType !== 'customer'">{{ avatarText(userData?.fullName || 'A') }}</span>
                    <span v-else>{{ avatarText(activeConversation.pelanggan.nama || 'P') }}</span>
                  </VAvatar>
                </div>

                <div class="chat-body d-inline-flex flex-column" :class="group.senderType !== 'customer' ? 'align-end' : 'align-start'">
                  <div class="chat-sender-label text-sm text-medium-emphasis mb-2">{{ group.pengirim }}</div>

                  <div
                    v-for="message in group.messages"
                    :key="message.id"
                    class="chat-content py-2 px-4 elevation-2"
                    style="background-color: rgb(var(--v-theme-surface));"
                    :class="[
                      group.senderType === 'customer' ? 'chat-left' : (group.senderType === 'internal' ? 'bg-primary text-white chat-right' : 'chat-agent chat-right'),
                      group.messages.at(-1)?.id !== message.id ? 'mb-2' : 'mb-1',
                    ]"
                  >
                    <div class="chat-bubble-meta text-xs mb-1 d-flex align-center gap-1" :class="group.senderType === 'internal' ? 'text-white' : 'text-medium-emphasis'">
                      <VIcon size="12" :icon="resolveChannelMeta(message.channel).icon" />
                      <span>{{ resolveChannelMeta(message.channel).label }}</span>
                    </div>
                    <p class="mb-0 text-base">{{ message.isiPesan }}</p>
                  </div>

                  <div :class="{ 'text-right': group.senderType !== 'customer' }">
                    <VIcon v-if="group.senderType !== 'customer'" size="16" class="me-1" icon="tabler-checks" />
                    <span class="text-sm ms-2 text-disabled">{{ formatTime(group.messages.at(-1)?.createdAt) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </PerfectScrollbar>

        <VForm class="chat-log-message-form inbox-composer mb-5 mx-5" @submit.prevent="sendMessage">
          <div class="chat-editor-meta d-flex flex-wrap align-center justify-space-between px-2 mb-2 gap-2">
            <div class="d-flex flex-wrap gap-2">
              <VChip
                size="small"
                :color="replyMode === 'internal' ? 'primary' : undefined"
                :variant="replyMode === 'internal' ? 'flat' : 'tonal'"
                class="cursor-pointer"
                :disabled="!canUpdateInbox"
                @click="replyMode = 'internal'"
              >
                {{ t('crm.conversations.composer.internalReply') }}
              </VChip>
              <VChip
                size="small"
                :color="replyMode === 'customer' ? 'info' : undefined"
                :variant="replyMode === 'customer' ? 'flat' : 'tonal'"
                class="cursor-pointer"
                :disabled="!canUpdateInbox"
                @click="replyMode = 'customer'"
              >
                {{ t('crm.conversations.composer.customerReply') }}
              </VChip>
              <VChip size="small" :color="resolveStatusColor(activeConversation.status)" variant="tonal">{{ resolveStatusLabel(activeConversation.status) }}</VChip>
              <VChip size="small" :color="resolveQueueStateColor(getConversationQueueState(activeConversation))" variant="tonal">{{ resolveQueueStateLabel(getConversationQueueState(activeConversation)) }}</VChip>
              <VChip size="small" :color="getConversationChannelMeta(activeConversation).color" variant="tonal">
                <VIcon start size="14" :icon="getConversationChannelMeta(activeConversation).icon" />
                {{ getConversationChannelMeta(activeConversation).label }}
              </VChip>
              <VChip size="small" color="secondary" variant="tonal">
                {{ t('crm.conversations.assignee.current', { name: activeConversation.assignedUser?.fullName || t('crm.conversations.assignee.unassigned') }) }}
              </VChip>
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('crm.conversations.composer.lastActivity', { time: formatRelativeTime(activeConversation.pesan.at(-1)?.createdAt) }) }}
            </div>
          </div>

          <div class="composer-shell">
            <VTextField
              :key="activeConversation.id"
              v-model="draftMessage"
              :disabled="!canUpdateInbox"
              variant="solo"
              density="default"
              class="chat-message-input"
              :placeholder="replyMode === 'customer' ? t('crm.conversations.composer.customerPlaceholder') : t('crm.conversations.composer.placeholder')"
              autofocus
            >
              <template #append-inner>
                <div class="d-flex gap-1">
                  <IconBtn>
                    <VIcon icon="tabler-mood-smile" size="22" />
                  </IconBtn>
                  <IconBtn>
                    <VIcon icon="tabler-microphone" size="22" />
                  </IconBtn>
                  <IconBtn>
                    <VIcon icon="tabler-paperclip" size="22" />
                  </IconBtn>
                  <div v-if="canUpdateInbox" class="d-none d-md-block">
                    <VBtn append-icon="tabler-send" :loading="isSending" @click="sendMessage">
                      {{ t('crm.conversations.composer.send') }}
                    </VBtn>
                  </div>
                  <IconBtn v-if="canUpdateInbox" class="d-block d-md-none" @click="sendMessage">
                    <VIcon icon="tabler-send" />
                  </IconBtn>
                </div>
              </template>
            </VTextField>
          </div>

          <div class="chat-editor-hint px-2 mt-2 text-body-2 text-medium-emphasis">
            {{ replyMode === 'customer' ? t('crm.conversations.composer.customerHint') : t('crm.conversations.composer.hint') }}
          </div>
        </VForm>
      </div>

      <div v-else class="d-flex h-100 align-center justify-center flex-column">
        <VAvatar size="98" variant="tonal" color="primary" class="mb-4">
          <VIcon size="50" class="rounded-0" icon="tabler-message-2" />
        </VAvatar>
        <VBtn v-if="$vuetify.display.smAndDown" rounded="pill" @click="isLeftSidebarOpen = true">
          {{ t('crm.conversations.empty.mobileStart') }}
        </VBtn>
        <p v-else style="max-inline-size: 40ch; text-wrap: balance;" class="text-center text-disabled">
          {{ t('crm.conversations.empty.start') }}
        </p>
      </div>
    </VMain>
  </VLayout>

  <VDialog
    v-model="isCreateTicketDialogVisible"
    max-width="640"
  >
    <VCard>
      <VCardItem :title="t('crm.conversations.createTicket.title', { name: selectedCustomer?.nama || '-' })" />
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="createTicketForm.kategori"
              :label="t('crm.conversations.createTicket.categoryLabel')"
              :items="ticketCategoryOptions"
            />
          </VCol>
          <VCol cols="12">
            <AppSelect
              v-model="createTicketForm.prioritas"
              :label="t('crm.conversations.createTicket.priorityLabel')"
              :items="ticketPriorityOptions"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="createTicketForm.subjek"
              :label="t('crm.conversations.createTicket.subjectLabel')"
              :placeholder="t('crm.conversations.createTicket.subjectPlaceholder')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextarea
              v-model="createTicketForm.isiPesan"
              :label="t('crm.conversations.createTicket.messageLabel')"
              :placeholder="t('crm.conversations.createTicket.messagePlaceholder')"
              rows="5"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="isCreateTicketDialogVisible = false"
        >
          {{ t('common.cancel') }}
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          :loading="isCreatingTicket"
          :disabled="!canCreateTickets"
          @click="createTicketFromContact"
        >
          {{ t('crm.conversations.createTicket.submit') }}
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
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core/scss/base/mixins";
@use "@core/scss/template/mixins" as templateMixins;
@use "vuetify/lib/styles/tools/states" as vuetifyStates;

$chat-app-header-height: 76px;

%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 1.5rem;
}

.chat-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-chat-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-chat-app-layout);
    }
  }

  .active-chat-user-profile-sidebar,
  .user-profile-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }

  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }

  .chat-list-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }
}

.chat-content-container {
  background-color: v-bind(containerBg);
  background-image: radial-gradient(circle at top left, rgba(var(--v-theme-primary), 0.08), transparent 32%), linear-gradient(180deg, rgba(var(--v-theme-surface), 1) 0%, rgba(var(--v-theme-surface), 0.96) 100%);

  .chat-message-input {
    .v-field__input {
      font-size: 0.9375rem !important;
      line-height: 1.375rem !important;
      padding-block: 0.6rem 0.5rem;
    }

    .v-field__append-inner {
      align-items: center;
      padding-block-start: 0;
    }

    .v-field--appended {
      padding-inline-end: 8px;
    }
  }
}

.chat-empty-state {
  inline-size: 100%;
  margin-inline: auto;
  max-inline-size: 720px;
}

.chat-user-profile-badge {
  .v-badge__badge {
    min-width: 12px !important;
    height: 0.75rem;
  }
}

.chat-contacts-list {
  --chat-content-spacing-x: 16px;

  padding-block-end: 0.75rem;

  .chat-contact-header {
    margin-block: 0.5rem 0.25rem;
  }

  .chat-contact-header,
  .no-chat-items-text {
    margin-inline: var(--chat-content-spacing-x);
  }
}

.chat-list-search {
  inline-size: 100%;

  .v-field--focused {
    box-shadow: none !important;
  }
}

.workspace-hero {
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
  border-radius: 28px;
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-info), 0.14), transparent 34%),
    linear-gradient(145deg, rgba(var(--v-theme-primary), 0.12), rgba(var(--v-theme-surface), 0.96));
  padding: 1.1rem;
}

.workspace-kicker,
.conversation-kicker {
  color: rgba(var(--v-theme-primary), 1);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.workspace-title {
  color: rgb(var(--v-theme-on-surface));
  font-size: 1.45rem;
  line-height: 1.15;
  margin: 0.45rem 0 0.35rem;
}

.workspace-subtitle,
.conversation-topbar-description {
  color: rgba(var(--v-theme-on-surface), 0.68);
  max-inline-size: 48ch;
}

.workspace-stat-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.workspace-stat-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 22px;
  background: rgba(var(--v-theme-surface), 0.86);
  min-block-size: 138px;
  padding: 0.9rem;
}

.workspace-stat-label,
.overview-label {
  color: rgba(var(--v-theme-on-surface), 0.56);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.workspace-stat-value,
.overview-value {
  color: rgb(var(--v-theme-on-surface));
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1.1;
  margin-top: 0.45rem;
}

.workspace-stat-note,
.overview-note {
  color: rgba(var(--v-theme-on-surface), 0.62);
  font-size: 0.76rem;
  margin-top: 0.35rem;
}

.chat-filter-toolbar {
  border: 1px solid rgba(var(--v-border-color), 0.6);
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(var(--v-theme-primary), 0.035) 0%, rgba(var(--v-theme-surface), 0.96) 100%);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);

  .text-xs {
    letter-spacing: 0.04em;
  }
}

.filter-summary-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.filter-summary-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid rgba(var(--v-border-color), 0.6);
  border-radius: 999px;
  background: rgba(var(--v-theme-surface), 0.8);
  color: rgba(var(--v-theme-on-surface), 0.72);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.45rem 0.75rem;
}

.filter-summary-pill-active {
  border-color: rgba(var(--v-theme-primary), 0.22);
  background: rgba(var(--v-theme-primary), 0.08);
  color: rgba(var(--v-theme-primary), 1);
}

.chat-filter-meta {
  min-inline-size: 0;
}

.chat-filter-trigger {
  min-inline-size: 128px;
}

.chat-filter-menu {
  inline-size: min(420px, calc(100vw - 32px));
}

.chat-filter-panel {
  .filter-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .filter-chip-set {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-block-size: 34px;
    padding-inline: 0.8rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease;

    &:hover {
      transform: translateY(-1px);
    }
  }

  .filter-chip-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-inline-size: 1.25rem;
    block-size: 1.25rem;
    border-radius: 999px;
    background: rgba(var(--v-theme-on-surface), 0.08);
    color: rgba(var(--v-theme-on-surface), 0.72);
    font-size: 0.6875rem;
    font-weight: 600;
    padding-inline: 0.3rem;
  }

  .filter-chip-active {
    .filter-chip-count {
      background: rgba(var(--v-theme-surface), 0.22);
      color: currentColor;
    }
  }
}

.active-filter-pills {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.queue-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding-top: 0.25rem;
}

.queue-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.queue-chip-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-inline-size: 1.2rem;
  block-size: 1.2rem;
  border-radius: 999px;
  background: rgba(var(--v-theme-on-surface), 0.08);
  color: rgba(var(--v-theme-on-surface), 0.72);
  font-size: 0.6875rem;
  font-weight: 600;
  padding-inline: 0.3rem;
}

.active-filter-chip {
  max-inline-size: 100%;
}

.chat-filter-reset {
  padding-inline: 0.25rem;
}

.activity-timeline {
  .activity-item + .activity-item {
    padding-block-start: 0.25rem;
    border-top: 1px dashed rgba(var(--v-border-color), 0.6);
  }
}

.chat-agent {
  background: linear-gradient(180deg, rgba(var(--v-theme-info), 0.16), rgba(var(--v-theme-info), 0.1)) !important;
  color: rgb(var(--v-theme-on-surface)) !important;
  border: 1px solid rgba(var(--v-theme-info), 0.18);
}

.chat-contact {
  border: 1px solid rgba(var(--v-border-color), 0.54);
  border-radius: 24px;
  background: rgba(var(--v-theme-surface), 0.88);
  margin-block: 0.3rem;
  padding-block: 14px;
  padding-inline: 14px;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;

  @include mixins.before-pseudo;
  @include vuetifyStates.states($active: false);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.26);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07);
    transform: translateY(-1px);
  }

  &.chat-contact-active {
    @include templateMixins.custom-elevation(var(--v-theme-primary), "sm");

    border-color: rgba(var(--v-theme-primary), 0.6);
    background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.18), rgba(var(--v-theme-info), 0.12));
    color: rgb(var(--v-theme-on-surface));

    --v-theme-on-background: rgb(var(--v-theme-on-surface));
  }

  .v-badge--bordered .v-badge__badge::after {
    color: #fff;
  }
}

.conversation-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.conversation-card-preview {
  color: rgba(var(--v-theme-on-surface), 0.72);
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.conversation-card-code,
.conversation-side-note {
  letter-spacing: 0.01em;
}

.conversation-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.conversation-card-meta-line {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
}

.conversation-meta-separator {
  margin-inline: 0.3rem;
}

.conversation-card-side {
  flex: 0 0 62px;
  min-inline-size: 62px;
}

.conversation-card {
  align-items: center !important;
}

.conversation-card-body {
  min-inline-size: 0;
}

.conversation-card-time {
  font-size: 0.75rem !important;
}

.conversation-card-summary {
  flex: 0 0 auto;
  text-align: end;
}

.conversation-card-owner {
  font-weight: 600;
}

.active-chat-header {
  padding: 1rem 1.5rem;
}

.conversation-topbar-main {
  min-inline-size: 0;
}

.conversation-topbar-actions {
  min-inline-size: min(100%, 32rem);
}

.conversation-overview-grid-compact {
  grid-template-columns: minmax(0, 1.45fr) repeat(3, minmax(0, 1fr));
}

.conversation-overview-card-accent {
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-primary), 0.12), transparent 34%),
    rgba(var(--v-theme-surface), 0.92);
}

.overview-inline-chips,
.overview-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.conversation-overview-card-actions {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.conversation-action-icon {
  border: 1px solid rgba(var(--v-border-color), 0.55);
  border-radius: 14px;
}

.conversation-overview-bar {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.6);
  background: linear-gradient(180deg, rgba(var(--v-theme-primary), 0.025), transparent 100%);
}

.conversation-overview-grid {
  display: grid;
  gap: 0.9rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: stretch;
}

.conversation-overview-card {
  border: 1px solid rgba(var(--v-border-color), 0.65);
  border-radius: 24px;
  background: rgba(var(--v-theme-surface), 0.84);
  min-block-size: 132px;
  padding: 1rem 1.05rem;
}

.recent-ticket-card {
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
    border-color: rgba(var(--v-theme-primary), 0.24) !important;
    transform: translateY(-1px);
  }
}

.chat-log {
  position: relative;

  .chat-date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-block: 0 1.5rem;

    span {
      border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
      border-radius: 999px;
      background-color: rgba(var(--v-theme-surface), 0.92);
      color: rgba(var(--v-theme-on-surface), 0.58);
      font-size: 0.75rem;
      line-height: 1;
      padding-block: 0.45rem;
      padding-inline: 0.85rem;
    }
  }

  .chat-body {
    max-inline-size: calc(100% - 6.75rem);

    .chat-content {
      border: 1px solid rgba(var(--v-border-color), 0.45);
      border-end-end-radius: 6px;
      border-end-start-radius: 6px;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);

      p {
        overflow-wrap: anywhere;
      }

      &.chat-left {
        border-start-end-radius: 6px;
      }

      &.chat-right {
        border-start-start-radius: 6px;
      }
    }
  }
}

.chat-log-surface {
  min-block-size: 100%;
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-primary), 0.06), transparent 28%),
    linear-gradient(180deg, rgba(var(--v-theme-surface), 0.62), rgba(var(--v-theme-surface), 0.98));
}

.chat-sender-label {
  font-weight: 600;
  letter-spacing: 0.01em;
}

.chat-bubble-meta {
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.inbox-composer {
  border: 1px solid rgba(var(--v-border-color), 0.65);
  border-radius: 28px;
  background:
    radial-gradient(circle at bottom right, rgba(var(--v-theme-primary), 0.08), transparent 28%),
    rgba(var(--v-theme-surface), 0.96);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
  padding: 1rem;
}

.composer-shell {
  border-radius: 22px;
  background: rgba(var(--v-theme-surface), 0.72);
}

.chat-editor-meta,
.chat-editor-hint {
  padding-inline: 0.25rem;
}

@media (max-width: 1279px) {
  .workspace-stat-grid,
  .conversation-overview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .conversation-overview-grid-compact {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 959px) {
  .workspace-stat-grid,
  .conversation-overview-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .active-chat-header {
    padding: 1.1rem;
  }

  .conversation-card {
    align-items: flex-start !important;
  }

  .conversation-card-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .conversation-card-summary {
    text-align: start !important;
  }

  .conversation-overview-grid-compact {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>