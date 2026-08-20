<script setup lang="ts">
import { ref, onMounted } from 'vue'

const levels = ref<any[]>([])
const isLoading = ref(true)
const isAddLevelDialogVisible = ref(false)
const isSubmitting = ref(false)
const notification = ref<{ text: string; color: string } | null>(null)

// Level Form State
const editingLevelId = ref<string | null>(null)
const levelForm = ref({
  level_number: 1,
  name: '',
  description: '',
  min_age: 3,
  max_age: 8,
})

// Delete Level State
const confirmDelete = ref(false)
const pendingDeleteLevel = ref<any | null>(null)
const isDeleting = ref(false)

const openAddLevelDialog = () => {
  editingLevelId.value = null
  levelForm.value = {
    level_number: levels.value.length + 1,
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

const fetchLevels = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/levels')
    if (res?.success) {
      levels.value = res.data
    }
  } catch (err) {
    console.error('Failed to load levels:', err)
  } finally {
    isLoading.value = false
  }
}

const saveLevel = async () => {
  if (!levelForm.value.name || !levelForm.value.level_number) {
    notification.value = { text: 'يرجى إدخال اسم المستوى ورقمه', color: 'warning' }
    return
  }

  isSubmitting.value = true
  try {
    const isEdit = !!editingLevelId.value
    const url = isEdit ? `/admin/levels/${editingLevelId.value}` : '/admin/levels'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, {
      method,
      body: levelForm.value,
    })

    if (res?.success) {
      isAddLevelDialogVisible.value = false
      notification.value = {
        text: isEdit ? 'تم تحديث بيانات المستوى بنجاح' : 'تمت إضافة المستوى بنجاح',
        color: 'success'
      }
      await fetchLevels()
    }
  } catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حفظ المستوى', color: 'error' }
  } finally {
    isSubmitting.value = false
  }
}

const deleteLevel = async () => {
  if (!pendingDeleteLevel.value) return
  isDeleting.value = true
  try {
    const res = await $api(`/admin/levels/${pendingDeleteLevel.value.id}`, {
      method: 'DELETE',
    })
    if (res?.success) {
      notification.value = { text: 'تم حذف المستوى بنجاح', color: 'success' }
      await fetchLevels()
    }
  } catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حذف المستوى', color: 'error' }
  } finally {
    isDeleting.value = false
    confirmDelete.value = false
    pendingDeleteLevel.value = null
  }
}

onMounted(() => {
  fetchLevels()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          مستويات الألعاب والأنشطة 🏆
        </h2>
        <p class="text-muted mb-0">
          تهيئة وإدارة الفئات العمرية والمستويات التدريبية للألعاب
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        @click="openAddLevelDialog"
      >
        إضافة مستوى جديد
      </VBtn>
    </div>

    <!-- Notifications -->
    <VAlert
      v-if="notification"
      :color="notification.color"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="notification = null"
    >
      {{ notification.text }}
    </VAlert>

    <!-- Levels List -->
    <VCard v-if="isLoading" class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </VCard>

    <VRow v-else-if="levels.length > 0">
      <VCol
        v-for="level in levels"
        :key="level.id"
        cols="12"
        md="4"
      >
        <VCard class="h-100 d-flex flex-column">
          <VCardText class="position-relative pa-6 flex-grow-1">
            <!-- Level Number Badge -->
            <VAvatar
              color="primary"
              variant="tonal"
              size="48"
              class="mb-4 font-weight-bold"
            >
              {{ level.level_number }}
            </VAvatar>

            <h3 class="text-h5 font-weight-bold mb-2">
              {{ level.name }}
            </h3>

            <p class="text-body-2 text-muted mb-4">
              {{ level.description || 'لا يوجد وصف لهذا المستوى.' }}
            </p>

            <div class="d-flex gap-4 mb-2">
              <div>
                <span class="text-caption text-disabled d-block">الفئة العمرية</span>
                <span class="text-body-2 font-weight-medium">من {{ level.min_age }} إلى {{ level.max_age }} سنة</span>
              </div>
              <VDivider vertical />
              <div>
                <span class="text-caption text-disabled d-block">الألعاب المرتبطة</span>
                <VChip size="small" color="primary" class="font-weight-medium">
                  {{ level.games_count }} لعبة
                </VChip>
              </div>
            </div>
          </VCardText>

          <VDivider />

          <!-- Card Actions -->
          <VCardActions class="pa-4 bg-background">
            <VBtn
              color="warning"
              variant="tonal"
              size="small"
              prepend-icon="tabler-edit"
              @click="openEditLevelDialog(level)"
            >
              تعديل
            </VBtn>

            <VSpacer />

            <VBtn
              color="error"
              variant="text"
              size="small"
              prepend-icon="tabler-trash"
              @click="requestDeleteLevel(level)"
            >
              حذف
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <VCard v-else class="text-center py-12">
      <VIcon icon="tabler-award-off" size="64" color="grey" class="mb-3" />
      <h3 class="text-h5 text-muted mb-2">لا توجد مستويات معرفة</h3>
      <p class="text-body-2 text-muted mb-4">اضغط على زر الإضافة لإنشاء مستوى تدريبي جديد</p>
      <VBtn color="primary" @click="openAddLevelDialog">إنشاء أول مستوى</VBtn>
    </VCard>

    <!-- Add/Edit Level Dialog -->
    <VDialog v-model="isAddLevelDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingLevelId ? 'تعديل بيانات المستوى' : 'إضافة مستوى تدريبي جديد' }}
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VRow>
            <!-- Level Number -->
            <VCol cols="12" sm="4">
              <AppTextField
                v-model.number="levelForm.level_number"
                type="number"
                label="رقم المستوى"
                min="1"
                placeholder="1"
              />
            </VCol>

            <!-- Level Name -->
            <VCol cols="12" sm="8">
              <AppTextField
                v-model="levelForm.name"
                label="اسم المستوى"
                placeholder="مثال: المستوى الأول"
              />
            </VCol>

            <!-- Description -->
            <VCol cols="12">
              <AppTextarea
                v-model="levelForm.description"
                label="الوصف والتفاصيل"
                rows="2"
                placeholder="أدخل وصفاً للمستوى التدريبي والفئة المستهدفة..."
              />
            </VCol>

            <!-- Min Age -->
            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="levelForm.min_age"
                type="number"
                label="الحد الأدنى للسن (بالسنوات)"
                min="1"
              />
            </VCol>

            <!-- Max Age -->
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
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isAddLevelDialogVisible = false"
          >
            إلغاء
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitting"
            @click="saveLevel"
          >
            حفظ البيانات
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm Delete Modal -->
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
