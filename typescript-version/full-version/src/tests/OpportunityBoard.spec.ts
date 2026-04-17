import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushUi, mountSalesView } from './test-utils'

const axiosState = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
}))

vi.mock('@/plugins/axios', () => ({
  axiosApi: {
    get: axiosState.get,
    post: axiosState.post,
    patch: axiosState.patch,
    delete: axiosState.delete,
  },
  resolveApiErrorMessage: (error: any, fallback: string) => error?.response?.data?.message || fallback,
}))

import OpportunityBoard from '@/views/crm/sales-enablement/OpportunityBoard.vue'

const baseOpportunity = {
  id: 101,
  code: 'OPP-000101',
  leadId: 1,
  name: 'Deal Laptop Enterprise',
  stage: 'new',
  amount: 185000000,
  currency: 'IDR',
  probability: 25,
  expectedCloseDate: '2026-04-20',
  statusNotes: 'Discovery stage',
  closedAt: null,
  quotationsCount: 0,
  lead: { id: 1, code: 'LED-1', fullName: 'Arif Pratama', company: 'PT Nusantara Jaya', status: 'qualified' },
  assignedUser: null,
  metadata: {},
}

const buildOpportunityResponse = (stage: 'new' | 'qualified' | 'negotiation' | 'closed_won' = 'new') => ({
  summary: { pipelineValue: 185000000, weightedForecast: 46250000, closedWonValue: stage === 'closed_won' ? 185000000 : 0, totalOpenDeals: stage === 'closed_won' ? 0 : 1 },
  salesUsers: [{ id: 10, fullName: 'Seno Sales', email: 'sales@example.com', role: 'sales' }],
  qualifiedLeads: [{ id: 1, code: 'LED-1', fullName: 'Arif Pratama', company: 'PT Nusantara Jaya', status: 'qualified' }],
  board: [
    { stage: 'new', items: stage === 'new' ? [{ ...baseOpportunity, stage }] : [] },
    { stage: 'qualified', items: stage === 'qualified' ? [{ ...baseOpportunity, stage, probability: 25 }] : [] },
    { stage: 'proposal', items: [] },
    { stage: 'negotiation', items: stage === 'negotiation' ? [{ ...baseOpportunity, stage, probability: 75 }] : [] },
    { stage: 'closed_won', items: stage === 'closed_won' ? [{ ...baseOpportunity, stage, probability: 100 }] : [] },
    { stage: 'closed_lost', items: [] },
  ],
  data: [{ ...baseOpportunity, stage, probability: stage === 'closed_won' ? 100 : stage === 'negotiation' ? 75 : 25 }],
})

describe('OpportunityBoard', () => {
  beforeEach(() => {
    axiosState.get.mockReset()
    axiosState.post.mockReset()
    axiosState.patch.mockReset()
    axiosState.delete.mockReset()
  })

  it('renders the opportunity board from dummy API data', async () => {
    axiosState.get.mockResolvedValue({ data: buildOpportunityResponse() })

    const wrapper = mountSalesView(OpportunityBoard)
    await flushUi()

    expect(wrapper.text()).toContain('Deal Laptop Enterprise')
    expect(wrapper.findAll('[data-stage]').length).toBe(6)
  })

  it('supports stage transitions through drag and drop and calls the stage API', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: buildOpportunityResponse('new') })
      .mockResolvedValueOnce({ data: buildOpportunityResponse('negotiation') })
      .mockResolvedValueOnce({ data: buildOpportunityResponse('closed_won') })
    axiosState.patch.mockResolvedValue({ data: { message: 'ok' } })

    const wrapper = mountSalesView(OpportunityBoard)
    await flushUi()

    await wrapper.get('[data-opportunity-id="101"]').trigger('dragstart')
    await wrapper.get('[data-stage="negotiation"]').trigger('dragover')
    await wrapper.get('[data-stage="negotiation"]').trigger('drop')
    await flushUi()

    await wrapper.get('[data-opportunity-id="101"]').trigger('dragstart')
    await wrapper.get('[data-stage="closed_won"]').trigger('dragover')
    await wrapper.get('[data-stage="closed_won"]').trigger('drop')
    await flushUi()

    expect(axiosState.patch).toHaveBeenNthCalledWith(1, '/opportunities/101/stage', { stage: 'negotiation' })
    expect(axiosState.patch).toHaveBeenNthCalledWith(2, '/opportunities/101/stage', { stage: 'closed_won' })
  })

  it('deletes an opportunity from the board after confirmation', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: buildOpportunityResponse('new') })
      .mockResolvedValueOnce({ data: { ...buildOpportunityResponse('new'), summary: { pipelineValue: 0, weightedForecast: 0, closedWonValue: 0, totalOpenDeals: 0 }, data: [] } })
    axiosState.delete.mockResolvedValue({ data: { message: 'deleted' } })

    const wrapper = mountSalesView(OpportunityBoard)
    await flushUi()

    await wrapper.get('[data-testid="opportunity-delete-button-101"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="opportunity-delete-confirm"]').trigger('click')
    await flushUi()

    expect(axiosState.delete).toHaveBeenCalledWith('/opportunities/101')
  })

  it('shows bilingual labels for id and en locales', async () => {
    axiosState.get.mockResolvedValue({ data: buildOpportunityResponse() })

    const idWrapper = mountSalesView(OpportunityBoard, 'id')
    await flushUi()
    expect(idWrapper.text()).toContain('Tambah Opportunity')

    const enWrapper = mountSalesView(OpportunityBoard, 'en')
    await flushUi()
    expect(enWrapper.text()).toContain('Add Opportunity')
  })
})