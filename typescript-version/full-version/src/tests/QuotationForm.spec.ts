import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushUi, mountSalesView } from './test-utils'

const axiosState = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
}))

vi.mock('@/plugins/axios', () => ({
  axiosApi: {
    get: axiosState.get,
    post: axiosState.post,
    patch: axiosState.patch,
  },
  resolveApiErrorMessage: (error: any, fallback: string) => error?.response?.data?.message || fallback,
}))

import QuotationForm from '@/views/crm/sales-enablement/QuotationForm.vue'

const quotationResponse = {
  summary: { draft: 1, submitted: 0, approved: 0, rejected: 0 },
  opportunities: [
    { id: 11, code: 'OPP-11', name: 'Software Subscription', stage: 'negotiation', amount: 96000000, currency: 'IDR', lead: { id: 1, fullName: 'Dewi Lestari', company: 'CV Teknologi Mandiri' } },
  ],
  data: [
    { id: 21, quoteNumber: 'QTN-000021', opportunityId: 11, title: 'Software Subscription Quote', amount: 96000000, currency: 'IDR', validUntil: '2026-04-30', status: 'draft', approvalNotes: null, submittedAt: null, approvedAt: null, rejectedAt: null, opportunity: { id: 11, code: 'OPP-11', name: 'Software Subscription', stage: 'negotiation', amount: 96000000, currency: 'IDR', lead: { id: 1, fullName: 'Dewi Lestari', company: 'CV Teknologi Mandiri' } }, metadata: {} },
  ],
  placeholder: { approval: 'Approval placeholder' },
}

describe('QuotationForm', () => {
  beforeEach(() => {
    axiosState.get.mockReset()
    axiosState.post.mockReset()
    axiosState.patch.mockReset()
  })

  it('renders the quotation form and list', async () => {
    axiosState.get.mockResolvedValue({ data: quotationResponse })

    const wrapper = mountSalesView(QuotationForm)
    await flushUi()

    expect(wrapper.text()).toContain('Generator Quotation')
    expect(wrapper.text()).toContain('Software Subscription Quote')
  })

  it('submits the form and calls the quotation API', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: quotationResponse })
      .mockResolvedValueOnce({ data: quotationResponse })
    axiosState.post.mockResolvedValue({ data: { message: 'created' } })

    const wrapper = mountSalesView(QuotationForm)
    await flushUi()

    const selects = wrapper.findAll('select')
    const inputs = wrapper.findAll('input')
    const textareas = wrapper.findAll('textarea')

    await selects[0].setValue('11')
    await inputs[0].setValue('Cloud Hosting Quote')
    await inputs[1].setValue('125000000')
    await inputs[2].setValue('IDR')
    await inputs[3].setValue('2026-04-30')
    await selects[1].setValue('draft')
    await textareas[0].setValue('Initial pricing review.')

    const submitButton = wrapper.findAll('button').find(button => button.text().includes('Buat Quotation'))
    await submitButton?.trigger('click')
    await flushUi()

    expect(axiosState.post).toHaveBeenCalledWith('/quotations', {
      opportunityId: 11,
      title: 'Cloud Hosting Quote',
      amount: 125000000,
      currency: 'IDR',
      validUntil: '2026-04-30',
      status: 'draft',
      approvalNotes: 'Initial pricing review.',
    })
  })

  it('updates quotation workflow status from draft to submitted to approved', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: quotationResponse })
      .mockResolvedValueOnce({ data: { ...quotationResponse, data: [{ ...quotationResponse.data[0], status: 'submitted' }] } })
      .mockResolvedValueOnce({ data: { ...quotationResponse, data: [{ ...quotationResponse.data[0], status: 'approved' }] } })
    axiosState.patch.mockResolvedValue({ data: { message: 'updated' } })

    const wrapper = mountSalesView(QuotationForm)
    await flushUi()

    await wrapper.get('.sales-link').trigger('click')
    await flushUi()

    let selects = wrapper.findAll('select')
    let saveButton = wrapper.findAll('button').find(button => button.text().includes('Simpan Approval'))

    await selects[2].setValue('submitted')
    await saveButton?.trigger('click')
    await flushUi()

    selects = wrapper.findAll('select')
    saveButton = wrapper.findAll('button').find(button => button.text().includes('Simpan Approval'))

    await selects[2].setValue('approved')
    await saveButton?.trigger('click')
    await flushUi()

    expect(axiosState.patch).toHaveBeenNthCalledWith(1, '/quotations/21', { status: 'submitted', approvalNotes: '' })
    expect(axiosState.patch).toHaveBeenNthCalledWith(2, '/quotations/21', { status: 'approved', approvalNotes: '' })
  })

  it('shows validation feedback when quotation submit returns 422', async () => {
    axiosState.get.mockResolvedValue({ data: quotationResponse })
    axiosState.post.mockRejectedValue({ response: { data: { message: 'Invalid quotation payload.' } } })

    const wrapper = mountSalesView(QuotationForm)
    await flushUi()

    const submitButton = wrapper.findAll('button').find(button => button.text().includes('Buat Quotation'))
    await submitButton?.trigger('click')
    await flushUi()

    expect(wrapper.text()).toContain('Invalid quotation payload.')
  })
})