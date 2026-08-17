import { ofetch } from 'ofetch'

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
})
