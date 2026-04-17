export type BroadcastCampaignStatus = 'draft' | 'running' | 'scheduled' | 'completed' | 'cancelled'

export interface BroadcastCampaign {
  id: number
  name: string
  templateId: string
  status: BroadcastCampaignStatus
  recipientCount: number
  deliveredCount: number
  failedCount: number
  pendingCount: number
  cost: number
  createdAt: string
  scheduledAt?: string | null
  description?: string
  audience?: string
  rateLimit?: string
}

const STORAGE_KEY = 'kitcrm.whatsapp.broadcast.campaigns'

export const defaultBroadcastCampaigns: BroadcastCampaign[] = [
  {
    id: 1,
    name: 'Promo April Existing Customer',
    templateId: 'promo-launch',
    status: 'completed',
    recipientCount: 14,
    deliveredCount: 14,
    failedCount: 0,
    pendingCount: 0,
    cost: 1400,
    createdAt: new Date('2026-04-07').toISOString(),
    description: 'Promo broadcast untuk existing customer aktif.',
    audience: 'all',
    rateLimit: '10',
  },
  {
    id: 2,
    name: 'Info Update Layanan',
    templateId: 'info-update',
    status: 'running',
    recipientCount: 9,
    deliveredCount: 4,
    failedCount: 0,
    pendingCount: 5,
    cost: 900,
    createdAt: new Date('2026-04-09').toISOString(),
    description: 'Update informasi layanan terbaru untuk customer existing.',
    audience: 'warm',
    rateLimit: '10',
  },
  {
    id: 3,
    name: 'Follow Up Ticket Warm Leads',
    templateId: 'follow-up-ticket',
    status: 'scheduled',
    recipientCount: 6,
    deliveredCount: 0,
    failedCount: 0,
    pendingCount: 6,
    cost: 600,
    createdAt: new Date('2026-04-10').toISOString(),
    scheduledAt: new Date('2026-04-10T09:00:00').toISOString(),
    description: 'Follow up ticket untuk warm leads.',
    audience: 'warm',
    rateLimit: '10',
  },
  {
    id: 4,
    name: 'Promo Loyal Customer',
    templateId: 'promo-launch',
    status: 'completed',
    recipientCount: 5,
    deliveredCount: 5,
    failedCount: 0,
    pendingCount: 0,
    cost: 500,
    createdAt: new Date('2026-04-04').toISOString(),
    description: 'Promo khusus untuk customer loyal.',
    audience: 'loyal',
    rateLimit: '10',
  },
]

export const loadBroadcastCampaigns = (): BroadcastCampaign[] => {
  if (typeof window === 'undefined')
    return defaultBroadcastCampaigns

  const rawValue = window.localStorage.getItem(STORAGE_KEY)

  if (!rawValue) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(defaultBroadcastCampaigns))
    return defaultBroadcastCampaigns
  }

  try {
    const parsed = JSON.parse(rawValue) as BroadcastCampaign[]

    return Array.isArray(parsed) ? parsed : defaultBroadcastCampaigns
  }
  catch {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(defaultBroadcastCampaigns))
    return defaultBroadcastCampaigns
  }
}

export const saveBroadcastCampaigns = (campaigns: BroadcastCampaign[]) => {
  if (typeof window === 'undefined')
    return

  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(campaigns))
}