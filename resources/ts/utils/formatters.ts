/**
 * Display formatting shared by every admin screen.
 *
 * These were copy-pasted into a handful of pages; keep them here so a date or a
 * file size looks the same everywhere.
 */

const LOCALE = 'ar-SA'

const EM_DASH = '—'

/**
 * 2026/08/20 — or any Intl shape you ask for.
 *
 * This is the project's ONLY date formatter. The Vuexy template shipped a second
 * one hard-coded to 'en-US'; that copy was removed and its callers land here.
 */
export const formatDate = (
  value?: string | null,
  formatting?: Intl.DateTimeFormatOptions,
  fallback = EM_DASH,
) => {
  if (!value)
    return fallback

  const date = new Date(value)

  return formatting
    ? new Intl.DateTimeFormat(LOCALE, formatting).format(date)
    : date.toLocaleDateString(LOCALE)
}

/** 2026/08/20, 1:45 PM */
export const formatDateTime = (value?: string | null, fallback = EM_DASH) =>
  (value ? new Date(value).toLocaleString(LOCALE) : fallback)

/** 1,024 */
export const formatNumber = (value?: number | string | null, fallback = EM_DASH) =>
  value === null || value === undefined || value === '' ? fallback : Number(value).toLocaleString()

/** 1.5 MB */
export const formatBytes = (bytes?: number | null, decimals = 2) => {
  if (!Number(bytes))
    return '0 Bytes'

  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(Number(bytes)) / Math.log(k))

  return `${Number.parseFloat((Number(bytes) / k ** i).toFixed(dm))} ${sizes[i]}`
}
