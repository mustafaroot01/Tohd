<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const assetTypes = statusOptions(ASSET_TYPE)

const headers: DataTableHeader[] = [
  { title: 'كود الملف', key: 'code', sortable: true },
  { title: 'الاسم والملف', key: 'name', sortable: true, hideable: false },
  {
    title: 'النوع',
    key: 'type',
    sortable: true,
    filter: { anyLabel: 'كل الأنواع', options: assetTypes },
  },
  { title: 'الحجم والتفاصيل', key: 'size', sortable: true },
  { title: 'المعاينة', key: 'preview', align: 'center' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
]

const table = useServerTable('/admin/assets', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { type: null },
})

const isUploadDialogVisible = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const { notification, notifySuccess, notifyInfo, notifyWarning, notifyError } = useNotification()
const confirmDelete = ref(false)
const pendingDeleteAsset = ref<any | null>(null)

const isPreviewDialogVisible = ref(false)
const previewAssetUrl = ref('')
const previewAssetName = ref('')
const previewAssetType = ref('')

const editingAssetId = ref<string | null>(null)

const newAsset = ref({
  code: '',
  name: '',
  type: 'LOTTIE',
  file: null as File | null,
})

const openPreview = (asset: any) => {
  previewAssetUrl.value = asset.url
  previewAssetName.value = asset.name
  previewAssetType.value = asset.type
  isPreviewDialogVisible.value = true
}

const openAddAssetDialog = () => {
  editingAssetId.value = null
  newAsset.value = { code: '', name: '', type: 'LOTTIE', file: null }
  isUploadDialogVisible.value = true
}

const openEditAssetDialog = (asset: any) => {
  editingAssetId.value = asset.id
  newAsset.value = { code: asset.code, name: asset.name, type: asset.type, file: null }
  isUploadDialogVisible.value = true
}

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement

  if (target.files && target.files[0]) {
    newAsset.value.file = target.files[0]
    if (!newAsset.value.name)
      newAsset.value.name = target.files[0].name.replace(/\.[^/.]+$/, '')
  }
}

const saveAsset = async () => {
  const isEdit = !!editingAssetId.value

  if (!isEdit && !newAsset.value.file) {
    notifyWarning('يرجى اختيار ملف لرفعه')

    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()

    if (newAsset.value.file)
      formData.append('file', newAsset.value.file)

    formData.append('type', newAsset.value.type)

    if (newAsset.value.name)
      formData.append('name', newAsset.value.name)
    if (newAsset.value.code)
      formData.append('code', newAsset.value.code)

    const url = isEdit ? `/admin/assets/${editingAssetId.value}` : '/admin/assets'
    const res = await $api(url, { method: 'POST', body: formData })

    if (res?.success) {
      isUploadDialogVisible.value = false
      newAsset.value = { code: '', name: '', type: 'LOTTIE', file: null }
      notifySuccess(isEdit ? 'تم تحديث بيانات الملف بنجاح!' : 'تم رفع وتدقيق الملف بنجاح!')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حفظ الملف')
  }
  finally {
    isSubmitting.value = false
  }
}

const confirmDeleteAsset = (asset: any) => {
  pendingDeleteAsset.value = asset
  confirmDelete.value = true
}

const deleteAsset = async () => {
  if (!pendingDeleteAsset.value)
    return

  isDeleting.value = true
  try {
    await $api(`/admin/assets/${pendingDeleteAsset.value.id}`, { method: 'DELETE' })
    notifyInfo('تم حذف الملف بنجاح')
    await table.afterDelete()
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isDeleting.value = false
    confirmDelete.value = false
    pendingDeleteAsset.value = null
  }
}


</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        مكتبة الوسائط ورسوم Lottie 🎨
      </h2>
      <p class="text-muted mb-0">
        إدارة وتدقيق ملفات الـ JSON Lottie التفاعلية والأصوات والصور المستخدمة في الألعاب
      </p>
    </div>

    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="مكتبة الوسائط"
      icon="tabler-photo"
      search-placeholder="ابحث بالاسم أو الكود…"
      add-label="رفع ملف جديد"
      add-icon="tabler-upload"
      empty-text="لا توجد وسائط مسجلة في هذا التصنيف."
      empty-icon="tabler-photo-off"
      @add="openAddAssetDialog"
    >
      <template #item.code="{ item }">
        <span class="font-weight-bold text-primary text-caption" dir="ltr">{{ item.code }}</span>
      </template>

      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3">
          <VAvatar :color="statusColor(ASSET_TYPE, item.type)" variant="tonal" rounded size="38">
            <VIcon :icon="item.type === 'LOTTIE' ? 'tabler-animation' : 'tabler-photo'" size="20" />
          </VAvatar>
          <div>
            <div class="font-weight-bold text-high-emphasis">
              {{ item.name }}
            </div>
            <div
              class="text-caption text-muted text-truncate"
              style="max-inline-size: 15rem;"
              dir="ltr"
            >
              {{ item.url ? item.url.split('/').pop() : '' }}
            </div>
          </div>
        </div>
      </template>

      <template #item.type="{ item }">
        <VChip size="small" :color="statusColor(ASSET_TYPE, item.type)" variant="tonal">
          {{ item.type }}
        </VChip>
      </template>

      <template #item.size="{ item }">
        <div class="font-weight-medium text-caption">
          {{ formatBytes(item.size) }}
        </div>
        <div
          v-if="item.metadata?.duration_seconds || item.metadata?.total_frames"
          class="text-caption text-muted"
        >
          <span v-if="item.metadata.duration_seconds">{{ item.metadata.duration_seconds }}ث </span>
          <span v-if="item.metadata.total_frames">({{ item.metadata.total_frames }} إطار)</span>
        </div>
      </template>

      <template #item.preview="{ item }">
        <div class="text-center">
          <VBtn
            v-if="item.url"
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="tabler-eye"
            @click="openPreview(item)"
          >
            معاينة
          </VBtn>
          <span v-else class="text-muted text-caption">—</span>
        </div>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            color="warning"
            variant="text"
            @click="openEditAssetDialog(item)"
          >
            <VIcon icon="tabler-edit" size="20" />
            <VTooltip activator="parent" location="top">
              تعديل
            </VTooltip>
          </VBtn>
          <VBtn
            icon
            size="small"
            color="error"
            variant="text"
            @click="confirmDeleteAsset(item)"
          >
            <VIcon icon="tabler-trash" size="20" />
            <VTooltip activator="parent" location="top">
              حذف
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Upload / Edit Dialog -->
    <VDialog v-model="isUploadDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingAssetId ? 'تعديل بيانات ملف الوسائط / Lottie' : 'رفع وتدقيق ملف وسائط / Lottie' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <AppSelect
            v-model="newAsset.type"
            :items="assetTypes"
            label="نوع الملف"
            class="mb-4"
          />

          <AppTextField
            v-model="newAsset.name"
            label="اسم الملف التعريفي"
            placeholder="مثال: نجمة ذهبية تلمع"
            class="mb-4"
          />

          <AppTextField
            v-model="newAsset.code"
            label="كود الملف (فريد)"
            placeholder="مثال: LOTTIE-GOLD-STAR"
            class="mb-4"
          />

          <VFileInput
            :label="editingAssetId ? 'اختر ملف جديد لاستبدال الملف الحالي (اختياري)' : 'اختر الملف من جهازك (JSON / MP3 / PNG / SVG)'"
            prepend-icon="tabler-file"
            class="mb-2"
            @change="handleFileUpload"
          />

          <div v-if="newAsset.type === 'LOTTIE'" class="text-caption text-muted">
            ⚡ سيقوم السيرفر تلقائياً بالتحقق من صحة بنية JSON واستخراج مدة الحركة ومعدل الإطارات وحساب بصمة التشفير.
          </div>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isUploadDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveAsset">
            {{ editingAssetId ? 'حفظ التعديلات' : 'بدء الرفع والتدقيق' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ConfirmDeleteDialog
      v-model="confirmDelete"
      :item-name="pendingDeleteAsset?.name"
      :loading="isDeleting"
      @confirm="deleteAsset"
    />

    <FilePreviewDialog
      v-model="isPreviewDialogVisible"
      :file-url="previewAssetUrl"
      :file-name="previewAssetName"
      :file-type="previewAssetType"
    />
  </div>
</template>
