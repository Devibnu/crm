<script setup>
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { themeConfig } from '@themeConfig';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  items: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits([
  'update:modelValue',
]);

const drawerState = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const closeDrawer = () => {
  drawerState.value = false;
};
</script>

<template>
  <VNavigationDrawer
    v-model="drawerState"
    temporary
    location="left"
    width="320"
    class="crm-mobile-drawer"
  >
    <div class="crm-mobile-drawer__header">
      <RouterLink
        to="/"
        class="crm-mobile-drawer__brand"
        @click="closeDrawer"
      >
        <span class="crm-mobile-drawer__logo">
          <VNodeRenderer :nodes="themeConfig.app.logo" />
        </span>

        <div class="crm-mobile-drawer__copy">
          <span class="crm-mobile-drawer__eyebrow">Dynamic CRM Menu</span>
          <strong class="crm-mobile-drawer__title">{{ themeConfig.app.title }}</strong>
        </div>
      </RouterLink>

      <IconBtn @click="closeDrawer">
        <VIcon icon="tabler-x" />
      </IconBtn>
    </div>

    <div class="crm-mobile-drawer__body">
      <ul class="crm-mobile-drawer__list">
        <MenuItem
          v-for="item in items"
          :key="item.id"
          :item="item"
          mobile
          @navigate="closeDrawer"
        />
      </ul>
    </div>
  </VNavigationDrawer>
</template>

<style lang="scss" scoped>
.crm-mobile-drawer {
  border: 0;

  :deep(.v-navigation-drawer__content) {
    display: flex;
    flex-direction: column;
    background:
      radial-gradient(circle at top, rgba(141, 109, 255, 0.16), transparent 28%),
      #fff;
    block-size: 100%;
    padding: 1rem;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-block-end: 1rem;
  }

  &__brand {
    display: flex;
    flex: 1;
    align-items: center;
    gap: 0.9rem;
    text-decoration: none;
  }

  &__logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: linear-gradient(135deg, rgba(110, 73, 255, 0.2), rgba(153, 111, 255, 0.1));
    block-size: 3rem;
    inline-size: 3rem;
  }

  &__copy {
    display: grid;
  }

  &__eyebrow {
    color: rgba(var(--v-theme-on-surface), 0.54);
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

  &__body {
    overflow: auto;
    flex: 1;
  }

  &__list {
    display: grid;
    gap: 0.55rem;
    margin: 0;
    padding: 0;
  }
}
</style>
