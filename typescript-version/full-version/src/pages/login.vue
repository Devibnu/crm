<!-- ❗Errors in the form are set on line 60 -->
<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { buildAuthAbilityRules } from '@/utils/crmAccess'
import { axiosApi, persistSanctumSession } from '@/plugins/axios'

interface AuthLoginResponse {
  accessToken: string
  userData: {
    role: string
    modulePermissions?: Record<string, string>
  } & Record<string, unknown>
}

const { t } = useI18n()

const authThemeImg = useGenerateImageVariant(authV2LoginIllustrationLight, authV2LoginIllustrationDark, authV2LoginIllustrationBorderedLight, authV2LoginIllustrationBorderedDark, true)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)

const route = useRoute()
const router = useRouter()

const ability = useAbility()

const errors = ref<Record<string, string | undefined>>({
  email: undefined,
  password: undefined,
})

const refVForm = ref<VForm>()

const credentials = ref({
  email: 'admin@demo.com',
  password: 'admin',
})

const showUatShortcutPanel = import.meta.env.DEV
const uatAccounts = [
  {
    title: 'Full CRM Admin',
    email: 'admin@demo.com',
    password: 'admin',
    role: 'Admin',
    scope: 'Akses penuh semua modul CRM',
  },
  {
    title: 'Read-only Observer',
    email: 'observer@demo.com',
    password: 'observer',
    role: 'Observer',
    scope: 'Pantau semua workspace tanpa aksi tulis',
  },
  {
    title: 'Inbox Operator',
    email: 'inbox-operator@demo.com',
    password: 'inbox',
    role: 'Operator',
    scope: 'Handle inbox dan ticket tanpa edit profil customer',
  },
  {
    title: 'Customer Admin',
    email: 'customer-admin@demo.com',
    password: 'customer',
    role: 'Customer Admin',
    scope: 'Kelola customer tanpa penanganan ticket/inbox',
  },
  {
    title: 'Finance Admin',
    email: 'finance-admin@demo.com',
    password: 'finance',
    role: 'Finance',
    scope: 'Kelola invoice tanpa hak admin pengguna atau aksi CRM tulis',
  },
  {
    title: 'Marketing Viewer',
    email: 'marketing@demo.com',
    password: 'marketing',
    role: 'Marketing',
    scope: 'Akses Customer Profile 360° dan data pelanggan secara read-only',
  },
  {
    title: 'Sales Viewer',
    email: 'sales@demo.com',
    password: 'sales',
    role: 'Sales',
    scope: 'Lihat Customer Profile 360° untuk tindak lanjut pipeline tanpa aksi tulis',
  },
  {
    title: 'Service Viewer',
    email: 'service@demo.com',
    password: 'service',
    role: 'Service',
    scope: 'Pantau profil pelanggan 360° untuk kebutuhan layanan tanpa ubah data',
  },
] as const

const rememberMe = ref(false)

const applyUatAccount = (account: (typeof uatAccounts)[number]) => {
  credentials.value = {
    email: account.email,
    password: account.password,
  }
  errors.value = {
    email: undefined,
    password: undefined,
  }
}

const login = async () => {
  try {
    const { data } = await axiosApi.post<AuthLoginResponse>('/auth/login', {
      email: credentials.value.email,
      password: credentials.value.password,
    })

    const { accessToken, userData } = data

    persistSanctumSession({
      accessToken,
      userData,
    })

    ability.update(buildAuthAbilityRules({
      role: userData.role,
      modulePermissions: userData.modulePermissions,
    }))

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  }
  catch (error: any) {
    if (error?.response?.status === 422) {
      errors.value = {
        ...errors.value,
        ...error.response.data?.errors,
      }
    }
    else {
      console.error(error)
    }
  }
}

const onSubmit = () => {
  refVForm.value?.validate()
    .then(({ valid: isValid }) => {
      if (isValid)
        login()
    })
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            {{ t('auth.login.title', { app: themeConfig.app.title }) }}
          </h4>
          <p class="mb-0">
            {{ t('auth.login.subtitle') }}
          </p>
        </VCardText>
        <VCardText>
          <VAlert
            color="primary"
            variant="tonal"
          >
            <p class="text-sm mb-2">
              {{ t('auth.login.adminHint', { email: 'admin@demo.com', password: 'admin' }) }}
            </p>
          </VAlert>
        </VCardText>
        <VCardText v-if="showUatShortcutPanel" class="pt-0">
          <VCard variant="tonal" color="secondary" class="uat-shortcut-panel">
            <VCardText>
              <div class="d-flex align-center justify-space-between gap-3 flex-wrap mb-4">
                <div>
                  <div class="text-subtitle-1 font-weight-medium text-high-emphasis">UAT Account Switcher</div>
                  <div class="text-body-2 text-medium-emphasis">Hanya tampil di mode development untuk mempercepat pengujian permission matrix.</div>
                </div>
                <VChip size="small" color="warning" variant="tonal">
                  DEV ONLY
                </VChip>
              </div>

              <div class="d-flex flex-column gap-3">
                <button
                  v-for="account in uatAccounts"
                  :key="account.email"
                  type="button"
                  class="uat-account-card text-start"
                  @click="applyUatAccount(account)"
                >
                  <div class="d-flex align-start justify-space-between gap-3 flex-wrap mb-2">
                    <div>
                      <div class="font-weight-medium text-high-emphasis">{{ account.title }}</div>
                      <div class="text-body-2 text-medium-emphasis">{{ account.email }}</div>
                    </div>
                    <VChip size="x-small" color="primary" variant="tonal">
                      {{ account.role }}
                    </VChip>
                  </div>
                  <div class="text-body-2 text-medium-emphasis mb-2">{{ account.scope }}</div>
                  <div class="text-caption text-disabled">Password: {{ account.password }}</div>
                </button>
              </div>
            </VCardText>
          </VCard>
        </VCardText>
        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  :label="t('common.email')"
                  placeholder="johndoe@email.com"
                  type="email"
                  autofocus
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  :label="t('common.password')"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap justify-space-between my-6">
                  <VCheckbox
                    v-model="rememberMe"
                    :label="t('auth.login.remember')"
                  />
                </div>

                <VBtn
                  block
                  type="submit"
                >
                  {{ t('auth.login.submit') }}
                </VBtn>
              </VCol>

              <!-- create account -->
              <VCol
                cols="12"
                class="text-center"
              >
                <span>{{ t('auth.login.singleAdmin') }}</span>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";

.uat-shortcut-panel {
  border: 1px solid rgba(var(--v-theme-secondary), 0.18);
}

.uat-account-card {
  inline-size: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 0.875rem;
  background: rgb(var(--v-theme-surface));
  transition: border-color 0.2s ease, transform 0.2s ease, background-color 0.2s ease;

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.42);
    background: rgba(var(--v-theme-primary), 0.04);
    transform: translateY(-1px);
  }
}
</style>
