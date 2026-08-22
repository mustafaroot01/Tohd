<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

// ── axes table (owns the URL query) ──────────────────────────────────────────
const axesTable = useServerTable('/admin/axes', {
  defaultSort: 'sort_order',
  defaultOrder: 'asc',
})

// ── skills table (second table on the page → no URL sync, keys would clash) ──
const skillsTable = useServerTable('/admin/skills', {
  defaultSort: 'sort_order',
  defaultOrder: 'asc',
  filters: { axis_id: null },
  syncQuery: false,
})

/** Axis options for the skill filter and the skill dialog. */
const axisOptions = ref<any[]>([])

const loadAxisOptions = async () => {
  try {
    const res = await $api('/admin/axes', { query: { per_page: 100 } })
    if (res?.success)
      axisOptions.value = res.data
  }
  catch (err) {
    console.error(err)
  }
}

const axesHeaders: DataTableHeader[] = [
  { title: 'المحور', key: 'name', sortable: true, hideable: false },
  { title: 'الرمز التعريفي', key: 'slug', sortable: true },
  { title: 'المهارات', key: 'skills_count', align: 'center' },
  { title: 'الترتيب', key: 'sort_order', sortable: true, align: 'center' },
  { title: 'الوصف', key: 'description' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
]

const skillsHeaders = computed<DataTableHeader[]>(() => [
  { title: 'المهارة', key: 'name', sortable: true, hideable: false },
  { title: 'الرمز التعريفي', key: 'slug', sortable: true },
  {
    title: 'المحور التابع له',
    key: 'axis',
    filter: {
      key: 'axis_id',
      anyLabel: 'كل المحاور',
      options: axisOptions.value.map(a => ({ title: a.name, value: a.id })),
    },
  },
  { title: 'الألعاب', key: 'games_count', align: 'center' },
  { title: 'الترتيب', key: 'sort_order', sortable: true, align: 'center' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
])

const isAddAxisDialogVisible = ref(false)
const isAddSkillDialogVisible = ref(false)
const isSubmitting = ref(false)
const alertMessage = ref<string | null>(null)

const newAxis = ref({ name: '', description: '', sort_order: 1 })
const newSkill = ref({ axis_id: '', name: '', description: '', sort_order: 1 })

const editingAxisId = ref<string | null>(null)
const editingSkillId = ref<string | null>(null)

const confirmDeleteAxis = ref(false)
const pendingDeleteAxis = ref<any | null>(null)
const isDeletingAxis = ref(false)

const confirmDeleteSkill = ref(false)
const pendingDeleteSkill = ref<any | null>(null)
const isDeletingSkill = ref(false)

const openAddAxisDialog = () => {
  editingAxisId.value = null
  newAxis.value = { name: '', description: '', sort_order: axesTable.total + 1 }
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

const saveAxis = async () => {
  if (!newAxis.value.name)
    return

  isSubmitting.value = true
  try {
    const isEdit = !!editingAxisId.value
    const url = isEdit ? `/admin/axes/${editingAxisId.value}` : '/admin/axes'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, { method, body: newAxis.value })

    if (res?.success) {
      isAddAxisDialogVisible.value = false
      newAxis.value = { name: '', description: '', sort_order: 1 }
      await Promise.all([axesTable.reload(), loadAxisOptions()])
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

    const res = await $api(url, { method, body: newSkill.value })

    if (res?.success) {
      isAddSkillDialogVisible.value = false
      newSkill.value = { axis_id: '', name: '', description: '', sort_order: 1 }
      await Promise.all([skillsTable.reload(), axesTable.reload()])
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
  if (!pendingDeleteAxis.value)
    return

  isDeletingAxis.value = true
  try {
    await $api(`/admin/axes/${pendingDeleteAxis.value.id}`, { method: 'DELETE' })
    await Promise.all([axesTable.afterDelete(), skillsTable.reload(), loadAxisOptions()])
  }
  catch (err: any) {
    alertMessage.value = err?.data?.message || 'تعذّر حذف المحور — تأكد من عدم ارتباطه بألعاب'
  }
  finally {
    isDeletingAxis.value = false
    confirmDeleteAxis.value = false
    pendingDeleteAxis.value = null
  }
}

const deleteSkill = async () => {
  if (!pendingDeleteSkill.value)
    return

  isDeletingSkill.value = true
  try {
    await $api(`/admin/skills/${pendingDeleteSkill.value.id}`, { method: 'DELETE' })
    await Promise.all([skillsTable.afterDelete(), axesTable.reload()])
  }
  catch (err: any) {
    alertMessage.value = err?.data?.message || 'تعذّر حذف المهارة — تأكد من عدم ارتباطها بألعاب'
  }
  finally {
    isDeletingSkill.value = false
    confirmDeleteSkill.value = false
    pendingDeleteSkill.value = null
  }
}

onMounted(loadAxisOptions)
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        المحاور والمهارات التدريبية 🎯
      </h2>
      <p class="text-muted mb-0">
        إدارة مجالات التدريب الأساسية (التركيز، التواصل البصري، التتبع، التفاعل الاجتماعي) والمهارات التابعة لها
      </p>
    </div>

    <VAlert
      v-if="alertMessage"
      color="error"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="alertMessage = null"
    >
      {{ alertMessage }}
    </VAlert>

    <!-- ── Axes ── -->
    <AppDataTableServer
      :table="axesTable"
      :headers="axesHeaders"
      title="المحاور التدريبية"
      icon="tabler-target"
      search-placeholder="ابحث باسم المحور…"
      add-label="إضافة محور جديد"
      empty-text="لا توجد محاور تدريبية معرفة بعد."
      empty-icon="tabler-target-off"
      class="mb-6"
      @add="openAddAxisDialog"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" rounded size="38">
            <VIcon icon="tabler-target" size="20" />
          </VAvatar>
          <span class="font-weight-bold text-high-emphasis">{{ item.name }}</span>
        </div>
      </template>

      <template #item.slug="{ item }">
        <span class="text-caption text-muted" dir="ltr">{{ item.slug }}</span>
      </template>

      <template #item.skills_count="{ item }">
        <VChip size="small" color="info" variant="tonal">
          {{ item.skills_count ?? 0 }} مهارة
        </VChip>
      </template>

      <template #item.description="{ item }">
        <span
          v-if="item.description"
          class="text-caption text-muted d-inline-block text-truncate"
          style="max-inline-size: 18rem;"
        >{{ item.description }}</span>
        <span v-else class="text-muted text-caption">—</span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            color="warning"
            variant="text"
            @click="openEditAxisDialog(item)"
          >
            <VIcon icon="tabler-edit" size="20" />
          </VBtn>
          <VBtn
            icon
            size="small"
            color="error"
            variant="text"
            @click="requestDeleteAxis(item)"
          >
            <VIcon icon="tabler-trash" size="20" />
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- ── Skills ── -->
    <AppDataTableServer
      :table="skillsTable"
      :headers="skillsHeaders"
      title="المهارات التدريبية"
      icon="tabler-bulb"
      search-placeholder="ابحث باسم المهارة…"
      add-label="إضافة مهارة"
      empty-text="لا توجد مهارات مضافة بعد."
      empty-icon="tabler-bulb-off"
      @add="openAddSkillDialog"
    >
      <template #item.name="{ item }">
        <div>
          <div class="font-weight-bold text-high-emphasis">
            {{ item.name }}
          </div>
          <div
            v-if="item.description"
            class="text-caption text-muted text-truncate"
            style="max-inline-size: 18rem;"
          >
            {{ item.description }}
          </div>
        </div>
      </template>

      <template #item.slug="{ item }">
        <span class="text-caption text-muted" dir="ltr">{{ item.slug }}</span>
      </template>

      <template #item.axis="{ item }">
        <VChip size="small" color="primary" variant="tonal">
          {{ item.axis?.name || '—' }}
        </VChip>
      </template>

      <template #item.games_count="{ item }">
        <VChip size="small" color="secondary" variant="tonal">
          {{ item.games_count ?? 0 }} لعبة
        </VChip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            color="warning"
            variant="text"
            @click="openEditSkillDialog(item)"
          >
            <VIcon icon="tabler-edit" size="20" />
          </VBtn>
          <VBtn
            icon
            size="small"
            color="error"
            variant="text"
            @click="requestDeleteSkill(item)"
          >
            <VIcon icon="tabler-trash" size="20" />
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Add/Edit Axis Dialog -->
    <VDialog v-model="isAddAxisDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingAxisId ? 'تعديل المحور التدريبي' : 'إضافة محور تدريبي جديد' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <AppTextField
            v-model="newAxis.name"
            label="اسم المحور"
            placeholder="مثال: التركيز والانتباه"
            class="mb-4"
          />
          <AppTextarea
            v-model="newAxis.description"
            label="الوصف والهدف التدريبي"
            placeholder="شرح مبسط للهدف من هذا المحور..."
            rows="3"
            class="mb-4"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddAxisDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveAxis">
            حفظ المحور
          </VBtn>
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
          <AppSelect
            v-model="newSkill.axis_id"
            :items="axisOptions"
            item-title="name"
            item-value="id"
            label="المحور التابع له"
            class="mb-4"
          />
          <AppTextField
            v-model="newSkill.name"
            label="اسم المهارة"
            placeholder="مثال: الانتباه الانتقائي"
            class="mb-4"
          />
          <AppTextarea
            v-model="newSkill.description"
            label="وصف المهارة"
            placeholder="تفاصيل المهارة..."
            rows="3"
            class="mb-4"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddSkillDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveSkill">
            حفظ المهارة
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ConfirmDeleteDialog
      v-model="confirmDeleteAxis"
      title="تأكيد حذف المحور التدريبي"
      :item-name="pendingDeleteAxis?.name"
      message="سيتم حذف هذا المحور التدريبي بشكل كامل مع جميع المهارات المرتبطة به. هل أنت متأكد؟"
      :loading="isDeletingAxis"
      @confirm="deleteAxis"
    />

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
