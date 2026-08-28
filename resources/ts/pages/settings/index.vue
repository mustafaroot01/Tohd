<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()

const activeTab = ref('general')
const isLoading = ref(true)
const isSaving = ref(false)
const { notification, notifySuccess, notifyError } = useNotification()

// Settings Form State
const settingsForm = ref({
  app_name: '',
  is_maintenance: false,
  otp_enabled: true,
  otp_base_url: '',
  profile_completion_enabled: false,
  otp_api_key: '', // always submitted blank; a new value here replaces the stored key
})

const isOtpApiKeyConfigured = ref(false)
const otpApiKeyPreview = ref<string | null>(null)
const isOtpApiKeyVisible = ref(false)

// Test SMS dialog state
const isTestSmsDialogOpen = ref(false)
const testSmsPhone = ref('')
const isSendingTestSms = ref(false)
const testSmsResult = ref<{ text: string; color: string } | null>(null)

const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)

const fetchSettingsData = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/settings')
    if (res?.success && res?.data) {
      settingsForm.value = {
        app_name: res.data.app_name,
        is_maintenance: !!res.data.is_maintenance,
        otp_enabled: !!res.data.otp_enabled,
        otp_base_url: res.data.otp_base_url || '',
        profile_completion_enabled: !!res.data.profile_completion_enabled,
        otp_api_key: '',
      }
      isOtpApiKeyConfigured.value = !!res.data.otp_api_key_configured
      otpApiKeyPreview.value = res.data.otp_api_key_preview || null
      logoPreview.value = res.data.app_logo_url || null
    }
  } catch (err: any) {
    notifyError(err, 'فشل تحميل إعدادات النظام')
  } finally {
    isLoading.value = false
  }
}

const onLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    logoFile.value = target.files[0]
    logoPreview.value = URL.createObjectURL(logoFile.value)
  }
}

const saveSettings = async () => {
  isSaving.value = true
  notification.value = null
  try {
    // We must send file upload as multipart/form-data via POST
    const formData = new FormData()
    formData.append('app_name', settingsForm.value.app_name)
    formData.append('is_maintenance', settingsForm.value.is_maintenance ? '1' : '0')
    formData.append('otp_enabled', settingsForm.value.otp_enabled ? '1' : '0')
    if (settingsForm.value.otp_base_url)
      formData.append('otp_base_url', settingsForm.value.otp_base_url)
    formData.append('profile_completion_enabled', settingsForm.value.profile_completion_enabled ? '1' : '0')
    if (settingsForm.value.otp_api_key)
      formData.append('otp_api_key', settingsForm.value.otp_api_key)

    if (logoFile.value) {
      formData.append('app_logo_file', logoFile.value)
    }

    const res = await $api('/admin/settings', {
      method: 'POST',
      body: formData,
    })

    if (res?.success) {
      notifySuccess('تم حفظ إعدادات النظام وتخصيص الهوية بنجاح')
      logoFile.value = null
      settingsForm.value.otp_api_key = ''
      isOtpApiKeyConfigured.value = !!res.data.otp_api_key_configured
      otpApiKeyPreview.value = res.data.otp_api_key_preview || null

      // Update the global layout store branding instantly
      await settingsStore.fetchSettings()
      if (res.data.app_logo_url) {
        logoPreview.value = res.data.app_logo_url
      }
    }
  } catch (err: any) {
    notifyError(err, 'حدث خطأ أثناء حفظ الإعدادات')
  } finally {
    isSaving.value = false
  }
}

const openTestSmsDialog = () => {
  testSmsPhone.value = ''
  testSmsResult.value = null
  isTestSmsDialogOpen.value = true
}

const sendTestSms = async () => {
  isSendingTestSms.value = true
  testSmsResult.value = null
  try {
    const res = await $api('/admin/settings/test-sms', {
      method: 'POST',
      body: { phone: testSmsPhone.value },
    })
    if (res?.success)
      testSmsResult.value = { text: res.message || 'تم إرسال رسالة الاختبار بنجاح', color: 'success' }
  } catch (err: any) {
    testSmsResult.value = { text: err?.data?.message || 'فشل إرسال رسالة الاختبار', color: 'error' }
  } finally {
    isSendingTestSms.value = false
  }
}

onMounted(() => {
  fetchSettingsData()
})
</script>

<template>
  <div>
    <!-- Title -->
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold text-primary mb-1">إعدادات النظام والهوية ⚙️</h2>
      <p class="text-body-2 text-muted mb-0">
        تخصيص الهوية البصرية للمنصة (الاسم والشعار)، وإدارة وضع صيانة التطبيق وإعدادات بوابة التحقق OTP.
      </p>
    </div>

    <!-- Alert Notification -->
    <AppNotification v-model="notification" />

    <div v-if="isLoading" class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <VRow v-else>
      <VCol cols="12" md="3">
        <!-- Tabs List -->
        <VTabs
          v-model="activeTab"
          direction="vertical"
          color="primary"
          class="border rounded bg-surface"
        >
          <VTab value="general" class="justify-start py-3">
            <VIcon icon="tabler-palette" class="me-3" />
            الهوية والإعدادات العامة
          </VTab>
          <VTab value="otp" class="justify-start py-3">
            <VIcon icon="tabler-shield-lock" class="me-3" />
            إعدادات التحقق OTP
          </VTab>
        </VTabs>
      </VCol>

      <VCol cols="12" md="9">
        <VWindow v-model="activeTab">
          <!-- General Branding Tab -->
          <VWindowItem value="general">
            <VCard class="pa-6">
              <h3 class="text-h5 font-weight-bold mb-6 text-primary d-flex align-center gap-2">
                <VIcon icon="tabler-palette" />
                تخصيص هوية النظام البصرية
              </h3>

              <VRow>
                <!-- Application Name -->
                <VCol cols="12" sm="8">
                  <VTextField
                    v-model="settingsForm.app_name"
                    label="اسم النظام / التطبيق"
                    placeholder="مثال: رحلة فارس"
                    hint="يظهر هذا الاسم في القائمة الجانبية وتفاصيل التطبيق وتبويب المتصفح"
                    persistent-hint
                    class="mb-4"
                  />
                </VCol>

                <!-- Application Logo Upload -->
                <VCol cols="12" sm="4" class="d-flex flex-column align-center justify-center">
                  <VAvatar
                    size="100"
                    variant="outlined"
                    color="primary"
                    class="mb-3 rounded border overflow-hidden"
                  >
                    <img
                      v-if="logoPreview"
                      :src="logoPreview"
                      alt="شعار التطبيق"
                      style="width: 100%; height: 100%; object-fit: contain;"
                    />
                    <VIcon v-else icon="tabler-photo-off" size="42" color="muted" />
                  </VAvatar>
                  
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="tabler-upload"
                    class="position-relative overflow-hidden"
                  >
                    رفع شعار جديد
                    <input
                      type="file"
                      accept="image/*"
                      class="position-absolute opacity-0 cursor-pointer"
                      style="inset: 0;"
                      @change="onLogoChange"
                    />
                  </VBtn>
                </VCol>

                <VCol cols="12">
                  <VDivider class="my-4" />
                </VCol>

                <!-- Second registration step -->
                <VCol cols="12">
                  <div class="d-flex align-center justify-space-between flex-wrap gap-4 bg-background pa-4 rounded mb-4">
                    <div>
                      <h4 class="font-weight-bold text-subtitle-1 mb-1 text-primary d-flex align-center gap-2">
                        <VIcon icon="tabler-clipboard-list" />
                        إكمال بيانات المشترك
                      </h4>
                      <p class="text-caption text-muted mb-0">
                        عند التفعيل يُطلب من المشترك إكمال بياناته (المحافظة، الجنس، العمر، التسلسل في العائلة، نوع الولادة) عند فتح التطبيق، ولا يستطيع المتابعة قبل إكمالها.
                        عند الإيقاف تختفي هذه الخطوة نهائياً من التطبيق ولا يظهر لها أي أثر.
                      </p>
                    </div>
                    <VSwitch
                      v-model="settingsForm.profile_completion_enabled"
                      color="primary"
                      inset
                    />
                  </div>
                </VCol>

                <!-- Maintenance Mode -->
                <VCol cols="12">
                  <div class="d-flex align-center justify-space-between flex-wrap gap-4 bg-background pa-4 rounded">
                    <div>
                      <h4 class="font-weight-bold text-subtitle-1 mb-1 text-warning d-flex align-center gap-2">
                        <VIcon icon="tabler-alert-triangle" />
                        وضع صيانة النظام (Maintenance Mode)
                      </h4>
                      <p class="text-caption text-muted mb-0">
                        عند التفعيل، سيتم إيقاف التطبيق وتجربة المباشر (Live App) للعملاء وستظهر لهم شاشة صيانة، بينما ستبقى لوحة التحكم للآدمن فعّالة لإمكانية الإدارة.
                      </p>
                    </div>
                    <VSwitch
                      v-model="settingsForm.is_maintenance"
                      color="warning"
                      inset
                    />
                  </div>
                </VCol>
              </VRow>
            </VCard>
          </VWindowItem>

          <!-- OTP Configurations Tab -->
          <VWindowItem value="otp">
            <VCard class="pa-6">
              <h3 class="text-h5 font-weight-bold mb-6 text-primary d-flex align-center gap-2">
                <VIcon icon="tabler-shield-lock" />
                تكوين بوابة التحقق وإرسال الأكواد OTP
              </h3>

              <VRow>
                <!-- OTP Enabled Toggle -->
                <VCol cols="12">
                  <div class="d-flex align-center justify-space-between bg-background pa-4 rounded mb-4">
                    <div>
                      <h4 class="font-weight-bold text-subtitle-1 mb-1">تفعيل نظام التحقق بالـ OTP</h4>
                      <p class="text-caption text-muted mb-0">
                        إرسال رمز واتساب لإثبات رقم الهاتف عند التسجيل وعند استعادة كلمة المرور. الدخول لا يرسل أي رمز. عند الإيقاف يُنشأ الحساب مباشرة (بيئة تجريبية).
                      </p>
                    </div>
                    <VSwitch
                      v-model="settingsForm.otp_enabled"
                      color="primary"
                      inset
                    />
                  </div>
                </VCol>

                <!-- Configuration Fields (Visible only if enabled) -->
                <template v-if="settingsForm.otp_enabled">
                  <!-- Arqam base URL -->
                  <VCol cols="12" sm="6">
                    <VTextField
                      v-model="settingsForm.otp_base_url"
                      label="رابط خدمة أرقم (OTP)"
                      placeholder="https://otp.arqam.tech/api"
                      hint="من لوحة أرقم — لا تحذف /api من آخر الرابط. الرمز يُرسل عبر واتساب"
                      persistent-hint
                      dir="ltr"
                      class="mb-4"
                    />
                  </VCol>

                  <!-- Arqam API key -->
                  <VCol cols="12" sm="6">
                    <VTextField
                      v-model="settingsForm.otp_api_key"
                      :type="isOtpApiKeyVisible ? 'text' : 'password'"
                      label="مفتاح أرقم (OTP)"
                      :placeholder="isOtpApiKeyConfigured ? `مُهيّأ حالياً (${otpApiKeyPreview}) — اتركه فارغاً للإبقاء عليه` : 'يبدأ بـ otplive_ من لوحة أرقم'"
                      persistent-placeholder
                      hint="لن يظهر المفتاح الحالي لأسباب أمنية، أدخل قيمة جديدة فقط إن رغبت باستبداله"
                      persistent-hint
                      dir="ltr"
                      :append-inner-icon="isOtpApiKeyVisible ? 'tabler-eye-off' : 'tabler-eye'"
                      @click:append-inner="isOtpApiKeyVisible = !isOtpApiKeyVisible"
                    />
                  </VCol>

                  <VCol cols="12" class="d-flex align-center flex-wrap gap-4">
                    <VAlert :type="isOtpApiKeyConfigured ? 'success' : 'warning'" variant="tonal" density="compact" class="flex-grow-1">
                      {{ isOtpApiKeyConfigured ? 'يوجد مفتاح أرقم مُهيّأ وجاهز لإرسال رموز التحقق عبر واتساب.' : 'لا يوجد مفتاح أرقم مُهيّأ بعد — لن يتم إرسال أي رموز تحقق حتى تُدخل الرابط والمفتاح.' }}
                    </VAlert>
                    <VBtn
                      v-if="isOtpApiKeyConfigured"
                      variant="tonal"
                      color="primary"
                      prepend-icon="tabler-send"
                      @click="openTestSmsDialog"
                    >
                      اختبار الإرسال
                    </VBtn>
                  </VCol>
                </template>

                <template v-else>
                  <VCol cols="12">
                    <VAlert type="warning" variant="tonal" density="compact">
                      عند تعطيل التحقق، سيتم تفعيل حسابات المشتركين الجدد فوراً دون إرسال أو طلب رمز تحقق. استخدم هذا فقط في بيئات الاختبار.
                    </VAlert>
                  </VCol>
                </template>
              </VRow>
            </VCard>
          </VWindowItem>
        </VWindow>

        <!-- Save Button -->
        <div class="d-flex justify-end mt-6">
          <VBtn
            color="primary"
            size="large"
            prepend-icon="tabler-device-floppy"
            :loading="isSaving"
            @click="saveSettings"
          >
            حفظ إعدادات النظام
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Test SMS Dialog -->
    <VDialog v-model="isTestSmsDialogOpen" max-width="480">
      <VCard class="pa-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="tabler-send" />
          اختبار إرسال رمز عبر أرقم (واتساب)
        </VCardTitle>
        <VCardText>
          <p class="text-body-2 text-muted mb-4">
            أدخل رقم هاتف عراقي لإرسال رمز تحقق تجريبي إليه باستخدام المفتاح المحفوظ حالياً، للتأكد من صحة الإعدادات.
          </p>
          <VTextField
            v-model="testSmsPhone"
            label="رقم الهاتف"
            placeholder="07701234567"
            dir="ltr"
            :disabled="isSendingTestSms"
          />
          <VAlert v-if="testSmsResult" :color="testSmsResult.color" variant="tonal" density="compact" class="mt-4">
            {{ testSmsResult.text }}
          </VAlert>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="isTestSmsDialogOpen = false">إغلاق</VBtn>
          <VBtn
            color="primary"
            :loading="isSendingTestSms"
            :disabled="!testSmsPhone"
            @click="sendTestSms"
          >
            إرسال
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.hover-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: all 0.2s ease-in-out;
}
</style>
