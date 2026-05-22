<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { themeConfig } from '@themeConfig';

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'request-expand',
]);
</script>

<template>
  <aside
    class="crm-sidebar-desktop"
    :class="{ 'crm-sidebar-desktop--collapsed': collapsed }"
  >
    <div class="crm-sidebar-desktop__shell">
      <RouterLink
        to="/"
        class="crm-sidebar-desktop__brand"
      >
        <span class="crm-sidebar-desktop__brand-logo">
          <VNodeRenderer :nodes="themeConfig.app.logo" />
        </span>

        <Transition name="crm-brand-fade">
          <div
            v-if="!collapsed"
            class="crm-sidebar-desktop__brand-copy"
          >
            <span class="crm-sidebar-desktop__eyebrow">CRM Workspace</span>
            <strong class="crm-sidebar-desktop__title">{{ themeConfig.app.title }}</strong>
          </div>
        </Transition>
      </RouterLink>

      <div class="crm-sidebar-desktop__nav">
        <ul class="crm-sidebar-desktop__list">
          <MenuItem
            v-for="item in items"
            :key="item.id"
            :item="item"
            :collapsed="collapsed"
            @request-expand="emit('request-expand')"
          />
        </ul>
      </div>
    </div>
  </aside>
</template>

<style lang="scss" scoped>
.crm-sidebar-desktop {
  position: sticky;
  top: 1.25rem;
  z-index: 20;
  align-self: flex-start;
  block-size: calc(100dvh - 2.5rem);
  inline-size: 320px;
  transition: inline-size 0.28s ease;

  &--collapsed {
    inline-size: 112px;
  }

  &__shell {
    display: flex;
    overflow: hidden;
    flex-direction: column;
    border: 1px solid rgba(124, 92, 255, 0.12);
    border-radius: 2rem;
    background:
      radial-gradient(circle at top, rgba(141, 109, 255, 0.12), transparent 30%),
      #fff;
    block-size: 100%;
    box-shadow: 0 28px 52px rgba(15, 23, 42, 0.08);
    padding: 1rem;
  }

  &__brand {
    display: flex;
    align-items: center;
    gap: 0.95rem;
    border-radius: 1.5rem;
    margin-block-end: 1rem;
    padding: 0.7rem 0.8rem;
    text-decoration: none;
  }

  &__brand-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1.25rem;
    background: linear-gradient(135deg, rgba(110, 73, 255, 0.18), rgba(153, 111, 255, 0.08));
    block-size: 3rem;
    flex-shrink: 0;
    inline-size: 3rem;
  }

  &__brand-copy {
    display: grid;
    gap: 0.1rem;
    overflow: hidden;
  }

  &__eyebrow {
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  &__title {
    color: rgb(var(--v-theme-on-surface));
    font-size: 1rem;
    font-weight: 800;
    text-transform: capitalize;
  }

  &__nav {
    overflow: auto;
    flex: 1;
    padding-inline-end: 0.15rem;
  }

  &__list {
    display: grid;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
  }
}

.crm-brand-fade-enter-active,
.crm-brand-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.crm-brand-fade-enter-from,
.crm-brand-fade-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
