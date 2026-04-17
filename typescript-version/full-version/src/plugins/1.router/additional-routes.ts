import type { RouteRecordRaw } from 'vue-router/auto'

const emailRouteComponent = () => import('@/pages/apps/email/index.vue')
const campaignBroadcastRouteComponent = () => import('@/pages/omnichannel/whatsapp/broadcast/index.vue')
const campaignBroadcastCreateRouteComponent = () => import('@/pages/omnichannel/whatsapp/broadcast/create.vue')
const campaignBroadcastDetailRouteComponent = () => import('@/pages/omnichannel/whatsapp/broadcast/[id].vue')
const campaignTemplatesRouteComponent = () => import('@/pages/omnichannel/whatsapp/templates.vue')
const auditTrailRouteComponent = () => import('@/pages/governance-security/audit-trail.vue')
const contractManagementRouteComponent = () => import('@/pages/integrasi-transaksi/contract-management-system.vue')
const executiveDashboardRouteComponent = () => import('@/pages/pelaporan-dashboard/executive-dashboard.vue')
const reportingRouteComponent = () => import('@/pages/pelaporan-dashboard/reporting.vue')

// 👉 Redirects
export const redirects: RouteRecordRaw[] = [
  // ℹ️ We are redirecting to different pages based on role.
  // NOTE: Role is just for UI purposes. ACL is based on abilities.
  {
    path: '/',
    name: 'index',
    redirect: to => {
      // TODO: Get type from backend
      const userData = useCookie<Record<string, unknown> | null | undefined>('userData')
      const userRole = userData.value?.role

      if (userRole === 'admin')
        return { name: 'dashboard' }

      return { name: 'login', query: to.query }
    },
  },
  {
    path: '/pages/user-profile',
    name: 'pages-user-profile',
    redirect: () => ({ name: 'pages-user-profile-tab', params: { tab: 'profile' } }),
  },
  {
    path: '/pages/account-settings',
    name: 'pages-account-settings',
    redirect: () => ({ name: 'pages-account-settings-tab', params: { tab: 'account' } }),
  },
  {
    path: '/marketing-automation/campaign-management/whatsapp-broadcast',
    name: 'marketing-automation-campaign-management-whatsapp-broadcast-legacy',
    redirect: '/marketing-automation/campaign-execution/whatsapp-broadcast',
  },
  {
    path: '/marketing-automation/campaign-management/whatsapp-broadcast/create',
    name: 'marketing-automation-campaign-management-whatsapp-broadcast-create-legacy',
    redirect: '/marketing-automation/campaign-execution/whatsapp-broadcast/create',
  },
  {
    path: '/marketing-automation/campaign-management/whatsapp-broadcast/:id',
    name: 'marketing-automation-campaign-management-whatsapp-broadcast-id-legacy',
    redirect: to => `/marketing-automation/campaign-execution/whatsapp-broadcast/${to.params.id}`,
  },
  {
    path: '/marketing-automation/campaign-management/whatsapp-templates',
    name: 'marketing-automation-campaign-management-whatsapp-templates-legacy',
    redirect: '/marketing-automation/campaign-execution/whatsapp-templates',
  },
]

export const routes: RouteRecordRaw[] = [
  {
    path: '/governance-security/audit-trail',
    name: 'governance-security-audit-trail',
    component: auditTrailRouteComponent,
    meta: {
      navActiveLink: 'governance-security-audit-trail',
      action: 'manage',
      subject: 'Admin',
    },
  },
  {
    path: '/integrasi-transaksi/contract-management-system',
    name: 'integrasi-transaksi-contract-management-system',
    component: contractManagementRouteComponent,
    meta: {
      navActiveLink: 'integrasi-transaksi-contract-management-system',
      action: 'manage',
      subject: 'Admin',
    },
  },
  {
    path: '/pelaporan-dashboard/executive-dashboard',
    name: 'pelaporan-dashboard-executive-dashboard',
    component: executiveDashboardRouteComponent,
    meta: {
      navActiveLink: 'pelaporan-dashboard-executive-dashboard',
      action: 'manage',
      subject: 'Admin',
    },
  },
  {
    path: '/pelaporan-dashboard/reporting',
    name: 'pelaporan-dashboard-reporting',
    component: reportingRouteComponent,
    meta: {
      navActiveLink: 'pelaporan-dashboard-reporting',
      action: 'manage',
      subject: 'Admin',
    },
  },
  {
    path: '/marketing-automation/customer-data-platform',
    name: 'marketing-automation-customer-data-platform',
    component: () => import('@/pages/pelanggan.vue'),
    meta: {
      navActiveLink: 'marketing-automation-customer-data-platform',
      action: 'read',
      subject: 'CrmCustomers',
    },
  },

  {
    path: '/marketing-automation/campaign-execution/whatsapp-broadcast',
    name: 'marketing-automation-campaign-execution-whatsapp-broadcast',
    component: campaignBroadcastRouteComponent,
    meta: {
      navActiveLink: 'marketing-automation-campaign-execution',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/marketing-automation/campaign-execution/whatsapp-broadcast/create',
    name: 'marketing-automation-campaign-execution-whatsapp-broadcast-create',
    component: campaignBroadcastCreateRouteComponent,
    meta: {
      navActiveLink: 'marketing-automation-campaign-execution',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/marketing-automation/campaign-execution/whatsapp-broadcast/:id',
    name: 'marketing-automation-campaign-execution-whatsapp-broadcast-id',
    component: campaignBroadcastDetailRouteComponent,
    meta: {
      navActiveLink: 'marketing-automation-campaign-execution',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/marketing-automation/campaign-execution/whatsapp-templates',
    name: 'marketing-automation-campaign-execution-whatsapp-templates',
    component: campaignTemplatesRouteComponent,
    meta: {
      navActiveLink: 'marketing-automation-campaign-execution',
    },
  },

  // Email filter
  {
    path: '/apps/email/filter/:filter',
    name: 'apps-email-filter',
    component: emailRouteComponent,
    meta: {
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  // Email label
  {
    path: '/apps/email/label/:label',
    name: 'apps-email-label',
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: 'apps-email',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/omnichannel/email-inbox/filter/:filter',
    name: 'omnichannel-email-inbox-filter',
    component: () => import('@/pages/omnichannel/email-inbox.vue'),
    meta: {
      navActiveLink: 'omnichannel-email-inbox',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/omnichannel/email-inbox/label/:label',
    name: 'omnichannel-email-inbox-label',
    component: () => import('@/pages/omnichannel/email-inbox.vue'),
    meta: {
      navActiveLink: 'omnichannel-email-inbox',
      layoutWrapperClasses: 'layout-content-height-fixed',
    },
  },

  {
    path: '/dashboards/logistics',
    name: 'dashboards-logistics',
    component: () => import('@/pages/apps/logistics/dashboard.vue'),
  },
  {
    path: '/dashboards/academy',
    name: 'dashboards-academy',
    component: () => import('@/pages/apps/academy/dashboard.vue'),
  },
  {
    path: '/apps/ecommerce/dashboard',
    name: 'apps-ecommerce-dashboard',
    component: () => import('@/pages/dashboards/ecommerce.vue'),
  },
]
