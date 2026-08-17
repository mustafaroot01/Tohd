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

const newAsset = ref({
  code: '',
  name: '',
  type: 'LOTTIE',
  file: null as File | null,
})

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

const uploadAsset = async () => {
  if (!newAsset.value.file) {
    notification.value = { text: 'يرجى اختيار ملف لرفعه', color: 'warning' }
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('file', newAsset.value.file)
    formData.append('type', newAsset.value.type)
    if (newAsset.value.name)
      formData.append('name', newAsset.value.name)
    if (newAsset.value.code)
      formData.append('code', newAsset.value.code)

    const res = await $api('/admin/assets', {
      method: 'POST',
      body: formData,
    })

    if (res?.success) {
      isUploadDialogVisible.value = false
      newAsset.value = { code: '', name: '', type: 'LOTTIE', file: null }
      notification.value = { text: 'تم رفع وتدقيق الملف بنجاح!', color: 'success' }
      await fetchAssets()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل رفع الملف', color: 'error' }
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
        @click="isUploadDialogVisible = true"
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

    <!-- Assets Grid -->
    <VRow v-if="!isLoading">
      <VCol
        v-for="asset in assets"
        :key="asset.id"
        cols="12"
        sm="6"
        md="4"
      >
        <VCard class="h-100">
          <VCardItem>
            <template #prepend>
              <VAvatar
                :color="asset.type === 'LOTTIE' ? 'warning' : 'primary'"
                variant="tonal"
                rounded
                size="44"
              >
                <VIcon :icon="asset.type === 'LOTTIE' ? 'tabler-animation' : 'tabler-photo'" size="24" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">
              {{ asset.name }}
            </VCardTitle>
            <VCardSubtitle>
              <code>{{ asset.code }}</code>
            </VCardSubtitle>
            <template #append>
              <VBtn
                icon="tabler-trash"
                size="small"
                color="error"
                variant="text"
                @click="confirmDeleteAsset(asset)"
              />
            </template>
          </VCardItem>

          <VCardText>
            <div class="bg-background pa-3 rounded text-caption mb-3">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-muted">النوع:</span>
                <span class="font-weight-bold">{{ asset.type }}</span>
              </div>
              <div class="d-flex justify-space-between mb-1">
                <span class="text-muted">الحجم:</span>
                <span>{{ formatBytes(asset.size) }}</span>
              </div>
              <div class="d-flex justify-space-between mb-1" v-if="asset.metadata?.duration_seconds">
                <span class="text-muted">مدة Lottie:</span>
                <span>{{ asset.metadata.duration_seconds }} ثانية</span>
              </div>
              <div class="d-flex justify-space-between" v-if="asset.metadata?.total_frames">
                <span class="text-muted">عدد الإطارات:</span>
                <span>{{ asset.metadata.total_frames }} إطار</span>
              </div>
            </div>

            <div class="d-flex gap-2">
              <VBtn
                v-if="asset.url"
                size="small"
                variant="tonal"
                color="primary"
                :href="asset.url"
                target="_blank"
                prepend-icon="tabler-download"
                block
              >
                تحميل / معاينة الملف
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol v-if="assets.length === 0" cols="12" class="text-center py-12 text-muted">
        لا توجد وسائط مسجلة في هذا التصنيف
      </VCol>
    </VRow>

    <div v-else class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- Upload Dialog -->
    <VDialog v-model="isUploadDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">رفع وتدقيق ملف وسائط / Lottie</VCardTitle>
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

          <VFileInput
            label="اختر الملف من جهازك (JSON / MP3 / PNG / SVG)"
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
          <VBtn color="primary" :loading="isSubmitting" @click="uploadAsset">بدء الرفع والتدقيق</VBtn>
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
  </div>
</template>
