<script setup lang="ts">
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()
</script>

<template>
  <div class="h-100 d-flex align-center justify-center">
    <span class="text-medium-emphasis">
      &copy; {{ new Date().getFullYear() }} {{ settingsStore.appName }}. جميع الحقوق محفوظة.
    </span>
  </div>
</template>
