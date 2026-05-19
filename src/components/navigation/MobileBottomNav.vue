<script setup>
const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
});

const route = useRoute();
const router = useRouter();

const normalizedPath = value => String(value || '').replace(/\/+$/, '') || '/';

const activeValue = computed(() => {
  return props.items.find(item => {
    const target = normalizedPath(item.route);
    const current = normalizedPath(route.path);

    return current === target || current.startsWith(`${target}/`);
  })?.route ?? null;
});

const navigateTo = routePath => {
  if (!routePath)
    return;

  router.push(routePath);
};
</script>

<template>
  <div class="crm-bottom-nav-wrap">
    <VBottomNavigation
      :model-value="activeValue"
      grow
      height="74"
      class="crm-bottom-nav"
    >
      <VBtn
        v-for="item in items.slice(0, 5)"
        :key="item.id"
        :value="item.route"
        class="crm-bottom-nav__item"
        @click="navigateTo(item.route)"
      >
        <VIcon
          :icon="item.icon || 'tabler-circle'"
          size="22"
        />
        <span>{{ item.title }}</span>
      </VBtn>
    </VBottomNavigation>
  </div>
</template>

<style lang="scss" scoped>
.crm-bottom-nav-wrap {
  position: fixed;
  z-index: 40;
  inset-block-end: calc(env(safe-area-inset-bottom, 0px) + 0.9rem);
  inset-inline: 0.75rem;
}

.crm-bottom-nav {
  overflow: hidden;
  border: 1px solid rgba(124, 92, 255, 0.14);
  border-radius: 1.5rem;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 22px 44px rgba(15, 23, 42, 0.12);
  backdrop-filter: blur(18px);

  &__item {
    color: rgba(var(--v-theme-on-surface), 0.64);
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: none;
  }

  :deep(.v-btn--active) {
    background: linear-gradient(135deg, rgba(110, 73, 255, 1), rgba(153, 111, 255, 0.92));
    box-shadow: 0 14px 28px rgba(103, 77, 240, 0.18);
    color: white;
  }
}
</style>
