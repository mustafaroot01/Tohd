<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const headers: DataTableHeader[] = [
  { title: 'المستوى', key: 'name', sortable: true, hideable: false },
  { title: 'رقم المستوى', key: 'level_number', sortable: true, align: 'center' },
  { title: 'الفئة العمرية', key: 'age_range' },
  { title: 'الألعاب المرتبطة', key: 'games_count', align: 'center' },
  { title: 'الوصف', key: 'description' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
]

const table = useServerTable('/admin/levels', {
  defaultSort: 'level_number',
  defaultOrder: 'asc',
})

const isAddLevelDialogVisible = ref(false)
const isSubmitting = ref(false)
const { notification, notifySuccess, notifyWarning, notifyError } = useNotification()

const editingLevelId = ref<string | null>(null)
const levelForm = ref({
  level_number: 1,
  name: '',
  description: '',
  min_age: 3,
  max_age: 8,
})

const confirmDelete = ref(false)
const pendingDeleteLevel = ref<any | null>(null)
const isDeleting = ref(false)

const openAddLevelDialog = () => {
  editingLevelId.value = null
  levelForm.value = {
    level_number: table.total + 1,
    name: '',
    description: '',
    min_age: 3,
    max_age: 8,
  }
  isAddLevelDialogVisible.value = true
}

const openEditLevelDialog = (level: any) => {
  editingLevelId.value = level.id
  levelForm.value = {
    level_number: level.level_number,
    name: level.name,
    description: level.description || '',
    min_age: level.min_age || 3,
    max_age: level.max_age || 8,
  }
  isAddLevelDialogVisible.value = true
}

const requestDeleteLevel = (level: any) => {
  pendingDeleteLevel.value = level
  confirmDelete.value = true
}

const saveLevel = async () => {
  if (!levelForm.value.name || !levelForm.value.level_number) {
    notifyWarning('يرجى إدخال اسم المستوى ورقمه')

    return
  }

  isSubmitting.value = true
  try {
    const isEdit = !!editingLevelId.value
    const url = isEdit ? `/admin/levels/${editingLevelId.value}` : '/admin/levels'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, { method, body: levelForm.value })

    if (res?.success) {
      isAddLevelDialogVisible.value = false
      notifySuccess(isEdit ? 'تم تحديث بيانات المستوى بنجاح' : 'تمت إضافة المستوى بنجاح')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حفظ المستوى')
  }
  finally {
    isSubmitting.value = false
  }
}

const deleteLevel = async () => {
  if (!pendingDeleteLevel.value)
    return

  isDeleting.value = true
  try {
    const res = await $api(`/admin/levels/${pendingDeleteLevel.value.id}`, { method: 'DELETE' })
    if (res?.success) {
      notifySuccess('تم حذف المستوى بنجاح')
      await table.afterDelete()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حذف المستوى')
  }
  finally {
    isDeleting.value = false
    confirmDelete.value = false
    pendingDeleteLevel.value = null
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        مستويات الألعاب والأنشطة 🏆
      </h2>
      <p class="text-muted mb-0">
        تهيئة وإدارة الفئات العمرية والمستويات التدريبية للألعاب
      </p>
    </div>

    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="المستويات"
      icon="tabler-stairs-up"
      search-placeholder="ابحث بالاسم أو الوصف…"
      add-label="إضافة مستوى جديد"
      empty-text="لا توجد مستويات معرفة بعد."
      empty-icon="tabler-award-off"
      @add="openAddLevelDialog"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="38" class="font-weight-bold">
            {{ item.level_number }}
          </VAvatar>
          <span class="font-weight-bold text-high-emphasis">{{ item.name }}</span>
        </div>
      </template>

      <template #item.level_number="{ item }">
        <span class="text-body-2">{{ item.level_number }}</span>
      </template>

      <template #item.age_range="{ item }">
        <span class="text-body-2">من {{ item.min_age }} إلى {{ item.max_age }} سنة</span>
      </template>

      <template #item.games_count="{ item }">
        <VChip size="small" color="primary" variant="tonal" class="font-weight-medium">
          {{ item.games_count ?? 0 }} لعبة
        </VChip>
      </template>

      <template #item.description="{ item }">
        <span
          v-if="item.description"
          class="text-caption text-muted d-inline-block text-truncate"
          style="max-inline-size: 20rem;"
        >{{ item.description }}</span>
        <span v-else class="text-muted text-caption">—</span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            variant="text"
            color="warning"
            @click="openEditLevelDialog(item)"
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
            @click="requestDeleteLevel(item)"
          >
            <VIcon icon="tabler-trash" size="20" />
            <VTooltip activator="parent" location="top">
              حذف
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Add/Edit Level Dialog -->
    <VDialog v-model="isAddLevelDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingLevelId ? 'تعديل بيانات المستوى' : 'إضافة مستوى تدريبي جديد' }}
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model.number="levelForm.level_number"
                type="number"
                label="رقم المستوى"
                min="1"
                placeholder="1"
              />
            </VCol>

            <VCol cols="12" sm="8">
              <AppTextField
                v-model="levelForm.name"
                label="اسم المستوى"
                placeholder="مثال: المستوى الأول"
              />
            </VCol>

            <VCol cols="12">
              <AppTextarea
                v-model="levelForm.description"
                label="الوصف والتفاصيل"
                rows="2"
                placeholder="أدخل وصفاً للمستوى التدريبي والفئة المستهدفة..."
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="levelForm.min_age"
                type="number"
                label="الحد الأدنى للسن (بالسنوات)"
                min="1"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="levelForm.max_age"
                type="number"
                label="الحد الأقصى للسن (بالسنوات)"
                min="1"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddLevelDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveLevel">
            حفظ البيانات
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ConfirmDeleteDialog
      v-model="confirmDelete"
      title="تأكيد حذف المستوى"
      :item-name="pendingDeleteLevel?.name"
      message="سيتم حذف هذا المستوى نهائياً من قاعدة البيانات. هل أنت متأكد؟"
      confirm-label="نعم، احذف المستوى"
      :loading="isDeleting"
      @confirm="deleteLevel"
    />
  </div>
</template>
