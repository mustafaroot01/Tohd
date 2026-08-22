<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const selectedCurriculum = ref<any | null>(null)
const isAddCurriculumDialogVisible = ref(false)
const isSubmitting = ref(false)
const { notification, notifySuccess, notifyInfo, notifyWarning, notifyError } = useNotification()

// Edit Curriculum State
const editingCurriculumId = ref<string | null>(null)

const newCurriculum = ref({
  code: '',
  name: '',
  description: '',
})

const generateCurriculumCode = () => {
  const random = Array.from({ length: 6 }, () => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 36)]).join('')
  return `CURR-${random}`
}

const openAddCurriculumDialog = () => {
  editingCurriculumId.value = null
  newCurriculum.value = {
    code: generateCurriculumCode(),
    name: '',
    description: '',
  }
  isAddCurriculumDialogVisible.value = true
}

const openEditCurriculumDialog = (curr: any) => {
  editingCurriculumId.value = curr.id
  newCurriculum.value = {
    code: curr.code,
    name: curr.name,
    description: curr.description || '',
  }
  isAddCurriculumDialogVisible.value = true
}

// Tree Builder Dialog States
const isAddMonthDialogVisible = ref(false)
const isAddWeekDialogVisible = ref(false)
const isAddDayDialogVisible = ref(false)
const isAttachGameDialogVisible = ref(false)

const activeMonthId = ref<string>('')
const activeWeekId = ref<string>('')
const activeDayId = ref<string>('')

const gamesList = ref<any[]>([])

const monthForm = ref({ name: '', sort_order: 1 })
const weekForm = ref({ name: '', sort_order: 1 })
const dayForm = ref({ name: '', estimated_duration_seconds: 600, sort_order: 1 })
const attachGameForm = ref({ game_ids: [] as string[], sort_order: 1 })
const editingDayId = ref<string | null>(null)


const fetchGamesList = async () => {
  try {
    const res = await $api('/admin/games')
    if (res?.success) {
      // Get only approved or published games for curriculum mapping
      gamesList.value = res.data
    }
  } catch (err) {
    console.error('Failed to load games list:', err)
  }
}

const openAddMonth = () => {
  monthForm.value = { name: `الشهر ${ (selectedCurriculum.value?.months?.length || 0) + 1 }`, sort_order: (selectedCurriculum.value?.months?.length || 0) + 1 }
  isAddMonthDialogVisible.value = true
}

const openAddWeek = (monthId: string, monthWeeksCount: number) => {
  activeMonthId.value = monthId
  weekForm.value = { name: `الأسبوع ${monthWeeksCount + 1}`, sort_order: monthWeeksCount + 1 }
  isAddWeekDialogVisible.value = true
}

const openAddDay = (weekId: string, weekDaysCount: number) => {
  if (weekDaysCount >= 7) {
    notifyWarning('لا يمكن إضافة أكثر من 7 أيام في الأسبوع الواحد')
    return
  }
  editingDayId.value = null
  activeWeekId.value = weekId
  dayForm.value = { name: `اليوم ${weekDaysCount + 1}`, estimated_duration_seconds: 600, sort_order: weekDaysCount + 1 }
  isAddDayDialogVisible.value = true
}

const openAttachGame = (dayId: string) => {
  activeDayId.value = dayId
  attachGameForm.value = { game_ids: [], sort_order: 1 }
  isAttachGameDialogVisible.value = true
}


const saveMonth = async () => {
  if (!monthForm.value.name) return
  isSubmitting.value = true
  try {
    const res = await $api(`/admin/curriculums/${selectedCurriculum.value.id}/months`, {
      method: 'POST',
      body: monthForm.value
    })
    if (res?.success) {
      isAddMonthDialogVisible.value = false
      notifySuccess('تمت إضافة الشهر للمنهج بنجاح')
      await selectCurriculum(selectedCurriculum.value.id)
    }
  } catch (err: any) {
    notifyError(err, 'فشل إضافة الشهر')
  } finally {
    isSubmitting.value = false
  }
}

const saveWeek = async () => {
  if (!weekForm.value.name || !activeMonthId.value) return
  isSubmitting.value = true
  try {
    const res = await $api(`/admin/curriculums/months/${activeMonthId.value}/weeks`, {
      method: 'POST',
      body: weekForm.value
    })
    if (res?.success) {
      isAddWeekDialogVisible.value = false
      notifySuccess('تمت إضافة الأسبوع بنجاح')
      await selectCurriculum(selectedCurriculum.value.id)
    }
  } catch (err: any) {
    notifyError(err, 'فشل إضافة الأسبوع')
  } finally {
    isSubmitting.value = false
  }
}

const saveDay = async () => {
  if (!dayForm.value.name) return
  isSubmitting.value = true
  try {
    const isEdit = !!editingDayId.value
    const url = isEdit ? `/admin/curriculums/days/${editingDayId.value}` : `/admin/curriculums/weeks/${activeWeekId.value}/days`
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, {
      method,
      body: dayForm.value
    })
    if (res?.success) {
      isAddDayDialogVisible.value = false
      notifySuccess(isEdit ? 'تم تعديل اليوم بنجاح' : 'تمت إضافة اليوم بنجاح')
      await selectCurriculum(selectedCurriculum.value.id)
    }
  } catch (err: any) {
    notifyError(err, 'فشل حفظ اليوم')
  } finally {
    isSubmitting.value = false
  }
}

const openEditDay = (day: any) => {
  editingDayId.value = day.id
  dayForm.value = {
    name: day.name,
    estimated_duration_seconds: day.estimated_duration_seconds || 600,
    sort_order: day.sort_order || 1
  }
  isAddDayDialogVisible.value = true
}

// Delete Day Confirmation
const isConfirmDeleteDayVisible = ref(false)
const pendingDeleteDay = ref<{ id: string; name: string } | null>(null)
const isDeletingDay = ref(false)

const handleDeleteDay = (day: { id: string; name: string }) => {
  pendingDeleteDay.value = day
  isConfirmDeleteDayVisible.value = true
}

const confirmDeleteDay = async () => {
  if (!pendingDeleteDay.value) return
  isDeletingDay.value = true
  try {
    const res = await $api(`/admin/curriculums/days/${pendingDeleteDay.value.id}`, {
      method: 'DELETE'
    })
    if (res?.success) {
      notifyInfo('تم حذف اليوم بنجاح')
      await selectCurriculum(selectedCurriculum.value.id)
    }
  } catch (err: any) {
    notifyError(err, 'فشل حذف اليوم')
  } finally {
    isDeletingDay.value = false
    isConfirmDeleteDayVisible.value = false
    pendingDeleteDay.value = null
  }
}

const saveAttachGame = async () => {
  if (attachGameForm.value.game_ids.length === 0 || !activeDayId.value) return
  isSubmitting.value = true
  try {
    const promises = attachGameForm.value.game_ids.map((id, index) => {
      return $api(`/admin/curriculums/days/${activeDayId.value}/games`, {
        method: 'POST',
        body: {
          game_id: id,
          sort_order: attachGameForm.value.sort_order + index
        }
      })
    })
    await Promise.all(promises)
    isAttachGameDialogVisible.value = false
    notifySuccess('تم ربط الألعاب المحددة بنجاح باليوم التدريبي')
    await selectCurriculum(selectedCurriculum.value.id)
  } catch (err: any) {
    notifyError(err, 'فشل ربط بعض أو كل الألعاب باليوم')
  } finally {
    isSubmitting.value = false
  }
}


// Detach Game Confirmation
const isConfirmDetachGameVisible = ref(false)
const pendingDetachGame = ref<{ dayId: string; gameId: string; name: string } | null>(null)
const isDetachingGame = ref(false)

const handleDetachGame = (dayId: string, game: { id: string; name?: string; game_name?: string }) => {
  pendingDetachGame.value = { dayId, gameId: game.id, name: game.game_name || game.name || '' }
  isConfirmDetachGameVisible.value = true
}

const confirmDetachGame = async () => {
  if (!pendingDetachGame.value) return
  isDetachingGame.value = true
  try {
    const res = await $api(`/admin/curriculums/days/${pendingDetachGame.value.dayId}/games/${pendingDetachGame.value.gameId}`, {
      method: 'DELETE'
    })
    if (res?.success) {
      notifyInfo('تم إلغاء ربط اللعبة بنجاح')
      isConfirmDetachGameVisible.value = false
      await selectCurriculum(selectedCurriculum.value.id)
    }
  } catch (err: any) {
    notifyError(err, 'فشل إلغاء ربط اللعبة')
  } finally {
    isDetachingGame.value = false
  }
}

const table = useServerTable('/admin/curriculums', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null },
})

const headers: DataTableHeader[] = [
  { title: 'المنهج', key: 'name', sortable: true, hideable: false },
  { title: 'الرمز', key: 'code', sortable: true },
  {
    title: 'الحالة',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(CURRICULUM_STATUS) },
  },
  { title: 'الأشهر', key: 'months_count', align: 'center' },
  { title: 'الإصدار', key: 'version', sortable: true, align: 'center' },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
]

const fetchCurriculums = async () => {
  await table.reload()

  if (!selectedCurriculum.value && table.items.length > 0)
    await selectCurriculum(table.items[0].id)
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
      notifySuccess('تم نشر المنهج التدريبي بنجاح وأصبح جاهزاً لاشتراكات الأطفال!')
      await selectCurriculum(id)
      await fetchCurriculums()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل نشر المنهج')
  }
}

const saveCurriculum = async () => {
  if (!newCurriculum.value.name || !newCurriculum.value.code)
    return

  isSubmitting.value = true
  try {
    const isEdit = !!editingCurriculumId.value
    const url = isEdit ? `/admin/curriculums/${editingCurriculumId.value}` : '/admin/curriculums'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, {
      method,
      body: newCurriculum.value,
    })
    if (res?.success) {
      isAddCurriculumDialogVisible.value = false
      newCurriculum.value = { code: '', name: '', description: '' }
      notifySuccess(isEdit ? 'تم تحديث بيانات المنهج التدريبي بنجاح' : 'تم إنشاء المنهج بنجاح')
      await fetchCurriculums()
      await selectCurriculum(res.data.id)
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حفظ المنهج')
  }
  finally {
    isSubmitting.value = false
  }
}


// The table fetches itself; just latch onto the first row so the builder
// below has something to show on arrival.
watch(() => table.items, rows => {
  if (!selectedCurriculum.value && rows.length)
    selectCurriculum(rows[0].id)
}, { immediate: true })

onMounted(() => {
  fetchGamesList()
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
    </div>

    <!-- Notification Alert -->
    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="المناهج التدريبية"
      icon="tabler-books"
      search-placeholder="ابحث بالاسم أو الرمز…"
      add-label="إنشاء منهج جديد"
      empty-text="لا توجد مناهج تدريبية بعد."
      empty-icon="tabler-book-off"
      class="mb-6"
      @add="openAddCurriculumDialog"
    >
      <template #item.name="{ item }">
        <div
          class="d-flex align-center gap-3 cursor-pointer"
          @click="selectCurriculum(item.id)"
        >
          <VAvatar
            :color="selectedCurriculum?.id === item.id ? 'primary' : 'secondary'"
            variant="tonal"
            size="36"
            rounded
          >
            <VIcon icon="tabler-book" size="20" />
          </VAvatar>
          <span
            class="font-weight-bold"
            :class="selectedCurriculum?.id === item.id ? 'text-primary' : 'text-high-emphasis'"
          >{{ item.name }}</span>
        </div>
      </template>

      <template #item.code="{ item }">
        <span class="text-caption text-muted" dir="ltr">{{ item.code }}</span>
      </template>

      <template #item.status="{ item }">
        <VChip
          size="small"
          :color="statusColor(CURRICULUM_STATUS, item.status)"
          variant="tonal"
        >
          {{ statusLabel(CURRICULUM_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.months_count="{ item }">
        <VChip size="small" color="info" variant="tonal">
          {{ item.months_count ?? 0 }} شهر
        </VChip>
      </template>

      <template #item.version="{ item }">
        <span class="text-caption">v{{ item.version }}</span>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            :color="selectedCurriculum?.id === item.id ? 'primary' : 'secondary'"
            variant="tonal"
            @click="selectCurriculum(item.id)"
          >
            <VIcon icon="tabler-sitemap" size="18" />
            <VTooltip activator="parent" location="top">
              استعراض الهيكل
            </VTooltip>
          </VBtn>
          <VBtn
            icon
            size="small"
            color="warning"
            variant="text"
            @click="openEditCurriculumDialog(item)"
          >
            <VIcon icon="tabler-edit" size="18" />
            <VTooltip activator="parent" location="top">
              تعديل
            </VTooltip>
          </VBtn>
          <VBtn
            v-if="item.status !== 'PUBLISHED'"
            icon
            size="small"
            color="success"
            variant="text"
            @click="publishCurriculum(item.id)"
          >
            <VIcon icon="tabler-send" size="18" />
            <VTooltip activator="parent" location="top">
              نشر
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <VRow>
      <VCol cols="12">
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
              <div class="d-flex align-center gap-2">
                <VBtn
                  color="info"
                  variant="tonal"
                  prepend-icon="tabler-calendar-plus"
                  @click="openAddMonth"
                >
                  إضافة شهر
                </VBtn>

                <VBtn
                  color="warning"
                  variant="tonal"
                  prepend-icon="tabler-edit"
                  @click="openEditCurriculumDialog(selectedCurriculum)"
                >
                  تعديل
                </VBtn>
                
                <VBtn
                  v-if="selectedCurriculum.status !== 'PUBLISHED'"
                  color="success"
                  prepend-icon="tabler-send"
                  @click="publishCurriculum(selectedCurriculum.id)"
                >
                  نشر
                </VBtn>
                <VChip
                  v-else
                  color="success"
                  variant="flat"
                  prepend-icon="tabler-check"
                >
                  منشور
                </VChip>
              </div>
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
                    <div class="d-flex align-center justify-space-between w-100 me-6">
                      <div>
                        <VIcon icon="tabler-calendar" class="me-2" />
                        {{ month.name }} ({{ month.weeks?.length || 0 }} أسابيع)
                      </div>
                      <VBtn
                        size="x-small"
                        color="primary"
                        variant="flat"
                        prepend-icon="tabler-plus"
                        @click.stop="openAddWeek(month.id, month.weeks?.length || 0)"
                      >
                        إضافة أسبوع
                      </VBtn>
                    </div>
                  </VExpansionPanelTitle>

                  <VExpansionPanelText>
                    <div
                      v-for="week in month.weeks"
                      :key="week.id"
                      class="mb-4 bg-background pa-4 rounded border"
                    >
                      <div class="font-weight-bold text-subtitle-1 mb-3 d-flex align-center justify-space-between flex-wrap gap-2">
                        <div class="d-flex align-center gap-2">
                          <VIcon icon="tabler-calendar-week" color="info" size="18" />
                          {{ week.name }}
                        </div>
                        <VBtn
                          size="x-small"
                          color="info"
                          variant="tonal"
                          prepend-icon="tabler-plus"
                          @click="openAddDay(week.id, week.days?.length || 0)"
                        >
                          إضافة يوم
                        </VBtn>
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
                              <div class="d-flex align-center gap-1">
                                <span class="font-weight-bold">{{ day.name }}</span>
                                <VBtn
                                  icon="tabler-edit"
                                  size="x-small"
                                  color="warning"
                                  variant="text"
                                  density="compact"
                                  @click.stop="openEditDay(day)"
                                />
                                <VBtn
                                  icon="tabler-trash"
                                  size="x-small"
                                  color="error"
                                  variant="text"
                                  density="compact"
                                  @click.stop="handleDeleteDay(day)"
                                />
                              </div>
                              <VChip size="x-small" color="secondary">{{ day.estimated_duration_seconds / 60 }} دقيقة</VChip>
                            </div>

                            <div class="d-flex justify-space-between align-center text-caption text-muted mb-2">
                              <span>الألعاب التدريبية ({{ day.games?.length || 0 }}):</span>
                              <VBtn
                                size="x-small"
                                color="success"
                                variant="text"
                                prepend-icon="tabler-plus"
                                density="compact"
                                @click="openAttachGame(day.id)"
                              >
                                ربط لعبة
                              </VBtn>
                            </div>

                            <div class="d-flex flex-column gap-1">
                              <div
                                v-for="g in day.games"
                                :key="g.id"
                                class="d-flex align-center justify-space-between bg-surface pa-2 rounded text-caption border"
                              >
                                <span>
                                  <strong>{{ g.game_code || g.code }}</strong> - {{ g.game_name || g.name }}
                                </span>
                                <div class="d-flex align-center gap-1">
                                  <VChip size="x-small" color="primary">{{ g.game_type || g.type }}</VChip>
                                  <VBtn
                                    icon="tabler-trash"
                                    size="x-small"
                                    color="error"
                                    variant="text"
                                    density="compact"
                                    @click="handleDetachGame(day.id, g)"
                                  />
                                </div>
                              </div>
                              <div v-if="!day.games || day.games.length === 0" class="text-caption text-center text-muted py-2">
                                لا توجد ألعاب مربوطة بهذا اليوم.
                              </div>
                            </div>
                          </VCard>
                        </VCol>
                        <VCol v-if="!week.days || week.days.length === 0" cols="12" class="text-center text-caption text-muted py-2">
                          لا توجد أيام مضافة بعد في هذا الأسبوع.
                        </VCol>
                      </VRow>
                    </div>
                    <div v-if="!month.weeks || month.weeks.length === 0" class="text-center text-muted py-4">
                      لا توجد أسابيع مضافة في هذا الشهر.
                    </div>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
            </div>

            <div v-else class="text-center py-8 text-muted">
              لا توجد أشهر أو أسابيع مبرمجة في هذا المنهج بعد. اضغط على "إضافة شهر" لبدء البناء.
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Create/Edit Curriculum Dialog -->
    <VDialog v-model="isAddCurriculumDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingCurriculumId ? 'تعديل بيانات المنهج التدريبي' : 'إنشاء منهج تدريبي جديد' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="newCurriculum.code"
            label="رمز المنهج (كود فريد)"
            readonly
            dir="ltr"
            hint="يُولَّد تلقائياً"
            persistent-hint
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

    <!-- Add Month Dialog -->
    <VDialog v-model="isAddMonthDialogVisible" max-width="400">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة شهر جديد للمنهج</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="monthForm.name"
            label="اسم الشهر"
            placeholder="مثال: الشهر الأول"
            class="mb-4"
          />
          <VTextField
            v-model.number="monthForm.sort_order"
            type="number"
            label="الترتيب"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddMonthDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveMonth">حفظ</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add Week Dialog -->
    <VDialog v-model="isAddWeekDialogVisible" max-width="400">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة أسبوع جديد للشهر</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="weekForm.name"
            label="اسم الأسبوع"
            placeholder="مثال: الأسبوع الأول"
            class="mb-4"
          />
          <VTextField
            v-model.number="weekForm.sort_order"
            type="number"
            label="الترتيب"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddWeekDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveWeek">حفظ الأسبوع</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Add Day Dialog -->
    <VDialog v-model="isAddDayDialogVisible" max-width="450">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingDayId ? 'تعديل بيانات اليوم' : 'إضافة يوم جديد للأسبوع' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VTextField
            v-model="dayForm.name"
            label="اسم اليوم"
            placeholder="مثال: اليوم الأول"
            class="mb-4"
          />
          <VTextField
            v-model.number="dayForm.estimated_duration_seconds"
            type="number"
            label="المدة المقدرة (بالثواني)"
            placeholder="مثال: 600"
            class="mb-4"
          />
          <VTextField
            v-model.number="dayForm.sort_order"
            type="number"
            label="الترتيب"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddDayDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveDay">حفظ اليوم</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Attach Game Dialog -->
    <VDialog v-model="isAttachGameDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">ربط ألعاب تدريبية باليوم</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VSelect
            v-model="attachGameForm.game_ids"
            :items="gamesList"
            item-title="name"
            item-value="id"
            label="اختر الألعاب التدريبية"
            multiple
            chips
            closable-chips
            class="mb-4"
            no-data-text="لا توجد ألعاب متوفرة"
          >
            <template #item="{ props: itemProps, item }">
              <VListItem v-bind="itemProps" :subtitle="`[${item.raw.code}] - ${item.raw.type}`" />
            </template>
          </VSelect>
          <VTextField
            v-model.number="attachGameForm.sort_order"
            type="number"
            label="ترتيب البدء"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAttachGameDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveAttachGame">ربط اللعبة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm Delete Day Modal -->
    <ConfirmDeleteDialog
      v-model="isConfirmDeleteDayVisible"
      title="تأكيد حذف اليوم"
      :item-name="pendingDeleteDay?.name"
      message="سيتم حذف هذا اليوم وكل الألعاب المرتبطة به نهائياً. هل أنت متأكد؟"
      :loading="isDeletingDay"
      @confirm="confirmDeleteDay"
    />

    <!-- Confirm Detach Game Modal -->
    <ConfirmDeleteDialog
      v-model="isConfirmDetachGameVisible"
      title="تأكيد إلغاء ربط اللعبة"
      :item-name="pendingDetachGame?.name"
      message="سيتم إلغاء ربط هذه اللعبة من اليوم الحالي. هل أنت متأكد؟"
      confirm-label="نعم، ألغِ الربط"
      :loading="isDetachingGame"
      @confirm="confirmDetachGame"
    />
  </div>
</template>

