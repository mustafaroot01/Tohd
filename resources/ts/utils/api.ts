import { ofetch } from 'ofetch'
import { router } from '@/plugins/1.router'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value

    const headers = new Headers(options.headers)
    headers.set('Accept', 'application/json')

    if (accessToken)
      headers.set('Authorization', `Bearer ${accessToken}`)

    options.headers = headers
  },
  async onResponseError({ response }) {
    if (response.status === 401 || response.status === 403) {
      useCookie('accessToken').value = null
      useCookie('userData').value = null
      useCookie('userAbilityRules').value = null

      if (router.currentRoute.value.name !== 'login')
        await router.push({ name: 'login', query: { to: router.currentRoute.value.fullPath !== '/' ? router.currentRoute.value.path : undefined } })
    }
  },
})
