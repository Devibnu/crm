<script setup lang="ts">
import type { Actions, Subjects } from '@/plugins/casl/ability'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { axiosApi, resetSanctumSession } from '@/plugins/axios'

const { t } = useI18n()
const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie<any>('userData')

const logout = async () => {
  try {
    await axiosApi.post('/auth/logout')
  }
  catch (error) {
    console.error(error)
  }

  resetSanctumSession(false)
  userData.value = null

  // Redirect to login page
  await router.push('/login')

  // Reset ability to initial ability
  ability.update([])
}

const userProfileList: Array<{
  type: string
  icon?: string
  title?: string
  to?: {
    name: string
  }
  action?: Actions
  subject?: Subjects
  badgeProps?: Record<string, unknown>
}> = [
  { type: 'divider' },
  { type: 'navItem', icon: 'tabler-layout-dashboard', title: 'crm.nav.dashboard', to: { name: 'dashboard' } },
  { type: 'navItem', icon: 'tabler-message-circle', title: 'crm.nav.conversations', to: { name: 'percakapan' } },
  { type: 'navItem', icon: 'tabler-users-group', title: 'crm.nav.customers', to: { name: 'marketing-automation-customer-data-platform' }, action: 'read', subject: 'CrmCustomers' },
  { type: 'navItem', icon: 'tabler-user-shield', title: 'crm.nav.users', to: { name: 'pengguna' }, action: 'manage', subject: 'Admin' },
]

const visibleUserProfileList = computed(() => userProfileList.filter(item => item.type !== 'navItem' || !item.action || !item.subject || ability.can(item.action, item.subject)))
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      />
      <VIcon
        v-else
        icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar
                    :color="!(userData && userData.avatar) ? 'primary' : undefined"
                    :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon
                      v-else
                      icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <h6 class="text-h6 font-weight-medium">
                  {{ userData.fullName || userData.username }}
                </h6>
                <VListItemSubtitle class="text-capitalize text-disabled">
                  {{ userData.role }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in visibleUserProfileList"
              :key="item.title"
            >
              <VListItem
                v-if="item.type === 'navItem'"
                :to="item.to"
              >
                <template #prepend>
                  <VIcon
                    :icon="item.icon"
                    size="22"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template
                  v-if="item.badgeProps"
                  #append
                >
                  <VBadge
                    rounded="sm"
                    class="me-3"
                    v-bind="item.badgeProps"
                  />
                </template>
              </VListItem>

              <VDivider
                v-else
                class="my-2"
              />
            </template>

            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                @click="logout"
              >
                {{ t('common.logout') }}
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
