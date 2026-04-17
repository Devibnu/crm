export type WhatsAppInboxTab = 'all' | 'unassigned' | 'mine'
export type WhatsAppInboxStatus = 'butuh_respons' | 'menunggu_pelanggan' | 'selesai'

export interface WhatsAppInboxMessage {
  id: number
  sender: 'customer' | 'agent'
  text: string
  time: string
  createdAt?: string | null
}

export interface WhatsAppInboxThread {
  id: number
  ticketId?: number
  name: string
  phone: string
  avatarColor: string
  initials: string
  lastSnippet: string
  lastTime: string
  lastActivityAt?: string | null
  unread: boolean
  assignedTo: string | null
  assignedUserId?: number | null
  status: WhatsAppInboxStatus
  priority: 'normal' | 'high'
  labels: string[]
  messages: WhatsAppInboxMessage[]
}

const STORAGE_KEY = 'kitcrm.whatsapp.inbox.threads'

export const defaultWhatsAppInboxThreads: WhatsAppInboxThread[] = [
  {
    id: 1,
    name: 'Pratama',
    phone: '6289627418930',
    avatarColor: 'primary',
    initials: 'PR',
    lastSnippet: 'https://www.youtube.com/watch?v=m02TeQ9kHVo',
    lastTime: '2h',
    unread: true,
    assignedTo: 'basic1@gmail.com',
    status: 'belum_dibaca',
    priority: 'normal',
    labels: ['inbound', 'belum_dibaca'],
    messages: [
      { id: 11, sender: 'customer', text: 'Siap', time: '21.57' },
      { id: 12, sender: 'agent', text: 'oke', time: '21.58' },
      { id: 13, sender: 'agent', text: 'apa gus', time: '22.14' },
      { id: 14, sender: 'customer', text: 'https://www.youtube.com/watch?v=m02TeQ9kHVo', time: '22.46' },
    ],
  },
  {
    id: 2,
    name: 'Muchtadi',
    phone: '6281355511022',
    avatarColor: 'error',
    initials: 'MU',
    lastSnippet: 'ok',
    lastTime: '2h',
    unread: false,
    assignedTo: null,
    status: 'dibaca',
    priority: 'normal',
    labels: ['inbound'],
    messages: [
      { id: 21, sender: 'customer', text: 'Halo admin', time: '18.20' },
      { id: 22, sender: 'agent', text: 'Halo, ada yang bisa kami bantu?', time: '18.23' },
      { id: 23, sender: 'customer', text: 'ok', time: '18.25' },
    ],
  },
  {
    id: 3,
    name: 'Ibnuqosim',
    phone: '6281244500193',
    avatarColor: 'pink',
    initials: 'IB',
    lastSnippet: 'dari mobile',
    lastTime: '3h',
    unread: false,
    assignedTo: 'basic1@gmail.com',
    status: 'dibaca',
    priority: 'normal',
    labels: ['followup'],
    messages: [
      { id: 31, sender: 'customer', text: 'Tes dari mobile', time: '17.00' },
      { id: 32, sender: 'customer', text: 'dari mobile', time: '17.05' },
    ],
  },
  {
    id: 4,
    name: 'D 14 HE',
    phone: '6282288272771',
    avatarColor: 'warning',
    initials: 'D1',
    lastSnippet: 'Video',
    lastTime: '4h',
    unread: false,
    assignedTo: null,
    status: 'dibaca',
    priority: 'high',
    labels: ['vip'],
    messages: [
      { id: 41, sender: 'customer', text: 'Video', time: '15.21' },
    ],
  },
  {
    id: 5,
    name: 'Anggi',
    phone: '6285231178181',
    avatarColor: 'info',
    initials: 'AN',
    lastSnippet: 'Hallo jasa Ibnu',
    lastTime: '4h',
    unread: false,
    assignedTo: 'basic1@gmail.com',
    status: 'dibaca',
    priority: 'normal',
    labels: ['prospect'],
    messages: [
      { id: 51, sender: 'customer', text: 'Hallo jasa Ibnu', time: '15.18' },
    ],
  },
  {
    id: 6,
    name: 'Test User',
    phone: '6281188877332',
    avatarColor: 'error',
    initials: 'TU',
    lastSnippet: 'kirim informasi ya?..',
    lastTime: '5h',
    unread: false,
    assignedTo: null,
    status: 'dibaca',
    priority: 'normal',
    labels: ['inbound'],
    messages: [
      { id: 61, sender: 'customer', text: 'kirim informasi ya?..', time: '14.32' },
    ],
  },
]

export const loadWhatsAppInboxThreads = (): WhatsAppInboxThread[] => {
  if (typeof window === 'undefined')
    return defaultWhatsAppInboxThreads

  const rawValue = window.localStorage.getItem(STORAGE_KEY)

  if (!rawValue) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(defaultWhatsAppInboxThreads))

    return defaultWhatsAppInboxThreads
  }

  try {
    const parsed = JSON.parse(rawValue) as WhatsAppInboxThread[]

    return Array.isArray(parsed) ? parsed : defaultWhatsAppInboxThreads
  }
  catch {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(defaultWhatsAppInboxThreads))

    return defaultWhatsAppInboxThreads
  }
}

export const saveWhatsAppInboxThreads = (threads: WhatsAppInboxThread[]) => {
  if (typeof window === 'undefined')
    return

  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(threads))
}