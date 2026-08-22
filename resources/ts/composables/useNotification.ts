/**
 * The page-level notice banner every admin screen was declaring by hand.
 *
 * Usage:
 *   const { notification, notifySuccess, notifyError } = useNotification()
 *   ...
 *   <AppNotification v-model="notification" />
 */

export type NotificationColor = 'success' | 'info' | 'warning' | 'error'

export interface PageNotification {
  text: string
  color: NotificationColor
}

export const useNotification = () => {
  const notification = ref<PageNotification | null>(null)

  const notify = (text: string, color: NotificationColor = 'success') => {
    notification.value = { text, color }
  }

  const notifySuccess = (text: string) => notify(text, 'success')
  const notifyInfo = (text: string) => notify(text, 'info')
  const notifyWarning = (text: string) => notify(text, 'warning')

  /** Pulls the API's Arabic message out of a failed $api call. */
  const notifyError = (error: unknown, fallback = 'حدث خطأ غير متوقع') => {
    const message = typeof error === 'string'
      ? error
      : (error as any)?.data?.message || (error as any)?.message || fallback

    notify(message, 'error')
  }

  const clearNotification = () => {
    notification.value = null
  }

  return { notification, notify, notifySuccess, notifyInfo, notifyWarning, notifyError, clearNotification }
}
