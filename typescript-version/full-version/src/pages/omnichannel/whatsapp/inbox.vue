<script setup lang="ts">
import type { WhatsAppInboxThread, WhatsAppInboxTab } from '@/data/whatsapp-inbox-threads'

definePage({
  meta: {
    action: 'read',
    layoutWrapperClasses: 'layout-content-height-fixed',
    navActiveLink: 'omnichannel-whatsapp-inbox',
    subject: 'CrmWhatsapp',
  },
})

const route = useRoute()
const router = useRouter()
const ability = useAbility()
const currentUser = computed<any>(() => useCookie<any>('userData').value ?? null)
const currentAgent = computed(() => currentUser.value?.email ?? '')
const searchQuery = ref('')
const activeTab = ref<WhatsAppInboxTab>('all')
const replyMessage = ref('')
const newConversationMessage = ref('')
const newConversationPriority = ref<'rendah' | 'sedang' | 'tinggi'>('sedang')
const isCreatingConversation = ref(false)
const conversationError = ref('')
const selectedAttachmentName = ref('')
const activeThreadId = ref<number | null>(1)
const attachmentInputRef = ref<HTMLInputElement>()
const isThreadActionLoading = ref(false)

const formatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
})

const { data: pelangganData } = await useApi<any>(createUrl('/crm/pelanggan'))
const { data: whatsappInboxData, execute: fetchWhatsAppInbox } = await useApi<{ threads: WhatsAppInboxThread[] }>('/crm/inbox/whatsapp')

const getQueryValue = (value: unknown) => typeof value === 'string' ? value : ''
const customerNameFromQuery = computed(() => getQueryValue(route.query.customerName))
const identityValueFromQuery = computed(() => getQueryValue(route.query.identityValue))
const openedFromCustomers = computed(() => getQueryValue(route.query.from) === 'customers')
const forceCreateConversation = computed(() => getQueryValue(route.query.create) === '1')

const normalizePhoneValue = (value?: string | null) => value?.replace(/\D/g, '') ?? ''

const filterTabs = [
  { title: 'SEMUA', value: 'all' },
  { title: 'BELUM DIAMBIL', value: 'unassigned' },
  { title: 'MILIK SAYA', value: 'mine' },
]

const filteredThreads = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const threads = whatsappInboxData.value?.threads ?? []

  return threads.filter(thread => {
    if (activeTab.value === 'unassigned' && thread.assignedTo)
      return false

    if (activeTab.value === 'mine' && thread.assignedTo !== currentAgent.value)
      return false

    if (!query)
      return true

    return thread.name.toLowerCase().includes(query)
      || thread.phone.toLowerCase().includes(query)
      || thread.lastSnippet.toLowerCase().includes(query)
  })
})

const threads = computed(() => whatsappInboxData.value?.threads ?? [])
const matchedCustomer = computed(() => {
  if (getQueryValue(route.query.customer)) {
    const customerId = Number(getQueryValue(route.query.customer))

    return (pelangganData.value?.pelanggan ?? []).find((customer: any) => customer.id === customerId) ?? null
  }

  if (!activeThread.value)
    return null

  const normalizedPhone = normalizePhoneValue(activeThread.value.phone)
  const normalizedName = activeThread.value.name.trim().toLowerCase()

  return (pelangganData.value?.pelanggan ?? []).find((customer: any) => {
    return normalizePhoneValue(customer.noHp) === normalizedPhone
      || customer.nama?.trim().toLowerCase() === normalizedName
  }) ?? null
})

const contextualThread = computed(() => {
  const customerId = Number(getQueryValue(route.query.customer))
  const normalizedIdentity = normalizePhoneValue(identityValueFromQuery.value)
  const normalizedName = customerNameFromQuery.value.trim().toLowerCase()

  return threads.value.find(thread => {
    if (customerId && matchedCustomer.value?.id === customerId) {
      const customerPhone = normalizePhoneValue(matchedCustomer.value?.noHp)

      if (customerPhone && normalizePhoneValue(thread.phone) === customerPhone)
        return true
    }

    if (normalizedIdentity && normalizePhoneValue(thread.phone) === normalizedIdentity)
      return true

    return Boolean(normalizedName) && thread.name.toLowerCase().includes(normalizedName)
  }) ?? null
})

const shouldShowCreateConversationState = computed(() => {
  return openedFromCustomers.value && Boolean(matchedCustomer.value) && (forceCreateConversation.value || !contextualThread.value)
})

const activeThread = computed(() => {
  if (shouldShowCreateConversationState.value)
    return null

  return filteredThreads.value.find(thread => thread.id === activeThreadId.value) ?? threads.value.find(thread => thread.id === activeThreadId.value) ?? filteredThreads.value[0] ?? null
})

const todayLabel = computed(() => formatter.format(new Date('2026-04-06')))
const canCreateTickets = computed(() => ability.can('create', 'CrmTickets'))
const canUpdateTickets = computed(() => ability.can('update', 'CrmTickets'))
const canUpdateInbox = computed(() => ability.can('update', 'CrmInbox'))
const whatsappWorkspaceAccessMessage = computed(() => {
  if (!canUpdateInbox.value && !canCreateTickets.value && !canUpdateTickets.value)
    return 'Mode baca saja. Anda dapat memantau thread WhatsApp, tetapi tidak bisa membalas, membuat percakapan outbound, atau mengubah status penanganan.'

  if (!canUpdateInbox.value)
    return 'Akses terbatas. Thread tetap bisa dipantau, tetapi balasan WhatsApp dinonaktifkan.'

  if (!canCreateTickets.value || !canUpdateTickets.value)
    return 'Akses ticket terbatas. Balasan WhatsApp tetap aktif, tetapi aksi ambil/lepas, tandai selesai, atau buat thread outbound mengikuti permission Anda.'

  return ''
})

const canCreateConversation = computed(() => {
  return canCreateTickets.value && Boolean(matchedCustomer.value && newConversationMessage.value.trim()) && !isCreatingConversation.value
})

const countByTab = (value: WhatsAppInboxTab) => {
  if (value === 'unassigned')
    return threads.value.filter(thread => !thread.assignedTo).length
  if (value === 'mine')
    return threads.value.filter(thread => thread.assignedTo === currentAgent.value).length

  return threads.value.length
}

const resolveThreadStatusLabel = (status: WhatsAppInboxThread['status']) => {
  if (status === 'butuh_respons')
    return 'Butuh respons'
  if (status === 'menunggu_pelanggan')
    return 'Menunggu pelanggan'

  return 'Selesai'
}

const resolveThreadStatusColor = (status: WhatsAppInboxThread['status']) => {
  if (status === 'butuh_respons')
    return 'error'
  if (status === 'menunggu_pelanggan')
    return 'warning'

  return 'success'
}

const resolveThreadDirectionLabel = (thread: WhatsAppInboxThread) => thread.labels.includes('outbound') ? 'Keluar' : 'Masuk'
const resolveThreadDirectionColor = (thread: WhatsAppInboxThread) => thread.labels.includes('outbound') ? 'primary' : 'success'

const resolveThreadPriorityLabel = (priority: WhatsAppInboxThread['priority']) => priority === 'high' ? 'Tinggi' : 'Sedang'

const openThread = (threadId: number) => {
  if (forceCreateConversation.value) {
    router.replace({
      query: {
        ...route.query,
        create: undefined,
      },
    })
  }

  activeThreadId.value = threadId
}

const openCustomerProfile = () => {
  if (!matchedCustomer.value || !ability.can('read', 'CrmCustomers'))
    return

  window.location.assign(`/pelanggan?customer=${matchedCustomer.value.id}&from=inbox`)
}

const syncThreadFromCustomerContext = () => {
  if (!threads.value.length)
    return

  const normalizedIdentity = normalizePhoneValue(identityValueFromQuery.value)
  const normalizedName = customerNameFromQuery.value.trim().toLowerCase()

  const exactThreadMatch = normalizedIdentity
    ? threads.value.find(thread => normalizePhoneValue(thread.phone) === normalizedIdentity)
    : null

  const nameThreadMatch = !exactThreadMatch && normalizedName
    ? threads.value.find(thread => thread.name.toLowerCase().includes(normalizedName))
    : null

  const matchedThread = exactThreadMatch ?? nameThreadMatch

  if (matchedThread) {
    activeThreadId.value = matchedThread.id

    if (forceCreateConversation.value)
      return

    return
  }

  if (openedFromCustomers.value && matchedCustomer.value) {
    activeThreadId.value = null
    return
  }

  if (normalizedName)
    searchQuery.value = customerNameFromQuery.value
}

const openCreateConversationForCustomer = () => {
  newConversationPriority.value = 'sedang'
  newConversationMessage.value = matchedCustomer.value
    ? `Halo ${matchedCustomer.value.nama}, kami menghubungi Anda dari KitCRM. Ada yang bisa kami bantu hari ini?`
    : ''
  conversationError.value = ''
  activeThreadId.value = null
}

const assignToMe = async () => {
  if (!activeThread.value || !currentUser.value?.id || !canUpdateTickets.value)
    return

  isThreadActionLoading.value = true

  try {
    await $api(`/crm/tiket/${activeThread.value.ticketId ?? activeThread.value.id}/assign`, {
      method: 'PATCH',
      body: {
        assignedUserId: currentUser.value.id,
      },
    })

    await fetchWhatsAppInbox()
  }
  finally {
    isThreadActionLoading.value = false
  }
}

const releaseThread = async () => {
  if (!activeThread.value || !canUpdateTickets.value)
    return

  isThreadActionLoading.value = true

  try {
    await $api(`/crm/tiket/${activeThread.value.ticketId ?? activeThread.value.id}/assign`, {
      method: 'PATCH',
      body: {
        assignedUserId: null,
      },
    })

    await fetchWhatsAppInbox()
  }
  finally {
    isThreadActionLoading.value = false
  }
}

const markDone = async () => {
  if (!activeThread.value || !canUpdateTickets.value)
    return

  isThreadActionLoading.value = true

  try {
    await $api(`/crm/tiket/${activeThread.value.ticketId ?? activeThread.value.id}/status`, {
      method: 'PATCH',
      body: {
        status: 'selesai',
      },
    })

    await fetchWhatsAppInbox()
  }
  finally {
    isThreadActionLoading.value = false
  }
}

const sendReply = async () => {
  if (!activeThread.value || !canUpdateInbox.value || (!replyMessage.value.trim() && !selectedAttachmentName.value))
    return

  const messageParts = [replyMessage.value.trim(), selectedAttachmentName.value ? `[Lampiran: ${selectedAttachmentName.value}]` : ''].filter(Boolean)

  isThreadActionLoading.value = true

  try {
    if (!activeThread.value.assignedTo && currentUser.value?.id) {
      await $api(`/crm/tiket/${activeThread.value.ticketId ?? activeThread.value.id}/assign`, {
        method: 'PATCH',
        body: {
          assignedUserId: currentUser.value.id,
        },
      })
    }

    await $api(`/crm/inbox/conversations/${activeThread.value.ticketId ?? activeThread.value.id}/reply`, {
      method: 'POST',
      body: {
        isiPesan: messageParts.join(' '),
        mode: 'customer',
        status: 'diproses',
      },
    })

    await fetchWhatsAppInbox()
  }
  finally {
    isThreadActionLoading.value = false
  }

  replyMessage.value = ''
  selectedAttachmentName.value = ''
}

const createConversation = async () => {
  if (!matchedCustomer.value || !canCreateTickets.value || !newConversationMessage.value.trim() || isCreatingConversation.value)
    return

  isCreatingConversation.value = true
  conversationError.value = ''

  try {
    const response = await $api<{ tiket: { id: number } }>('/crm/tiket', {
      method: 'POST',
      body: {
        pelangganId: matchedCustomer.value.id,
        assignedUserId: currentUser.value?.id ?? null,
        kategori: 'general',
        subjek: `Percakapan WhatsApp dengan ${matchedCustomer.value.nama}`,
        prioritas: newConversationPriority.value,
        isiPesan: newConversationMessage.value.trim(),
      },
    })

    await router.replace({
      query: {
        ...route.query,
        create: undefined,
      },
    })

    await fetchWhatsAppInbox()
    activeThreadId.value = response.tiket.id
    replyMessage.value = ''
  }
  catch (error: any) {
    conversationError.value = error?.data?.message || 'Gagal membuat percakapan WhatsApp baru.'
  }
  finally {
    isCreatingConversation.value = false
  }
}

const openAttachmentPicker = () => {
  attachmentInputRef.value?.click()
}

const handleAttachmentChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  selectedAttachmentName.value = file?.name ?? ''
}

const clearAttachment = () => {
  selectedAttachmentName.value = ''

  if (attachmentInputRef.value)
    attachmentInputRef.value.value = ''
}

onMounted(() => {
  activeThreadId.value = threads.value[0]?.id ?? null
  syncThreadFromCustomerContext()

  if (openedFromCustomers.value)
    openCreateConversationForCustomer()
})

watch(filteredThreads, value => {
  if (shouldShowCreateConversationState.value)
    return

  if (!value.some(thread => thread.id === activeThreadId.value))
    activeThreadId.value = value[0]?.id ?? null
})

watch(threads, value => {
  if (!value.length)
    return

  if (!shouldShowCreateConversationState.value && !value.some(thread => thread.id === activeThreadId.value))
    activeThreadId.value = value[0]?.id ?? null

  syncThreadFromCustomerContext()
}, { immediate: true })

watch(() => route.query, () => {
  syncThreadFromCustomerContext()
}, { deep: true })

watch(matchedCustomer, value => {
  if (!value)
    return

  if (openedFromCustomers.value && !contextualThread.value)
    openCreateConversationForCustomer()
}, { immediate: true })
</script>

<template>
  <section class="d-flex flex-column gap-4">
    <div>
      <div class="text-overline mb-2">Omnichannel / WhatsApp</div>
      <h5 class="text-h5 mb-0">Inbox</h5>
      <div v-if="openedFromCustomers" class="text-body-2 text-medium-emphasis mt-1">
        Konteks pelanggan: {{ customerNameFromQuery || 'Pelanggan' }}
      </div>
      <VAlert
        v-if="whatsappWorkspaceAccessMessage"
        color="warning"
        variant="tonal"
        class="mt-3"
      >
        {{ whatsappWorkspaceAccessMessage }}
      </VAlert>
    </div>

    <VCard class="whatsapp-inbox-shell overflow-hidden">
      <div class="whatsapp-inbox-grid">
        <aside class="inbox-list-panel">
          <div class="pa-4 border-b-sm">
            <AppTextField
              v-model="searchQuery"
              prepend-inner-icon="tabler-search"
              placeholder="Cari nama atau nomor..."
            />
          </div>

          <div class="d-flex gap-2 px-4 py-4 flex-wrap border-b-sm">
            <VChip
              v-for="tab in filterTabs"
              :key="tab.value"
              :color="activeTab === tab.value ? 'primary' : undefined"
              :variant="activeTab === tab.value ? 'flat' : 'outlined'"
              class="queue-chip"
              @click="activeTab = tab.value"
            >
              {{ tab.title }}
              <span class="ms-1">{{ countByTab(tab.value) }}</span>
            </VChip>
          </div>

          <div class="thread-scroll-area">
            <button
              v-for="thread in filteredThreads"
              :key="thread.id"
              type="button"
              class="thread-item text-start"
              :class="{ 'thread-item--active': activeThread?.id === thread.id }"
              @click="openThread(thread.id)"
            >
              <VAvatar :color="thread.avatarColor" size="42" class="flex-shrink-0">
                <span class="text-white font-weight-bold">{{ thread.initials }}</span>
              </VAvatar>

              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-center justify-space-between gap-3 mb-1">
                  <div class="font-weight-medium text-high-emphasis text-truncate">{{ thread.name }}</div>
                  <span class="text-caption text-medium-emphasis">{{ thread.lastTime }}</span>
                </div>
                <div class="text-body-2 text-medium-emphasis text-truncate mb-1">{{ thread.lastSnippet }}</div>
                <div class="d-flex flex-wrap align-center gap-2 mb-1">
                  <VChip size="x-small" :color="resolveThreadDirectionColor(thread)" variant="tonal">
                    {{ resolveThreadDirectionLabel(thread) }}
                  </VChip>
                  <VChip size="x-small" :color="resolveThreadStatusColor(thread.status)" variant="outlined">
                    {{ resolveThreadStatusLabel(thread.status) }}
                  </VChip>
                </div>
                <div class="d-flex align-center gap-2">
                  <span class="thread-dot" :class="thread.unread ? 'thread-dot--active' : ''" />
                  <span class="text-caption text-medium-emphasis">{{ thread.phone }}</span>
                </div>
              </div>
            </button>

            <div v-if="!filteredThreads.length" class="pa-6 text-body-2 text-medium-emphasis text-center">
              Tidak ada thread inbox yang cocok.
            </div>
          </div>
        </aside>

        <template v-if="activeThread">
          <main class="chat-panel">
            <div class="chat-header border-b-sm d-flex align-center gap-3 px-5 py-4">
              <VAvatar :color="activeThread.avatarColor" size="42">
                <span class="text-white font-weight-bold">{{ activeThread.initials }}</span>
              </VAvatar>

              <div class="flex-grow-1">
                <div class="font-weight-medium text-high-emphasis">{{ activeThread.name }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ activeThread.phone }}</div>
              </div>

              <VBtn
                v-if="matchedCustomer && ability.can('read', 'CrmCustomers')"
                color="primary"
                variant="tonal"
                prepend-icon="tabler-user-circle"
                @click="openCustomerProfile"
              >
                Pelanggan 360
              </VBtn>
            </div>

            <div class="chat-body">
              <div class="chat-date-pill">{{ todayLabel }}</div>

              <div
                v-for="message in activeThread.messages"
                :key="message.id"
                class="message-row"
                :class="message.sender === 'agent' ? 'message-row--agent' : 'message-row--customer'"
              >
                <div class="message-bubble">
                  <div>{{ message.text }}</div>
                  <div class="text-caption text-medium-emphasis mt-2">{{ message.time }}</div>
                </div>
              </div>
            </div>

            <div class="chat-input-area border-t-sm">
              <div class="d-flex flex-column gap-3 pa-4">
                <div v-if="selectedAttachmentName" class="d-flex align-center justify-space-between gap-3 attachment-chip rounded px-3 py-2">
                  <div class="d-flex align-center gap-2 text-body-2">
                    <VIcon icon="tabler-paperclip" size="16" />
                    <span class="text-truncate">{{ selectedAttachmentName }}</span>
                  </div>
                  <IconBtn size="x-small" @click="clearAttachment">
                    <VIcon icon="tabler-x" size="16" />
                  </IconBtn>
                </div>

                <input
                  ref="attachmentInputRef"
                  type="file"
                  hidden
                  @change="handleAttachmentChange"
                >

                <div class="d-flex align-center gap-3">
                <VTextField
                  v-model="replyMessage"
                  :disabled="!canUpdateInbox"
                  variant="solo"
                  flat
                  hide-details
                  placeholder="Ketik pesan..."
                  class="flex-grow-1"
                  @keyup.enter="sendReply"
                >
                  <template #prepend-inner>
                    <VIcon icon="tabler-file-description" size="18" />
                  </template>
                </VTextField>

                <VBtn v-if="canUpdateInbox" icon variant="text" color="secondary" @click="openAttachmentPicker">
                  <VIcon icon="tabler-paperclip" />
                </VBtn>

                <VBtn v-if="canUpdateInbox" icon color="primary" @click="sendReply">
                  <VIcon icon="tabler-send" />
                </VBtn>
                </div>
              </div>
            </div>
          </main>

          <aside class="thread-detail-panel">
            <div class="pa-6 d-flex flex-column align-center text-center border-b-sm">
              <VAvatar :color="activeThread.avatarColor" size="66" class="mb-4">
                <span class="text-h4 text-white font-weight-bold">{{ activeThread.initials }}</span>
              </VAvatar>
              <div class="text-h6 mb-1">{{ activeThread.name }}</div>
              <div class="text-body-2 text-medium-emphasis mb-4">{{ activeThread.phone }}</div>
              <div class="d-flex gap-2 flex-wrap justify-center">
                <VChip size="small" :color="resolveThreadDirectionColor(activeThread)" variant="tonal">
                  {{ resolveThreadDirectionLabel(activeThread) }}
                </VChip>
                <VChip size="small" :color="resolveThreadStatusColor(activeThread.status)" variant="outlined">
                  {{ resolveThreadStatusLabel(activeThread.status) }}
                </VChip>
                <VChip v-for="label in activeThread.labels" :key="label" size="small" color="primary" variant="tonal">
                  {{ label }}
                </VChip>
              </div>
            </div>

            <div class="pa-6 border-b-sm">
              <div class="text-overline mb-4 text-medium-emphasis">Informasi</div>
              <div class="d-flex flex-column gap-4 text-body-2">
                <div class="d-flex justify-space-between gap-3">
                  <span class="text-medium-emphasis">Status</span>
                  <span class="font-weight-medium">{{ resolveThreadStatusLabel(activeThread.status) }}</span>
                </div>
                <div class="d-flex justify-space-between gap-3">
                  <span class="text-medium-emphasis">Prioritas</span>
                  <span class="font-weight-medium">{{ resolveThreadPriorityLabel(activeThread.priority) }}</span>
                </div>
                <div class="d-flex justify-space-between gap-3">
                    <span class="text-medium-emphasis">Ditangani</span>
                  <span class="font-weight-medium">{{ activeThread.assignedTo ?? 'Belum diambil' }}</span>
                </div>
              </div>
            </div>

            <div class="pa-6 d-flex flex-column gap-3">
              <div class="text-overline text-medium-emphasis">Aksi</div>
              <VBtn v-if="canUpdateTickets" color="primary" block prepend-icon="tabler-check" @click="markDone">
                Tandai Selesai
              </VBtn>
              <VBtn v-if="canUpdateTickets && activeThread.assignedTo !== currentAgent" variant="outlined" color="secondary" block prepend-icon="tabler-hand-click" @click="assignToMe">
                Ambil
              </VBtn>
              <VBtn v-else-if="canUpdateTickets" variant="outlined" color="secondary" block prepend-icon="tabler-logout" @click="releaseThread">
                Lepas
              </VBtn>
            </div>
          </aside>
        </template>

        <div v-else class="whatsapp-empty-state d-flex align-center justify-center">
          <VCard v-if="shouldShowCreateConversationState" class="whatsapp-create-card" variant="text">
            <VCardText>
              <div class="text-overline text-medium-emphasis mb-2">Konteks Pelanggan</div>
              <div class="text-h5 text-high-emphasis mb-2">Mulai percakapan WhatsApp baru</div>
              <div class="text-body-2 text-medium-emphasis mb-6">
                Belum ada thread WhatsApp aktif untuk {{ matchedCustomer?.nama }}. Buat percakapan outbound agar agent bisa langsung follow up dari inbox ini.
              </div>

              <div class="d-flex flex-wrap gap-3 mb-4">
                <VChip size="small" color="primary" variant="tonal">
                  {{ matchedCustomer?.nama }}
                </VChip>
                <VChip size="small" color="secondary" variant="tonal">
                  {{ matchedCustomer?.noHp || identityValueFromQuery || '-' }}
                </VChip>
              </div>

              <VAlert
                v-if="conversationError"
                type="error"
                variant="tonal"
                class="mb-4"
              >
                {{ conversationError }}
              </VAlert>

              <VSelect
                v-model="newConversationPriority"
                class="mb-4"
                label="Prioritas"
                :items="[
                  { title: 'Rendah', value: 'rendah' },
                  { title: 'Sedang', value: 'sedang' },
                  { title: 'Tinggi', value: 'tinggi' },
                ]"
              />

              <VTextarea
                v-model="newConversationMessage"
                label="Pesan pembuka"
                rows="5"
                auto-grow
                class="mb-4"
              />

              <div class="d-flex flex-wrap gap-3">
                <VBtn
                  color="primary"
                  prepend-icon="tabler-message-circle-plus"
                  :loading="isCreatingConversation"
                  :disabled="!canCreateConversation"
                  @click="createConversation"
                >
                  Buat percakapan WhatsApp
                </VBtn>
                <VBtn
                  v-if="ability.can('read', 'CrmCustomers')"
                  variant="outlined"
                  color="secondary"
                  prepend-icon="tabler-user-circle"
                  @click="openCustomerProfile"
                >
                  Kembali ke Pelanggan 360
                </VBtn>
              </div>
            </VCardText>
          </VCard>

          <div v-else class="text-body-1 text-medium-emphasis">
            Pilih salah satu thread inbox untuk mulai membalas percakapan.
          </div>
        </div>
      </div>
    </VCard>
  </section>
</template>

<style scoped>
.whatsapp-inbox-shell {
  min-block-size: calc(100vh - 15rem);
}

.whatsapp-inbox-grid {
  display: grid;
  grid-template-columns: 270px minmax(0, 1fr) 250px;
  min-block-size: calc(100vh - 15rem);
}

.inbox-list-panel,
.thread-detail-panel {
  background: rgb(var(--v-theme-surface));
}

.chat-panel,
.inbox-list-panel,
.thread-detail-panel {
  min-block-size: 100%;
}

.chat-panel,
.thread-detail-panel,
.inbox-list-panel {
  border-inline-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.thread-detail-panel {
  border-inline-end: 0;
}

.thread-scroll-area {
  overflow: auto;
  max-block-size: calc(100vh - 23rem);
}

.thread-item {
  display: flex;
  gap: 0.875rem;
  inline-size: 100%;
  padding: 0.875rem 1rem;
  border: 0;
  background: transparent;
  border-inline-start: 3px solid transparent;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.thread-item:hover,
.thread-item--active {
  background: rgba(var(--v-theme-primary), 0.08);
  border-inline-start-color: rgb(var(--v-theme-primary));
}

.thread-dot {
  inline-size: 8px;
  block-size: 8px;
  border-radius: 999px;
  background: rgba(var(--v-theme-on-surface), 0.18);
}

.thread-dot--active {
  background: rgb(var(--v-theme-success));
}

.queue-chip {
  cursor: pointer;
  font-weight: 600;
}

.chat-panel {
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

.chat-body {
  flex: 1;
  overflow: auto;
  padding: 1.5rem;
  background-color: #f8fafc;
  background-image:
    radial-gradient(circle at 1px 1px, rgba(148, 163, 184, 0.16) 1px, transparent 0);
  background-size: 22px 22px;
}

.chat-date-pill {
  width: fit-content;
  margin: 0 auto 1.5rem;
  padding: 0.45rem 0.9rem;
  border-radius: 999px;
  background: white;
  box-shadow: 0 10px 24px rgba(58, 71, 101, 0.08);
  color: rgba(var(--v-theme-on-surface), 0.72);
  font-size: 0.75rem;
  font-weight: 600;
}

.message-row {
  display: flex;
  margin-block-end: 1rem;
}

.message-row--agent {
  justify-content: flex-end;
}

.message-bubble {
  max-inline-size: min(70%, 430px);
  padding: 0.875rem 1rem;
  border-radius: 18px;
  background: white;
  box-shadow: 0 12px 24px rgba(58, 71, 101, 0.08);
  color: rgba(var(--v-theme-on-surface), 0.86);
}

.message-row--agent .message-bubble {
  border-end-end-radius: 6px;
}

.message-row--customer .message-bubble {
  border-end-start-radius: 6px;
}

.chat-input-area {
  background: white;
}

.attachment-chip {
  background: rgba(var(--v-theme-primary), 0.08);
  border: 1px solid rgba(var(--v-theme-primary), 0.14);
}

.whatsapp-empty-state {
  min-block-size: 100%;
  padding: 2rem;
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-success), 0.12), transparent 28%),
    linear-gradient(180deg, rgba(var(--v-theme-surface), 1) 0%, rgba(var(--v-theme-surface), 0.98) 100%);
}

.whatsapp-create-card {
  max-inline-size: 720px;
  inline-size: 100%;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 24px;
  background: rgba(var(--v-theme-surface), 0.94);
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
}

@media (max-width: 1279px) {
  .whatsapp-inbox-grid {
    grid-template-columns: 250px minmax(0, 1fr);
  }

  .thread-detail-panel {
    grid-column: 1 / -1;
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

@media (max-width: 959px) {
  .whatsapp-inbox-shell,
  .whatsapp-inbox-grid {
    min-block-size: auto;
  }

  .whatsapp-inbox-grid {
    grid-template-columns: 1fr;
  }

  .inbox-list-panel,
  .chat-panel {
    border-inline-end: 0;
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .thread-scroll-area {
    max-block-size: 360px;
  }
}
</style>