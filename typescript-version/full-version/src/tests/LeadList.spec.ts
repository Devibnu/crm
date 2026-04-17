import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushUi, mountSalesView } from './test-utils'

const axiosState = vi.hoisted(() => ({
  get: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  push: vi.fn(),
}))

vi.mock('@/plugins/axios', () => ({
  axiosApi: {
    get: axiosState.get,
    patch: axiosState.patch,
    delete: axiosState.delete,
  },
  resolveApiErrorMessage: (error: any, fallback: string) => error?.response?.data?.message || fallback,
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: axiosState.push }),
  useRoute: () => ({ query: {} }),
}))

import LeadList from '@/views/crm/sales-enablement/LeadList.vue'

const leadResponse = {
  summary: { total: 3, new: 1, qualified: 1, disqualified: 1 },
  salesUsers: [
    { id: 10, fullName: 'Seno Sales', email: 'sales@example.com', role: 'sales' },
    { id: 20, fullName: 'Rani AE', email: 'rani@example.com', role: 'sales' },
  ],
  data: [
    { id: 1, code: 'LED-000001', fullName: 'Arif Pratama', email: 'arif@example.com', phone: '081', company: 'PT Nusantara Jaya', source: 'manual', status: 'new', qualificationNotes: null, lastContactedAt: null, qualifiedAt: null, disqualifiedAt: null, opportunitiesCount: 0, assignedUser: { id: 10, fullName: 'Seno Sales', email: 'sales@example.com', role: 'sales' }, capturedBy: null, metadata: {} },
    { id: 2, code: 'LED-000002', fullName: 'Dewi Lestari', email: 'dewi@example.com', phone: '082', company: 'CV Teknologi Mandiri', source: 'campaign', status: 'qualified', qualificationNotes: null, lastContactedAt: null, qualifiedAt: null, disqualifiedAt: null, opportunitiesCount: 1, assignedUser: null, capturedBy: null, metadata: {} },
    { id: 3, code: 'LED-000003', fullName: 'Budi Santoso', email: 'budi@example.com', phone: '083', company: 'UD Sumber Rejeki', source: 'form', status: 'disqualified', qualificationNotes: null, lastContactedAt: null, qualifiedAt: null, disqualifiedAt: null, opportunitiesCount: 0, assignedUser: null, capturedBy: null, metadata: {} },
  ],
}

describe('LeadList', () => {
  beforeEach(() => {
    axiosState.get.mockReset()
    axiosState.patch.mockReset()
    axiosState.delete.mockReset()
    axiosState.push.mockReset()
  })

  it('renders lead table with dummy API data', async () => {
    axiosState.get.mockResolvedValue({ data: leadResponse })

    const wrapper = mountSalesView(LeadList)
    await flushUi()

    expect(axiosState.get).toHaveBeenCalledWith('/leads', { params: { status: undefined } })
    expect(wrapper.text()).toContain('PT Nusantara Jaya')
    expect(wrapper.text()).toContain('CV Teknologi Mandiri')
    expect(wrapper.text()).toContain('UD Sumber Rejeki')
  })

  it('filters leads by status and refreshes the table', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockResolvedValueOnce({ data: { ...leadResponse, summary: { total: 1, new: 0, qualified: 1, disqualified: 0 }, data: [leadResponse.data[1]] } })

    const wrapper = mountSalesView(LeadList)
    await flushUi()

    await wrapper.get('[data-testid="lead-status-filter"] select').setValue('qualified')
    await flushUi()

    expect(axiosState.get).toHaveBeenLastCalledWith('/leads', { params: { status: 'qualified' } })
    expect(wrapper.text()).toContain('CV Teknologi Mandiri')
    expect(wrapper.text()).not.toContain('PT Nusantara Jaya')
  })

  it('assign button calls the assign API endpoint', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockResolvedValueOnce({ data: { data: leadResponse.salesUsers } })
      .mockResolvedValueOnce({ data: { ...leadResponse, data: [{ ...leadResponse.data[0], assignedUser: { id: 20, fullName: 'Rani AE', email: 'rani@example.com', role: 'sales' } }] } })
    axiosState.patch.mockResolvedValue({ data: { message: 'ok' } })

    const wrapper = mountSalesView(LeadList)
    await flushUi()

    await wrapper.get('[data-testid="lead-assign-button-1"]').trigger('click')
    await flushUi()

    expect(axiosState.get).toHaveBeenNthCalledWith(2, '/users', { params: { role: 'sales' } })

    await wrapper.get('[data-testid="lead-assign-dialog-select"] select').setValue('20')
    await wrapper.get('[data-testid="lead-assign-dialog-save"]').trigger('click')
    await wrapper.get('[data-testid="lead-assign-button-1"]').trigger('click')
    await flushUi()

    expect(axiosState.patch).toHaveBeenCalledWith('/leads/1/assign', { assignedUserId: 20 })
  })

  it('shows assign validation error inline in Indonesian locale for 422 responses', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockResolvedValueOnce({ data: { data: leadResponse.salesUsers } })
    axiosState.patch.mockRejectedValue({
      isAxiosError: true,
      response: {
        status: 422,
        data: {
          message: 'Assignment lead gagal diperbarui.',
          errors: {
            assignedUserId: ['User sales wajib dipilih.'],
          },
        },
      },
    })

    const wrapper = mountSalesView(LeadList, 'id')
    await flushUi()

    await wrapper.get('[data-testid="lead-assign-button-2"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="lead-assign-dialog-save"]').trigger('click')
    await flushUi()

    expect(wrapper.text()).toContain('Assign ke Sales')
    expect(wrapper.text()).toContain('Simpan Assign')
    expect(wrapper.get('[data-testid="lead-assign-dialog-error"]').text()).toContain('User sales wajib dipilih.')
  })

  it('shows assign sales-user loading error inline in Indonesian locale', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockRejectedValueOnce({
        response: {
          status: 500,
          data: {},
        },
      })

    const wrapper = mountSalesView(LeadList, 'id')
    await flushUi()

    await wrapper.get('[data-testid="lead-assign-button-2"]').trigger('click')
    await flushUi()

    expect(axiosState.get).toHaveBeenNthCalledWith(2, '/users', { params: { role: 'sales' } })
    expect(wrapper.text()).toContain('Assign ke Sales')
    expect(wrapper.text()).toContain('Daftar User Sales')
    expect(wrapper.get('[data-testid="lead-assign-dialog-load-error"]').text()).toContain('Daftar user sales gagal dimuat.')
  })

  it('shows assign validation error inline in English locale for 422 responses', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockResolvedValueOnce({ data: { data: leadResponse.salesUsers } })
    axiosState.patch.mockRejectedValue({
      isAxiosError: true,
      response: {
        status: 422,
        data: {
          message: 'Failed to update lead assignment.',
          errors: {
            assignedUserId: ['Please select a sales user.'],
          },
        },
      },
    })

    const wrapper = mountSalesView(LeadList, 'en')
    await flushUi()

    await wrapper.get('[data-testid="lead-assign-button-2"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="lead-assign-dialog-save"]').trigger('click')
    await flushUi()

    expect(wrapper.text()).toContain('Assign to Sales')
    expect(wrapper.text()).toContain('Save Assignment')
    expect(wrapper.get('[data-testid="lead-assign-dialog-error"]').text()).toContain('Please select a sales user.')
  })

  it('shows assign sales-user loading error inline in English locale', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockRejectedValueOnce({
        response: {
          status: 403,
          data: {},
        },
      })

    const wrapper = mountSalesView(LeadList, 'en')
    await flushUi()

    await wrapper.get('[data-testid="lead-assign-button-2"]').trigger('click')
    await flushUi()

    expect(axiosState.get).toHaveBeenNthCalledWith(2, '/users', { params: { role: 'sales' } })
    expect(wrapper.text()).toContain('Assign to Sales')
    expect(wrapper.text()).toContain('Sales Users')
    expect(wrapper.get('[data-testid="lead-assign-dialog-load-error"]').text()).toContain('Failed to load the sales user list.')
  })

  it('delete confirmation calls delete endpoint and removes the row after refresh', async () => {
    axiosState.get
      .mockResolvedValueOnce({ data: leadResponse })
      .mockResolvedValueOnce({ data: { ...leadResponse, summary: { total: 2, new: 0, qualified: 1, disqualified: 1 }, data: leadResponse.data.slice(1) } })
    axiosState.delete.mockResolvedValue({ data: { message: 'deleted' } })

    const wrapper = mountSalesView(LeadList)
    await flushUi()

    await wrapper.get('[data-testid="lead-delete-button-1"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="lead-delete-dialog-confirm"]').trigger('click')
    await flushUi()

    expect(axiosState.delete).toHaveBeenCalledWith('/leads/1')
    expect(wrapper.text()).not.toContain('PT Nusantara Jaya')
  })

  it('shows delete error inline in Indonesian locale for 404 responses', async () => {
    axiosState.get.mockResolvedValue({ data: leadResponse })
    axiosState.delete.mockRejectedValue({
      response: {
        status: 404,
        data: {
          message: 'Lead tidak ditemukan.',
        },
      },
    })

    const wrapper = mountSalesView(LeadList, 'id')
    await flushUi()

    await wrapper.get('[data-testid="lead-delete-button-1"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="lead-delete-dialog-confirm"]').trigger('click')
    await flushUi()

    expect(wrapper.text()).toContain('Hapus Lead')
    expect(wrapper.text()).toContain('Ya, Hapus')
    expect(wrapper.get('[data-testid="lead-delete-dialog-error"]').text()).toContain('Lead tidak ditemukan.')
  })

  it('shows delete error inline in English locale for 404 responses', async () => {
    axiosState.get.mockResolvedValue({ data: leadResponse })
    axiosState.delete.mockRejectedValue({
      response: {
        status: 404,
        data: {
          message: 'Lead not found.',
        },
      },
    })

    const wrapper = mountSalesView(LeadList, 'en')
    await flushUi()

    await wrapper.get('[data-testid="lead-delete-button-1"]').trigger('click')
    await flushUi()
    await wrapper.get('[data-testid="lead-delete-dialog-confirm"]').trigger('click')
    await flushUi()

    expect(wrapper.text()).toContain('Delete Lead')
    expect(wrapper.text()).toContain('Yes, Delete')
    expect(wrapper.get('[data-testid="lead-delete-dialog-error"]').text()).toContain('Lead not found.')
  })

  it('shows bilingual labels based on locale', async () => {
    axiosState.get.mockResolvedValue({ data: leadResponse })

    const idWrapper = mountSalesView(LeadList, 'id')
    await flushUi()
    expect(idWrapper.text()).toContain('Tambah Lead')

    const enWrapper = mountSalesView(LeadList, 'en')
    await flushUi()
    expect(enWrapper.text()).toContain('Add Lead')
  })
})