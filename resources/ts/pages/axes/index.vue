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

// Edit Axis State
const editingAxisId = ref<string | null>(null)

// Edit Skill State
const editingSkillId = ref<string | null>(null)

// Delete Axis State
const confirmDeleteAxis = ref(false)
const pendingDeleteAxis = ref<any | null>(null)
const isDeletingAxis = ref(false)

// Delete Skill State
const confirmDeleteSkill = ref(false)
const pendingDeleteSkill = ref<any | null>(null)
const isDeletingSkill = ref(false)

const openAddAxisDialog = () => {
  editingAxisId.value = null
  newAxis.value = { name: '', description: '', sort_order: axes.value.length + 1 }
  isAddAxisDialogVisible.value = true
}

const openEditAxisDialog = (axis: any) => {
  editingAxisId.value = axis.id
  newAxis.value = {
    name: axis.name,
    description: axis.description || '',
    sort_order: axis.sort_order || 1,
  }
  isAddAxisDialogVisible.value = true
}

const openAddSkillDialog = () => {
  editingSkillId.value = null
  newSkill.value = { axis_id: '', name: '', description: '', sort_order: 1 }
  isAddSkillDialogVisible.value = true
}

const openEditSkillDialog = (skill: any) => {
  editingSkillId.value = skill.id
  newSkill.value = {
    axis_id: skill.axis_id,
    name: skill.name,
    description: skill.description || '',
    sort_order: skill.sort_order || 1,
  }
  isAddSkillDialogVisible.value = true
}

const requestDeleteAxis = (axis: any) => {
  pendingDeleteAxis.value = axis
  confirmDeleteAxis.value = true
}

const requestDeleteSkill = (skill: any) => {
  pendingDeleteSkill.value = skill
  confirmDeleteSkill.value = true
}



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
    const isEdit = !!editingAxisId.value
    const url = isEdit ? `/admin/axes/${editingAxisId.value}` : '/admin/axes'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, {
      method,
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
    const isEdit = !!editingSkillId.value
    const url = isEdit ? `/admin/skills/${editingSkillId.value}` : '/admin/skills'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, {
      method,
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


const deleteAxis = async () => {
  if (!pendingDeleteAxis.value) return
  isDeletingAxis.value = true
  try {
    await $api(`/admin/axes/${pendingDeleteAxis.value.id}`, { method: 'DELETE' })
    await fetchData()
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isDeletingAxis.value = false
    confirmDeleteAxis.value = false
    pendingDeleteAxis.value = null
  }
}

const deleteSkill = async () => {
  if (!pendingDeleteSkill.value) return
  isDeletingSkill.value = true
  try {
    await $api(`/admin/skills/${pendingDeleteSkill.value.id}`, { method: 'DELETE' })
    await fetchData()
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isDeletingSkill.value = false
    confirmDeleteSkill.value = false
    pendingDeleteSkill.value = null
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
          @click="openAddSkillDialog"
        >
          إضافة مهارة
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openAddAxisDialog"
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
              <div class="d-flex gap-1">
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  color="warning"
                  variant="text"
                  @click="openEditAxisDialog(axis)"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  color="error"
                  variant="text"
                  @click="requestDeleteAxis(axis)"
                />
              </div>
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
                <template #append>
                  <div class="d-flex gap-1">
                    <VBtn
                      icon="tabler-edit"
                      size="x-small"
                      color="warning"
                      variant="text"
                      @click="openEditSkillDialog(skill)"
                    />
                    <VBtn
                      icon="tabler-trash"
                      size="x-small"
                      color="error"
                      variant="text"
                      @click="requestDeleteSkill(skill)"
                    />
                  </div>
                </template>
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

    <!-- Add/Edit Axis Dialog -->
    <VDialog v-model="isAddAxisDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingAxisId ? 'تعديل المحور التدريبي' : 'إضافة محور تدريبي جديد' }}
        </VCardTitle>
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

    <!-- Add/Edit Skill Dialog -->
    <VDialog v-model="isAddSkillDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingSkillId ? 'تعديل المهارة التدريبية' : 'إضافة مهارة جديدة' }}
        </VCardTitle>
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
    <!-- Confirm Delete Axis Modal -->
    <ConfirmDeleteDialog
      v-model="confirmDeleteAxis"
      title="تأكيد حذف المحور التدريبي"
      :item-name="pendingDeleteAxis?.name"
      message="سيتم حذف هذا المحور التدريبي بشكل كامل مع جميع المهارات المرتبطة به. هل أنت متأكد؟"
      :loading="isDeletingAxis"
      @confirm="deleteAxis"
    />

    <!-- Confirm Delete Skill Modal -->
    <ConfirmDeleteDialog
      v-model="confirmDeleteSkill"
      title="تأكيد حذف المهارة"
      :item-name="pendingDeleteSkill?.name"
      message="سيتم حذف هذه المهارة من المحور الحالي. هل أنت متأكد؟"
      :loading="isDeletingSkill"
      @confirm="deleteSkill"
    />
  </div>
</template>
