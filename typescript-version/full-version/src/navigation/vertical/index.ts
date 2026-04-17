import crm from './crm'
import type { VerticalNavItems } from '@layouts/types'

const adminBackofficeNav = [
	{ heading: 'backoffice.nav.governanceSecurity' },
	{
		title: 'backoffice.nav.users',
		icon: { icon: 'tabler-users' },
		to: 'pengguna',
		action: 'manage',
		subject: 'Admin',
	},
	{
		title: 'backoffice.nav.rolesPermissions',
		icon: { icon: 'tabler-lock' },
		action: 'manage',
		subject: 'Admin',
		children: [
			{ title: 'backoffice.nav.roles', to: 'apps-roles', action: 'manage', subject: 'Admin' },
			{ title: 'backoffice.nav.permissions', to: 'apps-permissions', action: 'manage', subject: 'Admin' },
		],
	},
	{
		title: 'backoffice.nav.auditTrail',
		icon: { icon: 'tabler-file-search' },
		to: '/governance-security/audit-trail',
		action: 'manage',
		subject: 'Admin',
	},
	{ heading: 'backoffice.nav.integrationsTransactions' },
	{
		title: 'backoffice.nav.invoice',
		icon: { icon: 'tabler-file-invoice' },
		action: 'read',
		subject: 'BackofficeInvoice',
		children: [
			{ title: 'List', to: 'apps-invoice-list', action: 'read', subject: 'BackofficeInvoice' },
			{ title: 'Preview', to: { name: 'apps-invoice-preview-id', params: { id: '5036' } }, action: 'read', subject: 'BackofficeInvoice' },
			{ title: 'Edit', to: { name: 'apps-invoice-edit-id', params: { id: '5036' } }, action: 'update', subject: 'BackofficeInvoice' },
			{ title: 'Add', to: 'apps-invoice-add', action: 'create', subject: 'BackofficeInvoice' },
		],
	},
	{
		title: 'backoffice.nav.contractManagementSystem',
		icon: { icon: 'tabler-file-description' },
		to: '/integrasi-transaksi/contract-management-system',
		action: 'manage',
		subject: 'Admin',
	},
	{ heading: 'backoffice.nav.reportingDashboard' },
	{
		title: 'backoffice.nav.executiveDashboard',
		icon: { icon: 'tabler-layout-dashboard' },
		to: '/pelaporan-dashboard/executive-dashboard',
		action: 'manage',
		subject: 'Admin',
	},
	{
		title: 'backoffice.nav.reporting',
		icon: { icon: 'tabler-report-analytics' },
		to: '/pelaporan-dashboard/reporting',
		action: 'manage',
		subject: 'Admin',
	},
] as VerticalNavItems

const fullAdminNav = [
	...crm,
	...adminBackofficeNav,
] as VerticalNavItems

const sessionUser = useCookie<Record<string, unknown> | null | undefined>('userData')
const sessionRole = sessionUser.value?.role

export default (sessionRole === 'admin' ? fullAdminNav : crm) as VerticalNavItems
