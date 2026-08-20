import { defineStore } from 'pinia'
import { ref } from 'vue'
import { layoutConfig } from '@themeConfig'

export const useSettingsStore = defineStore('settings', () => {
  const appName = ref('رحلة فارس')
  const appLogoUrl = ref<string | null>(null)
  const isMaintenance = ref(false)
  const otpEnabled = ref(true)
  const isLoadingSettings = ref(false)

  const fetchSettings = async () => {
    isLoadingSettings.value = true
    try {
      // Use standard fetch api to request settings/public config (unauthenticated)
      const res = await $api('/settings/public')
      if (res?.success && res?.data) {
        appName.value = res.data.app_name || 'رحلة فارس'
        appLogoUrl.value = res.data.app_logo_url || null
        isMaintenance.value = !!res.data.is_maintenance
        otpEnabled.value = !!res.data.otp_enabled

        // Dynamically update Vuetify/Template configurations globally
        layoutConfig.app.title = appName.value as typeof layoutConfig.app.title
        
        // Sync browser document title tab
        if (typeof document !== 'undefined') {
          document.title = `${appName.value} - لوحة التحكم`
        }
      }
    } catch (err) {
      console.error('Failed to load system settings from server:', err)
    } finally {
      isLoadingSettings.value = false
    }
  }

  return {
    appName,
    appLogoUrl,
    isMaintenance,
    otpEnabled,
    isLoadingSettings,
    fetchSettings,
  }
})
