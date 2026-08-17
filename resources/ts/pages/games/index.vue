<script setup lang="ts">
const games = ref<any[]>([])
const axes = ref<any[]>([])
const skills = ref<any[]>([])
const isLoading = ref(true)
const isAddGameDialogVisible = ref(false)
const isSubmitting = ref(false)
const isArchiving = ref(false)
const notification = ref<{ text: string; color: string } | null>(null)
const confirmArchive = ref(false)
const pendingArchiveGame = ref<any | null>(null)

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

const newGame = ref({
  code: '',
  name: '',
  description: '',
  type: 'TAP',
  axis_id: '',
  skill_id: '',
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
})

const fetchGames = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/games')
    if (res?.success)
      games.value = res.data

    const axesRes = await $api('/admin/axes')
    if (axesRes?.success)
      axes.value = axesRes.data

    const skillsRes = await $api('/admin/skills')
    if (skillsRes?.success)
      skills.value = skillsRes.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const saveGame = async () => {
  if (!newGame.value.name || !newGame.value.code || !newGame.value.axis_id || !newGame.value.skill_id)
    return

  isSubmitting.value = true
  try {
    const res = await $api('/admin/games', {
      method: 'POST',
      body: newGame.value,
    })
    if (res?.success) {
      isAddGameDialogVisible.value = false
      notification.value = { text: 'تمت إضافة اللعبة بنجاح كمسودة', color: 'success' }
      await fetchGames()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حفظ اللعبة', color: 'error' }
  }
  finally {
    isSubmitting.value = false
  }
}

const publishGame = async (game: any) => {
  try {
    const res = await $api(`/admin/games/${game.id}/publish`, { method: 'POST' })
    if (res?.success) {
      notification.value = { text: `تم نشر لعبة (${game.name}) بنجاح!`, color: 'success' }
      await fetchGames()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل نشر اللعبة', color: 'error' }
  }
}

const approveGame = async (game: any) => {
  try {
    const res = await $api(`/admin/games/${game.id}/approve`, { method: 'POST' })
    if (res?.success) {
      notification.value = { text: `تم اعتماد لعبة (${game.name}) بنجاح`, color: 'info' }
      await fetchGames()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل اعتماد اللعبة', color: 'error' }
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
      notification.value = { text: `تمت أرشفة لعبة (‎${pendingArchiveGame.value.name}‎)`, color: 'warning' }
      await fetchGames()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل أرشفة اللعبة', color: 'error' }
  }
  finally {
    isArchiving.value = false
    confirmArchive.value = false
    pendingArchiveGame.value = null
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'PUBLISHED': return 'success'
    case 'APPROVED': return 'info'
    case 'TESTING': return 'warning'
    case 'ARCHIVED': return 'secondary'
    default: return 'primary'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'PUBLISHED': return 'منشورة (متاحة)'
    case 'APPROVED': return 'معتمدة'
    case 'TESTING': return 'قيد الاختبار'
    case 'ARCHIVED': return 'مؤرشفة'
    default: return 'مسودة'
  }
}

onMounted(() => {
  fetchGames()
})
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
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        @click="isAddGameDialogVisible = true"
      >
        إضافة لعبة جديدة
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

    <!-- Games Table Card -->
    <VCard>
      <VCardText class="pa-0">
        <VTable hover class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-start">كود اللعبة</th>
              <th class="text-start">اسم اللعبة</th>
              <th class="text-start">النوع والمحور</th>
              <th class="text-start">المستوى والمدة</th>
              <th class="text-start">الحالة</th>
              <th class="text-center">الإجراءات ودورة النشر</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="game in games" :key="game.id">
              <td>
                <span class="font-weight-bold text-primary"><code>{{ game.code }}</code></span>
              </td>
              <td>
                <div class="d-flex align-center gap-2">
                  <VAvatar color="primary" variant="tonal" size="36" rounded>
                    <VIcon icon="tabler-device-gamepad" size="20" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold">{{ game.name }}</div>
                    <div class="text-caption text-muted">{{ game.description }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div>
                  <VChip size="x-small" color="primary" class="me-1 mb-1">
                    {{ game.type }}
                  </VChip>
                  <div class="text-caption text-muted">
                    {{ game.axis?.name || 'غير محدد' }}
                  </div>
                </div>
              </td>
              <td>
                <div class="text-caption font-weight-medium">مستوى {{ game.level }} ({{ game.difficulty }})</div>
                <div class="text-caption text-muted">{{ game.duration_seconds }} ثانية</div>
              </td>
              <td>
                <VChip
                  size="small"
                  :color="getStatusColor(game.status)"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  {{ getStatusLabel(game.status) }}
                </VChip>
              </td>
              <td class="text-center">
                <div class="d-flex justify-center gap-1">
                  <!-- Approve -->
                  <VBtn
                    v-if="game.status === 'DRAFT' || game.status === 'TESTING'"
                    size="small"
                    color="info"
                    variant="tonal"
                    prepend-icon="tabler-check"
                    @click="approveGame(game)"
                  >
                    اعتماد
                  </VBtn>

                  <!-- Publish -->
                  <VBtn
                    v-if="game.status !== 'PUBLISHED'"
                    size="small"
                    color="success"
                    variant="flat"
                    prepend-icon="tabler-send"
                    @click="publishGame(game)"
                  >
                    نشر
                  </VBtn>

                  <!-- Archive -->
                  <VBtn
                    v-if="game.status === 'PUBLISHED'"
                    size="small"
                    color="secondary"
                    variant="tonal"
                    prepend-icon="tabler-archive"
                    @click="requestArchiveGame(game)"
                  >
                    أرشفة
                  </VBtn>
                </div>
              </td>
            </tr>

            <tr v-if="games.length === 0 && !isLoading">
              <td colspan="6" class="text-center py-8 text-muted">
                لا توجد ألعاب مسجلة في النظام بعد.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <!-- Add Game Dialog -->
    <VDialog v-model="isAddGameDialogVisible" max-width="700">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة لعبة تدريبية جديدة</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="4">
              <VTextField
                v-model="newGame.code"
                label="كود اللعبة (فريد)"
                placeholder="مثال: ATT-002"
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
  </div>
</template>
