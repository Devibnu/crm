import { canNavigate } from '@layouts/plugins/casl'
import { store } from '@/plugins/2.pinia'
import { useDynamicMenuStore } from '@/stores/dynamicMenu'

const protectedPrefixes = ['/dashboards', '/service', '/sales', '/marketing', '/customer-profile']

const normalizePath = path => String(path || '').replace(/\/+$/, '') || '/'

const isDynamicMenuProtectedPath = path => {
  const normalized = normalizePath(path)

  return protectedPrefixes.some(prefix => normalized === prefix || normalized.startsWith(`${prefix}/`))
}

export const setupGuards = router => {
  router.beforeEach(async to => {
    if (to.meta.public)
      return

    const isLoggedIn = !!(useCookie('userData').value && useCookie('accessToken').value)

    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/'
      else
        return undefined
    }

    if (!isLoggedIn && isDynamicMenuProtectedPath(to.path)) {
      return {
        name: 'login',
        query: {
          ...to.query,
          to: to.fullPath !== '/' ? to.path : undefined,
        },
      }
    }

    if (isLoggedIn && isDynamicMenuProtectedPath(to.path)) {
      const menuStore = useDynamicMenuStore(store)

      if (!menuStore.isLoaded)
        await menuStore.fetchMenus()

      const canAccessRoute = menuStore.accessibleRoutes.has(normalizePath(to.path))

      if (!canAccessRoute)
        return { name: 'not-authorized' }
    }

    if (!to.matched.some(route => route.meta.action || route.meta.subject))
      return undefined

    if (!canNavigate(to) && to.matched.length) {
      return isLoggedIn
        ? { name: 'not-authorized' }
        : {
            name: 'login',
            query: {
              ...to.query,
              to: to.fullPath !== '/' ? to.path : undefined,
            },
          }
    }
  })
}
