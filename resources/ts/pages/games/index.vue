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

/**
 * The form speaks the admin's units, not the API's:
 *
 *   duration_value + unit → duration_seconds  (×60 when the unit is minutes)
 *   success_score /10  → success_threshold  (÷10, stored as 0.1–1.0)
 *   unlimited attempts → config.attempts = 0
 *
 * Interaction type and difficulty are no longer chosen here — the API defaults
 * them to TAP and easy.
 */
const UNLIMITED_ATTEMPTS = 0

type DurationUnit = 'minutes' | 'seconds'

/** The API stores seconds; the form shows whichever unit reads cleanly. */
const splitDuration = (seconds: number) => (seconds % 60 === 0 && seconds >= 60
  ? { duration_value: seconds / 60, duration_unit: 'minutes' as DurationUnit }
  : { duration_value: seconds, duration_unit: 'seconds' as DurationUnit })

const toSeconds = (value: number, unit: DurationUnit) =>
  Math.round(unit === 'minutes' ? value * 60 : value)

const newGame = ref({
  code: '',
  name: '',
  description: '',
  axis_id: '',
  skill_id: '',
  level_id: '',
  level: 1,
  min_age: 3,
  max_age: 8,
  duration_value: 1,
  duration_unit: 'minutes' as DurationUnit,
  success_score: 8,
  is_unlimited_attempts: false,
  attempts: 10,
  selectedAssetIds: [] as string[], // الملفات المتحركة المختارة
  startAudioId: null as string | null, // صوت بدء اللعبة
  endAudioId: null as string | null, // صوت نهاية اللعبة
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
    axis_id: '',
    skill_id: '',
    level_id: '',
    level: 1,
    min_age: 3,
    max_age: 8,
    duration_value: 1,
    duration_unit: 'minutes' as DurationUnit,
    success_score: 8,
    is_unlimited_attempts: false,
    attempts: 10,
    selectedAssetIds: [],
    startAudioId: null,
    endAudioId: null,
  }
  await loadAvailableLottie()
  isAddGameDialogVisible.value = true
}

const openEditGameDialog = async (game: any) => {
  editingGameId.value = game.id
  // convert the stored API units back into the ones the form shows
  const storedAttempts = Number(game.config?.attempts ?? 10)

  newGame.value = {
    code: game.code,
    name: game.name,
    description: game.description || '',
    axis_id: game.axis_id,
    skill_id: game.skill_id,
    level_id: game.level_id || '',
    level: game.level,
    min_age: game.min_age,
    max_age: game.max_age,
    ...splitDuration(game.duration_seconds ?? 60),
    success_score: Math.round((game.config?.success_threshold ?? 0.8) * 10),
    is_unlimited_attempts: storedAttempts === UNLIMITED_ATTEMPTS,
    attempts: storedAttempts === UNLIMITED_ATTEMPTS ? 10 : storedAttempts,
    selectedAssetIds: [],
    startAudioId: null,
    endAudioId: null,
  }
  // Load current assets for this game
  try {
    const res = await $api(`/admin/games/${game.id}/assets`)
    if (res?.success) {
      const linked = res.data as any[]

      newGame.value.selectedAssetIds = linked.filter(a => a.role === 'ANIMATION').map(a => a.id)
      newGame.value.startAudioId = linked.find(a => a.role === 'START_AUDIO')?.id ?? null
      newGame.value.endAudioId = linked.find(a => a.role === 'END_AUDIO')?.id ?? null
    }
  } catch (e) {}
  await loadAvailableLottie()
  isAddGameDialogVisible.value = true
}

/** The API accepts 10–3600 seconds; express that window in the chosen unit. */
const durationBounds = computed(() => (newGame.value.duration_unit === 'minutes'
  ? { min: 1, max: 60 }
  : { min: 10, max: 3600 }))

/**
 * Switching the unit preserves the actual duration: 3 دقائق becomes 180 ثانية,
 * not 3 ثوانٍ. The value is then clamped into the new unit's window.
 */
const changeDurationUnit = (unit: DurationUnit) => {
  if (!unit || unit === newGame.value.duration_unit)
    return

  const seconds = toSeconds(newGame.value.duration_value || 0, newGame.value.duration_unit)

  newGame.value.duration_unit = unit

  const raw = unit === 'minutes' ? Math.round(seconds / 60) : seconds
  const { min, max } = unit === 'minutes' ? { min: 1, max: 60 } : { min: 10, max: 3600 }

  newGame.value.duration_value = Math.min(max, Math.max(min, raw))
}

/**
 * The grade is attention held over attention asked for, so this field really
 * says "how much of the game the child must stay with". Spelling that out in
 * seconds keeps the two settings from being tuned in the dark.
 */
const successHint = computed(() => {
  const total = toSeconds(newGame.value.duration_value || 0, newGame.value.duration_unit)
  const needed = Math.round((newGame.value.success_score / 10) * total)

  if (!total)
    return 'كم عُشراً من مدة اللعبة يجب أن ينتبه الطفل'

  return `ينتبه ${needed} ثانية على الأقل من أصل ${total} — أي ${newGame.value.success_score * 10}% من مدة اللعبة`
})

const durationHint = computed(() => {
  const seconds = toSeconds(newGame.value.duration_value || 0, newGame.value.duration_unit)

  if (newGame.value.duration_unit === 'minutes')
    return `من 1 إلى 60 دقيقة — تُحفظ كـ ${seconds} ثانية`

  return `من 10 إلى 3600 ثانية${seconds >= 60 ? ` — أي ${(seconds / 60).toFixed(1)} دقيقة` : ''}`
})

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
    
    const form = newGame.value
    const durationSeconds = toSeconds(form.duration_value, form.duration_unit)

    // translate the admin's units back into what the API expects
    const payload = {
      code: form.code,
      name: form.name,
      description: form.description,
      axis_id: form.axis_id,
      skill_id: form.skill_id,
      level_id: form.level_id,
      min_age: form.min_age,
      max_age: form.max_age,
      duration_seconds: durationSeconds,
      config: {
        attempts: form.is_unlimited_attempts ? UNLIMITED_ATTEMPTS : form.attempts,
        success_threshold: form.success_score / 10,
        time_limit_seconds: durationSeconds,
      },
      assets: [
        ...form.selectedAssetIds.map((assetId, i) => ({
          asset_id: assetId,
          role: 'ANIMATION',
          sort_order: i,
        })),
        ...(form.startAudioId ? [{ asset_id: form.startAudioId, role: 'START_AUDIO', sort_order: 0 }] : []),
        ...(form.endAudioId ? [{ asset_id: form.endAudioId, role: 'END_AUDIO', sort_order: 0 }] : []),
      ],
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

const lottieSearch = ref('')
const isLoadingLottie = ref(false)

/** Which kinds of file the main-visual search shows; 'ALL' = Lottie, video and image together. */
const VISUAL_TYPES = ['LOTTIE', 'VIDEO', 'IMAGE'] as const
const visualFilter = ref<'ALL' | 'LOTTIE' | 'VIDEO' | 'IMAGE'>('ALL')

const visualIcon = (type: string) =>
  type === 'LOTTIE' ? 'tabler-animation' : type === 'VIDEO' ? 'tabler-video' : 'tabler-photo'

const visualColor = (type: string) =>
  type === 'LOTTIE' ? 'warning' : type === 'VIDEO' ? 'info' : 'success'

/**
 * Searches the server rather than preloading a fixed slice — the old call took
 * the first 100 rows, so anything past that was unreachable from the dialog.
 * Already-selected files are kept in the list so their chips keep their names.
 */
const loadAvailableLottie = async (term?: string) => {
  isLoadingLottie.value = true
  try {
    const query: Record<string, any> = {
      // a comma list, not an array: repeated query keys reach PHP as the last value only
      type: visualFilter.value === 'ALL' ? VISUAL_TYPES.join(',') : visualFilter.value,
      per_page: 25,
    }
    if (term)
      query.search = term

    const res = await $api('/admin/assets', { query })
    if (res?.success) {
      const rows = res.data as any[]
      const chosen = availableLottieAssets.value.filter(
        a => newGame.value.selectedAssetIds.includes(a.id) && !rows.some(r => r.id === a.id),
      )

      availableLottieAssets.value = [...chosen, ...rows]
    }
  }
  catch (e) {
    console.error(e)
  }
  finally {
    isLoadingLottie.value = false
  }
}

watchDebounced(lottieSearch, term => loadAvailableLottie(term || undefined), { debounce: 350 })
watch(visualFilter, () => loadAvailableLottie(lottieSearch.value || undefined))

const previewGame = ref<any | null>(null)

const previewGameAnimation = (game: any) => {
  if (!game.assets?.length) {
    notifyWarning('لا توجد ملفات مرتبطة بهذه اللعبة — أضف الرسوم والأصوات من حوار التعديل')

    return
  }

  previewGame.value = game
  isPreviewDialogVisible.value = true
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
    <VDialog
      v-model="isAddGameDialogVisible"
      :width="$vuetify.display.smAndDown ? 'auto' : 900"
      scrollable
    >
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn @click="isAddGameDialogVisible = false" />

      <VCard class="pa-sm-10 pa-2">
        <VCardText>
          <!-- 👉 Title -->
          <h4 class="text-h4 text-center mb-2">
            {{ editingGameId ? 'تعديل بيانات اللعبة' : 'إضافة لعبة جديدة' }}
          </h4>
          <p class="text-body-1 text-center mb-6">
            {{ editingGameId ? 'عدّل بيانات اللعبة وإعداداتها وملفاتها.' : 'حدّد بيانات اللعبة، ثم معيار النجاح ومدتها، ثم ملفاتها من مكتبة الوسائط.' }}
          </p>

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

          <!-- 👉 Form -->
          <VForm
            class="mt-6"
            @submit.prevent="saveGame"
          >
            <VRow>
              <!-- 👉 Basic info -->
              <VCol cols="12">
                <h6 class="text-h6">
                  البيانات الأساسية
                </h6>
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="newGame.code"
                  label="كود اللعبة"
                  placeholder="يُولَّد تلقائياً"
                  readonly
                  dir="ltr"
                />
              </VCol>

              <VCol
                cols="12"
                md="8"
              >
                <AppTextField
                  v-model="newGame.name"
                  label="اسم اللعبة"
                  placeholder="مثال: قطف الفواكه اللامعة"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="newGame.description"
                  label="وصف اللعبة والتعليمات"
                  placeholder="ما الذي سيراه الطفل، وماذا نطلب منه"
                  rows="2"
                  auto-grow
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="newGame.axis_id"
                  :items="axes"
                  item-title="name"
                  item-value="id"
                  label="المحور التدريبي"
                  placeholder="اختر المحور"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="newGame.skill_id"
                  :items="skills.filter(s => s.axis_id === newGame.axis_id)"
                  item-title="name"
                  item-value="id"
                  label="المهارة المستهدفة"
                  placeholder="اختر المهارة"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="newGame.level_id"
                  :items="levels"
                  item-title="name"
                  item-value="id"
                  label="المستوى التدريبي"
                  placeholder="اختر المستوى"
                />
              </VCol>

              <!-- 👉 Rules -->
              <VCol cols="12">
                <VDivider class="my-2" />
                <h6 class="text-h6 mt-4">
                  قواعد اللعب
                </h6>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model.number="newGame.success_score"
                  type="number"
                  min="1"
                  max="10"
                  label="معيار النجاح (من 10)"
                  placeholder="8"
                  :hint="successHint"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model.number="newGame.attempts"
                  type="number"
                  min="1"
                  max="100"
                  label="عدد المحاولات"
                  placeholder="10"
                  :disabled="newGame.is_unlimited_attempts"
                  :hint="newGame.is_unlimited_attempts ? 'المحاولات غير محدودة' : 'من 1 إلى 100 محاولة'"
                  persistent-hint
                >
                  <template #append>
                    <VSwitch
                      v-model="newGame.is_unlimited_attempts"
                      label="غير محدودة"
                      density="compact"
                      color="primary"
                      hide-details
                    />
                  </template>
                </AppTextField>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VLabel class="mb-1 text-body-2 text-high-emphasis">
                  وحدة المدة
                </VLabel>
                <VBtnToggle
                  :model-value="newGame.duration_unit"
                  mandatory
                  density="comfortable"
                  variant="outlined"
                  divided
                  color="primary"
                  class="d-flex"
                  @update:model-value="changeDurationUnit"
                >
                  <VBtn
                    value="minutes"
                    prepend-icon="tabler-clock-hour-3"
                    class="flex-grow-1"
                  >
                    دقائق
                  </VBtn>
                  <VBtn
                    value="seconds"
                    prepend-icon="tabler-stopwatch"
                    class="flex-grow-1"
                  >
                    ثواني
                  </VBtn>
                </VBtnToggle>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model.number="newGame.duration_value"
                  type="number"
                  :min="durationBounds.min"
                  :max="durationBounds.max"
                  label="مدة اللعبة"
                  :suffix="newGame.duration_unit === 'minutes' ? 'دقيقة' : 'ثانية'"
                  :placeholder="String(durationBounds.min)"
                  :hint="durationHint"
                  persistent-hint
                />
              </VCol>

              <!-- 👉 Media -->
              <VCol cols="12">
                <VDivider class="my-2" />
                <h6 class="text-h6 mt-4">
                  ملفات اللعبة
                </h6>
                <p class="text-body-2 mb-0">
                  ابحث بالاسم أو الكود في مكتبة الوسائط.
                </p>
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AssetPicker
                  v-model="newGame.startAudioId"
                  type="AUDIO"
                  label="صوت بدء اللعبة"
                  prepend-icon="tabler-player-play"
                  hint="يُشغَّل عند فتح اللعبة"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <AssetPicker
                  v-model="newGame.endAudioId"
                  type="AUDIO"
                  label="صوت نهاية اللعبة"
                  prepend-icon="tabler-flag-check"
                  hint="يُشغَّل عند إنهاء اللعبة"
                />
              </VCol>

              <VCol
                cols="12"
                md="5"
              >
                <VLabel class="mb-1 text-body-2 text-high-emphasis">
                  نوع الملف الرئيسي
                </VLabel>
                <VBtnToggle
                  v-model="visualFilter"
                  density="comfortable"
                  variant="outlined"
                  color="primary"
                  class="d-flex"
                  mandatory
                  divided
                >
                  <VBtn
                    value="ALL"
                    class="flex-grow-1"
                  >
                    الكل
                  </VBtn>
                  <VBtn
                    value="LOTTIE"
                    class="flex-grow-1"
                  >
                    Lottie
                  </VBtn>
                  <VBtn
                    value="VIDEO"
                    class="flex-grow-1"
                  >
                    فيديو
                  </VBtn>
                  <VBtn
                    value="IMAGE"
                    class="flex-grow-1"
                  >
                    صورة
                  </VBtn>
                </VBtnToggle>
              </VCol>

              <VCol
                cols="12"
                md="7"
              >
                <AppAutocomplete
                  v-model="newGame.selectedAssetIds"
                  v-model:search="lottieSearch"
                  :items="availableLottieAssets"
                  :loading="isLoadingLottie"
                  item-title="name"
                  item-value="id"
                  label="ملف اللعبة الرئيسي — رسوم Lottie أو فيديو أو صورة"
                  placeholder="اكتب الاسم أو الكود للبحث…"
                  hint="يمكن اختيار أكثر من ملف"
                  persistent-hint
                  no-filter
                  multiple
                  chips
                  closable-chips
                  clearable
                  :menu-props="{ maxHeight: 300 }"
                >
                  <template #item="{ item, props: itemProps }">
                    <VListItem
                      v-bind="itemProps"
                      :title="item.raw.name"
                    >
                      <template #prepend>
                        <VAvatar
                          size="32"
                          rounded
                          variant="tonal"
                          :color="visualColor(item.raw.type)"
                        >
                          <VIcon
                            :icon="visualIcon(item.raw.type)"
                            size="18"
                          />
                        </VAvatar>
                      </template>
                      <VListItemSubtitle
                        dir="ltr"
                        class="text-start"
                      >
                        {{ [item.raw.code, item.raw.type].filter(Boolean).join(' · ') }}
                      </VListItemSubtitle>
                    </VListItem>
                  </template>
                  <template #chip="{ item, props: chipProps }">
                    <VChip
                      v-bind="chipProps"
                      :prepend-icon="visualIcon(item.raw.type)"
                      :text="item.raw.name"
                    />
                  </template>
                  <template #no-data>
                    <div class="pa-4 text-center text-body-2 text-medium-emphasis">
                      {{ lottieSearch ? 'لا توجد ملفات مطابقة' : 'ابدأ الكتابة للبحث' }}
                    </div>
                  </template>
                </AppAutocomplete>
              </VCol>

              <!-- 👉 Actions -->
              <VCol
                cols="12"
                class="d-flex flex-wrap justify-center gap-4 mt-4"
              >
                <VBtn
                  type="submit"
                  :loading="isSubmitting"
                >
                  {{ editingGameId ? 'حفظ التعديلات' : 'إضافة اللعبة' }}
                </VBtn>
                <VBtn
                  color="secondary"
                  variant="tonal"
                  @click="isAddGameDialogVisible = false"
                >
                  إلغاء
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
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
    <GamePreviewDialog
      v-model="isPreviewDialogVisible"
      :game="previewGame"
    />
  </div>
</template>
