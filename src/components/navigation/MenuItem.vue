<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
  depth: {
    type: Number,
    default: 0,
  },
  mobile: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'navigate',
  'request-expand',
]);

const route = useRoute();

const hasChildren = computed(() => (props.item.children || []).length > 0);
const firstChildRoute = computed(() => {
  return (props.item.children || []).find(child => child.route)?.route ?? null;
});

const normalizedPath = value => {
  if (!value)
    return '';

  return String(value).replace(/\/+$/, '') || '/';
};

const isPathActive = targetPath => {
  if (!targetPath)
    return false;

  const currentPath = normalizedPath(route.path);
  const resolvedTarget = normalizedPath(targetPath);

  if (resolvedTarget === '/')
    return currentPath === '/';

  return currentPath === resolvedTarget || currentPath.startsWith(`${resolvedTarget}/`);
};

const hasActiveDescendant = items => {
  return items.some(child => {
    if (isPathActive(child.route))
      return true;

    return hasActiveDescendant(child.children || []);
  });
};

const isActive = computed(() => isPathActive(props.item.route));
const isGroupActive = computed(() => hasActiveDescendant(props.item.children || []));
const isOpen = ref(isGroupActive.value);
const itemTarget = computed(() => props.item.route || firstChildRoute.value);

watch(() => route.path, () => {
  if (hasChildren.value)
    isOpen.value = isGroupActive.value;
}, { immediate: true });

const handlePrimaryClick = () => {
  if (hasChildren.value && props.collapsed && !props.mobile) {
    emit('request-expand');

    return;
  }

  if (!hasChildren.value)
    emit('navigate');
};

const handleGroupToggle = () => {
  if (props.collapsed && !props.mobile) {
    emit('request-expand');

    return;
  }

  isOpen.value = !isOpen.value;
};
</script>

<template>
  <li
    class="crm-menu-item"
    :class="[
      `crm-menu-item--depth-${depth}`,
      {
        'crm-menu-item--collapsed': collapsed,
        'crm-menu-item--leaf-active': isActive,
        'crm-menu-item--group-active': isGroupActive,
      },
    ]"
  >
    <div class="crm-menu-item__row">
      <Component
        :is="itemTarget ? 'RouterLink' : 'button'"
        v-bind="itemTarget ? { to: itemTarget } : { type: 'button' }"
        class="crm-menu-item__link"
        :class="{ 'crm-menu-item__link--active': isActive || isGroupActive }"
        @click="handlePrimaryClick"
      >
        <span class="crm-menu-item__icon">
          <VIcon
            :icon="item.icon || 'tabler-circle'"
            size="20"
          />
        </span>

        <Transition name="crm-menu-fade">
          <span
            v-if="!collapsed"
            class="crm-menu-item__label"
          >
            {{ item.title }}
          </span>
        </Transition>
      </Component>

      <button
        v-if="hasChildren && !collapsed"
        type="button"
        class="crm-menu-item__toggle"
        :aria-label="`Toggle ${item.title}`"
        @click="handleGroupToggle"
      >
        <VIcon
          icon="tabler-chevron-right"
          size="16"
          :class="{ 'crm-menu-item__toggle-icon--open': isOpen }"
        />
      </button>
    </div>

    <VExpandTransition>
      <ul
        v-if="hasChildren && !collapsed"
        v-show="isOpen"
        class="crm-menu-item__children"
      >
        <MenuItem
          v-for="child in item.children"
          :key="child.id"
          :item="child"
          :collapsed="false"
          :mobile="mobile"
          :depth="depth + 1"
          @navigate="$emit('navigate')"
          @request-expand="$emit('request-expand')"
        />
      </ul>
    </VExpandTransition>
  </li>
</template>

<style lang="scss" scoped>
.crm-menu-item {
  list-style: none;

  &__row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  &__link {
    position: relative;
    display: flex;
    flex: 1;
    align-items: center;
    gap: 0.875rem;
    border-radius: 1.125rem;
    color: rgb(var(--v-theme-on-surface));
    min-block-size: 3rem;
    padding-block: 0.75rem;
    padding-inline: 0.875rem;
    text-decoration: none;
    transition: background-color 0.24s ease, box-shadow 0.24s ease, color 0.24s ease, transform 0.24s ease;

    &:hover {
      background: rgba(124, 92, 255, 0.08);
      color: rgb(var(--v-theme-primary));
      transform: translateX(2px);
    }

    &--active {
      background: linear-gradient(135deg, rgba(110, 73, 255, 0.98), rgba(153, 111, 255, 0.92));
      box-shadow: 0 18px 36px rgba(103, 77, 240, 0.22);
      color: white;
    }
  }

  &__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.95rem;
    background: rgba(124, 92, 255, 0.1);
    block-size: 2.25rem;
    flex-shrink: 0;
    inline-size: 2.25rem;
  }

  &__label {
    overflow: hidden;
    flex: 1;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.35;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 999px;
    background: rgba(124, 92, 255, 0.1);
    block-size: 2rem;
    color: rgb(var(--v-theme-primary));
    cursor: pointer;
    inline-size: 2rem;
    transition: transform 0.24s ease, background-color 0.24s ease;

    &:hover {
      background: rgba(124, 92, 255, 0.18);
    }
  }

  &__toggle-icon--open {
    transform: rotate(90deg);
  }

  &__children {
    display: grid;
    gap: 0.35rem;
    margin: 0.35rem 0 0;
    padding: 0 0 0 0.65rem;
  }

  &--depth-1 &__link {
    min-block-size: 2.8rem;
    padding-inline-start: 1rem;
  }

  &--depth-2 &__link {
    min-block-size: 2.7rem;
    padding-inline-start: 1.125rem;
  }

  &--depth-1 &__icon,
  &--depth-2 &__icon,
  &--depth-3 &__icon {
    block-size: 2rem;
    inline-size: 2rem;
  }

  &--collapsed &__link {
    justify-content: center;
    padding-inline: 0.5rem;
  }
}

.crm-menu-fade-enter-active,
.crm-menu-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.crm-menu-fade-enter-from,
.crm-menu-fade-leave-to {
  opacity: 0;
  transform: translateX(-6px);
}
</style>
