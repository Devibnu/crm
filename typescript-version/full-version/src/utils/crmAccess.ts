import type { Rule, Actions } from '@/plugins/casl/ability'
import type { UserModulePermissions, UserPermissionLevel } from '@/plugins/fake-api/handlers/apps/users/types'

export const crmSubjects = {
  customers: 'CrmCustomers',
  tickets: 'CrmTickets',
  inbox: 'CrmInbox',
  whatsapp: 'CrmWhatsapp',
  invoice: 'BackofficeInvoice',
} as const

type CrmModuleKey = keyof UserModulePermissions

const permissionActions: Record<UserPermissionLevel, Actions[]> = {
  full: ['manage', 'create', 'read', 'update', 'delete'],
  manage: ['create', 'read', 'update', 'delete'],
  handle: ['read', 'update'],
  view: ['read'],
}

export const buildCrmAbilityRules = (modulePermissions?: Partial<UserModulePermissions> | null): Rule[] => {
  if (!modulePermissions)
    return []

  return (Object.entries(crmSubjects) as Array<[CrmModuleKey, (typeof crmSubjects)[CrmModuleKey]]>)
    .flatMap(([moduleKey, subject]) => {
      const permissionLevel = modulePermissions[moduleKey]

      if (!permissionLevel)
        return []

      return permissionActions[permissionLevel].map(action => ({ action, subject }))
    })
}

export const mergeAbilityRules = (...ruleGroups: Array<Rule[] | undefined>): Rule[] => {
  const mergedRules: Rule[] = []
  const seenRules = new Set<string>()

  ruleGroups.forEach((rules) => {
    rules?.forEach((rule) => {
      const ruleKey = `${rule.action}:${rule.subject}`

      if (!seenRules.has(ruleKey)) {
        seenRules.add(ruleKey)
        mergedRules.push(rule)
      }
    })
  })

  return mergedRules
}

const crmSubjectSet = new Set(Object.values(crmSubjects))

export const buildAuthAbilityRules = ({
  role,
  modulePermissions,
  existingRules,
}: {
  role: string
  modulePermissions?: Partial<UserModulePermissions> | null
  existingRules?: Rule[]
}): Rule[] => {
  const retainedRules = existingRules?.filter(rule => rule.subject !== 'Admin' && !crmSubjectSet.has(rule.subject as (typeof crmSubjects)[keyof typeof crmSubjects]))
  const adminRules = role === 'admin'
    ? [{ action: 'manage', subject: 'Admin' } satisfies Rule]
    : []

  return mergeAbilityRules(retainedRules, adminRules, buildCrmAbilityRules(modulePermissions))
}