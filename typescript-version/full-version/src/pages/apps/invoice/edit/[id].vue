<script lang="ts" setup>
import InvoiceAddPaymentDrawer from '@/views/apps/invoice/InvoiceAddPaymentDrawer.vue'
import InvoiceEditable from '@/views/apps/invoice/InvoiceEditable.vue'
import InvoiceSendInvoiceDrawer from '@/views/apps/invoice/InvoiceSendInvoiceDrawer.vue'
const { t } = useI18n()

definePage({
  meta: {
    action: 'update',
    subject: 'BackofficeInvoice',
  },
})

// Type: Invoice data
import type { InvoiceData, PurchasedProduct } from '@/views/apps/invoice/types'

const invoiceData = ref<InvoiceData>()
const route = useRoute('apps-invoice-edit-id')

// 👉 fetchInvoice

const { data: invoiceDetails } = await useApi<any>(`/apps/invoice/${route.params.id}`)

if (invoiceDetails.value) {
  invoiceData.value = {
    invoice: invoiceDetails.value.invoice,
    paymentDetails: invoiceDetails.value.paymentDetails,

    /*
      We are adding some extra data in response for data purpose
      Your response will contain this extra data
      Purpose is to make it more API friendly and less static as possible
    */

    purchasedProducts: [
      {
        title: 'App Design',
        cost: 24,
        hours: 2,
        description: 'Designed UI kit & app pages.',
      },
    ],
    note: 'It was a pleasure working with you and your team. We hope you will keep us in mind for future freelance projects. Thank You!',
    paymentMethod: 'Bank Account',
    salesperson: 'Tom Cook',
    thanksNote: 'Thanks for your business',
  }
}

const addProduct = (value: PurchasedProduct) => {
  invoiceData.value?.purchasedProducts.push(value)
}

const removeProduct = (id: number) => {
  invoiceData.value?.purchasedProducts.splice(id, 1)
}

const isSendSidebarActive = ref(false)
const isAddPaymentSidebarActive = ref(false)
const paymentTerms = ref(true)
const clientNotes = ref(false)
const paymentStub = ref(false)
const selectedPaymentMethod = ref('Bank Account')
const paymentMethods = ['Bank Account', 'PayPal', 'UPI Transfer']
</script>

<template>
  <VRow v-if="invoiceData && invoiceData?.invoice">
    <!-- 👉 InvoiceEditable -->
    <VCol
      cols="12"
      md="9"
    >
      <InvoiceEditable
        v-if="invoiceData?.invoice"
        :data="invoiceData"
        @push="addProduct"
        @remove="removeProduct"
      />
    </VCol>

    <!-- 👉 Right Column: Invoice Action -->
    <VCol
      cols="12"
      md="3"
    >
      <VCard class="mb-8">
        <VCardText>
          <!-- 👉 Send Invoice Trigger button -->
          <VBtn
            block
            prepend-icon="tabler-send"
            class="mb-4"
            @click="isSendSidebarActive = true"
          >
            Send Invoice
          </VBtn>

          <div class="d-flex flex-wrap gap-4">
            <!-- 👉  Preview button -->
            <VBtn
              color="secondary"
              variant="tonal"
              class="flex-grow-1"
              :to="{ name: 'apps-invoice-preview-id', params: { id: route.params.id } }"
            >
              {{ t('Preview') }}
            </VBtn>

            <!-- 👉 Save button -->
            <VBtn
              color="secondary"
              variant="tonal"
              class="mb-4 flex-grow-1"
            >
              Save
            </VBtn>
          </div>

          <!-- 👉 Add Payment trigger button -->
          <VBtn
            block
            color="success"
            prepend-icon="tabler-currency-dollar"
            @click="isAddPaymentSidebarActive = true"
          >
            Add Payment
          </VBtn>
        </VCardText>
      </VCard>

      <!-- 👉 Accept payment via  -->
      <AppSelect
        id="payment-method"
        v-model="selectedPaymentMethod"
        :items="paymentMethods"
        label="Accept Payment Via"
        class="mb-4"
      />

      <!-- 👉 Payment Terms -->
      <div class="d-flex align-center justify-space-between">
        <VLabel for="payment-terms">
          Payment Terms
        </VLabel>
        <div>
          <VSwitch
            id="payment-terms"
            v-model="paymentTerms"
          />
        </div>
      </div>

      <!-- 👉 Client Notes -->
      <div class="d-flex align-center justify-space-between">
        <VLabel for="client-notes">
          Client Notes
        </VLabel>
        <div>
          <VSwitch
            id="client-notes"
            v-model="clientNotes"
          />
        </div>
      </div>

      <!-- 👉 Payment Stub -->
      <div class="d-flex align-center justify-space-between">
        <VLabel for="payment-stub">
          Payment Stub
        </VLabel>
        <div>
          <VSwitch
            id="payment-stub"
            v-model="paymentStub"
          />
        </div>
      </div>
    </VCol>

    <!-- 👉 Invoice send drawer -->
    <InvoiceSendInvoiceDrawer v-model:is-drawer-open="isSendSidebarActive" />

    <!-- 👉 Invoice add payment drawer -->
    <InvoiceAddPaymentDrawer v-model:is-drawer-open="isAddPaymentSidebarActive" />
  </VRow>

  <section v-else>
    <VAlert
      type="error"
      variant="tonal"
    >
      Invoice with ID  {{ route.params.id }} not found!
    </VAlert>
  </section>
</template>
