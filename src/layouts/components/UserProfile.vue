<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import avatar1 from '@images/avatars/avatar-1.png'

const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie('userData')
const profileUser = computed(() => userData.value ?? {
  fullName: 'John Doe',
  username: 'john.doe',
  role: 'Admin',
  avatar: avatar1,
})

const logout = async () => {

  // Remove "accessToken" from cookie
  useCookie('accessToken').value = null

  // Remove "userData" from cookie
  userData.value = null

  // Redirect to login page
  await router.push('/login')

  // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page

  // Remove "userAbilities" from cookie
  useCookie('userAbilityRules').value = null

  // Reset ability to initial ability
  ability.update([])
}

const userProfileList = [
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'tabler-user',
    title: 'Profile',
    to: {
      name: 'apps-user-view-id',
      params: { id: 21 },
    },
  },
  {
    type: 'navItem',
    icon: 'tabler-settings',
    title: 'Settings',
    to: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
  {
    type: 'navItem',
    icon: 'tabler-file-dollar',
    title: 'Billing Plan',
    to: {
      name: 'pages-account-settings-tab',
      params: { tab: 'billing-plans' },
    },
    badgeProps: {
      color: 'error',
      content: '4',
    },
  },
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'tabler-currency-dollar',
    title: 'Pricing',
    to: { name: 'pages-pricing' },
  },
  {
    type: 'navItem',
    icon: 'tabler-question-mark',
    title: 'FAQ',
    to: { name: 'pages-faq' },
  },
]
</script>

<template>
  <VBadge
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
      :color="!profileUser.avatar ? 'primary' : undefined"
      :variant="!profileUser.avatar ? 'tonal' : undefined"
    >
      <VImg
        v-if="profileUser.avatar"
        :src="profileUser.avatar"
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
                    :color="!profileUser.avatar ? 'primary' : undefined"
                    :variant="!profileUser.avatar ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="profileUser.avatar"
                      :src="profileUser.avatar"
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
                  {{ profileUser.fullName || profileUser.username }}
                </h6>
                <VListItemSubtitle class="text-capitalize text-disabled">
                  {{ profileUser.role }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in userProfileList"
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
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
