import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushUi, mountSalesView } from './test-utils'

const axiosState = vi.hoisted(() => ({
  get: vi.fn(),
}))

vi.mock('@/plugins/axios', () => ({
  axiosApi: {
    get: axiosState.get,
  },
  resolveApiErrorMessage: (error: any, fallback: string) => error?.response?.data?.message || fallback,
}))

import ForecastDashboard from '@/views/crm/sales-enablement/ForecastDashboard.vue'

const forecastResponse = {
  summary: { pipelineValue: 281000000, weightedForecast: 102200000, committedForecast: 96000000, closedWonValue: 150000000 },
  byStage: [
    { stage: 'prospecting', count: 1, amount: 35000000, weightedAmount: 7000000 },
    { stage: 'negotiation', count: 1, amount: 96000000, weightedAmount: 67200000 },
    { stage: 'closed_won', count: 1, amount: 150000000, weightedAmount: 150000000 },
  ],
  openDeals: [
    { id: 1, code: 'OPP-001', name: 'Software Subscription', stage: 'negotiation', amount: 96000000, probability: 70, expectedCloseDate: '2026-04-30', lead: { fullName: 'Dewi Lestari', company: 'CV Teknologi Mandiri' } },
  ],
  snapshots: [
    { id: 1, periodLabel: 'April 2026', snapshotDate: '2026-04-01', forecastAmount: 150000000, weightedAmount: 102200000, committedAmount: 96000000, status: 'published', notes: null },
    { id: 2, periodLabel: 'May 2026', snapshotDate: '2026-05-01', forecastAmount: 175000000, weightedAmount: 120000000, committedAmount: 110000000, status: 'draft', notes: null },
  ],
  placeholder: { forecastEngine: 'Forecast placeholder message' },
}

describe('ForecastDashboard', () => {
  beforeEach(() => {
    axiosState.get.mockReset()
  })

  it('renders forecast multi-month data from the API', async () => {
    axiosState.get.mockResolvedValue({ data: forecastResponse })

    const wrapper = mountSalesView(ForecastDashboard)
    await flushUi()

    expect(wrapper.text()).toContain('April 2026')
    expect(wrapper.text()).toContain('May 2026')
    expect(wrapper.text()).toContain('Software Subscription')
  })

  it('shows closed won revenue from the forecast summary', async () => {
    axiosState.get.mockResolvedValue({ data: forecastResponse })

    const wrapper = mountSalesView(ForecastDashboard)
    await flushUi()

    expect(wrapper.text()).toContain('150.000.000')
  })

  it('keeps multiple snapshot labels and forecast amounts visible', async () => {
    axiosState.get.mockResolvedValue({ data: forecastResponse })

    const wrapper = mountSalesView(ForecastDashboard)
    await flushUi()

    expect(wrapper.text()).toContain('April 2026')
    expect(wrapper.text()).toContain('175.000.000')
  })

  it('shows an empty forecast alert when no snapshot exists', async () => {
    axiosState.get.mockResolvedValue({ data: { ...forecastResponse, snapshots: [] } })

    const wrapper = mountSalesView(ForecastDashboard)
    await flushUi()

    expect(wrapper.text()).toContain('Belum ada snapshot forecast tersimpan.')
  })
})