<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue: boolean
  fileUrl: string
  fileName: string
  fileType: 'LOTTIE' | 'IMAGE' | 'AUDIO' | 'VIDEO' | string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  fileUrl: '',
  fileName: '',
  fileType: 'LOTTIE',
})

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
}>()

const close = () => {
  emit('update:modelValue', false)
}

const lottieJson = ref<string>('')
const isLoadingLottie = ref(false)

const getAbsoluteUrl = (url: string) => {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) return url

  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''
  if (apiBaseUrl && (apiBaseUrl.startsWith('http://') || apiBaseUrl.startsWith('https://'))) {
    try {
      const parsed = new URL(apiBaseUrl)
      return `${parsed.origin}${url.startsWith('/') ? '' : '/'}${url}`
    } catch (e) {
      console.error(e)
    }
  }

  // Fallback to local Laravel serve port 8000 if running locally on Vite port (5173 / 3000)
  if (window.location.port === '5173' || window.location.port === '3000') {
    return `http://localhost:8000${url.startsWith('/') ? '' : '/'}${url}`
  }

  return `${window.location.origin}${url.startsWith('/') ? '' : '/'}${url}`
}

watch(() => props.modelValue, async (isOpen) => {
  if (isOpen && props.fileType === 'LOTTIE' && props.fileUrl) {
    isLoadingLottie.value = true
    lottieJson.value = ''
    try {
      const absoluteUrl = getAbsoluteUrl(props.fileUrl)
      const response = await fetch(absoluteUrl)
      if (response.ok) {
        const data = await response.json()
        lottieJson.value = JSON.stringify(data)
      } else {
        console.error('Failed to fetch lottie JSON')
      }
    } catch (err) {
      console.error('Error fetching lottie JSON:', err)
    } finally {
      isLoadingLottie.value = false
    }
  }
})

</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="600"
    @update:model-value="close"
  >
    <VCard class="file-preview-card">
      <VCardTitle class="d-flex justify-space-between align-center pa-4 bg-primary text-white">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-player-play" size="24" />
          <span class="text-h6 font-weight-bold text-white">{{ fileName || 'معاينة الملف' }}</span>
        </div>
        <VBtn
          icon="tabler-x"
          variant="text"
          color="white"
          density="comfortable"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6 text-center d-flex flex-column align-center justify-center bg-background">
        <div
          v-if="fileType === 'LOTTIE'"
          class="w-100 d-flex flex-column align-center justify-center py-4"
        >
          <div v-if="isLoadingLottie" class="py-12 text-center">
            <VProgressCircular indeterminate color="primary" size="48" class="mb-2" />
            <div class="text-caption text-muted">جاري تحميل رسوم Lottie...</div>
          </div>
          <lottie-player
            v-else-if="lottieJson"
            :src="lottieJson"
            background="transparent"
            speed="1"
            style="width: 100%; max-width: 400px; height: 350px; margin: 0 auto;"
            loop
            controls
            autoplay
          />
          <div
            v-else
            class="text-muted py-6"
          >
            لا يمكن تحميل ملف الرسوم التفاعلية
          </div>
        </div>

        <!-- 2. IMAGE Preview -->
        <div
          v-else-if="fileType === 'IMAGE'"
          class="w-100 d-flex justify-center pa-2"
        >
          <VImg
            :src="getAbsoluteUrl(fileUrl)"
            max-height="400"
            contain
            class="rounded border shadow-sm"
          />
        </div>

        <!-- 3. AUDIO Preview -->
        <div
          v-else-if="fileType === 'AUDIO'"
          class="w-100 py-8 px-4"
        >
          <VIcon
            icon="tabler-volume"
            size="64"
            color="primary"
            class="mb-4"
          />
          <div class="mb-4 text-body-1 font-weight-medium">
            ملف صوتي
          </div>
          <audio
            :src="getAbsoluteUrl(fileUrl)"
            controls
            autoplay
            class="w-100"
          />
        </div>

        <!-- 4. VIDEO Preview -->
        <div
          v-else-if="fileType === 'VIDEO'"
          class="w-100"
        >
          <video
            :src="getAbsoluteUrl(fileUrl)"
            controls
            autoplay
            style="max-width: 100%; max-height: 400px; border-radius: 8px;"
            class="w-100 shadow border"
          />
        </div>

        <!-- 5. Default/Other Preview -->
        <div
          v-else
          class="py-6"
        >
          <VIcon
            icon="tabler-file"
            size="64"
            class="mb-2"
          />
          <p class="text-body-1 mb-4">
            معاينة هذا الملف غير مدعومة مباشرة.
          </p>
          <VBtn
            :href="getAbsoluteUrl(fileUrl)"
            target="_blank"
            color="primary"
            prepend-icon="tabler-external-link"
          >
            فتح في علامة تبويب جديدة
          </VBtn>
        </div>
      </VCardText>

      <VCardActions class="pa-4 bg-background border-t">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="tonal"
          @click="close"
        >
          إغلاق المعاينة
        </VBtn>
        <VBtn
          :href="getAbsoluteUrl(fileUrl)"
          target="_blank"
          color="primary"
          variant="flat"
          prepend-icon="tabler-download"
        >
          تحميل الملف
        </VBtn>
      </VCardActions>

    </VCard>
  </VDialog>
</template>

<style scoped>
.file-preview-card {
  border-radius: 12px !important;
  overflow: hidden;
}
</style>
