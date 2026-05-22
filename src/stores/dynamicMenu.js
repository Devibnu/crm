const flattenRoutes = items => {
  return items.flatMap(item => {
    const currentRoute = item.route ? [item.route] : [];

    return [...currentRoute, ...flattenRoutes(item.children || [])];
  });
};

export const useDynamicMenuStore = defineStore('dynamicMenu', () => {
  const items = ref([]);
  const bottomNavigation = ref([]);
  const isLoading = ref(false);
  const isLoaded = ref(false);
  const errorMessage = ref('');
  const meta = ref({});
  const userData = useCookie('userData');

  const role = computed(() => userData.value?.role ?? null);
  const primaryItems = computed(() => items.value);
  const accessibleRoutes = computed(() => new Set(flattenRoutes(items.value)));

  const fetchMenus = async ({ force = false } = {}) => {
    if (isLoading.value || (isLoaded.value && !force))
      return;

    isLoading.value = true;
    errorMessage.value = '';

    const query = role.value ? { role: role.value } : {};
    const requestOptions = role.value ? { headers: { 'X-User-Role': role.value } } : {};
    const { data, error } = await useApi(createUrl('/crm/menus', { query }), requestOptions);

    if (error.value) {
      errorMessage.value = 'Unable to load CRM navigation.';
      items.value = [];
      bottomNavigation.value = [];
      meta.value = {};
    }
    else {
      const payload = data.value?.data ?? {};

      items.value = payload.items ?? [];
      bottomNavigation.value = payload.bottom_navigation ?? [];
      meta.value = payload.meta ?? {};
      isLoaded.value = true;
    }

    isLoading.value = false;
  };

  const reset = () => {
    items.value = [];
    bottomNavigation.value = [];
    meta.value = {};
    errorMessage.value = '';
    isLoaded.value = false;
  };

  return {
    items,
    bottomNavigation,
    meta,
    role,
    isLoading,
    isLoaded,
    errorMessage,
    primaryItems,
    accessibleRoutes,
    fetchMenus,
    reset,
  };
});
