<script setup lang="ts">
const curriculums = ref<any[]>([])
const selectedCurriculum = ref<any | null>(null)
const isLoading = ref(true)
const isAddCurriculumDialogVisible = ref(false)
const isSubmitting = ref(false)
const notification = ref<{ text: string; color: string } | null>(null)

const newCurriculum = ref({
  code: '',
  name: '',
  description: '',
})

const fetchCurriculums = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/curriculums')
    if (res?.success) {
      curriculums.value = res.data
      if (curriculums.value.length > 0 && !selectedCurriculum.value) {
        await selectCurriculum(curriculums.value[0].id)
      }
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const selectCurriculum = async (id: string) => {
  try {
    const res = await $api(`/admin/curriculums/${id}`)
    if (res?.success)
      selectedCurriculum.value = res.data
  }
  catch (err) {
    console.error(err)
  }
}

const publishCurriculum = async (id: string) => {
  try {
    const res = await $api(`/admin/curriculums/${id}/publish`, { method: 'POST' })
    if (res?.success) {
      notification.value = { text: 'تم نشر المنهج التدريبي بنجاح وأصبح جاهزاً لاشتراكات الأطفال!', color: 'success' }
      await selectCurriculum(id)
      await fetchCurriculums()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل نشر المنهج', color: 'error' }
  }
}

const saveCurriculum = async () => {
  if (!newCurriculum.value.name || !newCurriculum.value.code)
    return

  isSubmitting.value = true
  try {
    const res = await $api('/admin/curriculums', {
      method: 'POST',
      body: newCurriculum.value,
    })
    if (res?.success) {
      isAddCurriculumDialogVisible.value = false
      newCurriculum.value = { code: '', name: '', description: '' }
      notification.value = { text: 'تم إنشاء المنهج بنجاح', color: 'success' }
      await fetchCurriculums()
      await selectCurriculum(res.data.id)
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حفظ المنهج', color: 'error' }
  }
  finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchCurriculums()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          منشئ المناهج والخطط التدريبية 📚
        </h2>
        <p class="text-muted mb-0">
          بناء الهيكل التعليمي التفاعلي (الأشهر، الأسابيع، الأيام) وتوزيع الألعاب التدريبية اليومية
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        @click="isAddCurriculumDialogVisible = true"
      >
        إنشاء منهج جديد
      </VBtn>
    </div>

    <!-- Notification Alert -->
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

    <VRow>
      <!-- Curriculums Selector Column -->
      <VCol cols="12" md="4">
        <VCard class="mb-6">
          <VCardItem title="المناهج المتاحة" subtitle="اختر المنهج لاستعراض هيكله">
            <template #append>
              <VChip size="small" color="primary">{{ curriculums.length }}</VChip>
            </template>
          </VCardItem>
          <VDivider />
          <VList density="comfortable" class="pa-2">
            <VListItem
              v-for="curr in curriculums"
              :key="curr.id"
              :active="selectedCurriculum?.id === curr.id"
              active-color="primary"
              class="rounded mb-2 cursor-pointer"
              @click="selectCurriculum(curr.id)"
            >
              <template #prepend>
                <VAvatar color="primary" variant="tonal" size="36" rounded>
                  <VIcon icon="tabler-book" size="20" />
                </VAvatar>
              </template>
              <VListItemTitle class="font-weight-bold">
                {{ curr.name }}
              </VListItemTitle>
              <VListItemSubtitle>
                <code>{{ curr.code }}</code>
              </VListItemSubtitle>
              <template #append>
                <VChip
                  size="x-small"
                  :color="curr.status === 'PUBLISHED' ? 'success' : 'warning'"
                  variant="tonal"
                >
                  {{ curr.status === 'PUBLISHED' ? 'منشور' : 'مسودة' }}
                </VChip>
              </template>
            </VListItem>
          </VList>
        </VCard>
      </VCol>

      <!-- Curriculum Details & Tree Builder View -->
      <VCol cols="12" md="8">
        <VCard v-if="selectedCurriculum">
          <VCardItem>
            <template #prepend>
              <VAvatar color="primary" variant="tonal" rounded size="48">
                <VIcon icon="tabler-books" size="28" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h5 font-weight-bold">
              {{ selectedCurriculum.name }}
            </VCardTitle>
            <VCardSubtitle>
              الرمز: <code>{{ selectedCurriculum.code }}</code> | الإصدار: {{ selectedCurriculum.version }}
            </VCardSubtitle>
            <template #append>
              <VBtn
                v-if="selectedCurriculum.status !== 'PUBLISHED'"
                color="success"
                prepend-icon="tabler-send"
                @click="publishCurriculum(selectedCurriculum.id)"
              >
                نشر المنهج
              </VBtn>
              <VChip
                v-else
                color="success"
                variant="flat"
                prepend-icon="tabler-check"
              >
                منشور وجاهز للاستخدام
              </VChip>
            </template>
          </VCardItem>

          <VDivider />

          <VCardText class="pa-6">
            <p class="text-body-2 text-muted mb-6">
              {{ selectedCurriculum.description || 'لا يوجد وصف للمنهج.' }}
            </p>

            <h4 class="text-h6 font-weight-bold mb-4 text-primary d-flex align-center gap-2">
              <VIcon icon="tabler-hierarchy" size="22" />
              هيكل الخطة التدريبية (الأشهر والأسابيع والأيام):
            </h4>

            <!-- Month / Week / Day Accordions -->
            <div v-if="selectedCurriculum.months && selectedCurriculum.months.length > 0">
              <VExpansionPanels multiple>
                <VExpansionPanel
                  v-for="month in selectedCurriculum.months"
                  :key="month.id"
                  class="mb-3 border rounded"
                >
                  <VExpansionPanelTitle class="font-weight-bold text-primary">
                    <VIcon icon="tabler-calendar" class="me-2" />
                    {{ month.name }} ({{ month.weeks?.length || 0 }} أسابيع)
                  </VExpansionPanelTitle>

                  <VExpansionPanelText>
                    <div
                      v-for="week in month.weeks"
                      :key="week.id"
                      class="mb-4 bg-background pa-4 rounded"
                    >
                      <div class="font-weight-bold text-subtitle-1 mb-2 d-flex align-center gap-2">
                        <VIcon icon="tabler-calendar-week" color="info" size="18" />
                        {{ week.name }}
                      </div>

                      <VRow>
                        <VCol
                          v-for="day in week.days"
                          :key="day.id"
                          cols="12"
                          sm="6"
                        >
                          <VCard variant="outlined" class="pa-3">
                            <div class="d-flex justify-space-between align-center mb-2">
                              <span class="font-weight-bold">{{ day.name }}</span>
                              <VChip size="x-small" color="secondary">{{ day.estimated_duration_seconds / 60 }} دقيقة</VChip>
                            </div>

                            <div class="text-caption text-muted mb-2">
                              الألعاب التدريبية ({{ day.games?.length || 0 }}):
                            </div>

                            <div class="d-flex flex-column gap-1">
                              <div
                                v-for="g in day.games"
                                :key="g.id"
                                class="d-flex align-center justify-space-between bg-surface pa-2 rounded text-caption border"
                              >
                                <span>
                                  <strong>{{ g.game_code }}</strong> - {{ g.game_name }}
                                </span>
                                <VChip size="x-small" color="primary">{{ g.game_type }}</VChip>
                              </div>
                            </div>
                          </VCard>
                        </VCol>
                      </VRow>
                    </div>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
            </div>

            <div v-else class="text-center py-8 text-muted">
              لا توجد أشهر أو أسابيع مبرمجة في هذا المنهج بعد.
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Create Curriculum Dialog -->
    <VDialog v-model="isAddCurriculumDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إنشاء منهج تدريبي جديد</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="newCurriculum.code"
            label="رمز المنهج (كود فريد)"
            placeholder="مثال: CURR-002"
            class="mb-4"
          />
          <VTextField
            v-model="newCurriculum.name"
            label="اسم المنهج"
            placeholder="مثال: منهج التأسيس المتقدم"
            class="mb-4"
          />
          <VTextarea
            v-model="newCurriculum.description"
            label="وصف المنهج"
            rows="3"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddCurriculumDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveCurriculum">حفظ المنهج</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
