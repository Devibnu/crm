<script setup>
import MobileBottomNav from '@/components/navigation/MobileBottomNav.vue';
import MobileSidebarDrawer from '@/components/navigation/MobileSidebarDrawer.vue';
import SidebarDesktop from '@/components/navigation/SidebarDesktop.vue';
import Footer from '@/layouts/components/Footer.vue';
import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue';
import NavSearchBar from '@/layouts/components/NavSearchBar.vue';
import NavbarShortcuts from '@/layouts/components/NavbarShortcuts.vue';
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue';
import UserProfile from '@/layouts/components/UserProfile.vue';
import { useDynamicMenuStore } from '@/stores/dynamicMenu';
import NavBarI18n from '@core/components/I18n.vue';
import { useLayoutConfigStore } from '@layouts/stores/config';
import { themeConfig } from '@themeConfig';

const menuStore = useDynamicMenuStore();
const configStore = useLayoutConfigStore();
const display = useDisplay();
const route = useRoute();
const isMobileDrawerOpen = ref(false);

const isMobile = computed(() => display.smAndDown.value);
const shouldShowDesktopSidebar = computed(() => !isMobile.value);
const shouldShowBottomNav = computed(() => isMobile.value && menuStore.bottomNavigation.length > 0);
const currentRole = computed(() => menuStore.role);

const isSidebarCollapsed = computed({
  get: () => !isMobile.value && configStore.isVerticalNavCollapsed.value,
  set: value => {
    configStore.isVerticalNavCollapsed.value = value;
  },
});

const flattenItems = items => {
  return items.flatMap(item => [item, ...flattenItems(item.children || [])]);
};

const pageTitle = computed(() => {
  const currentItem = flattenItems(menuStore.primaryItems).find(item => item.route === route.path);

  return currentItem?.title ?? 'CRM Workspace';
});

const toggleSidebar = () => {
  if (isMobile.value) {
    isMobileDrawerOpen.value = true;

    return;
  }

  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const expandSidebar = () => {
  isSidebarCollapsed.value = false;
};

watch(() => route.path, () => {
  isMobileDrawerOpen.value = false;
});

watch(currentRole, async () => {
  menuStore.reset();
  await menuStore.fetchMenus({ force: true });
}, { immediate: true });
</script>

<template>
  <div class="crm-app-shell">
    <SidebarDesktop
      v-if="shouldShowDesktopSidebar"
      :items="menuStore.primaryItems"
      :collapsed="isSidebarCollapsed"
      @request-expand="expandSidebar"
    />

    <MobileSidebarDrawer
      v-model="isMobileDrawerOpen"
      :items="menuStore.primaryItems"
    />

    <div
      class="crm-app-shell__content"
      :class="{ 'crm-app-shell__content--bottom-nav': shouldShowBottomNav }"
    >
      <header class="crm-app-shell__topbar">
        <div class="crm-app-shell__toolbar">
          <div class="crm-app-shell__toolbar-left">
            <IconBtn @click="toggleSidebar">
              <VIcon :icon="isMobile ? 'tabler-menu-2' : isSidebarCollapsed ? 'tabler-layout-sidebar-left-expand' : 'tabler-layout-sidebar-left-collapse'" />
            </IconBtn>

            <div class="crm-app-shell__title-copy d-none d-md-grid">
              <span class="crm-app-shell__title-eyebrow">Premium Navigation</span>
              <strong class="crm-app-shell__title-text">{{ pageTitle }}</strong>
            </div>
          </div>

          <NavSearchBar class="crm-app-shell__search" />

          <div class="crm-app-shell__toolbar-right">
            <NavBarI18n
              v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
              :languages="themeConfig.app.i18n.langConfig"
            />
            <NavbarThemeSwitcher />
            <NavbarShortcuts class="d-none d-md-inline-flex" />
            <NavBarNotifications class="me-1" />
            <UserProfile />
          </div>
        </div>
      </header>

      <main class="crm-app-shell__main">
        <div class="crm-app-shell__main-inner">
          <VAlert
            v-if="menuStore.errorMessage"
            type="warning"
            variant="tonal"
            class="mb-6"
          >
            {{ menuStore.errorMessage }}
          </VAlert>

          <slot />
        </div>
      </main>

      <footer class="crm-app-shell__footer">
        <Footer />
      </footer>
    </div>

    <MobileBottomNav
      v-if="shouldShowBottomNav"
      :items="menuStore.bottomNavigation"
    />

    <TheCustomizer />
  </div>
</template>

<style lang="scss" scoped>
.crm-app-shell {
  display: flex;
  gap: 1rem;
  min-block-size: 100dvh;
  padding: 1.25rem;

  &__content {
    display: flex;
    min-inline-size: 0;
    flex: 1;
    flex-direction: column;

    &--bottom-nav {
      padding-block-end: 6.5rem;
    }
  }

  &__topbar {
    position: sticky;
    top: 1.25rem;
    z-index: 10;
    margin-block-end: 1rem;
  }

  &__toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid rgba(124, 92, 255, 0.1);
    border-radius: 1.75rem;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.06);
    min-block-size: 5rem;
    padding: 0.9rem 1rem;
    backdrop-filter: blur(18px);
  }

  &__toolbar-left,
  &__toolbar-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  &__toolbar-right {
    margin-inline-start: auto;
  }

  &__title-copy {
    gap: 0.05rem;
  }

  &__title-eyebrow {
    color: rgba(var(--v-theme-on-surface), 0.54);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  &__title-text {
    color: rgb(var(--v-theme-on-surface));
    font-size: 0.98rem;
    font-weight: 800;
  }

  &__search {
    flex: 1;
    min-inline-size: 0;
  }

  &__main {
    min-inline-size: 0;
    flex: 1;
  }

  &__main-inner {
    min-inline-size: 0;
  }

  &__footer {
    padding-block: 1.5rem 0.5rem;
  }
}

@media (max-width: 1279px) {
  .crm-app-shell {
    padding: 1rem;
  }
}

@media (max-width: 959px) {
  .crm-app-shell {
    padding: 0.75rem;

    &__toolbar {
      gap: 0.75rem;
      min-block-size: auto;
      padding: 0.8rem;
    }

    &__toolbar-right {
      gap: 0.35rem;
    }
  }
}
</style>
