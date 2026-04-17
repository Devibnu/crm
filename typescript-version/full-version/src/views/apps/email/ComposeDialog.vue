<script lang="ts" setup>
import type { ComposeEmailPayload, ComposeEmailRecipient } from '@/views/apps/email/useEmail'

interface Props {
  isSubmitting?: boolean
  errorMessage?: string
  initialTo?: string
}

const props = withDefaults(defineProps<Props>(), {
  isSubmitting: false,
  errorMessage: '',
  initialTo: '',
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'send', payload: ComposeEmailPayload): void
}>()

const content = ref('')
const to = ref(props.initialTo)
const subject = ref('')
const message = ref('')
const cc = ref('')
const bcc = ref('')
const isEmailCc = ref(false)
const isEmailBcc = ref(false)

const normalizeRecipients = (value: string): ComposeEmailRecipient[] => value
  .split(',')
  .map(item => item.trim())
  .filter(Boolean)
  .map(email => ({ email }))

const canSubmit = computed(() => normalizeRecipients(to.value).length > 0 && !props.isSubmitting)

const resetValues = () => {
  to.value = props.initialTo
  subject.value = ''
  message.value = ''
  cc.value = ''
  bcc.value = ''
  content.value = ''
}

const submit = () => {
  const recipients = normalizeRecipients(to.value)

  if (!recipients.length || props.isSubmitting)
    return

  emit('send', {
    to: recipients,
    cc: normalizeRecipients(cc.value),
    bcc: normalizeRecipients(bcc.value),
    subject: subject.value,
    message: content.value || message.value,
  })
}

watch(() => props.initialTo, value => {
  to.value = value
})
</script>

<template>
  <VCard
    class="email-compose-dialog"
    elevation="10"
    max-width="30vw"
  >
    <VCardItem class="py-3 px-6">
      <div class="d-flex align-center">
        <h5 class="text-h5">
          Tulis Email
        </h5>
        <VSpacer />

        <div class="d-flex align-center gap-x-2">
          <IconBtn
            size="small"
            icon="tabler-minus"
            @click="emit('close')"
          />
          <IconBtn
            size="small"
            icon="tabler-x"
            @click="emit('close'); resetValues(); isEmailCc = false; isEmailBcc = false;"
          />
        </div>
      </div>
    </VCardItem>

    <div class="px-1 pe-6 py-1">
      <VTextField
        v-model="to"
        density="compact"
        placeholder="pelanggan@example.com"
      >
        <template #prepend-inner>
          <div class="text-base font-weight-medium text-disabled">
            Ke:
          </div>
        </template>
        <template #append>
          <span class="cursor-pointer">
            <span @click="isEmailCc = !isEmailCc">Cc</span>
            <span> | </span>
            <span @click="isEmailBcc = !isEmailBcc">Bcc</span>
          </span>
        </template>
      </VTextField>
    </div>

    <VExpandTransition>
      <div v-if="isEmailCc">
        <VDivider />

        <div class="px-1 pe-6 py-1">
          <VTextField
            v-model="cc"
            density="compact"
          >
            <template #prepend-inner>
              <div class="text-disabled font-weight-medium">
                Cc:
              </div>
            </template>
          </VTextField>
        </div>
      </div>
    </VExpandTransition>

    <VExpandTransition>
      <div v-if="isEmailBcc">
        <VDivider />

        <div class="px-1 pe-6 py-1">
          <VTextField
            v-model="bcc"
            density="compact"
          >
            <template #prepend-inner>
              <div class="text-disabled font-weight-medium">
                Bcc:
              </div>
            </template>
          </VTextField>
        </div>
      </div>
    </VExpandTransition>

    <VDivider />
    <div class="px-1 pe-6 py-1">
      <VTextField
        v-model="subject"
        density="compact"
      >
        <template #prepend-inner>
          <div class="text-base font-weight-medium text-disabled">
            Subjek:
          </div>
        </template>
      </VTextField>
    </div>

    <VDivider />

    <VAlert
      v-if="props.errorMessage"
      type="error"
      variant="tonal"
      class="ma-4 mb-0"
    >
      {{ props.errorMessage }}
    </VAlert>

    <TiptapEditor
      v-model="content"
      placeholder="Tulis pesan"
    />

    <div class="d-flex align-center px-6 py-4">
      <VBtn
        color="primary"
        class="me-4"
        append-icon="tabler-send"
        :loading="props.isSubmitting"
        :disabled="!canSubmit"
        @click="submit"
      >
        Kirim
      </VBtn>

      <IconBtn size="small">
        <VIcon icon="tabler-paperclip" />
      </IconBtn>

      <VSpacer />

      <IconBtn
        size="small"
        class="me-2"
      >
        <VIcon icon="tabler-dots-vertical" />
      </IconBtn>

      <IconBtn
        size="small"
        @click="emit('close'); resetValues(); isEmailCc = false; isEmailBcc = false;"
      >
        <VIcon icon="tabler-trash" />
      </IconBtn>
    </div>
  </VCard>
</template>

<style lang="scss">
@use "@core/scss/base/mixins";

.v-card.email-compose-dialog {
  z-index: 910 !important;

  @include mixins.elevation(18);

  .v-field--prepended {
    padding-inline-start: 20px;
  }

  .v-field__prepend-inner {
    align-items: center;
    padding: 0;
  }

  .v-field__prepend-inner {
    align-items: center;
    padding: 0;
  }

  .v-card-item {
    background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
  }

  .v-textarea .v-field {
    --v-field-padding-start: 20px;
  }

  .v-field__outline {
    display: none;
  }

  .v-input {
    .v-field__prepend-inner {
      display: flex;
      align-items: center;
      padding-block-start: 0;
    }
  }

  .app-text-field {
    .v-field__input {
      padding-block-start: 6px;
    }

    .v-field--focused {
      box-shadow: none !important;
    }
  }
}

.email-compose-dialog {
  .ProseMirror {
    p {
      margin-block-end: 0;
    }

    padding: 1.5rem;
    block-size: 100px;
    overflow-y: auto;
    padding-block: 0.5rem;

    &:focus-visible {
      outline: none;
    }

    p.is-editor-empty:first-child::before {
      block-size: 0;
      color: #adb5bd;
      content: attr(data-placeholder);
      float: inline-start;
      pointer-events: none;
    }

    ul,
    ol {
      padding-inline: 1.125rem;
    }

    &-focused {
      outline: none;
    }
  }
}
</style>
