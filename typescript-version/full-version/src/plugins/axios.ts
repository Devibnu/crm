import axios from 'axios'
import type { App } from 'vue'

interface PersistSanctumSessionPayload {
  accessToken: string
  userData: unknown
  userAbilityRules?: unknown
}

export const getSanctumAccessToken = () => useCookie<string | null>('accessToken').value

export const persistSanctumSession = ({ accessToken, userData, userAbilityRules = null }: PersistSanctumSessionPayload) => {
  useCookie('accessToken').value = accessToken
  useCookie('userData').value = userData
  useCookie('userAbilityRules').value = userAbilityRules
}

export const resetSanctumSession = (redirectToLogin = true) => {
  useCookie('accessToken').value = null
  useCookie('userData').value = null
  useCookie('userAbilityRules').value = null

  if (redirectToLogin && typeof window !== 'undefined' && window.location.pathname !== '/login')
    window.location.assign('/login')
}

export const resolveApiErrorMessage = (error: unknown, fallback: string) => {
  if (axios.isAxiosError(error))
    return error.response?.data?.message || fallback

  return fallback
}

export const axiosApi = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
})

axiosApi.interceptors.request.use(config => {
  const accessToken = getSanctumAccessToken()

  if (accessToken)
    config.headers.Authorization = `Bearer ${accessToken}`

  return config
})

axiosApi.interceptors.response.use(
  response => response,
  error => {
    if (axios.isAxiosError(error) && error.response?.status === 401)
      resetSanctumSession()

    return Promise.reject(error)
  },
)

export default function (_app: App) {
  // Axios is configured as a shared singleton via module import.
}