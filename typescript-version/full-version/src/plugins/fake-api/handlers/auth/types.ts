import type { Rule, Subjects, Actions } from '@/plugins/casl/ability'
import type { UserModulePermissions } from '@/plugins/fake-api/handlers/apps/users/types'

export type UserAbilityRule = Rule

export interface User {
  id: number
  fullName?: string
  username: string
  password: string
  avatar?: string
  email: string
  role: string
  modulePermissions?: UserModulePermissions
  abilityRules: UserAbilityRule[]
}

export interface UserOut {
  userAbilityRules: User['abilityRules']
  accessToken: string
  userData: Omit<User, 'abilities' | 'password'>
}

export interface LoginResponse {
  accessToken: string
  userData: User
  userAbilityRules: User['abilityRules']
}

export interface RegisterResponse {
  accessToken: string
  userData: User
  userAbilityRules: User['abilityRules']
}
