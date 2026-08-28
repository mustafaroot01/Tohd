<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const headers: DataTableHeader[] = [
  { title: 'المحافظة', key: 'name', sortable: true, hideable: false },
  { title: 'الرمز', key: 'code', sortable: true },
  { title: 'الترتيب', key: 'sort_order', sortable: true, align: 'center' },
  {
    title: 'الحالة',
    key: 'is_active',
    align: 'center',
    filter: {
      anyLabel: 'الكل',
      options: [
        { title: 'ظاهرة', value: 1 },
        { title: 'مخفية', value: 0 },
      ],
    },
  },
  { title: 'المشتركون', key: 'profiles_count', align: 'center' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
]

const table = useServerTable('/admin/governorates', {
  defaultSort: 'sort_order',
  defaultOrder: 'asc',
  filters: { is_active: null },
})

const { notification, notifySuccess, notifyWarning, notifyError } = useNotification()

const isDialogVisible = ref(false)
const isSubmitting = ref(false)
const editingId = ref<string | null>(null)
const form = ref({
  name: '',
  code: '',
  sort_order: 0,
  is_active: true,
})

const confirmDelete = ref(false)
const pendingDelete = ref<any | null>(null)
const isDeleting = ref(false)
const togglingId = ref<string | null>(null)

const openAddDialog = () => {
  editingId.value = null
  form.value = { name: '', code: '', sort_order: table.total + 1, is_active: true }
  isDialogVisible.value = true
}

const openEditDialog = (governorate: any) => {
  editingId.value = governorate.id
  form.value = {
    name: governorate.name,
    code: governorate.code || '',
    sort_order: governorate.sort_order ?? 0,
    is_active: !!governorate.is_active,
  }
  isDialogVisible.value = true
}

const save = async () => {
  if (!form.value.name) {
    notifyWarning('يرجى إدخال اسم المحافظة')

    return
  }

  isSubmitting.value = true
  try {
    const isEdit = !!editingId.value
    const res = await $api(isEdit ? `/admin/governorates/${editingId.value}` : '/admin/governorates', {
      method: isEdit ? 'PUT' : 'POST',
      body: { ...form.value, code: form.value.code || null },
    })

    if (res?.success) {
      isDialogVisible.value = false
      notifySuccess(isEdit ? 'تم تحديث بيانات المحافظة' : 'تمت إضافة المحافظة')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حفظ المحافظة')
  }
  finally {
    isSubmitting.value = false
  }
}

/** Hiding is a one-field update — the row keeps its name and its subscribers. */
const toggleVisibility = async (governorate: any) => {
  togglingId.value = governorate.id
  try {
    const res = await $api(`/admin/governorates/${governorate.id}`, {
      method: 'PUT',
      body: { is_active: !governorate.is_active },
    })

    if (res?.success) {
      notifySuccess(governorate.is_active ? 'تم إخفاء المحافظة من التطبيق' : 'تم إظهار المحافظة في التطبيق')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل تغيير حالة المحافظة')
  }
  finally {
    togglingId.value = null
  }
}

const requestDelete = (governorate: any) => {
  pendingDelete.value = governorate
  confirmDelete.value = true
}

const remove = async () => {
  if (!pendingDelete.value)
    return

  isDeleting.value = true
  try {
    const res = await $api(`/admin/governorates/${pendingDelete.value.id}`, { method: 'DELETE' })
    if (res?.success) {
      notifySuccess('تم حذف المحافظة')
      await table.afterDelete()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حذف المحافظة')
  }
  finally {
    isDeleting.value = false
    confirmDelete.value = false
    pendingDelete.value = null
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        المحافظات 🗺️
      </h2>
      <p class="text-muted mb-0">
        قائمة المحافظات التي يختار منها المشترك عند إكمال بياناته. المحافظة المخفية لا تظهر في التطبيق ويحتفظ من اختارها ببياناته.
      </p>
    </div>

    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="المحافظات"
      icon="tabler-map-pin"
      search-placeholder="ابحث بالاسم أو الرمز…"
      add-label="إضافة محافظة"
      empty-text="لا توجد محافظات بعد."
      empty-icon="tabler-map-off"
      @add="openAddDialog"
    >
      <template #item.name="{ item }">
        <span class="font-weight-bold text-high-emphasis">{{ item.name }}</span>
      </template>

      <template #item.code="{ item }">
        <span v-if="item.code" class="text-caption text-muted" dir="ltr">{{ item.code }}</span>
        <span v-else class="text-muted">—</span>
      </template>

      <template #item.sort_order="{ item }">
        <span class="text-body-2">{{ item.sort_order }}</span>
      </template>

      <template #item.is_active="{ item }">
        <VChip
          size="small"
          variant="tonal"
          :color="item.is_active ? 'success' : 'secondary'"
        >
          {{ item.is_active ? 'ظاهرة' : 'مخفية' }}
        </VChip>
      </template>

      <template #item.profiles_count="{ item }">
        <span class="text-body-2">{{ item.profiles_count ?? 0 }}</span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            variant="text"
            :color="item.is_active ? 'secondary' : 'success'"
            :loading="togglingId === item.id"
            @click="toggleVisibility(item)"
          >
            <VIcon :icon="item.is_active ? 'tabler-eye-off' : 'tabler-eye'" size="20" />
            <VTooltip activator="parent" location="top">
              {{ item.is_active ? 'إخفاء من التطبيق' : 'إظهار في التطبيق' }}
            </VTooltip>
          </VBtn>
          <VBtn
            icon
            size="small"
            variant="text"
            color="warning"
            @click="openEditDialog(item)"
          >
            <VIcon icon="tabler-edit" size="20" />
            <VTooltip activator="parent" location="top">
              تعديل
            </VTooltip>
          </VBtn>
          <VBtn
            icon
            size="small"
            variant="text"
            color="error"
            @click="requestDelete(item)"
          >
            <VIcon icon="tabler-trash" size="20" />
            <VTooltip activator="parent" location="top">
              حذف
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Add / Edit -->
    <VDialog v-model="isDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingId ? 'تعديل المحافظة' : 'إضافة محافظة' }}
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="8">
              <AppTextField
                v-model="form.name"
                label="اسم المحافظة"
                placeholder="مثال: بغداد"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField
                v-model="form.code"
                label="الرمز (اختياري)"
                placeholder="BGD"
                dir="ltr"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="form.sort_order"
                type="number"
                min="0"
                label="ترتيب الظهور"
              />
            </VCol>

            <VCol cols="12" sm="6" class="d-flex align-center">
              <VSwitch
                v-model="form.is_active"
                color="primary"
                inset
                :label="form.is_active ? 'ظاهرة في التطبيق' : 'مخفية عن التطبيق'"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="save">
            حفظ
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ConfirmDeleteDialog
      v-model="confirmDelete"
      title="تأكيد حذف المحافظة"
      :item-name="pendingDelete?.name"
      message="لا يمكن حذف محافظة اختارها مشتركون — استخدم الإخفاء بدلاً من ذلك. هل تريد المتابعة؟"
      confirm-label="نعم، احذف"
      :loading="isDeleting"
      @confirm="remove"
    />
  </div>
</template>
