/**
 * Resolve a stored asset URL into one the browser can actually fetch.
 *
 * The backend builds these from APP_URL. When APP_URL points at a different
 * local port than the one serving the app, the browser gets
 * ERR_CONNECTION_REFUSED — so local origins are re-homed onto the current one.
 * A real CDN or S3 host is always left untouched.
 */
const isLocalHost = (hostname: string) =>
  hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '[::1]' || hostname === '::1'

export const resolveAssetUrl = (url?: string | null): string => {
  if (!url)
    return ''

  if (url.startsWith('http://') || url.startsWith('https://')) {
    try {
      const parsed = new URL(url)

      if (parsed.origin !== window.location.origin && isLocalHost(parsed.hostname))
        return `${window.location.origin}${parsed.pathname}${parsed.search}`
    }
    catch {
      // not parseable — hand it back untouched
    }

    return url
  }

  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''

  if (apiBaseUrl.startsWith('http://') || apiBaseUrl.startsWith('https://')) {
    try {
      return `${new URL(apiBaseUrl).origin}${url.startsWith('/') ? '' : '/'}${url}`
    }
    catch {
      // fall through to same-origin
    }
  }

  return `${window.location.origin}${url.startsWith('/') ? '' : '/'}${url}`
}
