import { ofetch } from 'ofetch'

const resetAuthSession = () => {
  useCookie('accessToken').value = null
  useCookie('userData').value = null
  useCookie('userAbilityRules').value = null

  if (typeof window !== 'undefined' && window.location.pathname !== '/login')
    window.location.assign('/login')
}

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    options.headers = new Headers(options.headers)
    options.headers.set('Accept', 'application/json')

    const accessToken = useCookie('accessToken').value
    if (accessToken)
      options.headers.append('Authorization', `Bearer ${accessToken}`)
  },
  onResponseError({ response }) {
    if (response.status === 401)
      resetAuthSession()
  },
})
