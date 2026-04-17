<script lang="ts" setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useDisplay, useTheme } from 'vuetify'
import { themes } from '@/plugins/vuetify/theme'
import ChatActiveChatUserProfileSidebarContent from '@/views/apps/chat/ChatActiveChatUserProfileSidebarContent.vue'
import ChatLeftSidebarContent from '@/views/apps/chat/ChatLeftSidebarContent.vue'
import ChatLog from '@/views/apps/chat/ChatLog.vue'
import ChatUserProfileSidebarContent from '@/views/apps/chat/ChatUserProfileSidebarContent.vue'
import { useChat } from '@/views/apps/chat/useChat'
import { useChatStore } from '@/views/apps/chat/useChatStore'
import type { ChatContact as TypeChatContact } from '@db/apps/chat/types'

definePage({
  meta: {
    action: 'read',
    layoutWrapperClasses: 'layout-content-height-fixed',
    subject: 'CrmInbox',
  },
})

const vuetifyDisplays = useDisplay()
const store = useChatStore()
const { isLeftSidebarOpen } = useResponsiveLeftSidebar(vuetifyDisplays.smAndDown)
const { resolveAvatarBadgeVariant } = useChat()
const router = useRouter()
const ability = useAbility()
const canUpdateInbox = computed(() => ability.can('update', 'CrmInbox'))
const liveChatAccessMessage = computed(() => {
  if (!canUpdateInbox.value)
    return 'Mode baca saja. Anda dapat memantau percakapan live chat, tetapi tidak bisa mengirim balasan dari workspace ini.'

  return ''
})

const chatLogPS = ref()

const scrollToBottomInChatLog = () => {
  const scrollEl = chatLogPS.value.$el || chatLogPS.value

  scrollEl.scrollTop = scrollEl.scrollHeight
}

const q = ref('')
const { data: pelangganData } = await useApi<any>(createUrl('/crm/pelanggan'))

watch(
  q,
  val => store.fetchChatsAndContacts(val),
  { immediate: true },
)

const startConversation = () => {
  if (vuetifyDisplays.mdAndUp.value)
    return
  isLeftSidebarOpen.value = true
}

const msg = ref('')

const sendMessage = async () => {
  if (!canUpdateInbox.value || !msg.value)
    return

  await store.sendMsg(msg.value)
  msg.value = ''

  nextTick(() => {
    scrollToBottomInChatLog()
  })
}

const openChatOfContact = async (userId: TypeChatContact['id']) => {
  await store.getChat(userId)
  msg.value = ''

  const contact = store.chatsContacts.find(c => c.id === userId)
  if (contact)
    contact.chat.unseenMsgs = 0

  if (vuetifyDisplays.smAndDown.value)
    isLeftSidebarOpen.value = false

  nextTick(() => {
    scrollToBottomInChatLog()
  })
}

const isUserProfileSidebarOpen = ref(false)
const isActiveChatUserProfileSidebarOpen = ref(false)
const refInputEl = ref<HTMLElement>()

const { name } = useTheme()

const normalizeValue = (value?: string | null) => value?.trim().toLowerCase() ?? ''

const activeChatSubtitle = computed(() => {
  if (matchedCustomer.value)
    return 'Terhubung ke data pelanggan yang sudah terdaftar'

  return 'Percakapan masuk dari kanal live chat'
})

const matchedCustomer = computed(() => {
  const activeContactName = normalizeValue(store.activeChat?.contact.fullName)

  if (!activeContactName)
    return null

  return (pelangganData.value?.pelanggan ?? []).find((customer: any) => normalizeValue(customer.nama) === activeContactName) ?? null
})

const openCustomerProfile = () => {
  if (!matchedCustomer.value || !ability.can('read', 'CrmCustomers'))
    return

  router.push({
    path: '/marketing-automation/customer-data-platform',
    query: {
      customer: String(matchedCustomer.value.id),
      from: 'live-chat',
    },
  })
}

const chatContentContainerBg = computed(() => {
  let color = 'transparent'

  if (themes)
    color = themes?.[name.value].colors?.background as string

  return color
})
</script>

<template>
  <section class="d-flex flex-column gap-4">
    <div>
      <div class="text-overline mb-2">Omnichannel / Inbox Live Chat</div>
      <h5 class="text-h5 mb-1">Inbox Live Chat</h5>
      <div class="text-body-2 text-medium-emphasis live-chat-subtitle">
        Workspace admin untuk menangani percakapan masuk dari customer. Halaman ini bukan portal customer dan belum mewakili widget chat di sisi customer.
      </div>
      <VAlert
        v-if="liveChatAccessMessage"
        color="warning"
        variant="tonal"
        class="mt-3"
      >
        {{ liveChatAccessMessage }}
      </VAlert>
    </div>

    <VLayout class="chat-app-layout" style="z-index: 0;">
      <VNavigationDrawer
        v-model="isUserProfileSidebarOpen"
        data-allow-mismatch
        temporary
        touchless
        absolute
        class="user-profile-sidebar"
        location="start"
        width="370"
      >
        <ChatUserProfileSidebarContent @close="isUserProfileSidebarOpen = false" />
      </VNavigationDrawer>

      <VNavigationDrawer
        v-model="isActiveChatUserProfileSidebarOpen"
        data-allow-mismatch
        width="374"
        absolute
        temporary
        location="end"
        touchless
        class="active-chat-user-profile-sidebar"
      >
        <ChatActiveChatUserProfileSidebarContent
          :matched-customer="matchedCustomer"
          :can-open-customer="ability.can('read', 'CrmCustomers')"
          @open-customer="openCustomerProfile"
          @close="isActiveChatUserProfileSidebarOpen = false"
        />
      </VNavigationDrawer>

      <VNavigationDrawer
        v-model="isLeftSidebarOpen"
        data-allow-mismatch
        absolute
        touchless
        location="start"
        width="370"
        :temporary="$vuetify.display.smAndDown"
        class="chat-list-sidebar"
        :permanent="$vuetify.display.mdAndUp"
      >
        <ChatLeftSidebarContent
          v-model:is-drawer-open="isLeftSidebarOpen"
          v-model:search="q"
          @open-chat-of-contact="openChatOfContact"
          @show-user-profile="isUserProfileSidebarOpen = true"
          @close="isLeftSidebarOpen = false"
        />
      </VNavigationDrawer>

      <VMain class="chat-content-container">
        <div v-if="store.activeChat" class="d-flex flex-column h-100">
        <div class="active-chat-header d-flex align-center text-medium-emphasis bg-surface">
          <IconBtn class="d-md-none me-3" @click="isLeftSidebarOpen = true">
            <VIcon icon="tabler-menu-2" />
          </IconBtn>

          <div class="d-flex align-center cursor-pointer" @click="isActiveChatUserProfileSidebarOpen = true">
            <VBadge
              dot
              location="bottom right"
              offset-x="3"
              offset-y="0"
              :color="resolveAvatarBadgeVariant(store.activeChat.contact.status)"
              bordered
            >
              <VAvatar
                size="40"
                :variant="!store.activeChat.contact.avatar ? 'tonal' : undefined"
                :color="!store.activeChat.contact.avatar ? resolveAvatarBadgeVariant(store.activeChat.contact.status) : undefined"
                class="cursor-pointer"
              >
                <VImg
                  v-if="store.activeChat.contact.avatar"
                  :src="store.activeChat.contact.avatar"
                  :alt="store.activeChat.contact.fullName"
                />
                <span v-else>{{ avatarText(store.activeChat.contact.fullName) }}</span>
              </VAvatar>
            </VBadge>

            <div class="flex-grow-1 ms-4 overflow-hidden">
              <div class="text-h6 mb-0 font-weight-regular">
                {{ store.activeChat.contact.fullName }}
              </div>
              <p class="text-truncate mb-0 text-body-2">
                {{ activeChatSubtitle }}
              </p>
            </div>
          </div>

          <VSpacer />

          <div class="d-sm-flex align-center d-none text-medium-emphasis gap-2">
            <VBtn
              v-if="matchedCustomer && ability.can('read', 'CrmCustomers')"
              color="primary"
              variant="tonal"
              size="small"
              prepend-icon="tabler-user-circle"
              @click="openCustomerProfile"
            >
              Pelanggan 360
            </VBtn>
            <IconBtn>
              <VIcon icon="tabler-search" />
            </IconBtn>
            <IconBtn>
              <VIcon icon="tabler-dots-vertical" />
            </IconBtn>
          </div>
        </div>

        <VDivider />

        <PerfectScrollbar
          ref="chatLogPS"
          tag="ul"
          :options="{ wheelPropagation: false }"
          class="flex-grow-1"
        >
          <ChatLog />
        </PerfectScrollbar>

        <VForm class="chat-log-message-form mb-5 mx-5" @submit.prevent="sendMessage">
          <VTextField
            :key="store.activeChat?.contact.id"
            v-model="msg"
            :disabled="!canUpdateInbox"
            variant="solo"
            density="default"
            class="chat-message-input"
            placeholder="Tulis balasan admin..."
            autofocus
          >
            <template #append-inner>
              <div class="d-flex gap-1">
                <IconBtn>
                  <VIcon icon="tabler-microphone" size="22" />
                </IconBtn>
                <IconBtn v-if="canUpdateInbox" @click="refInputEl?.click()">
                  <VIcon icon="tabler-paperclip" size="22" />
                </IconBtn>
                <div v-if="canUpdateInbox" class="d-none d-md-block">
                  <VBtn append-icon="tabler-send" @click="sendMessage">
                    Kirim
                  </VBtn>
                </div>
                <IconBtn v-if="canUpdateInbox" class="d-block d-md-none" @click="sendMessage">
                  <VIcon icon="tabler-send" />
                </IconBtn>
              </div>
            </template>
          </VTextField>

          <input
            ref="refInputEl"
            type="file"
            name="file"
            accept=".jpeg,.png,.jpg,GIF"
            hidden
          >
        </VForm>
      </div>

      <div v-else class="d-flex h-100 align-center justify-center flex-column">
        <VAvatar size="98" variant="tonal" color="primary" class="mb-4">
          <VIcon size="50" class="rounded-0" icon="tabler-message-2" />
        </VAvatar>
        <VBtn v-if="$vuetify.display.smAndDown" rounded="pill" @click="startConversation">
          Buka Percakapan
        </VBtn>

        <p
          v-else
          style="max-inline-size: 40ch; text-wrap: balance;"
          class="text-center text-disabled"
        >
          Pilih salah satu percakapan masuk di sisi kiri untuk mulai menangani pelanggan dari workspace admin.
        </p>
        </div>
      </VMain>
    </VLayout>
  </section>
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core/scss/base/mixins";

$chat-app-header-height: 76px;

%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 1.5rem;
}

.chat-start-conversation-btn {
  cursor: default;
}

.live-chat-subtitle {
  max-inline-size: 72ch;
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
  /* stylelint-disable-next-line value-keyword-case */
  background-color: v-bind(chatContentContainerBg);

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

.chat-user-profile-badge {
  .v-badge__badge {
    /* stylelint-disable liberty/use-logical-spec */
    min-width: 12px !important;
    height: 0.75rem;
    /* stylelint-enable liberty/use-logical-spec */
  }
}
</style>