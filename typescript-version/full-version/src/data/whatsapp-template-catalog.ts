export interface WhatsAppTemplate {
  id: string
  name: string
  category: 'marketing' | 'utility' | 'service'
  status: 'approved' | 'draft' | 'pending-meta' | 'rejected'
  language: string
  tone: string
  body: string
  variables: string[]
  channels: Array<'broadcast' | 'flow' | 'inbox'>
}

export const whatsappTemplateCatalog: WhatsAppTemplate[] = [
  {
    id: 'promo-launch',
    name: 'Promo Launch',
    category: 'marketing',
    status: 'approved',
    language: 'ID',
    tone: 'Persuasif',
    body: 'Halo {{nama}}, ada promo baru yang bisa Anda gunakan hari ini. Balas pesan ini jika ingin dibantu oleh agent kami.',
    variables: ['nama'],
    channels: ['broadcast'],
  },
  {
    id: 'info-update',
    name: 'Info Update',
    category: 'utility',
    status: 'approved',
    language: 'ID',
    tone: 'Informatif',
    body: 'Halo {{nama}}, kami ingin memberikan update informasi terbaru terkait layanan Anda. Tim kami siap membantu bila ada pertanyaan.',
    variables: ['nama'],
    channels: ['broadcast', 'flow'],
  },
  {
    id: 'follow-up-ticket',
    name: 'Follow Up Ticket',
    category: 'service',
    status: 'approved',
    language: 'ID',
    tone: 'Empatik',
    body: 'Halo {{nama}}, kami ingin memastikan kebutuhan Anda sudah tertangani. Jika masih ada kendala, silakan balas pesan ini.',
    variables: ['nama'],
    channels: ['broadcast', 'inbox'],
  },
  {
    id: 'billing-reminder',
    name: 'Billing Reminder',
    category: 'utility',
    status: 'pending-meta',
    language: 'ID',
    tone: 'Tegas',
    body: 'Halo {{nama}}, kami mengingatkan bahwa tagihan {{periode}} akan jatuh tempo pada {{jatuh_tempo}}. Balas pesan ini jika butuh bantuan.',
    variables: ['nama', 'periode', 'jatuh_tempo'],
    channels: ['flow'],
  },
  {
    id: 'reactivation-offer',
    name: 'Reactivation Offer',
    category: 'marketing',
    status: 'rejected',
    language: 'ID',
    tone: 'Hangat',
    body: 'Halo {{nama}}, kami punya penawaran khusus untuk Anda. Jika berminat, balas pesan ini dan tim kami akan menghubungi Anda.',
    variables: ['nama'],
    channels: ['broadcast'],
  },
]