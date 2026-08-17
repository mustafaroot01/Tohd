<script setup lang="ts">
interface Props {
  modelValue: boolean
  title?: string
  message?: string
  confirmLabel?: string
  cancelLabel?: string
  loading?: boolean
  itemName?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'تأكيد الحذف',
  message: '',
  confirmLabel: 'نعم، احذف',
  cancelLabel: 'إلغاء',
  loading: false,
  itemName: '',
})

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const close = () => {
  emit('update:modelValue', false)
  emit('cancel')
}

const confirm = () => {
  emit('confirm')
}
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="420"
    persistent
    @update:model-value="close"
  >
    <VCard class="confirm-delete-card">
      <!-- Icon Header -->
      <div class="confirm-icon-wrap">
        <VAvatar
          color="error"
          variant="tonal"
          size="72"
          class="confirm-avatar"
        >
          <VIcon icon="tabler-trash" size="36" color="error" />
        </VAvatar>
      </div>

      <VCardText class="text-center pt-2 pb-4 px-6">
        <h3 class="text-h5 font-weight-bold mb-2">
          {{ title }}
        </h3>
        <p
          v-if="message || itemName"
          class="text-body-1 text-medium-emphasis mb-0"
        >
          {{ message || `هل أنت متأكد من حذف` }}
          <strong
            v-if="itemName"
            class="text-error"
          >
            «{{ itemName }}»
          </strong>
          {{ message ? '' : '؟' }}
        </p>
        <p
          v-if="!message && !itemName"
          class="text-body-2 text-medium-emphasis mt-2 mb-0"
        >
          هذا الإجراء لا يمكن التراجع عنه.
        </p>
        <p
          v-if="itemName"
          class="text-caption text-medium-emphasis mt-1 mb-0"
        >
          هذا الإجراء لا يمكن التراجع عنه.
        </p>
      </VCardText>

      <VCardActions class="justify-center gap-3 pb-5 px-6">
        <VBtn
          variant="tonal"
          color="secondary"
          :disabled="loading"
          min-width="120"
          @click="close"
        >
          <VIcon icon="tabler-x" class="me-1" size="18" />
          {{ cancelLabel }}
        </VBtn>
        <VBtn
          color="error"
          variant="flat"
          :loading="loading"
          min-width="120"
          @click="confirm"
        >
          <VIcon icon="tabler-trash" class="me-1" size="18" />
          {{ confirmLabel }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.confirm-delete-card {
  border-radius: 16px !important;
  overflow: visible !important;
}

.confirm-icon-wrap {
  display: flex;
  justify-content: center;
  padding-top: 28px;
  padding-bottom: 8px;
}

.confirm-avatar {
  animation: shake-in 0.4s ease;
}

@keyframes shake-in {
  0%   { transform: scale(0.5) rotate(-10deg); opacity: 0; }
  60%  { transform: scale(1.1) rotate(4deg); }
  100% { transform: scale(1) rotate(0deg); opacity: 1; }
}
</style>
