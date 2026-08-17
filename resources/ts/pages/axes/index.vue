<script setup lang="ts">
const axes = ref<any[]>([])
const skills = ref<any[]>([])
const isLoading = ref(true)

const isAddAxisDialogVisible = ref(false)
const isAddSkillDialogVisible = ref(false)

const newAxis = ref({
  name: '',
  description: '',
  sort_order: 1,
})

const newSkill = ref({
  axis_id: '',
  name: '',
  description: '',
  sort_order: 1,
})

const isSubmitting = ref(false)
const alertMessage = ref<string | null>(null)

const fetchData = async () => {
  isLoading.value = true
  try {
    const axesRes = await $api('/admin/axes')
    if (axesRes?.success)
      axes.value = axesRes.data

    const skillsRes = await $api('/admin/skills')
    if (skillsRes?.success)
      skills.value = skillsRes.data
  }
  catch (err) {
    console.error('Failed to load axes/skills:', err)
  }
  finally {
    isLoading.value = false
  }
}

const saveAxis = async () => {
  if (!newAxis.value.name)
    return
  isSubmitting.value = true
  try {
    const res = await $api('/admin/axes', {
      method: 'POST',
      body: newAxis.value,
    })
    if (res?.success) {
      isAddAxisDialogVisible.value = false
      newAxis.value = { name: '', description: '', sort_order: axes.value.length + 1 }
      await fetchData()
    }
  }
  catch (err: any) {
    alertMessage.value = err?.data?.message || 'حدث خطأ أثناء حفظ المحور'
  }
  finally {
    isSubmitting.value = false
  }
}

const saveSkill = async () => {
  if (!newSkill.value.name || !newSkill.value.axis_id)
    return
  isSubmitting.value = true
  try {
    const res = await $api('/admin/skills', {
      method: 'POST',
      body: newSkill.value,
    })
    if (res?.success) {
      isAddSkillDialogVisible.value = false
      newSkill.value = { axis_id: '', name: '', description: '', sort_order: 1 }
      await fetchData()
    }
  }
  catch (err: any) {
    alertMessage.value = err?.data?.message || 'حدث خطأ أثناء حفظ المهارة'
  }
  finally {
    isSubmitting.value = false
  }
}

const deleteAxis = async (id: string) => {
  if (!confirm('هل أنت متأكد من حذف هذا المحور وجميع المهارات المرتبطة به؟'))
    return
  try {
    await $api(`/admin/axes/${id}`, { method: 'DELETE' })
    await fetchData()
  }
  catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          المحاور والمهارات التدريبية 🎯
        </h2>
        <p class="text-muted mb-0">
          إدارة مجالات التدريب الأساسية (التركيز، التواصل البصري، التتبع، التفاعل الاجتماعي) والمهارات التابعة لها
        </p>
      </div>
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="tabler-plus"
          @click="isAddSkillDialogVisible = true"
        >
          إضافة مهارة
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="isAddAxisDialogVisible = true"
        >
          إضافة محور جديد
        </VBtn>
      </div>
    </div>

    <!-- Axes Grid -->
    <VRow v-if="!isLoading">
      <VCol
        v-for="axis in axes"
        :key="axis.id"
        cols="12"
        md="6"
      >
        <VCard class="h-100">
          <VCardItem>
            <template #prepend>
              <VAvatar
                color="primary"
                variant="tonal"
                rounded
                size="44"
              >
                <VIcon icon="tabler-target" size="24" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h5 font-weight-bold">
              {{ axis.name }}
            </VCardTitle>
            <VCardSubtitle>
              الرمز التعريفي: <code>{{ axis.slug }}</code>
            </VCardSubtitle>
            <template #append>
              <VBtn
                icon="tabler-trash"
                size="small"
                color="error"
                variant="text"
                @click="deleteAxis(axis.id)"
              />
            </template>
          </VCardItem>

          <VCardText>
            <p class="text-body-2 text-muted mb-4">
              {{ axis.description || 'لا يوجد وصف تفصيلي للمحور' }}
            </p>

            <h5 class="text-subtitle-2 font-weight-bold mb-2 text-primary">
              المهارات المندرجة تحت هذا المحور ({{ (skills.filter(s => s.axis_id === axis.id)).length }}):
            </h5>

            <VList density="compact" class="bg-background rounded pa-2">
              <VListItem
                v-for="skill in skills.filter(s => s.axis_id === axis.id)"
                :key="skill.id"
                class="rounded mb-1"
              >
                <template #prepend>
                  <VIcon icon="tabler-circle-check" color="success" size="18" class="me-2" />
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ skill.name }}
                </VListItemTitle>
                <VListItemSubtitle class="text-caption">
                  {{ skill.description }}
                </VListItemSubtitle>
              </VListItem>

              <div
                v-if="skills.filter(s => s.axis_id === axis.id).length === 0"
                class="text-center text-muted py-2 text-caption"
              >
                لا توجد مهارات مضافة بعد
              </div>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <div v-else class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- Add Axis Dialog -->
    <VDialog v-model="isAddAxisDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة محور تدريبي جديد</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="newAxis.name"
            label="اسم المحور"
            placeholder="مثال: التركيز والانتباه"
            class="mb-4"
          />
          <VTextarea
            v-model="newAxis.description"
            label="الوصف والهدف التدريبي"
            placeholder="شرح مبسط للهدف من هذا المحور..."
            rows="3"
            class="mb-4"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddAxisDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveAxis">حفظ المحور</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add Skill Dialog -->
    <VDialog v-model="isAddSkillDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة مهارة جديدة</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VSelect
            v-model="newSkill.axis_id"
            :items="axes"
            item-title="name"
            item-value="id"
            label="المحور التابع له"
            class="mb-4"
          />
          <VTextField
            v-model="newSkill.name"
            label="اسم المهارة"
            placeholder="مثال: الانتباه الانتقائي"
            class="mb-4"
          />
          <VTextarea
            v-model="newSkill.description"
            label="وصف المهارة"
            placeholder="تفاصيل المهارة..."
            rows="3"
            class="mb-4"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddSkillDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveSkill">حفظ المهارة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
