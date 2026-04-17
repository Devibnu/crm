<script setup lang="ts">
import type { Rule } from '@/plugins/casl/ability'
import type { UserModulePermissions, UserPermissionLevel } from '@/plugins/fake-api/handlers/apps/users/types'
import { buildAuthAbilityRules } from '@/utils/crmAccess'

definePage({
  meta: {
    action: 'manage',
    subject: 'Admin',
  },
})

const snackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

const { t } = useI18n()
const ability = useAbility()
const sessionUserData = useCookie<any>('userData')
const sessionUserAbilityRules = useCookie<Rule[]>('userAbilityRules')

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
})

const { data: usersData, execute: fetchUsers } = await useApi<any>(createUrl('/apps/users', {
  query: {
    itemsPerPage: 50,
    page: 1,
  },
}))

const users = computed(() => usersData.value?.users ?? [])
const searchQuery = ref('')
const selectedRole = ref('all')
const selectedStatus = ref('all')
const savingUserId = ref<number | null>(null)
const userDrafts = reactive<Record<number, { role: string; status: string; modulePermissions: UserModulePermissions }>>({})

const permissionModules = ['customers', 'tickets', 'inbox', 'whatsapp', 'invoice'] as const

const rolePermissionMatrix: Record<string, UserModulePermissions> = {
  admin: {
    customers: 'full',
    tickets: 'full',
    inbox: 'full',
    whatsapp: 'full',
    invoice: 'full',
  },
  maintainer: {
    customers: 'manage',
    tickets: 'manage',
    inbox: 'manage',
    whatsapp: 'manage',
    invoice: 'manage',
  },
  author: {
    customers: 'view',
    tickets: 'manage',
    inbox: 'handle',
    whatsapp: 'handle',
    invoice: 'view',
  },
  editor: {
    customers: 'view',
    tickets: 'handle',
    inbox: 'handle',
    whatsapp: 'view',
    invoice: 'view',
  },
  marketing: {
    customers: 'view',
    tickets: 'view',
    inbox: 'view',
    whatsapp: 'view',
    invoice: 'view',
  },
  sales: {
    customers: 'view',
    tickets: 'view',
    inbox: 'view',
    whatsapp: 'view',
    invoice: 'view',
  },
  service: {
    customers: 'view',
    tickets: 'view',
    inbox: 'view',
    whatsapp: 'view',
    invoice: 'view',
  },
  subscriber: {
    customers: 'view',
    tickets: 'view',
    inbox: 'view',
    whatsapp: 'view',
    invoice: 'view',
  },
}

const roleOptions = computed(() => [
  { title: t('crm.users.filters.allRoles'), value: 'all' },
  { title: roleLabel('admin'), value: 'admin' },
  { title: roleLabel('maintainer'), value: 'maintainer' },
  { title: roleLabel('author'), value: 'author' },
  { title: roleLabel('editor'), value: 'editor' },
  { title: roleLabel('marketing'), value: 'marketing' },
  { title: roleLabel('sales'), value: 'sales' },
  { title: roleLabel('service'), value: 'service' },
  { title: roleLabel('subscriber'), value: 'subscriber' },
])

const editableRoleOptions = computed(() => roleOptions.value.slice(1))

const statusOptions = computed(() => [
  { title: t('crm.users.filters.allStatuses'), value: 'all' },
  { title: statusLabel('active'), value: 'active' },
  { title: statusLabel('pending'), value: 'pending' },
  { title: statusLabel('inactive'), value: 'inactive' },
])

const editableStatusOptions = computed(() => statusOptions.value.slice(1))

const permissionLevelOptions = computed(() => [
  { title: permissionLevelLabel('full'), value: 'full' },
  { title: permissionLevelLabel('manage'), value: 'manage' },
  { title: permissionLevelLabel('handle'), value: 'handle' },
  { title: permissionLevelLabel('view'), value: 'view' },
])

const filteredUsers = computed(() => {
  const normalizedQuery = searchQuery.value.trim().toLowerCase()

  return users.value.filter((user: any) => {
    const matchesQuery = !normalizedQuery
      || [user.fullName, user.email, user.username, user.company]
        .filter(Boolean)
        .some((value: string) => value.toLowerCase().includes(normalizedQuery))

    const matchesRole = selectedRole.value === 'all' || user.role === selectedRole.value
    const matchesStatus = selectedStatus.value === 'all' || user.status === selectedStatus.value

    return matchesQuery && matchesRole && matchesStatus
  })
})

const hasActiveFilters = computed(() => searchQuery.value.trim() || selectedRole.value !== 'all' || selectedStatus.value !== 'all')

const clonePermissions = (permissions: UserModulePermissions): UserModulePermissions => ({
  customers: permissions.customers,
  tickets: permissions.tickets,
  inbox: permissions.inbox,
  whatsapp: permissions.whatsapp,
  invoice: permissions.invoice,
})

watch(users, currentUsers => {
  const nextIds = new Set<number>()

  currentUsers.forEach((user: any) => {
    nextIds.add(user.id)

    const basePermissions = clonePermissions(user.modulePermissions ?? resolveRolePermissions(user.role))

    if (!userDrafts[user.id]) {
      userDrafts[user.id] = {
        role: user.role,
        status: user.status,
        modulePermissions: basePermissions,
      }
    }
    else if (savingUserId.value !== user.id) {
      userDrafts[user.id].role = user.role
      userDrafts[user.id].status = user.status
      userDrafts[user.id].modulePermissions = basePermissions
    }
  })

  Object.keys(userDrafts).forEach(key => {
    const numericId = Number(key)

    if (!nextIds.has(numericId))
      delete userDrafts[numericId]
  })
}, { immediate: true })

const summaryCards = computed(() => {
  const allUsers = users.value

  return [
    {
      title: t('crm.users.metrics.total'),
      value: allUsers.length,
      color: 'primary',
      icon: 'tabler-users',
    },
    {
      title: t('crm.users.metrics.active'),
      value: allUsers.filter((user: any) => user.status === 'active').length,
      color: 'success',
      icon: 'tabler-user-check',
    },
    {
      title: t('crm.users.metrics.pending'),
      value: allUsers.filter((user: any) => user.status === 'pending').length,
      color: 'warning',
      icon: 'tabler-user-exclamation',
    },
    {
      title: t('crm.users.metrics.owner'),
      value: allUsers.filter((user: any) => ['admin', 'maintainer'].includes(user.role)).length,
      color: 'info',
      icon: 'tabler-shield-check',
    },
  ]
})

const permissionMatrixRows = computed(() => editableRoleOptions.value.map(roleOption => ({
  role: roleOption.value,
  label: roleOption.title,
  permissions: rolePermissionMatrix[roleOption.value],
})))

const roleColor = (role: string) => {
  if (role === 'admin')
    return 'primary'

  if (role === 'maintainer')
    return 'success'

  if (role === 'author')
    return 'info'

  if (role === 'editor')
    return 'warning'

  if (role === 'marketing')
    return 'info'

  if (role === 'sales')
    return 'primary'

  if (role === 'service')
    return 'success'

  return 'secondary'
}

const statusColor = (status: string) => {
  if (status === 'active')
    return 'success'

  if (status === 'pending')
    return 'warning'

  return 'secondary'
}

const roleLabel = (role: string) => t(`crm.users.roles.${role}`)
const statusLabel = (status: string) => t(`crm.users.statuses.${status}`)
const planLabel = (plan: string) => t(`crm.users.plans.${plan}`)
const permissionModuleLabel = (moduleKey: (typeof permissionModules)[number]) => t(`crm.users.permissions.modules.${moduleKey}`)
const permissionLevelLabel = (level: UserPermissionLevel) => t(`crm.users.permissions.levels.${level}`)

const permissionColor = (level: UserPermissionLevel) => {
  if (level === 'full')
    return 'primary'

  if (level === 'manage')
    return 'success'

  if (level === 'handle')
    return 'warning'

  return 'secondary'
}

const resolveRolePermissions = (role: string) => rolePermissionMatrix[role] ?? rolePermissionMatrix.subscriber

const resolveUserPermissions = (user: any) => user.modulePermissions ?? resolveRolePermissions(user.role)

const resetFilters = () => {
  searchQuery.value = ''
  selectedRole.value = 'all'
  selectedStatus.value = 'all'
}

const showSnackbar = (text: string, color: 'success' | 'error' = 'success') => {
  snackbarMessage.value = text
  snackbarColor.value = color
  snackbarVisible.value = true
}

const getErrorMessage = (error: unknown, fallback: string) => {
  if (error && typeof error === 'object') {
    const apiError = error as { data?: { message?: string }, message?: string }

    if (apiError.data?.message)
      return apiError.data.message

    if (apiError.message)
      return apiError.message
  }

  return fallback
}

const hasUserAccessChanges = (user: any) => {
  const draft = userDrafts[user.id]

  if (!draft)
    return false

  return draft.role !== user.role
    || draft.status !== user.status
    || permissionModules.some(moduleKey => draft.modulePermissions[moduleKey] !== resolveUserPermissions(user)[moduleKey])
}

const resetUserAccess = (user: any) => {
  if (!userDrafts[user.id])
    return

  userDrafts[user.id].role = user.role
  userDrafts[user.id].status = user.status
  userDrafts[user.id].modulePermissions = clonePermissions(resolveUserPermissions(user))
}

const applyRoleDefaultPermissions = (userId: number) => {
  const draft = userDrafts[userId]

  if (!draft)
    return

  draft.modulePermissions = clonePermissions(resolveRolePermissions(draft.role))
}

const saveUserAccess = async (user: any) => {
  const draft = userDrafts[user.id]

  if (!draft || !hasUserAccessChanges(user))
    return

  savingUserId.value = user.id

  try {
    const updatedUser = await $api(`/apps/users/${user.id}`, {
      method: 'PUT',
      body: {
        role: draft.role,
        status: draft.status,
        modulePermissions: draft.modulePermissions,
      },
    })

    if (sessionUserData.value?.email === updatedUser.email) {
      sessionUserData.value = {
        ...sessionUserData.value,
        role: updatedUser.role,
        modulePermissions: updatedUser.modulePermissions,
      }

      const nextAbilityRules = buildAuthAbilityRules({
        role: updatedUser.role,
        modulePermissions: updatedUser.modulePermissions,
      })

      sessionUserAbilityRules.value = null
      ability.update(nextAbilityRules)
    }

    await fetchUsers()
    showSnackbar(t('crm.users.snackbar.saveSuccess', { name: user.fullName }))
  }
  catch (error) {
    showSnackbar(getErrorMessage(error, t('crm.users.snackbar.saveError', { name: user.fullName })), 'error')
  }
  finally {
    savingUserId.value = null
  }
}

const userPermissionChips = (permissions: UserModulePermissions) => permissionModules.map(moduleKey => ({
  key: moduleKey,
  module: permissionModuleLabel(moduleKey),
  level: permissions[moduleKey],
}))

const formatCreated = () => formatter.format(new Date())
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-md-row gap-4 justify-space-between align-md-center">
        <div>
          <h4 class="text-h4 mb-1">
            {{ t('crm.users.title') }}
          </h4>
          <p class="mb-0 text-body-1">
            {{ t('crm.users.subtitle') }}
          </p>
        </div>

        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="tabler-refresh"
          @click="fetchUsers()"
        >
          {{ t('common.refresh') }}
        </VBtn>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-md-row justify-space-between align-md-center gap-3">
          <div>
            <div class="text-h6">{{ t('crm.users.filters.title') }}</div>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('crm.users.filters.subtitle', { count: filteredUsers.length, total: users.length }) }}
            </div>
          </div>

          <VBtn
            v-if="hasActiveFilters"
            color="secondary"
            variant="tonal"
            prepend-icon="tabler-rotate-2"
            @click="resetFilters"
          >
            {{ t('crm.users.filters.reset') }}
          </VBtn>
        </div>

        <VRow>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="searchQuery"
              :placeholder="t('crm.users.filters.searchPlaceholder')"
              prepend-inner-icon="tabler-search"
            />
          </VCol>
          <VCol cols="12" md="3">
            <AppSelect
              v-model="selectedRole"
              :items="roleOptions"
              :label="t('crm.users.filters.role')"
            />
          </VCol>
          <VCol cols="12" md="3">
            <AppSelect
              v-model="selectedStatus"
              :items="statusOptions"
              :label="t('crm.users.filters.status')"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow>
      <VCol
        v-for="card in summaryCards"
        :key="card.title"
        cols="12"
        sm="6"
        xl="3"
      >
        <VCard>
          <VCardText class="d-flex align-center justify-space-between gap-3">
            <div>
              <div class="text-sm text-medium-emphasis mb-1">{{ card.title }}</div>
              <div class="text-h4">{{ card.value }}</div>
            </div>

            <VAvatar
              :color="card.color"
              variant="tonal"
              size="42"
            >
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div>
          <div class="text-h6 mb-1">{{ t('crm.users.permissions.title') }}</div>
          <div class="text-body-2 text-medium-emphasis">{{ t('crm.users.permissions.subtitle') }}</div>
        </div>

        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th>{{ t('crm.users.permissions.roleColumn') }}</th>
              <th v-for="moduleKey in permissionModules" :key="`permission-head-${moduleKey}`">
                {{ permissionModuleLabel(moduleKey) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in permissionMatrixRows" :key="row.role">
              <td class="font-weight-medium">{{ row.label }}</td>
              <td v-for="moduleKey in permissionModules" :key="`permission-row-${row.role}-${moduleKey}`">
                <VChip
                  :color="permissionColor(row.permissions[moduleKey])"
                  size="small"
                  variant="tonal"
                >
                  {{ permissionLevelLabel(row.permissions[moduleKey]) }}
                </VChip>
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <VRow>

      <VCol
        v-for="user in filteredUsers"
        :key="user.id"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardText class="d-flex gap-4 align-start">
            <VAvatar
              size="48"
              color="primary"
              variant="tonal"
            >
              <VImg
                v-if="user.avatar"
                :src="user.avatar"
                cover
              />
              <span v-else>{{ user.fullName?.slice(0, 1) }}</span>
            </VAvatar>

            <div class="flex-grow-1">
              <div class="d-flex flex-column flex-sm-row justify-space-between gap-2 mb-2">
                <div>
                  <div class="text-h6">
                    {{ user.fullName }}
                  </div>
                  <div class="text-sm text-medium-emphasis">
                    {{ user.email }}
                  </div>
                </div>

                <div class="d-flex flex-wrap justify-end gap-2">
                  <VChip
                    :color="roleColor(user.role)"
                    variant="tonal"
                    size="small"
                  >
                    {{ roleLabel(user.role) }}
                  </VChip>

                  <VChip
                    :color="statusColor(user.status)"
                    variant="tonal"
                    size="small"
                  >
                    {{ statusLabel(user.status) }}
                  </VChip>
                </div>
              </div>

              <VRow>
                <VCol cols="12" sm="6">
                  <div class="text-sm text-medium-emphasis">{{ t('crm.users.fields.username') }}</div>
                  <div class="text-body-1">{{ user.username }}</div>
                </VCol>
                <VCol cols="12" sm="6">
                  <div class="text-sm text-medium-emphasis">{{ t('crm.users.fields.plan') }}</div>
                  <div class="text-body-1">{{ planLabel(user.currentPlan) }}</div>
                </VCol>
                <VCol cols="12" sm="6">
                  <div class="text-sm text-medium-emphasis">{{ t('crm.users.fields.role') }}</div>
                  <div class="text-body-1">{{ roleLabel(user.role) }}</div>
                </VCol>
                <VCol cols="12" sm="6">
                  <div class="text-sm text-medium-emphasis">{{ t('crm.users.fields.snapshot') }}</div>
                  <div class="text-body-1">{{ formatCreated() }}</div>
                </VCol>
              </VRow>

              <div class="text-sm text-medium-emphasis mt-3">
                {{ t('crm.users.accessSummary', { role: roleLabel(user.role), plan: planLabel(user.currentPlan), status: statusLabel(user.status) }) }}
              </div>

              <div class="d-flex flex-column gap-2 mt-4">
                <div class="text-sm text-medium-emphasis">{{ t('crm.users.permissions.userAccessTitle') }}</div>
                <div class="d-flex flex-wrap gap-2">
                  <VChip
                    v-for="permission in userPermissionChips(userDrafts[user.id].modulePermissions)"
                    :key="`${user.id}-${permission.key}`"
                    :color="permissionColor(permission.level)"
                    size="small"
                    variant="tonal"
                  >
                    {{ permission.module }} · {{ permissionLevelLabel(permission.level) }}
                  </VChip>
                </div>
              </div>

              <VDivider class="my-4" />

              <div class="text-sm text-medium-emphasis mb-3">
                {{ t('crm.users.accessControlTitle') }}
              </div>

              <VRow>
                <VCol cols="12" sm="6">
                  <AppSelect
                    v-model="userDrafts[user.id].role"
                    :items="editableRoleOptions"
                    :label="t('crm.users.fields.role')"
                    :disabled="savingUserId === user.id"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppSelect
                    v-model="userDrafts[user.id].status"
                    :items="editableStatusOptions"
                    :label="t('crm.users.fields.status')"
                    :disabled="savingUserId === user.id"
                  />
                </VCol>
              </VRow>

              <div class="d-flex flex-column gap-3 mt-2">
                <div class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-2">
                  <div class="text-sm text-medium-emphasis">{{ t('crm.users.permissions.overrideTitle') }}</div>
                  <VBtn
                    size="small"
                    variant="text"
                    color="primary"
                    :disabled="savingUserId === user.id"
                    @click="applyRoleDefaultPermissions(user.id)"
                  >
                    {{ t('crm.users.permissions.applyRoleDefault') }}
                  </VBtn>
                </div>

                <VRow>
                  <VCol
                    v-for="moduleKey in permissionModules"
                    :key="`${user.id}-permission-${moduleKey}`"
                    cols="12"
                    sm="6"
                  >
                    <AppSelect
                      v-model="userDrafts[user.id].modulePermissions[moduleKey]"
                      :items="permissionLevelOptions"
                      :label="permissionModuleLabel(moduleKey)"
                      :disabled="savingUserId === user.id"
                    />
                  </VCol>
                </VRow>
              </div>

              <div class="d-flex flex-wrap justify-end gap-2 mt-2">
                <VBtn
                  variant="tonal"
                  color="secondary"
                  :disabled="savingUserId === user.id || !hasUserAccessChanges(user)"
                  @click="resetUserAccess(user)"
                >
                  {{ t('crm.users.actions.resetAccess') }}
                </VBtn>
                <VBtn
                  color="primary"
                  :loading="savingUserId === user.id"
                  :disabled="!hasUserAccessChanges(user)"
                  @click="saveUserAccess(user)"
                >
                  {{ t('crm.users.actions.saveAccess') }}
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        v-if="!filteredUsers.length"
        cols="12"
      >
        <VCard>
          <VCardText class="text-center text-medium-emphasis">
            {{ hasActiveFilters ? t('crm.users.emptyFiltered') : t('crm.users.empty') }}
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar
      v-model="snackbarVisible"
      :color="snackbarColor"
      location="top end"
      timeout="2500"
    >
      {{ snackbarMessage }}
    </VSnackbar>
  </section>
</template>