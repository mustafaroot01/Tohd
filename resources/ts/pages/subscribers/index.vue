<script setup lang="ts">
const router = useRouter()

const subscribers = ref<any[]>([])
const isLoading = ref(true)

const isAddDialogVisible = ref(false)
const isSaving = ref(false)
const addErrors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  phone: '',
  password: '',
  address: '',
  status: 'ACTIVE',
})

const statusLabel = (status: string) => {
  if (status === 'ACTIVE')
    return 'فعال'
  if (status === 'SUSPENDED')
    return 'موقوف'

  return 'غير مفعل'
}

const statusColor = (status: string) => {
  if (status === 'ACTIVE')
    return 'success'
  if (status === 'SUSPENDED')
    return 'error'

  return 'warning'
}

const fetchSubscribers = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/subscribers')
    if (res?.success)
      subscribers.value = res.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const openSubscriber = (subscriber: any) => {
  router.push(`/subscribers/${subscriber.id}`)
}

const resetForm = () => {
  form.value = { name: '', phone: '', password: '', address: '', status: 'ACTIVE' }
  addErrors.value = {}
}

const submitAdd = async () => {
  isSaving.value = true
  addErrors.value = {}
  try {
    const res = await $api('/admin/subscribers', {
      method: 'POST',
      body: form.value,
      onResponseError({ response }) {
        if (response._data?.errors)
          addErrors.value = response._data.errors
      },
    })

    if (res?.success) {
      isAddDialogVisible.value = false
      resetForm()
      await fetchSubscribers()
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isSaving.value = false
  }
}

onMounted(() => {
  fetchSubscribers()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          المشتركون 👨‍👩‍👧
        </h2>
        <p class="text-muted mb-0">
          استعراض حسابات المشتركين (عملاء التطبيق)، حالة الاشتراك، وبيانات المناهج المفعلة
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        @click="isAddDialogVisible = true"
      >
        إضافة مشترك
      </VBtn>
    </div>

    <!-- Subscribers Table Card -->
    <VCard>
      <VCardText class="pa-0">
        <VTable hover class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-start">المشترك</th>
              <th class="text-start">رقم الهاتف</th>
              <th class="text-start">الحالة</th>
              <th class="text-start">تاريخ التسجيل</th>
              <th class="text-center">التفاصيل</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="subscriber in subscribers" :key="subscriber.id">
              <td>
                <div class="d-flex align-center gap-3">
                  <VAvatar color="primary" variant="tonal" size="38">
                    <VIcon icon="tabler-user" size="20" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold">{{ subscriber.name }}</div>
                  </div>
                </div>
              </td>
              <td dir="ltr" class="text-end">{{ subscriber.phone }}</td>
              <td>
                <VChip
                  size="small"
                  :color="statusColor(subscriber.status)"
                  variant="tonal"
                >
                  {{ statusLabel(subscriber.status) }}
                </VChip>
              </td>
              <td>
                <span class="text-caption">
                  {{ subscriber.created_at ? new Date(subscriber.created_at).toLocaleDateString('ar-SA') : '-' }}
                </span>
              </td>
              <td class="text-center">
                <VBtn
                  size="small"
                  variant="tonal"
                  color="primary"
                  prepend-icon="tabler-eye"
                  @click="openSubscriber(subscriber)"
                >
                  استعراض
                </VBtn>
              </td>
            </tr>

            <tr v-if="subscribers.length === 0 && !isLoading">
              <td colspan="5" class="text-center py-8 text-muted">
                لا يوجد مشتركون مسجلون بعد.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <!-- Add Subscriber Dialog -->
    <VDialog v-model="isAddDialogVisible" max-width="500" @after-leave="resetForm">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة مشترك جديد</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="form.name"
                label="الاسم"
                :error-messages="addErrors.name"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.phone"
                label="رقم الهاتف"
                placeholder="07701234567"
                dir="ltr"
                :error-messages="addErrors.phone"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.password"
                type="password"
                label="كلمة المرور"
                :error-messages="addErrors.password"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="form.address"
                label="العنوان"
                :error-messages="addErrors.address"
              />
            </VCol>
            <VCol cols="12">
              <AppSelect
                v-model="form.status"
                label="الحالة"
                :items="[
                  { title: 'فعال', value: 'ACTIVE' },
                  { title: 'غير مفعل', value: 'UNVERIFIED' },
                  { title: 'موقوف', value: 'SUSPENDED' },
                ]"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSaving" @click="submitAdd">حفظ</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
