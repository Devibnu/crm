<script lang="ts" setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useChat } from './useChat'
import { useChatStore } from '@/views/apps/chat/useChatStore'

interface Props {
  matchedCustomer?: {
    id: number
    nama: string
    email?: string | null
    noHp?: string | null
  } | null
  canOpenCustomer?: boolean
}

defineEmits<{
  (e: 'close'): void
  (e: 'openCustomer'): void
}>()

const props = defineProps<Props>()
const store = useChatStore()

const { resolveAvatarBadgeVariant } = useChat()
</script>

<template>
  <template v-if="store.activeChat">
    <!-- Close Button -->
    <div
      class="pt-6 px-6"
      :class="$vuetify.locale.isRtl ? 'text-left' : 'text-right'"
    >
      <IconBtn @click="$emit('close')">
        <VIcon
          icon="tabler-x"
          class="text-medium-emphasis"
        />
      </IconBtn>
    </div>

    <!-- User Avatar + Name + Role -->
    <div class="text-center px-6">
      <VBadge
        location="bottom right"
        offset-x="7"
        offset-y="4"
        bordered
        :color="resolveAvatarBadgeVariant(store.activeChat.contact.status)"
        class="chat-user-profile-badge mb-5"
      >
        <VAvatar
          size="84"
          :variant="!store.activeChat.contact.avatar ? 'tonal' : undefined"
          :color="!store.activeChat.contact.avatar ? resolveAvatarBadgeVariant(store.activeChat.contact.status) : undefined"
        >
          <VImg
            v-if="store.activeChat.contact.avatar"
            :src="store.activeChat.contact.avatar"
          />
          <span
            v-else
            class="text-3xl"
          >{{ avatarText(store.activeChat.contact.fullName) }}</span>
        </VAvatar>
      </VBadge>
      <h5 class="text-h5">
        {{ store.activeChat.contact.fullName }}
      </h5>
      <p class="text-body-1 mb-0">
        Kontak live chat
      </p>
      <p class="text-body-2 text-medium-emphasis mt-2 mb-0">
        Kontak inbound untuk workspace admin live chat.
      </p>
    </div>

    <!-- User Data -->
    <PerfectScrollbar
      class="ps-chat-user-profile-sidebar-content text-medium-emphasis pb-6 px-6"
      :options="{ wheelPropagation: false }"
    >
      <!-- Conversation Summary -->
      <div class="my-6">
        <div class="text-sm text-disabled">
          RINGKASAN KONTAK
        </div>
        <p class="mt-1 mb-6">
          Kontak ini masuk melalui alur live chat dan sedang dipantau dari workspace admin. Gunakan panel ini untuk menilai apakah percakapan perlu dihubungkan ke data pelanggan atau ditindaklanjuti ke Customer 360.
        </p>
      </div>

      <!-- Workspace Context -->
      <div class="mb-6">
        <div class="text-sm text-disabled mb-1">
          KONTEKS WORKSPACE
        </div>
        <div class="d-flex align-center text-high-emphasis pa-2">
          <VIcon
            class="me-2"
            icon="tabler-user"
            size="22"
          />
          <div class="text-base">
            {{ store.activeChat.contact.fullName }}
          </div>
        </div>
        <div class="d-flex align-center text-high-emphasis pa-2">
          <VIcon
            class="me-2"
            icon="tabler-message-circle"
            size="22"
          />
          <div class="text-base">
            Sumber: Live Chat
          </div>
        </div>
        <div class="d-flex align-center text-high-emphasis pa-2">
          <VIcon
            class="me-2"
            icon="tabler-broadcast"
            size="22"
          />
          <div class="text-base">
            Status kontak: {{ store.activeChat.contact.status }}
          </div>
        </div>
      </div>

      <div>
        <div class="text-sm text-disabled mb-1">
          CATATAN ADMIN
        </div>
        <VAlert v-if="props.matchedCustomer" color="success" variant="tonal" class="mt-2">
          Kontak ini cocok dengan customer <strong>{{ props.matchedCustomer.nama }}</strong>. Anda bisa membuka Customer 360 untuk melihat histori dan konteks penanganannya.

          <div v-if="props.canOpenCustomer" class="mt-3">
            <VBtn size="small" color="success" prepend-icon="tabler-user-circle" @click="$emit('openCustomer')">
              Buka Customer 360
            </VBtn>
          </div>
        </VAlert>
        <VAlert v-else color="info" variant="tonal" class="mt-2">
          Kontak live chat ini belum cocok dengan data pelanggan yang ada. Untuk fase sekarang, halaman ini diposisikan sebagai inbox admin untuk chat masuk.
        </VAlert>
      </div>
    </PerfectScrollbar>
  </template>
</template>
