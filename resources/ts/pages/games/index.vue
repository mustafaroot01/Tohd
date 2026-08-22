<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const axes = ref<any[]>([])
const skills = ref<any[]>([])
const levels = ref<any[]>([])
const isAddGameDialogVisible = ref(false)
const isSubmitting = ref(false)
const isArchiving = ref(false)
const { notification, notifySuccess, notifyInfo, notifyWarning, notifyError } = useNotification()
const confirmArchive = ref(false)
const pendingArchiveGame = ref<any | null>(null)

// Edit Game State
const editingGameId = ref<string | null>(null)

const newGame = ref({
  code: '',
  name: '',
  description: '',
  type: 'TAP',
  axis_id: '',
  skill_id: '',
  level_id: '',
  level: 1,
  difficulty: 'easy',
  min_age: 3,
  max_age: 8,
  duration_seconds: 60,
  config: {
    attempts: 10,
    success_threshold: 0.8,
    time_limit_seconds: 60,
  },
  selectedAssetIds: [] as string[], // الملفات المتحركة المختارة
})

const generateGameCode = () => {
  const random = Array.from({ length: 6 }, () => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 36)]).join('')
  return `GAME-${random}`
}

const openAddGameDialog = async () => {
  editingGameId.value = null
  newGame.value = {
    code: generateGameCode(),
    name: '',
    description: '',
    type: 'TAP',
    axis_id: '',
    skill_id: '',
    level_id: '',
    level: 1,
    difficulty: 'easy',
    min_age: 3,
    max_age: 8,
    duration_seconds: 60,
    config: {
      attempts: 10,
      success_threshold: 0.8,
      time_limit_seconds: 60,
    },
    selectedAssetIds: []
  }
  await loadAvailableLottie()
  isAddGameDialogVisible.value = true
}

const openEditGameDialog = async (game: any) => {
  editingGameId.value = game.id
  newGame.value = {
    code: game.code,
    name: game.name,
    description: game.description || '',
    type: game.type,
    axis_id: game.axis_id,
    skill_id: game.skill_id,
    level_id: game.level_id || '',
    level: game.level,
    difficulty: game.difficulty,
    min_age: game.min_age,
    max_age: game.max_age,
    duration_seconds: game.duration_seconds,
    config: game.config ? { ...game.config } : { attempts: 10, success_threshold: 0.8, time_limit_seconds: 60 },
    selectedAssetIds: [],
  }
  // Load current assets for this game
  try {
    const res = await $api(`/admin/games/${game.id}/assets`)
    if (res?.success) {
      newGame.value.selectedAssetIds = res.data.map((a: any) => a.id)
    }
  } catch (e) {}
  await loadAvailableLottie()
  isAddGameDialogVisible.value = true
}

const gameTypes = [
  { value: 'TAP', title: 'نقر مباشر (TAP)' },
  { value: 'CHOOSE', title: 'اختيار من متعدد (CHOOSE)' },
  { value: 'MATCH', title: 'مطابقة (MATCH)' },
  { value: 'TRACKING', title: 'تتبع بصري (TRACKING)' },
  { value: 'MEMORY', title: 'ذاكرة واسترجاع (MEMORY)' },
  { value: 'EMOTION', title: 'تمييز المشاعر (EMOTION)' },
  { value: 'SEQUENCE', title: 'تسلسل منطقي (SEQUENCE)' },
  { value: 'DRAG_DROP', title: 'سحب وإفلات (DRAG_DROP)' },
  { value: 'ORDER', title: 'ترتيب (ORDER)' },
]

const table = useServerTable('/admin/games', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null, type: null, axis_id: null, difficulty: null },
})

const headers = computed<DataTableHeader[]>(() => [
  { title: 'كود اللعبة', key: 'code', sortable: true },
  { title: 'اسم اللعبة', key: 'name', sortable: true, hideable: false },
  {
    title: 'النوع',
    key: 'type',
    sortable: true,
    filter: { anyLabel: 'كل الأنواع', options: gameTypes.map(t => ({ title: t.title, value: t.value })) },
  },
  {
    title: 'المحور',
    key: 'axis',
    filter: {
      key: 'axis_id',
      anyLabel: 'كل المحاور',
      options: axes.value.map(a => ({ title: a.name, value: a.id })),
    },
  },
  {
    title: 'المستوى والمدة',
    key: 'difficulty',
    sortable: true,
    filter: { anyLabel: 'كل الصعوبات', options: statusOptions(GAME_DIFFICULTY) },
  },
  {
    title: 'الحالة',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(GAME_STATUS) },
  },
  { title: 'الإجراءات ودورة النشر', key: 'actions', align: 'center', hideable: false },
])

/** Reference lists used by the filters and the create/edit dialog. */
const fetchReferenceData = async () => {
  try {
    const [axesRes, skillsRes, levelsRes] = await Promise.all([
      $api('/admin/axes', { query: { per_page: 100 } }),
      $api('/admin/skills', { query: { per_page: 100 } }),
      $api('/admin/levels', { query: { all: true } }),
    ])

    if (axesRes?.success)
      axes.value = axesRes.data
    if (skillsRes?.success)
      skills.value = skillsRes.data
    if (levelsRes?.success)
      levels.value = levelsRes.data
  }
  catch (err) {
    console.error(err)
  }
}

const saveGame = async () => {
  if (!newGame.value.name || !newGame.value.code || !newGame.value.axis_id || !newGame.value.skill_id || !newGame.value.level_id) {
    notifyWarning('يرجى إدخال جميع الحقول المطلوبة (اسم اللعبة، الكود، المحور، المهارة، والمستوى)')
    return
  }

  isSubmitting.value = true
  try {
    const isEdit = !!editingGameId.value
    const url = isEdit ? `/admin/games/${editingGameId.value}` : '/admin/games'
    const method = isEdit ? 'PUT' : 'POST'
    
    // Map selected asset IDs to matching backend asset relationship payload
    const payload = {
      ...newGame.value,
      assets: newGame.value.selectedAssetIds.map(assetId => ({
        asset_id: assetId,
        role: 'ANIMATION',
        sort_order: 0
      }))
    }

    const res = await $api(url, {
      method,
      body: payload,
    })
    if (res?.success) {
      isAddGameDialogVisible.value = false
      notifySuccess(isEdit ? 'تم تحديث بيانات اللعبة بنجاح' : 'تمت إضافة اللعبة بنجاح كمسودة')
      await table.reload()
    }
  }

  catch (err: any) {
    notifyError(err, 'فشل حفظ اللعبة')
  }

  finally {
    isSubmitting.value = false
  }
}

const publishGame = async (game: any) => {
  try {
    const res = await $api(`/admin/games/${game.id}/publish`, { method: 'POST' })
    if (res?.success) {
      notifySuccess(`تم نشر لعبة (${game.name}) بنجاح!`)
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل نشر اللعبة')
  }
}

const approveGame = async (game: any) => {
  try {
    const res = await $api(`/admin/games/${game.id}/approve`, { method: 'POST' })
    if (res?.success) {
      notifySuccess(`تم اعتماد لعبة (${game.name}) بنجاح`)
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل اعتماد اللعبة')
  }
}

const requestArchiveGame = (game: any) => {
  pendingArchiveGame.value = game
  confirmArchive.value = true
}

const archiveGame = async () => {
  if (!pendingArchiveGame.value) return
  isArchiving.value = true
  try {
    const res = await $api(`/admin/games/${pendingArchiveGame.value.id}/archive`, { method: 'POST' })
    if (res?.success) {
      notifyInfo(`تمت أرشفة لعبة (‎${pendingArchiveGame.value.name}‎)`)
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل أرشفة اللعبة')
  }
  finally {
    isArchiving.value = false
    confirmArchive.value = false
    pendingArchiveGame.value = null
  }
}

onMounted(() => {
  fetchReferenceData()
})

// ─────────────────────────────────────────
// Lottie Preview Logic (معاينة اللعبة التفاعلية)
// ─────────────────────────────────────────
const availableLottieAssets = ref<any[]>([])
const isPreviewDialogVisible = ref(false)
const previewFileUrl = ref('')
const previewFileName = ref('')
const previewFileType = ref('LOTTIE')

const loadAvailableLottie = async () => {
  try {
    const res = await $api('/admin/assets?type=LOTTIE&per_page=100')
    if (res?.success) availableLottieAssets.value = res.data
  }
  catch (e) { console.error(e) }
}

const previewGameAnimation = (game: any) => {
  const lottieAsset = game.assets?.find((a: any) => a.type === 'LOTTIE') || game.assets?.[0]
  if (lottieAsset) {
    previewFileUrl.value = lottieAsset.url
    previewFileName.value = `${game.name} - ${lottieAsset.name}`
    previewFileType.value = lottieAsset.type
    isPreviewDialogVisible.value = true
  } else {
    notifyWarning('لا يوجد ملف متحرك مرتبط بهذه اللعبة حالياً لمعاينته')
  }
}

// Auto-fill age limits when Level is selected (Not needed, backend handles age limits sync)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          الألعاب والأنشطة التفاعلية 🎮
        </h2>
        <p class="text-muted mb-0">
          إدارة مستودع الألعاب، تهيئة البارامترات، واختبار واعتماد الألعاب للنشر في المناهج
        </p>
      </div>
    </div>

    <!-- Notification Alert -->
    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="مستودع الألعاب"
      icon="tabler-device-gamepad"
      search-placeholder="ابحث بالاسم أو الكود…"
      add-label="إضافة لعبة جديدة"
      empty-text="لا توجد ألعاب مسجلة في النظام بعد."
      empty-icon="tabler-device-gamepad-off"
      @add="openAddGameDialog"
    >
      <template #item.code="{ item }">
        <span class="font-weight-bold text-primary text-caption" dir="ltr">{{ item.code }}</span>
      </template>

      <template #item.name="{ item }">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="36" rounded>
            <VIcon icon="tabler-device-gamepad" size="20" />
          </VAvatar>
          <div>
            <div class="font-weight-bold text-high-emphasis">
              {{ item.name }}
            </div>
            <div
              v-if="item.description"
              class="text-caption text-muted text-truncate"
              style="max-inline-size: 16rem;"
            >
              {{ item.description }}
            </div>
          </div>
        </div>
      </template>

      <template #item.type="{ item }">
        <VChip size="x-small" color="primary" variant="tonal">
          {{ item.type }}
        </VChip>
      </template>

      <template #item.axis="{ item }">
        <span class="text-body-2">{{ item.axis?.name || '—' }}</span>
      </template>

      <template #item.difficulty="{ item }">
        <div class="text-caption font-weight-medium">
          {{ item.game_level?.name || `مستوى ${item.level}` }} ({{ item.difficulty }})
        </div>
        <div class="text-caption text-muted">
          {{ item.duration_seconds }} ثانية
        </div>
      </template>

      <template #item.status="{ item }">
        <VChip
          size="small"
          :color="statusColor(GAME_STATUS, item.status)"
          variant="tonal"
          class="font-weight-medium"
        >
          {{ statusLabel(GAME_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            color="purple"
            variant="tonal"
            @click="previewGameAnimation(item)"
          >
            <VIcon icon="tabler-device-gamepad-2" size="18" />
            <VTooltip activator="parent" location="top">
              معاينة اللعبة
            </VTooltip>
          </VBtn>

          <VBtn
            icon
            size="small"
            color="warning"
            variant="tonal"
            @click="openEditGameDialog(item)"
          >
            <VIcon icon="tabler-edit" size="18" />
            <VTooltip activator="parent" location="top">
              تعديل
            </VTooltip>
          </VBtn>

          <VBtn
            v-if="item.status === 'DRAFT' || item.status === 'TESTING'"
            icon
            size="small"
            color="info"
            variant="tonal"
            @click="approveGame(item)"
          >
            <VIcon icon="tabler-check" size="18" />
            <VTooltip activator="parent" location="top">
              اعتماد اللعبة
            </VTooltip>
          </VBtn>

          <VBtn
            v-if="item.status !== 'PUBLISHED'"
            icon
            size="small"
            color="success"
            variant="tonal"
            @click="publishGame(item)"
          >
            <VIcon icon="tabler-send" size="18" />
            <VTooltip activator="parent" location="top">
              نشر اللعبة
            </VTooltip>
          </VBtn>

          <VBtn
            v-if="item.status === 'PUBLISHED'"
            icon
            size="small"
            color="secondary"
            variant="tonal"
            @click="requestArchiveGame(item)"
          >
            <VIcon icon="tabler-archive" size="18" />
            <VTooltip activator="parent" location="top">
              أرشفة
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Add/Edit Game Dialog -->
    <VDialog v-model="isAddGameDialogVisible" max-width="700">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingGameId ? 'تعديل بيانات اللعبة التدريبية' : 'إضافة لعبة تدريبية جديدة' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <!-- Inner Dialog Alert -->
          <VAlert
            v-if="notification"
            :color="notification.color"
            variant="tonal"
            class="mb-4"
            closable
            @click:close="notification = null"
          >
            {{ notification.text }}
          </VAlert>

          <VRow>
            <VCol cols="12" sm="4">
              <VTextField
                v-model="newGame.code"
                label="كود اللعبة (فريد)"
                readonly
                dir="ltr"
                hint="يُولَّد تلقائياً"
                persistent-hint
              />
            </VCol>
            <VCol cols="12" sm="8">
              <VTextField
                v-model="newGame.name"
                label="اسم اللعبة"
                placeholder="مثال: قطف الفواكه اللامعة"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="newGame.description"
                label="وصف اللعبة والتعليمات"
                rows="2"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VSelect
                v-model="newGame.type"
                :items="gameTypes"
                label="نوع التفاعل"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VSelect
                v-model="newGame.axis_id"
                :items="axes"
                item-title="name"
                item-value="id"
                label="المحور التدريبي"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VSelect
                v-model="newGame.skill_id"
                :items="skills.filter(s => s.axis_id === newGame.axis_id)"
                item-title="name"
                item-value="id"
                label="المهارة المستهدفة"
              />
            </VCol>

            <!-- 👉 Level selection dropdown -->
            <VCol cols="12" sm="4">
              <VSelect
                v-model="newGame.level_id"
                :items="levels"
                item-title="name"
                item-value="id"
                label="المستوى التدريبي"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VSelect
                v-model="newGame.difficulty"
                :items="['easy', 'medium', 'hard']"
                label="مستوى الصعوبة"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VTextField
                v-model.number="newGame.config.attempts"
                type="number"
                label="عدد المحاولات"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VTextField
                v-model.number="newGame.config.success_threshold"
                type="number"
                step="0.05"
                label="معيار النجاح (0.1 - 1.0)"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <VTextField
                v-model.number="newGame.duration_seconds"
                type="number"
                label="المدة بالثواني"
              />
            </VCol>
            <!-- 👉 Lottie file selection -->
            <VCol cols="12">
              <VSelect
                v-model="newGame.selectedAssetIds"
                :items="availableLottieAssets"
                item-title="name"
                item-value="id"
                label="رسوم Lottie المتحركة المرتبطة"
                placeholder="اختر ملف Lottie..."
                multiple
                chips
                closable-chips
              >
                <template #item="{ item, props }">
                  <VListItem v-bind="props">
                    <template #prepend>
                      <VIcon icon="tabler-file-3d" color="purple" size="18" class="me-2" />
                    </template>
                    <VListItemSubtitle>{{ item.raw.code }}</VListItemSubtitle>
                  </VListItem>
                </template>
              </VSelect>
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddGameDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveGame">حفظ اللعبة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    <!-- Confirm Archive Modal -->
    <ConfirmDeleteDialog
      v-model="confirmArchive"
      title="تأكيد أرشفة اللعبة"
      :item-name="pendingArchiveGame?.name"
      message="سيتم سحب هذه اللعبة من النشر وأرشفتها. هل أنت متأكد؟"
      confirm-label="نعم، أرشف اللعبة"
      :loading="isArchiving"
      @confirm="archiveGame"
    />
    <!-- ─────── File Preview Dialog ─────── -->
    <FilePreviewDialog
      v-model="isPreviewDialogVisible"
      :file-url="previewFileUrl"
      :file-name="previewFileName"
      :file-type="previewFileType"
    />
  </div>
</template>
