<script lang="ts" setup>
import { ref, onMounted } from 'vue'

const router = useRouter()

// Form data
const form = ref({
  name: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: '',
})

const isLoading = ref(false)
const isSaving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const lastLoginAt = ref('')
const createdAt = ref('')

// Fetch admin profile
const fetchProfile = async () => {
  isLoading.value = true
  try {
    const token = useCookie('accessToken').value
    const res = await fetch('/api/v1/admin/profile', {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    })
    const json = await res.json()
    if (json.success) {
      form.value.name = json.data.name ?? ''
      form.value.email = json.data.email ?? ''
      lastLoginAt.value = json.data.last_login_at
        ? new Date(json.data.last_login_at).toLocaleString('ar-IQ')
        : 'غير متاح'
      createdAt.value = json.data.created_at
        ? new Date(json.data.created_at).toLocaleDateString('ar-IQ')
        : ''
    }
  } catch (e) {
    errorMsg.value = 'تعذّر تحميل بيانات الحساب'
  } finally {
    isLoading.value = false
  }
}

// Save profile
const saveProfile = async () => {
  successMsg.value = ''
  errorMsg.value = ''
  isSaving.value = true

  try {
    const token = useCookie('accessToken').value
    const payload: any = {
      name: form.value.name,
      email: form.value.email,
    }

    if (form.value.password) {
      payload.current_password = form.value.current_password
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }

    const res = await fetch('/api/v1/admin/profile', {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const json = await res.json()

    if (json.success) {
      successMsg.value = json.message || 'تم الحفظ بنجاح'
      // Update cookie if name changed
      const userData = useCookie<any>('userData')
      if (userData.value) {
        userData.value = { ...userData.value, fullName: form.value.name }
      }
      // Clear password fields
      form.value.current_password = ''
      form.value.password = ''
      form.value.password_confirmation = ''
    } else {
      const errors = json.errors
        ? Object.values(json.errors).flat().join(' | ')
        : json.message || 'حدث خطأ ما'
      errorMsg.value = errors
    }
  } catch (e) {
    errorMsg.value = 'تعذّر الاتصال بالخادم'
  } finally {
    isSaving.value = false
  }
}

onMounted(fetchProfile)

definePage({
  meta: {
    navActiveLink: 'pages-account-settings-tab',
  },
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading" class="d-flex justify-center align-center py-16">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <template v-else>
      <!-- Success / Error Alerts -->
      <VAlert
        v-if="successMsg"
        type="success"
        variant="tonal"
        closable
        class="mb-6"
        @click:close="successMsg = ''"
      >
        {{ successMsg }}
      </VAlert>

      <VAlert
        v-if="errorMsg"
        type="error"
        variant="tonal"
        closable
        class="mb-6"
        @click:close="errorMsg = ''"
      >
        {{ errorMsg }}
      </VAlert>

      <VRow>
        <!-- Left: Account Info Card -->
        <VCol cols="12" md="4">
          <VCard class="pa-6 text-center h-100">
            <!-- Avatar -->
            <VAvatar
              size="96"
              color="primary"
              variant="tonal"
              class="mb-4"
            >
              <VIcon icon="tabler-user-shield" size="52" />
            </VAvatar>

            <h5 class="text-h5 font-weight-bold mb-1">{{ form.name || '—' }}</h5>
            <p class="text-body-2 text-disabled mb-4">{{ form.email }}</p>

            <VChip color="success" size="small" class="mb-6">
              <VIcon icon="tabler-shield-check" size="14" class="me-1" />
              مدير النظام
            </VChip>

            <VDivider class="mb-4" />

            <div class="text-start">
              <div class="d-flex align-center gap-2 mb-3">
                <VIcon icon="tabler-clock" size="18" color="primary" />
                <div>
                  <p class="text-caption text-disabled mb-0">آخر دخول</p>
                  <p class="text-body-2 font-weight-medium mb-0">{{ lastLoginAt }}</p>
                </div>
              </div>
              <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-calendar" size="18" color="primary" />
                <div>
                  <p class="text-caption text-disabled mb-0">تاريخ الإنشاء</p>
                  <p class="text-body-2 font-weight-medium mb-0">{{ createdAt }}</p>
                </div>
              </div>
            </div>
          </VCard>
        </VCol>

        <!-- Right: Edit Form -->
        <VCol cols="12" md="8">
          <VCard>
            <VCardItem>
              <VCardTitle class="d-flex align-center gap-2">
                <VIcon icon="tabler-edit" color="primary" />
                تعديل بيانات الحساب
              </VCardTitle>
              <VCardSubtitle>يمكنك تعديل الاسم والبريد الإلكتروني وكلمة المرور</VCardSubtitle>
            </VCardItem>

            <VDivider />

            <VCardText class="pa-6">
              <VForm @submit.prevent="saveProfile">
                <!-- Section: Basic Info -->
                <p class="text-overline text-primary font-weight-bold mb-4">المعلومات الأساسية</p>

                <VRow>
                  <VCol cols="12">
                    <AppTextField
                      v-model="form.name"
                      label="الاسم الكامل"
                      placeholder="اسم المدير"
                      prepend-inner-icon="tabler-user"
                      :rules="[(v: string) => !!v || 'الاسم مطلوب']"
                    />
                  </VCol>

                  <VCol cols="12">
                    <AppTextField
                      v-model="form.email"
                      label="البريد الإلكتروني"
                      placeholder="admin@example.com"
                      type="email"
                      prepend-inner-icon="tabler-mail"
                      :rules="[(v: string) => !!v || 'البريد مطلوب']"
                    />
                  </VCol>
                </VRow>

                <VDivider class="my-6" />

                <!-- Section: Password -->
                <p class="text-overline text-primary font-weight-bold mb-4">تغيير كلمة المرور <span class="text-caption text-disabled font-weight-regular">(اختياري)</span></p>

                <VRow>
                  <VCol cols="12">
                    <AppTextField
                      v-model="form.current_password"
                      label="كلمة المرور الحالية"
                      placeholder="أدخل كلمة المرور الحالية"
                      prepend-inner-icon="tabler-lock"
                      :type="showCurrentPassword ? 'text' : 'password'"
                      :append-inner-icon="showCurrentPassword ? 'tabler-eye-off' : 'tabler-eye'"
                      @click:append-inner="showCurrentPassword = !showCurrentPassword"
                    />
                  </VCol>

                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="form.password"
                      label="كلمة المرور الجديدة"
                      placeholder="8 أحرف على الأقل"
                      prepend-inner-icon="tabler-lock-plus"
                      :type="showNewPassword ? 'text' : 'password'"
                      :append-inner-icon="showNewPassword ? 'tabler-eye-off' : 'tabler-eye'"
                      @click:append-inner="showNewPassword = !showNewPassword"
                    />
                  </VCol>

                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="form.password_confirmation"
                      label="تأكيد كلمة المرور"
                      placeholder="أعد كتابة كلمة المرور الجديدة"
                      prepend-inner-icon="tabler-lock-check"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      :append-inner-icon="showConfirmPassword ? 'tabler-eye-off' : 'tabler-eye'"
                      @click:append-inner="showConfirmPassword = !showConfirmPassword"
                    />
                  </VCol>
                </VRow>

                <VDivider class="my-6" />

                <!-- Actions -->
                <div class="d-flex gap-3">
                  <VBtn
                    type="submit"
                    color="primary"
                    :loading="isSaving"
                    prepend-icon="tabler-device-floppy"
                  >
                    حفظ التغييرات
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    prepend-icon="tabler-refresh"
                    @click="fetchProfile"
                  >
                    إعادة تحميل
                  </VBtn>
                </div>
              </VForm>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </template>
  </div>
</template>
