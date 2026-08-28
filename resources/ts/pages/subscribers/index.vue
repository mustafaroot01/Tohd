<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const router = useRouter()

const headers: DataTableHeader[] = [
  { title: 'المشترك', key: 'name', sortable: true, hideable: false },
  { title: 'رقم الهاتف', key: 'phone', sortable: true },
  {
    title: 'الحالة',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(SUBSCRIBER_STATUS) },
  },
  { title: 'آخر نشاط', key: 'last_activity_at', sortable: true },
  { title: 'تاريخ التسجيل', key: 'created_at', sortable: true },
  { title: 'التفاصيل', key: 'actions', align: 'center', hideable: false },
]

const table = useServerTable('/admin/subscribers', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null },
})

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
      await table.reload()
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        المشتركون 👨‍👩‍👧
      </h2>
      <p class="text-muted mb-0">
        استعراض حسابات المشتركين (عملاء التطبيق)، حالة الاشتراك، وبيانات المناهج المفعلة
      </p>
    </div>

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="قائمة المشتركين"
      icon="tabler-users"
      search-placeholder="ابحث بالاسم أو الهاتف…"
      add-label="إضافة مشترك"
      empty-text="لا يوجد مشتركون مسجلون بعد."
      empty-icon="tabler-user-off"
      @add="isAddDialogVisible = true"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-user" size="20" />
          </VAvatar>
          <span class="font-weight-bold text-high-emphasis">{{ item.name }}</span>
        </div>
      </template>

      <template #item.phone="{ item }">
        <span dir="ltr" class="d-inline-block">{{ item.phone }}</span>
      </template>

      <template #item.status="{ item }">
        <VChip size="small" :color="statusColor(SUBSCRIBER_STATUS, item.status)" variant="tonal">
          {{ statusLabel(SUBSCRIBER_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.last_activity_at="{ item }">
        <span class="text-caption">{{ formatDate(item.last_activity_at) }}</span>
      </template>

      <template #item.created_at="{ item }">
        <span class="text-caption">{{ formatDate(item.created_at) }}</span>
      </template>

      <template #item.actions="{ item }">
        <div class="text-center">
          <VBtn
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="tabler-eye"
            @click="openSubscriber(item)"
          >
            استعراض
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Add Subscriber Dialog -->
    <VDialog v-model="isAddDialogVisible" max-width="500" @after-leave="resetForm">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          إضافة مشترك جديد
        </VCardTitle>
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
                  { title: 'موقوف', value: 'SUSPENDED' },
                ]"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSaving" @click="submitAdd">
            حفظ
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
