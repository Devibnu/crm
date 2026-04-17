<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { ComposeEmailPayload } from '@/views/apps/email/useEmail'
import type { MoveEmailToAction } from '@/views/apps/email/useEmail'
import { useEmail } from '@/views/apps/email/useEmail'
import type { Email } from '@db/apps/email/types'

interface Props {
  email: Email | null
  emailMeta: {
    hasPreviousEmail: boolean
    hasNextEmail: boolean
  }
  canManageEmail?: boolean
  canComposeEmail?: boolean
  customerProfile?: {
    id: number
    name: string
  } | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'navigated', direction: 'previous' | 'next'): void
  (e: 'close'): void
  (e: 'trash'): void
  (e: 'unread'): void
  (e: 'read'): void
  (e: 'star'): void
  (e: 'unstar'): void
  (e: 'openCustomer'): void
}>()

const emailReply = ref('')
const showReplyBox = ref(false)
const showForwardBox = ref(false)
const showReplyCard = ref(true)
const isSendingReply = ref(false)
const isSendingForward = ref(false)
const replyError = ref('')
const forwardError = ref('')
const forwardTo = ref('')
const emailForward = ref('')
const { updateEmailLabels, sendEmail } = useEmail()

const { labels, resolveLabelColor, emailMoveToFolderActions, shallShowMoveToActionFor, moveSelectedEmailTo } = useEmail()

const replySubject = computed(() => {
  const subject = props.email?.subject?.trim() ?? ''

  if (!subject)
    return 'Re: Follow up'

  return /^re:/i.test(subject) ? subject : `Re: ${subject}`
})

const forwardSubject = computed(() => {
  const subject = props.email?.subject?.trim() ?? ''

  if (!subject)
    return 'Fwd: Follow up'

  return /^fwd:/i.test(subject) ? subject : `Fwd: ${subject}`
})

const canSendReply = computed(() => Boolean(emailReply.value.trim()) && !isSendingReply.value)
const canSendForward = computed(() => normalizeRecipients(forwardTo.value).length > 0 && Boolean(emailForward.value.trim()) && !isSendingForward.value)

const normalizeRecipients = (value: string): ComposeEmailPayload['to'] => value
  .split(',')
  .map(item => item.trim())
  .filter(Boolean)
  .map(email => ({ email }))

const getRecipientSummary = () => props.email?.to
  ?.map(recipient => recipient.name ? `${recipient.name} <${recipient.email}>` : recipient.email)
  .join(', ') ?? ''

const buildForwardMessage = () => {
  if (!props.email)
    return ''

  const originalDate = props.email.time ? new Date(props.email.time).toLocaleString() : '-'
  const toSummary = getRecipientSummary()

  return [
    '<p></p>',
    '<p>---------- Forwarded message ---------</p>',
    `<p><strong>From:</strong> ${props.email.from.name} &lt;${props.email.from.email}&gt;</p>`,
    `<p><strong>Date:</strong> ${originalDate}</p>`,
    toSummary ? `<p><strong>To:</strong> ${toSummary}</p>` : '',
    `<p><strong>Subject:</strong> ${props.email.subject}</p>`,
    `<blockquote style="margin:16px 0 0;padding-inline-start:12px;border-inline-start:3px solid rgba(0,0,0,0.12);">${props.email.message}</blockquote>`,
  ].filter(Boolean).join('')
}

const handleMoveMailsTo = async (action: MoveEmailToAction) => {
  if (!props.canManageEmail)
    return

  await moveSelectedEmailTo(action, [(props.email as Email).id])
  emit('refresh')
  emit('close')
}

const updateMailLabel = async (label: Email['labels'][number]) => {
  if (!props.canManageEmail)
    return

  await updateEmailLabels([(props.email as Email).id], label)

  emit('refresh')
}

const resetReplyState = () => {
  showReplyBox.value = false
  showReplyCard.value = true
  emailReply.value = ''
  replyError.value = ''
}

const resetForwardState = () => {
  showForwardBox.value = false
  showReplyCard.value = true
  forwardTo.value = ''
  emailForward.value = ''
  forwardError.value = ''
}

const openReplyComposer = () => {
  if (!props.canComposeEmail)
    return

  showReplyBox.value = true
  showForwardBox.value = false
  showReplyCard.value = false
  replyError.value = ''
}

const openForwardComposer = () => {
  if (!props.canComposeEmail)
    return

  showForwardBox.value = true
  showReplyBox.value = false
  showReplyCard.value = false
  forwardError.value = ''
  emailForward.value = buildForwardMessage()
}

const sendReply = async () => {
  if (!props.email || !props.canComposeEmail || !emailReply.value.trim() || isSendingReply.value)
    return

  isSendingReply.value = true
  replyError.value = ''

  const payload: ComposeEmailPayload = {
    to: [{
      email: props.email.from.email,
      name: props.email.from.name,
    }],
    subject: replySubject.value,
    message: emailReply.value,
    customerId: props.customerProfile?.id ?? null,
  }

  try {
    await sendEmail(payload)
    resetReplyState()
    emit('refresh')
  }
  catch (error: any) {
    replyError.value = error?.data?.message || 'Balasan email gagal dikirim.'
  }
  finally {
    isSendingReply.value = false
  }
}

const sendForward = async () => {
  if (!props.email || !props.canComposeEmail || !canSendForward.value)
    return

  isSendingForward.value = true
  forwardError.value = ''

  const payload: ComposeEmailPayload = {
    to: normalizeRecipients(forwardTo.value),
    subject: forwardSubject.value,
    message: emailForward.value,
    customerId: props.customerProfile?.id ?? null,
  }

  try {
    await sendEmail(payload)
    resetForwardState()
    emit('refresh')
  }
  catch (error: any) {
    forwardError.value = error?.data?.message || 'Email gagal diteruskan.'
  }
  finally {
    isSendingForward.value = false
  }
}
</script>

<template>
  <!-- ℹ️ calc(100% - 256px) => 265px is left sidebar width -->
  <VNavigationDrawer
    data-allow-mismatch
    temporary
    :model-value="!!props.email"
    location="right"
    :scrim="false"
    floating
    class="email-view"
  >
    <template v-if="props.email">
      <!-- 👉 header -->

      <div class="email-view-header d-flex align-center px-5 py-3">
        <IconBtn
          class="me-2"
          @click="$emit('close'); resetReplyState(); resetForwardState()"
        >
          <VIcon
            size="22"
            icon="tabler-chevron-left"
            class="flip-in-rtl"
          />
        </IconBtn>

        <div class="d-flex align-center flex-wrap flex-grow-1 overflow-hidden gap-2">
          <div class="text-body-1 text-high-emphasis text-truncate">
            {{ props.email.subject }}
          </div>

          <div class="d-flex flex-wrap gap-2">
            <VChip
              v-for="label in props.email.labels"
              :key="label"
              :color="resolveLabelColor(label)"
              class="text-capitalize flex-shrink-0"
              size="small"
              :label="false"
            >
              {{ label }}
            </VChip>
          </div>
        </div>

        <div>
          <div class="d-flex align-center">
            <IconBtn
              :disabled="!props.emailMeta.hasPreviousEmail"
              @click="$emit('navigated', 'previous')"
            >
              <VIcon
                icon="tabler-chevron-left"
                class="flip-in-rtl"
              />
            </IconBtn>

            <IconBtn
              :disabled="!props.emailMeta.hasNextEmail"
              @click="$emit('navigated', 'next')"
            >
              <VIcon
                icon="tabler-chevron-right"
                class="flip-in-rtl"
              />
            </IconBtn>
          </div>
        </div>
      </div>

      <VDivider />

      <!-- 👉 Action bar -->
      <div class="email-view-action-bar d-flex align-center text-medium-emphasis px-6 gap-x-1">
        <!-- Trash -->
        <IconBtn
          v-if="props.canManageEmail"
          v-show="!props.email.isDeleted"
          @click="$emit('trash'); $emit('close')"
        >
          <VIcon
            icon="tabler-trash"
            size="22"
          />
          <VTooltip
            activator="parent"
            location="top"
          >
            Hapus email
          </VTooltip>
        </IconBtn>

        <!-- Read/Unread -->
        <IconBtn v-if="props.canManageEmail" @click.stop="$emit('unread'); $emit('close')">
          <VIcon
            icon="tabler-mail"
            size="22"
          />
          <VTooltip
            activator="parent"
            location="top"
          >
            Tandai belum dibaca
          </VTooltip>
        </IconBtn>

        <!-- Move to folder -->
        <IconBtn v-if="props.canManageEmail">
          <VIcon
            icon="tabler-folder"
            size="22"
          />
          <VTooltip
            activator="parent"
            location="top"
          >
            Pindahkan ke
          </VTooltip>

          <VMenu activator="parent">
            <VList density="compact">
              <template
                v-for="moveTo in emailMoveToFolderActions"
                :key="moveTo.title"
              >
                <VListItem
                  :class="shallShowMoveToActionFor(moveTo.action) ? 'd-flex' : 'd-none'"
                  class="align-center"
                  href="#"
                  @click="handleMoveMailsTo(moveTo.action)"
                >
                  <template #prepend>
                    <VIcon
                      :icon="moveTo.icon"
                      class="me-2"
                      size="20"
                    />
                  </template>
                  <VListItemTitle class="text-capitalize">
                    {{ moveTo.action }}
                  </VListItemTitle>
                </VListItem>
              </template>
            </VList>
          </VMenu>
        </IconBtn>

        <!-- Update labels -->
        <IconBtn>
          <VIcon
            icon="tabler-tag"
            size="22"
          />
          <VTooltip
            activator="parent"
            location="top"
          >
            Label
          </VTooltip>

          <VMenu activator="parent">
            <VList density="compact">
              <VListItem
                v-for="label in labels"
                :key="label.title"
                href="#"
                @click.stop="updateMailLabel(label.title)"
              >
                <template #prepend>
                  <VBadge
                    inline
                    :color="resolveLabelColor(label.title)"
                    dot
                  />
                </template>
                <VListItemTitle class="ms-2 text-capitalize">
                  {{ label.title }}
                </VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </IconBtn>

        <VSpacer />

        <div class="d-flex align-center gap-x-1">
          <VBtn
            v-if="props.customerProfile"
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="tabler-user-circle"
            class="me-2"
            @click="$emit('openCustomer')"
          >
            Pelanggan 360
          </VBtn>
          <!-- Star/Unstar -->
          <IconBtn
            v-if="props.canManageEmail"
            :color="props.email.isStarred ? 'warning' : 'default'"
            @click="props.email?.isStarred ? $emit('unstar') : $emit('star'); $emit('refresh')"
          >
            <VIcon icon="tabler-star" />
          </IconBtn>
          <IconBtn>
            <VIcon icon="tabler-dots-vertical" />
          </IconBtn>
        </div>
      </div>

      <VDivider />

      <!-- 👉 Mail Content -->
      <PerfectScrollbar
        tag="div"
        class="mail-content-container flex-grow-1 pa-sm-12 pa-6"
        :options="{ wheelPropagation: false }"
      >
        <VCard class="mb-4">
          <div class="d-flex align-start align-sm-center pa-6 gap-x-4">
            <VAvatar size="38">
              <VImg
                :src="props.email.from.avatar"
                :alt="props.email.from.name"
              />
            </VAvatar>

            <div class="d-flex flex-wrap flex-grow-1 overflow-hidden">
              <div class="text-truncate">
                <div class="text-body-1 text-high-emphasis text-truncate">
                  {{ props.email.from.name }}
                </div>
                <div class="text-sm">
                  {{ props.email.from.email }}
                </div>
                <div v-if="props.customerProfile" class="text-sm text-primary mt-1 cursor-pointer" @click="$emit('openCustomer')">
                  {{ props.customerProfile.name }} / Pelanggan 360
                </div>
              </div>

              <VSpacer />

              <div class="d-flex align-center gap-x-4">
                <div class="text-disabled text-base">
                  {{ new Date(props.email.time).toDateString() }}
                </div>
                <div>
                  <IconBtn v-show="props.email.attachments.length">
                    <VIcon
                      icon="tabler-paperclip"
                      size="22"
                    />
                  </IconBtn>
                  <IconBtn>
                    <VIcon
                      icon="tabler-dots-vertical"
                      size="22"
                    />
                  </IconBtn>
                </div>
              </div>
            </div>
          </div>

          <VDivider />

          <VCardText>
            <!-- eslint-disable vue/no-v-html -->
            <div class="text-body-1 font-weight-medium text-truncate mb-4">
              {{ props.email.from.name }},
            </div>
            <div
              class="text-base"
              v-html="props.email.message"
            />
            <!-- eslint-enable -->
          </VCardText>

          <template v-if="props.email.attachments.length">
            <VDivider />

            <VCardText class="d-flex flex-column gap-y-4 pt-4">
              <span>{{ props.email.attachments.length }} lampiran</span>
              <div
                v-for="attachment in props.email.attachments"
                :key="attachment.fileName"
                class="d-flex align-center"
              >
                <VImg
                  :src="attachment.thumbnail"
                  :alt="attachment.fileName"
                  aspect-ratio="1"
                  max-height="24"
                  max-width="24"
                  class="me-2"
                />
                <span>{{ attachment.fileName }}</span>
              </div>
            </VCardText>
          </template>
        </VCard>

        <!-- Reply or Forward -->
        <VCard v-if="props.canComposeEmail" v-show="showReplyCard">
          <VCardText class="font-weight-medium text-high-emphasis">
            <div class="text-base">
              Klik di sini untuk <span
                class="text-primary cursor-pointer"
                @click="openReplyComposer"
              >
                membalas
              </span> atau <span class="text-primary cursor-pointer" @click="openForwardComposer">
                meneruskan
              </span>
            </div>
          </VCardText>
        </VCard>

        <VCard v-if="props.canComposeEmail && showReplyBox">
          <VCardText>
            <h6 class="text-h6 mb-6">
              Balas ke {{ email?.from.name }}
            </h6>
            <VAlert
              v-if="replyError"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              {{ replyError }}
            </VAlert>
            <TiptapEditor
              v-model="emailReply"
              placeholder="Tulis balasan Anda..."
            />
            <div class="d-flex justify-end gap-4 pt-2 flex-wrap">
              <VBtn
                icon
                variant="text"
                color="secondary"
                @click="resetReplyState()"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
              <VBtn
                variant="text"
                color="secondary"
              >
                <template #prepend>
                  <VIcon
                    icon="tabler-paperclip"
                    class="text-high-emphasis"
                    size="16"
                  />
                </template>
                Lampiran
              </VBtn>
              <VBtn
                append-icon="tabler-send"
                :loading="isSendingReply"
                :disabled="!props.canComposeEmail || !canSendReply"
                @click="sendReply"
              >
                Kirim
              </VBtn>
            </div>
          </VCardText>
        </VCard>

        <VCard v-if="props.canComposeEmail && showForwardBox">
          <VCardText>
            <h6 class="text-h6 mb-4">
              Teruskan email
            </h6>
            <VAlert
              v-if="forwardError"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              {{ forwardError }}
            </VAlert>
            <VTextField
              v-model="forwardTo"
              class="mb-4"
              placeholder="penerima@example.com, lain@example.com"
              label="Teruskan ke"
            />
            <VTextField
              :model-value="forwardSubject"
              class="mb-4"
              label="Subjek"
              readonly
            />
            <TiptapEditor
              v-model="emailForward"
              placeholder="Tambahkan catatan singkat sebelum meneruskan..."
            />
            <div class="d-flex justify-end gap-4 pt-2 flex-wrap">
              <VBtn
                icon
                variant="text"
                color="secondary"
                @click="resetForwardState()"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
              <VBtn
                append-icon="tabler-send"
                :loading="isSendingForward"
                :disabled="!props.canComposeEmail || !canSendForward"
                @click="sendForward"
              >
                Teruskan
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </PerfectScrollbar>
    </template>
  </VNavigationDrawer>
</template>

<style lang="scss">
.email-view {
  &:not(.v-navigation-drawer--active) {
    transform: translateX(110%) !important;
  }

  inline-size: 100% !important;

  @media only screen and (min-width: 1280px) {
    inline-size: calc(100% - 256px) !important;
  }

  .v-navigation-drawer__content {
    display: flex;
    flex-direction: column;
  }

  .editor {
    padding-block-start: 0 !important;
    padding-inline: 0 !important;
  }

  .ProseMirror {
    padding: 0.5rem;
    block-size: 100px;
    overflow-y: auto;
    padding-block: 0.5rem;
  }
}

.email-view-action-bar {
  min-block-size: 56px;
}

.mail-content-container {
  background-color: rgb(var(--v-theme-on-surface), var(--v-hover-opacity));

  .mail-header {
    margin-block: 12px;
    margin-inline: 24px;
  }
}
</style>
