<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import ComposeDialog from '@/views/apps/email/ComposeDialog.vue'
import EmailLeftSidebarContent from '@/views/apps/email/EmailLeftSidebarContent.vue'
import EmailView from '@/views/apps/email/EmailView.vue'
import type { ComposeEmailPayload } from '@/views/apps/email/useEmail'
import type { MoveEmailToAction } from '@/views/apps/email/useEmail'
import { useEmail } from '@/views/apps/email/useEmail'
import type { Email, EmailLabel } from '@db/apps/email/types'

definePage({
  meta: {
    action: 'read',
    layoutWrapperClasses: 'layout-content-height-fixed',
    navActiveLink: 'omnichannel-email-inbox',
    subject: 'CrmInbox',
  },
})

const { isLeftSidebarOpen } = useResponsiveLeftSidebar()
const ability = useAbility()
const route = useRoute<'omnichannel-email-inbox' | 'omnichannel-email-inbox-filter' | 'omnichannel-email-inbox-label'>()
const getQueryValue = (value: unknown) => typeof value === 'string' ? value : ''
const openedFromCustomers = computed(() => getQueryValue(route.query.from) === 'customers')
const customerNameFromQuery = computed(() => getQueryValue(route.query.customerName))
const identityValueFromQuery = computed(() => getQueryValue(route.query.identityValue))

const {
  labels,
  resolveLabelColor,
  emailMoveToFolderActions,
  shallShowMoveToActionFor,
  moveSelectedEmailTo,
  updateEmails,
  updateEmailLabels,
  sendEmail,
} = useEmail()

const isComposeDialogVisible = ref(false)
const isSendingEmail = ref(false)
const composeError = ref('')
const q = ref(identityValueFromQuery.value || customerNameFromQuery.value)
const selectedEmails = ref<Email['id'][]>([])
const hasAutoOpenedCustomerEmail = ref(false)
const canCreateInbox = computed(() => ability.can('create', 'CrmInbox'))
const canUpdateInbox = computed(() => ability.can('update', 'CrmInbox'))
const emailWorkspaceAccessMessage = computed(() => {
  if (!canCreateInbox.value && !canUpdateInbox.value)
    return 'Mode baca saja. Anda dapat meninjau email masuk, tetapi tidak bisa mengirim email baru atau mengubah status mailbox.'

  if (canCreateInbox.value && !canUpdateInbox.value)
    return 'Akses terbatas. Anda masih bisa menulis email baru, tetapi aksi mailbox seperti tandai baca, pindah folder, label, atau hapus dinonaktifkan.'

  return ''
})

const { data: pelangganData } = await useApi<any>(createUrl('/crm/pelanggan'))

const { data: emailData, execute: fetchEmails } = await useApi<any>(createUrl('/apps/email', {
  query: {
    q,
    filter: () => 'filter' in route.params ? route.params.filter : undefined,
    label: () => 'label' in route.params ? route.params.label : undefined,
  },
}))

const emails = computed<Email[]>(() => emailData.value.emails)
const emailsMeta = computed(() => emailData.value.emailsMeta)

const toggleSelectedEmail = (emailId: Email['id']) => {
  const emailIndex = selectedEmails.value.indexOf(emailId)
  if (emailIndex === -1)
    selectedEmails.value.push(emailId)
  else selectedEmails.value.splice(emailIndex, 1)
}

const selectAllEmailCheckbox = computed(
  () => emails.value.length && emails.value.length === selectedEmails.value.length,
)

const isSelectAllEmailCheckboxIndeterminate = computed(
  () => Boolean(selectedEmails.value.length) && emails.value.length !== selectedEmails.value.length,
)

const isAllMarkRead = computed(() => selectedEmails.value.every(emailId => emails.value.find(email => email.id === emailId)?.isRead))

const selectAllCheckboxUpdate = () => {
  selectedEmails.value = !selectAllEmailCheckbox.value
    ? emails.value.map(email => email.id)
    : []
}

const openedEmail = ref<Email | null>(null)
const matchedCustomer = computed(() => {
  if (getQueryValue(route.query.customer)) {
    const customerId = Number(getQueryValue(route.query.customer))

    return (pelangganData.value?.pelanggan ?? []).find((customer: any) => customer.id === customerId) ?? null
  }

  if (!openedEmail.value)
    return null

  const normalizedEmail = openedEmail.value.from.email?.trim().toLowerCase()
  const normalizedName = openedEmail.value.from.name?.trim().toLowerCase()

  return (pelangganData.value?.pelanggan ?? []).find((customer: any) => {
    return customer.email?.trim().toLowerCase() === normalizedEmail
      || customer.nama?.trim().toLowerCase() === normalizedName
  }) ?? null
})

const emailViewMeta = computed(() => {
  const returnValue = {
    hasNextEmail: false,
    hasPreviousEmail: false,
  }

  if (openedEmail.value) {
    const openedEmailIndex = emails.value.findIndex(e => e.id === openedEmail.value?.id)
    returnValue.hasNextEmail = !!emails.value[openedEmailIndex + 1]
    returnValue.hasPreviousEmail = !!emails.value[openedEmailIndex - 1]
  }

  return returnValue
})

const refreshOpenedEmail = async () => {
  await fetchEmails()

  if (openedEmail.value)
    openedEmail.value = emails.value.find(e => e.id === openedEmail.value?.id)!
}

const handleActionClick = async (
  action: 'trash' | 'unread' | 'read' | 'spam' | 'star' | 'unstar',
  emailIds: Email['id'][] = selectedEmails.value,
) => {
  if (!canUpdateInbox.value)
    return

  selectedEmails.value = []
  if (!emailIds.length)
    return

  if (action === 'trash')
    await updateEmails(emailIds, { isDeleted: true })
  else if (action === 'spam')
    await updateEmails(emailIds, { folder: 'spam' })
  else if (action === 'unread')
    await updateEmails(emailIds, { isRead: false })
  else if (action === 'read')
    await updateEmails(emailIds, { isRead: true })
  else if (action === 'star')
    await updateEmails(emailIds, { isStarred: true })
  else if (action === 'unstar')
    await updateEmails(emailIds, { isStarred: false })

  if (openedEmail.value)
    refreshOpenedEmail()
  else
    await fetchEmails()
}

const handleMoveMailsTo = async (action: MoveEmailToAction) => {
  if (!canUpdateInbox.value)
    return

  await moveSelectedEmailTo(action, selectedEmails.value)
  await fetchEmails()
}

const handleEmailLabels = async (labelTitle: EmailLabel) => {
  if (!canUpdateInbox.value)
    return

  await updateEmailLabels(selectedEmails.value, labelTitle)
  await fetchEmails()
}

const changeOpenedEmail = (dir: 'previous' | 'next') => {
  if (!openedEmail.value)
    return

  const openedEmailIndex = emails.value.findIndex(e => e.id === openedEmail.value?.id)
  const newEmailIndex = dir === 'previous' ? openedEmailIndex - 1 : openedEmailIndex + 1
  openedEmail.value = emails.value[newEmailIndex]
}

const openEmail = async (email: Email) => {
  openedEmail.value = email

  if (canUpdateInbox.value && !email.isRead)
    await handleActionClick('read', [email.id])
}

const openCustomerProfile = () => {
  if (!matchedCustomer.value || !ability.can('read', 'CrmCustomers'))
    return

  window.location.assign(`/pelanggan?customer=${matchedCustomer.value.id}&from=inbox`)
}

const composeInitialTo = computed(() => {
  if (matchedCustomer.value?.email)
    return matchedCustomer.value.email

  return identityValueFromQuery.value.includes('@') ? identityValueFromQuery.value : ''
})

const handleComposeSend = async (payload: ComposeEmailPayload) => {
  if (!canCreateInbox.value)
    return

  isSendingEmail.value = true
  composeError.value = ''

  try {
    await sendEmail({
      ...payload,
      customerId: matchedCustomer.value?.id ?? undefined,
    })
    await fetchEmails()
    isComposeDialogVisible.value = false
  }
  catch (error: any) {
    composeError.value = error?.data?.message || 'Email gagal dikirim.'
  }
  finally {
    isSendingEmail.value = false
  }
}

watch(
  () => route.params,
  () => { selectedEmails.value = [] },
  { deep: true },
)

watch(emails, async value => {
  if (!openedFromCustomers.value || hasAutoOpenedCustomerEmail.value || !value.length)
    return

  hasAutoOpenedCustomerEmail.value = true
  await openEmail(value[0])
}, { immediate: true })

watch(() => route.query, () => {
  q.value = identityValueFromQuery.value || customerNameFromQuery.value
  hasAutoOpenedCustomerEmail.value = false
}, { deep: true })
</script>

<template>
  <VLayout style="z-index: 0; min-block-size: 100%;" class="email-app-layout">
    <VAlert
      v-if="emailWorkspaceAccessMessage"
      color="warning"
      variant="tonal"
      class="ma-4 mb-0"
    >
      {{ emailWorkspaceAccessMessage }}
    </VAlert>

    <VNavigationDrawer
      v-model="isLeftSidebarOpen"
      data-allow-mismatch
      absolute
      touchless
      location="start"
      :temporary="$vuetify.display.mdAndDown"
    >
      <EmailLeftSidebarContent
        :emails-meta="emailsMeta"
        base-route-name="omnichannel-email-inbox"
        :can-compose-email="canCreateInbox"
        filter-route-name="omnichannel-email-inbox-filter"
        label-route-name="omnichannel-email-inbox-label"
        @toggle-compose-dialog-visibility="canCreateInbox ? (isComposeDialogVisible = !isComposeDialogVisible) : null"
      />
    </VNavigationDrawer>

    <EmailView
      :email="openedEmail"
      :email-meta="emailViewMeta"
      :can-compose-email="canCreateInbox"
      :can-manage-email="canUpdateInbox"
      :customer-profile="matchedCustomer && ability.can('read', 'CrmCustomers') ? { id: matchedCustomer.id, name: matchedCustomer.nama } : null"
      @refresh="refreshOpenedEmail"
      @navigated="changeOpenedEmail"
      @close="openedEmail = null"
      @open-customer="openCustomerProfile"
      @trash="handleActionClick('trash', openedEmail ? [openedEmail.id] : [])"
      @unread="handleActionClick('unread', openedEmail ? [openedEmail.id] : [])"
      @star="handleActionClick('star', openedEmail ? [openedEmail.id] : [])"
      @unstar="handleActionClick('unstar', openedEmail ? [openedEmail.id] : [])"
    />

    <VMain>
      <VCard flat class="email-content-list h-100 d-flex flex-column">
        <div class="d-flex align-center">
          <IconBtn class="d-lg-none ms-3" @click="isLeftSidebarOpen = true">
            <VIcon icon="tabler-menu-2" />
          </IconBtn>

          <VTextField
            v-model="q"
            density="default"
            class="email-search px-sm-2 flex-grow-1 py-1"
            placeholder="Cari email"
          >
            <template #prepend-inner>
              <VIcon icon="tabler-search" size="24" class="me-1 text-medium-emphasis" />
            </template>
          </VTextField>
        </div>
        <div v-if="openedFromCustomers" class="px-4 py-2 text-body-2 text-medium-emphasis border-b-sm">
          Konteks pelanggan: {{ customerNameFromQuery || 'Pelanggan' }}
        </div>
        <VDivider />

        <div class="py-2 px-4 d-flex align-center d-flex gap-x-1">
          <VCheckbox
            :model-value="selectAllEmailCheckbox"
            :indeterminate="isSelectAllEmailCheckboxIndeterminate"
            class="d-flex"
            @update:model-value="selectAllCheckboxUpdate"
          />
          <div
            class="w-100 d-flex align-center action-bar-actions gap-x-1"
            :style="{ visibility: canUpdateInbox && (isSelectAllEmailCheckboxIndeterminate || selectAllEmailCheckbox) ? undefined : 'hidden' }"
          >
            <IconBtn v-show="('filter' in route.params ? route.params.filter !== 'trashed' : true)" @click="handleActionClick('trash')">
              <VIcon icon="tabler-trash" size="22" />
              <VTooltip activator="parent" location="top">Hapus email</VTooltip>
            </IconBtn>
            <IconBtn @click="isAllMarkRead ? handleActionClick('unread') : handleActionClick('read')">
              <VIcon :icon="isAllMarkRead ? 'tabler-mail' : 'tabler-mail-opened'" size="22" />
              <VTooltip activator="parent" location="top">{{ isAllMarkRead ? 'Tandai belum dibaca' : 'Tandai sudah dibaca' }}</VTooltip>
            </IconBtn>
            <IconBtn>
              <VIcon icon="tabler-folder" size="22" />
              <VTooltip activator="parent" location="top">Pindahkan folder</VTooltip>
              <VMenu activator="parent">
                <VList density="compact">
                  <template v-for="moveTo in emailMoveToFolderActions" :key="moveTo.title">
                    <VListItem
                      :class="shallShowMoveToActionFor(moveTo.action) ? 'd-flex' : 'd-none'"
                      href="#"
                      class="items-center"
                      @click="handleMoveMailsTo(moveTo.action)"
                    >
                      <template #prepend>
                        <VIcon :icon="moveTo.icon" class="me-2" size="20" />
                      </template>
                      <VListItemTitle class="text-capitalize">{{ moveTo.action }}</VListItemTitle>
                    </VListItem>
                  </template>
                </VList>
              </VMenu>
            </IconBtn>
            <IconBtn>
              <VIcon icon="tabler-tag" size="22" />
              <VTooltip activator="parent" location="top">Label</VTooltip>
              <VMenu activator="parent">
                <VList density="compact">
                  <VListItem v-for="label in labels" :key="label.title" href="#" @click="handleEmailLabels(label.title)">
                    <template #prepend>
                      <VBadge inline :color="resolveLabelColor(label.title)" dot />
                    </template>
                    <VListItemTitle class="ms-2 text-capitalize">{{ label.title }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </IconBtn>
          </div>
          <VSpacer />
          <IconBtn @click="fetchEmails">
            <VIcon icon="tabler-refresh" size="22" />
          </IconBtn>
          <IconBtn>
            <VIcon icon="tabler-dots-vertical" size="22" />
          </IconBtn>
        </div>
        <VDivider />

        <PerfectScrollbar tag="ul" :options="{ wheelPropagation: false }" class="email-list">
          <li
            v-for="email in emails"
            v-show="emails?.length"
            :key="email.id"
            class="email-item d-flex align-center pa-4 gap-2 cursor-pointer"
            :class="[{ 'email-read': email.isRead }]"
            @click="openEmail(email)"
          >
            <VCheckbox :model-value="selectedEmails.includes(email.id)" class="flex-shrink-0" @update:model-value="toggleSelectedEmail(email.id)" @click.stop />
            <IconBtn v-if="canUpdateInbox" :color="email.isStarred ? 'warning' : 'default'" @click.stop="handleActionClick(email.isStarred ? 'unstar' : 'star', [email.id])">
              <VIcon icon="tabler-star" size="22" />
            </IconBtn>
            <VAvatar size="32">
              <VImg :src="email.from.avatar" :alt="email.from.name" />
            </VAvatar>
            <h6 class="text-h6">{{ email.from.name }}</h6>
            <span class="text-body-2 truncate">{{ email.subject }}</span>
            <VSpacer />
            <div class="email-meta align-center gap-2" :class="$vuetify.display.xs ? 'd-none' : ''">
              <VIcon v-for="label in email.labels" :key="label" icon="tabler-circle-filled" size="10" :color="resolveLabelColor(label)" />
              <span class="text-sm text-disabled">{{ formatDateToMonthShort(email.time) }}</span>
            </div>
            <div class="email-actions d-none">
              <IconBtn v-if="canUpdateInbox" @click.stop="handleActionClick('trash', [email.id])">
                <VIcon icon="tabler-trash" size="22" />
                <VTooltip activator="parent" location="top">Delete Mail</VTooltip>
              </IconBtn>
              <IconBtn v-if="canUpdateInbox" class="mx-2" @click.stop="handleActionClick(email.isRead ? 'unread' : 'read', [email.id])">
                <VIcon :icon="email.isRead ? 'tabler-mail' : 'tabler-mail-opened'" size="22" />
                <VTooltip activator="parent" location="top">{{ email.isRead ? 'Mark as Unread' : 'Mark as Read' }}</VTooltip>
              </IconBtn>
              <IconBtn v-if="canUpdateInbox" @click.stop="handleActionClick('spam', [email.id])">
                <VIcon icon="tabler-info-circle" size="22" />
                <VTooltip activator="parent" location="top">Move to Spam</VTooltip>
              </IconBtn>
            </div>
          </li>
          <li v-show="!emails.length" class="py-4 px-5 text-center">
            <span class="text-high-emphasis">Tidak ada email yang ditemukan.</span>
          </li>
        </PerfectScrollbar>
      </VCard>
      <ComposeDialog
        v-show="isComposeDialogVisible"
        :initial-to="composeInitialTo"
        :is-submitting="isSendingEmail"
        :error-message="composeError"
        @close="isComposeDialogVisible = false; composeError = ''"
        @send="handleComposeSend"
      />
    </VMain>
  </VLayout>
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core/scss/base/mixins";

.email-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-email-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-email-app-layout);
    }
  }
}

.email-content-list {
  border-end-start-radius: 0;
  border-start-start-radius: 0;
}

.email-list {
  white-space: nowrap;

  .email-item {
    block-size: 4.375rem;
    transition: all 0.2s ease-in-out;
    will-change: transform, box-shadow;

    &.email-read {
      background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
    }

    & + .email-item {
      border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    }
  }

  .email-item .email-meta {
    display: flex;
  }

  .email-item:hover {
    transform: translateY(-2px);

    @include mixins.elevation(4);

    @media screen and (min-width: 1280px) {
      .email-actions {
        display: block !important;
      }

      .email-meta {
        display: none;
      }
    }

    + .email-item {
      border-color: transparent;
    }

    @media screen and (max-width: 600px) {
      .email-actions {
        display: none !important;
      }
    }
  }
}

.email-compose-dialog {
  position: absolute;
  inset-block-end: 0;
  inset-inline-end: 0;
  min-inline-size: 100%;

  @media only screen and (min-width: 800px) {
    min-inline-size: 712px;
  }
}

.email-search {
  .v-field__outline {
    display: none;
  }

  .v-field__field {
    .v-field__input {
      font-size: 0.9375rem !important;
      line-height: 1.375rem !important;
    }
  }
}
</style>