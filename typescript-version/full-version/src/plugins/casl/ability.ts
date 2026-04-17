import { createMongoAbility } from '@casl/ability'

export type Actions = 'create' | 'read' | 'update' | 'delete' | 'manage'

export type Subjects =
	| 'Post'
	| 'Comment'
	| 'Auth'
	| 'Admin'
	| 'AclDemo'
	| 'CrmCustomers'
	| 'CrmTickets'
	| 'CrmInbox'
	| 'CrmWhatsapp'
	| 'BackofficeInvoice'
	| 'all'

export interface Rule { action: Actions; subject: Subjects }

export const ability = createMongoAbility<[Actions, Subjects]>()
