import { setupWorker } from 'msw/browser'

// Handlers
import { handlerAppBarSearch } from '@db/app-bar-search/index'
import { handlerAppsAcademy } from '@db/apps/academy/index'
import { handlerAppsCalendar } from '@db/apps/calendar/index'
import { handlerAppsChat } from '@db/apps/chat/index'
import { handlerAppsEcommerce } from '@db/apps/ecommerce/index'
import { handlerAppsEmail } from '@db/apps/email/index'
import { handlerAppsInvoice } from '@db/apps/invoice/index'
import { handlerAppsKanban } from '@db/apps/kanban/index'
import { handlerAppLogistics } from '@db/apps/logistics/index'
import { handlerAppsPermission } from '@db/apps/permission/index'
import { handlerAppsUsers } from '@db/apps/users/index'
import { handlerAuth } from '@db/auth/index'
import { handlerDashboard } from '@db/dashboard/index'
import { handlerPagesDatatable } from '@db/pages/datatable/index'
import { handlerPagesFaq } from '@db/pages/faq/index'
import { handlerPagesHelpCenter } from '@db/pages/help-center/index'
import { handlerPagesProfile } from '@db/pages/profile/index'

const worker = setupWorker(
  ...handlerAppsEcommerce,
  ...handlerAppsAcademy,
  ...handlerAppsInvoice,
  ...handlerAppsUsers,
  ...handlerAppsEmail,
  ...handlerAppsCalendar,
  ...handlerAppsChat,
  ...handlerAppsPermission,
  ...handlerPagesHelpCenter,
  ...handlerPagesProfile,
  ...handlerPagesFaq,
  ...handlerPagesDatatable,
  ...handlerAppBarSearch,
  ...handlerAppLogistics,
  ...handlerAuth,
  ...handlerAppsKanban,
  ...handlerDashboard,
)

const MOCK_SERVICE_WORKER_PATH = 'mockServiceWorker.js'
const MOCK_SW_RELOAD_KEY = 'kitcrm:mock-sw-cleanup-reload'

async function cleanupMockServiceWorker() {
  if (!('serviceWorker' in navigator))
    return

  const registrations = await navigator.serviceWorker.getRegistrations()
  const mockRegistrations = registrations.filter(registration => {
    return [registration.active, registration.installing, registration.waiting]
      .filter(Boolean)
      .some(workerRegistration => workerRegistration?.scriptURL.includes(MOCK_SERVICE_WORKER_PATH))
  })

  if (!mockRegistrations.length)
    return

  await Promise.all(mockRegistrations.map(registration => registration.unregister()))

  const isControlledByMockWorker = navigator.serviceWorker.controller?.scriptURL.includes(MOCK_SERVICE_WORKER_PATH)
  const hasReloadedAfterCleanup = sessionStorage.getItem(MOCK_SW_RELOAD_KEY) === 'true'

  if (isControlledByMockWorker && !hasReloadedAfterCleanup) {
    sessionStorage.setItem(MOCK_SW_RELOAD_KEY, 'true')
    window.location.reload()

    return
  }

  sessionStorage.removeItem(MOCK_SW_RELOAD_KEY)
}

export default function () {
  const shouldEnableMockApi = import.meta.env.DEV && import.meta.env.VITE_ENABLE_MSW === 'true'

  if (!shouldEnableMockApi) {
    const reason = import.meta.env.DEV
      ? 'mock API is disabled for this environment'
      : 'production build detected'

    console.log('[MSW] Disabled —', reason)
    cleanupMockServiceWorker().catch(error => {
      console.warn('[MSW] Failed to clean up mock service worker', error)
    })

    return
  }

  const workerUrl = `${import.meta.env.BASE_URL ?? '/'}mockServiceWorker.js`

  worker.start({
    serviceWorker: {
      url: workerUrl,
    },
    onUnhandledRequest: 'bypass',
  })
}
