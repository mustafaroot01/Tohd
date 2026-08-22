<script setup lang="ts">
import type { PageNotification } from '@/composables/useNotification'

defineProps<{ modelValue: PageNotification | null }>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: null): void
}>()
</script>

<template>
  <VAlert
    v-if="modelValue"
    :color="modelValue.color"
    variant="tonal"
    class="mb-6"
    closable
    @click:close="emit('update:modelValue', null)"
  >
    {{ modelValue.text }}
  </VAlert>
</template>
