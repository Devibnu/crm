export default [
  {
    heading: 'crm.nav.serviceManagement',
  },
  {
    title: 'crm.nav.dashboard',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'dashboard',
  },
  {
    title: 'crm.customers.title',
    icon: { icon: 'tabler-users-group' },
    to: 'pelanggan',
    action: 'read',
    subject: 'CrmCustomers',
  },
  {
    title: 'crm.nav.ticketManagement',
    icon: { icon: 'tabler-ticket' },
    to: '/service-management/ticket-list',
    action: 'read',
    subject: 'CrmTickets',
  },
  {
    title: 'crm.nav.slaManagement',
    icon: { icon: 'tabler-clock-hour-4' },
    to: '/service-management/sla-dashboard',
    action: 'read',
    subject: 'CrmTickets',
  },
  {
    heading: 'crm.nav.salesEnablement',
  },
  {
    title: 'crm.nav.salesDashboard',
    icon: { icon: 'tabler-layout-dashboard' },
    to: '/sales-enablement/dashboard',
  },
  {
    title: 'crm.nav.leadManagement',
    icon: { icon: 'tabler-users-plus' },
    to: '/sales-enablement/lead-management',
  },
  {
    title: 'crm.nav.opportunityManagement',
    icon: { icon: 'tabler-briefcase-2' },
    to: '/sales-enablement/opportunity-board',
  },
  {
    title: 'crm.nav.pipelineForecasting',
    icon: { icon: 'tabler-chart-funnel' },
    to: '/sales-enablement/pipeline-forecasting',
  },
  {
    title: 'crm.nav.salesActivityTracking',
    icon: { icon: 'tabler-calendar-stats' },
    to: '/sales-enablement/sales-activity-tracking',
  },
  {
    title: 'crm.nav.quotationDeal',
    icon: { icon: 'tabler-file-dollar' },
    to: '/sales-enablement/quotation-deal',
  },
  {
    heading: 'crm.nav.marketingAutomation',
  },
  {
    title: 'crm.customers.profile360.headerTitle',
    icon: { icon: 'tabler-user-circle' },
    to: '/marketing-automation/customer-data-platform',
    action: 'read',
    subject: 'CrmCustomers',
  },
  {
    title: 'crm.nav.consentManagement',
    icon: { icon: 'tabler-shield-check' },
    to: '/marketing-automation/consent-management',
  },
  {
    title: 'crm.nav.campaignManagement',
    icon: { icon: 'tabler-speakerphone' },
    children: [
      {
        title: 'crm.nav.campaignPlanning',
        to: '/marketing-automation/campaign-planning',
      },
      {
        title: 'crm.nav.campaignExecution',
        to: '/marketing-automation/campaign-execution',
      },
      {
        title: 'crm.nav.campaignMonitoring',
        to: '/marketing-automation/campaign-monitoring',
      },
    ],
  },
  {
    title: 'crm.nav.landingPageFormBuilder',
    icon: { icon: 'tabler-layout-grid-add' },
    to: '/marketing-automation/landing-page-form-builder',
  },
  {
    title: 'crm.nav.socialMediaEngagement',
    icon: { icon: 'tabler-brand-instagram' },
    to: '/marketing-automation/social-media-engagement',
  },
  {
    title: 'crm.nav.marketingAnalytics',
    icon: { icon: 'tabler-chart-dots-3' },
    to: '/marketing-automation/marketing-analytics',
  },
  {
    heading: 'crm.nav.omnichannel',
  },
  {
    title: 'crm.nav.whatsappInbox',
    icon: { icon: 'tabler-brand-whatsapp' },
    to: '/omnichannel/whatsapp/inbox',
    action: 'read',
    subject: 'CrmWhatsapp',
  },
  {
    title: 'crm.nav.emailInbox',
    icon: { icon: 'tabler-mail' },
    to: '/omnichannel/email-inbox',
    action: 'read',
    subject: 'CrmInbox',
  },
  {
    title: 'crm.nav.liveChat',
    icon: { icon: 'tabler-message-chatbot' },
    to: '/omnichannel/live-chat',
    action: 'read',
    subject: 'CrmInbox',
  },
]