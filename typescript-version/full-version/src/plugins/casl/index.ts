import type { App } from 'vue'

import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import type { UserModulePermissions } from '@/plugins/fake-api/handlers/apps/users/types'
import { buildAuthAbilityRules } from '@/utils/crmAccess'

export default function (app: App) {
  const userAbilityRules = useCookie('userAbilityRules')
  const userData = useCookie<{ role?: string; modulePermissions?: Partial<UserModulePermissions> } | null>('userData')
  const resolvedAbilityRules = userData.value?.role
    ? buildAuthAbilityRules({
        role: userData.value.role,
        modulePermissions: userData.value.modulePermissions,
      })
    : []

  // Ability rules are derived from persisted userData to avoid stale or oversized cookies.
  if (userAbilityRules.value)
    userAbilityRules.value = null

  const initialAbility = createMongoAbility(resolvedAbilityRules)

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
