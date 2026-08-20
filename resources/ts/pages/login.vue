<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()

const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true,
)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)
const isLoading = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAbility()

const errorMessage = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
const refVForm = ref<VForm>()

const credentials = ref({
  email: 'admin@demo.com',
  password: 'admin123456',
})

const rememberMe = ref(true)

const login = async () => {
  isLoading.value = true
  errorMessage.value = null
  errors.value = {}

  try {
    const res = await $api('/auth/login', {
      method: 'POST',
      body: {
        email: credentials.value.email,
        password: credentials.value.password,
      },
      onResponseError({ response }) {
        if (response._data?.message)
          errorMessage.value = response._data.message
        if (response._data?.errors)
          errors.value = response._data.errors
      },
    })

    if (res?.success && res.data) {
      const { user, token } = res.data

      const userAbilityRules = [
        { action: 'manage', subject: 'all' },
      ]

      useCookie('userAbilityRules').value = userAbilityRules
      ability.update(userAbilityRules)

      useCookie('userData').value = {
        id: user.id,
        fullName: user.name,
        username: user.name,
        email: user.email,
        role: 'admin',
        avatar: undefined,
        abilityRules: userAbilityRules,
      }

      useCookie('accessToken').value = token

      await nextTick(() => {
        router.replace(route.query.to ? String(route.query.to) : '/')
      })
    }
  }
  catch (err: any) {
    console.error(err)
    if (!errorMessage.value)
      errorMessage.value = err?.message || 'حدث خطأ أثناء محاولة تسجيل الدخول'
  }
  finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      login()
  })
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <img
        v-if="settingsStore.appLogoUrl"
        :src="settingsStore.appLogoUrl"
        alt="logo"
        height="32"
        width="32"
        style="object-fit: contain;"
      >
      <VNodeRenderer v-else :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ settingsStore.appName }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100 flex-column"
          style="padding-inline: 6.25rem;"
        >
          <!-- Custom brand layout in the blank space -->
          <div class="d-flex flex-column align-center text-center">
            <!-- App Logo -->
            <div
              v-if="settingsStore.appLogoUrl"
              class="mb-6 d-flex align-center justify-center"
              style="max-width: 280px; max-height: 180px;"
            >
              <img
                :src="settingsStore.appLogoUrl"
                alt="Logo"
                style="max-width: 100%; max-height: 100%; object-fit: contain;"
              >
            </div>
            <VAvatar
              v-else
              color="primary"
              variant="tonal"
              size="200"
              class="mb-6 elevation-4"
            >
              <VIcon icon="tabler-smart-home" size="100" />
            </VAvatar>

            <!-- App Name -->
            <h1 class="text-h1 font-weight-bold text-primary mb-3">
              {{ settingsStore.appName }}
            </h1>

            <!-- Control Panel Title -->
            <VChip
              color="primary"
              variant="tonal"
              size="large"
              class="px-8 py-6 font-weight-bold text-h4"
            >
              لوحة التحكم والإدارة
            </VChip>

            <p class="text-subtitle-1 text-muted text-center max-w-lg mt-6">
              مرحباً بك في نظام الإدارة والتحكم للمنصة التدريبية التفاعلية للأطفال
            </p>
          </div>
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            تسجيل الدخول 👋
          </h4>
          <p class="mb-0 text-muted">
            أدخل بيانات حسابك للوصول إلى لوحة التحكم أو التطبيق
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
            closable
          >
            {{ errorMessage }}
          </VAlert>

          <VAlert
            color="primary"
            variant="tonal"
            class="mb-4"
          >
            <p class="text-sm mb-0">
              🔹 <strong>حساب مدير النظام:</strong> admin@demo.com / كلمة المرور: <code>admin123456</code>
            </p>
          </VAlert>

          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  label="البريد الإلكتروني"
                  placeholder="admin@demo.com"
                  type="email"
                  autofocus
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  label="كلمة المرور"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="current-password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap justify-space-between my-4">
                  <VCheckbox
                    v-model="rememberMe"
                    label="تذكرني على هذا الجهاز"
                  />
                </div>

                <VBtn
                  block
                  type="submit"
                  size="large"
                  :loading="isLoading"
                >
                  تسجيل الدخول
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
