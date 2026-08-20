<script setup lang="ts">
const assets = ref<any[]>([])
const isLoading = ref(true)
const isUploadDialogVisible = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const selectedType = ref<string>('')
const notification = ref<{ text: string; color: string } | null>(null)
const confirmDelete = ref(false)
const pendingDeleteAsset = ref<any | null>(null)

// Preview File State
const isPreviewDialogVisible = ref(false)
const previewAssetUrl = ref('')
const previewAssetName = ref('')
const previewAssetType = ref('')

const openPreview = (asset: any) => {
  previewAssetUrl.value = asset.url
  previewAssetName.value = asset.name
  previewAssetType.value = asset.type
  isPreviewDialogVisible.value = true
}


// Edit Asset State
const editingAssetId = ref<string | null>(null)

const newAsset = ref({
  code: '',
  name: '',
  type: 'LOTTIE',
  file: null as File | null,
})

const openAddAssetDialog = () => {
  editingAssetId.value = null
  newAsset.value = {
    code: '',
    name: '',
    type: 'LOTTIE',
    file: null,
  }
  isUploadDialogVisible.value = true
}

const openEditAssetDialog = (asset: any) => {
  editingAssetId.value = asset.id
  newAsset.value = {
    code: asset.code,
    name: asset.name,
    type: asset.type,
    file: null,
  }
  isUploadDialogVisible.value = true
}


const assetTypes = [
  { value: 'LOTTIE', title: 'رسوم Lottie المتحركة (JSON)' },
  { value: 'IMAGE', title: 'صورة (PNG / JPG / SVG)' },
  { value: 'AUDIO', title: 'ملف صوتي (MP3 / WAV)' },
  { value: 'VIDEO', title: 'فيديو توضيحي (MP4)' },
]

const fetchAssets = async () => {
  isLoading.value = true
  try {
    const url = selectedType.value ? `/admin/assets?type=${selectedType.value}` : '/admin/assets'
    const res = await $api(url)
    if (res?.success)
      assets.value = res.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    newAsset.value.file = target.files[0]
    if (!newAsset.value.name)
      newAsset.value.name = target.files[0].name.replace(/\.[^/.]+$/, "")
  }
}

const saveAsset = async () => {
  const isEdit = !!editingAssetId.value

  if (!isEdit && !newAsset.value.file) {
    notification.value = { text: 'يرجى اختيار ملف لرفعه', color: 'warning' }
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    if (newAsset.value.file) {
      formData.append('file', newAsset.value.file)
    }
    formData.append('type', newAsset.value.type)
    if (newAsset.value.name)
      formData.append('name', newAsset.value.name)
    if (newAsset.value.code)
      formData.append('code', newAsset.value.code)

    const url = isEdit ? `/admin/assets/${editingAssetId.value}` : '/admin/assets'
    const res = await $api(url, {
      method: 'POST',
      body: formData,
    })

    if (res?.success) {
      isUploadDialogVisible.value = false
      newAsset.value = { code: '', name: '', type: 'LOTTIE', file: null }
      notification.value = { 
        text: isEdit ? 'تم تحديث بيانات الملف بنجاح!' : 'تم رفع وتدقيق الملف بنجاح!', 
        color: 'success' 
      }
      await fetchAssets()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حفظ الملف', color: 'error' }
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
  if (!pendingDeleteAsset.value) return
  isDeleting.value = true
  try {
    await $api(`/admin/assets/${pendingDeleteAsset.value.id}`, { method: 'DELETE' })
    notification.value = { text: 'تم حذف الملف بنجاح', color: 'info' }
    await fetchAssets()
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

const formatBytes = (bytes: number, decimals = 2) => {
  if (!+bytes) return '0 Bytes'
  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

onMounted(() => {
  fetchAssets()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          مكتبة الوسائط ورسوم Lottie 🎨
        </h2>
        <p class="text-muted mb-0">
          إدارة وتدقيق ملفات الـ JSON Lottie التفاعلية والأصوات والصور المستخدمة في الألعاب
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-upload"
        @click="openAddAssetDialog"
      >
        رفع ملف جديد
      </VBtn>
    </div>

    <!-- Notification -->
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

    <!-- Filter chips -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <VChip
        :color="selectedType === '' ? 'primary' : 'default'"
        variant="tonal"
        class="cursor-pointer"
        @click="selectedType = ''; fetchAssets()"
      >
        الكل ({{ assets.length }})
      </VChip>
      <VChip
        v-for="t in assetTypes"
        :key="t.value"
        :color="selectedType === t.value ? 'primary' : 'default'"
        variant="tonal"
        class="cursor-pointer"
        @click="selectedType = t.value; fetchAssets()"
      >
        {{ t.title }}
      </VChip>
    </div>

    <!-- Assets Table -->
    <VCard v-if="!isLoading" class="mb-6">
      <VCardText class="pa-0">
        <VTable hover class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-start">كود الملف</th>
              <th class="text-start">الاسم والملف</th>
              <th class="text-start">النوع</th>
              <th class="text-start">الحجم والتفاصيل</th>
              <th class="text-center">المعاينة</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="asset in assets" :key="asset.id">
              <!-- Code -->
              <td>
                <span class="font-weight-bold text-primary"><code>{{ asset.code }}</code></span>
              </td>
              
              <!-- Name & Avatar -->
              <td>
                <div class="d-flex align-center gap-3">
                  <VAvatar
                    :color="asset.type === 'LOTTIE' ? 'warning' : 'primary'"
                    variant="tonal"
                    rounded
                    size="38"
                  >
                    <VIcon :icon="asset.type === 'LOTTIE' ? 'tabler-animation' : 'tabler-photo'" size="20" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold">{{ asset.name }}</div>
                    <div class="text-caption text-muted" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                      {{ asset.url ? asset.url.split('/').pop() : '' }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Type -->
              <td>
                <VChip
                  size="small"
                  :color="asset.type === 'LOTTIE' ? 'warning' : asset.type === 'IMAGE' ? 'success' : asset.type === 'AUDIO' ? 'info' : 'secondary'"
                  variant="tonal"
                >
                  {{ asset.type }}
                </VChip>
              </td>

              <!-- Size & Metadata -->
              <td>
                <div>
                  <div class="font-weight-medium text-caption">{{ formatBytes(asset.size) }}</div>
                  <div v-if="asset.metadata?.duration_seconds || asset.metadata?.total_frames" class="text-caption text-muted">
                    <span v-if="asset.metadata.duration_seconds">{{ asset.metadata.duration_seconds }}ث </span>
                    <span v-if="asset.metadata.total_frames">({{ asset.metadata.total_frames }} إطار)</span>
                  </div>
                </div>
              </td>

              <!-- Preview -->
              <td class="text-center">
                <VBtn
                  v-if="asset.url"
                  size="small"
                  variant="tonal"
                  color="primary"
                  prepend-icon="tabler-eye"
                  @click="openPreview(asset)"
                >
                  معاينة
                </VBtn>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <div class="d-flex justify-center gap-1">
                  <VBtn
                    icon="tabler-edit"
                    size="small"
                    color="warning"
                    variant="text"
                    @click="openEditAssetDialog(asset)"
                  />
                  <VBtn
                    icon="tabler-trash"
                    size="small"
                    color="error"
                    variant="text"
                    @click="confirmDeleteAsset(asset)"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="assets.length === 0">
              <td colspan="6" class="text-center py-8 text-muted">
                لا توجد وسائط مسجلة في هذا التصنيف.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <div v-else class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>


    <!-- Upload / Edit Dialog -->
    <VDialog v-model="isUploadDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingAssetId ? 'تعديل بيانات ملف الوسائط / Lottie' : 'رفع وتدقيق ملف وسائط / Lottie' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VSelect
            v-model="newAsset.type"
            :items="assetTypes"
            label="نوع الملف"
            class="mb-4"
          />

          <VTextField
            v-model="newAsset.name"
            label="اسم الملف التعريفي"
            placeholder="مثال: نجمة ذهبية تلمع"
            class="mb-4"
          />

          <VTextField
            v-model="newAsset.code"
            label="كود الملف (فريد)"
            placeholder="مثال: LOTTIE-GOLD-STAR"
            class="mb-4"
          />

          <VFileInput
            :label="editingAssetId ? 'اختر ملف جديد لاستبدال الملف الحالي (اختياري)' : 'اختر الملف من جهازك (JSON / MP3 / PNG / SVG)'"
            prepend-icon="tabler-file"
            @change="handleFileUpload"
            class="mb-2"
          />

          <div class="text-caption text-muted" v-if="newAsset.type === 'LOTTIE'">
            ⚡ سيقوم السيرفر تلقائياً بالتحقق من صحة بنية JSON واستخراج مدة الحركة ومعدل الإطارات وحساب بصمة التشفير.
          </div>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isUploadDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveAsset">
            {{ editingAssetId ? 'حفظ التعديلات' : 'بدء الرفع والتدقيق' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    <!-- Confirm Delete Modal -->
    <ConfirmDeleteDialog
      v-model="confirmDelete"
      :item-name="pendingDeleteAsset?.name"
      :loading="isDeleting"
      @confirm="deleteAsset"
    />

    <!-- File Preview Modal -->
    <FilePreviewDialog
      v-model="isPreviewDialogVisible"
      :file-url="previewAssetUrl"
      :file-name="previewAssetName"
      :file-type="previewAssetType"
    />
  </div>
</template>
