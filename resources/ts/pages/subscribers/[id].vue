<script setup lang="ts">
const route = useRoute('subscribers-id')
const router = useRouter()

const subscriber = ref<any | null>(null)
const isLoading = ref(true)

const isEditDialogVisible = ref(false)
const isSaving = ref(false)
const editErrors = ref<Record<string, string[]>>({})
const editForm = ref({
  name: '',
  phone: '',
  password: '',
  address: '',
  status: 'ACTIVE',
})

const isCancelDialogVisible = ref(false)
const cancelReason = ref('')
const assignmentToCancel = ref<any | null>(null)
const isCancelling = ref(false)

/** The week shown in the weekly report card; seeded from `subscriber.weekly`. */
const weekly = ref<any | null>(null)
const isWeeklyLoading = ref(false)

const assignmentStatusLabel = (status: string): string => {
  const map: Record<string, string> = {
    ACTIVE: 'فعال',
    COMPLETED: 'مكتمل',
    EXPIRED: 'منتهي',
    PAUSED: 'متوقف مؤقتاً',
    CANCELLED: 'ملغى',
  }

  return map[status] ?? status
}

const assignmentStatusColor = (status: string): string => {
  const map: Record<string, string> = {
    ACTIVE: 'success',
    COMPLETED: 'info',
    EXPIRED: 'secondary',
    PAUSED: 'warning',
    CANCELLED: 'error',
  }

  return map[status] ?? 'secondary'
}


const fetchSubscriber = async () => {
  isLoading.value = true
  try {
    const res = await $api(`/admin/subscribers/${route.params.id}`)
    if (res?.success) {
      subscriber.value = res.data
      weekly.value = res.data?.weekly ?? null
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const openEditDialog = () => {
  editForm.value = {
    name: subscriber.value.name,
    phone: subscriber.value.phone,
    password: '',
    address: subscriber.value.address ?? '',
    status: subscriber.value.status,
  }
  editErrors.value = {}
  isEditDialogVisible.value = true
}

const submitEdit = async () => {
  isSaving.value = true
  editErrors.value = {}
  try {
    const payload: Record<string, any> = { ...editForm.value }
    if (!payload.password)
      delete payload.password

    const res = await $api(`/admin/subscribers/${route.params.id}`, {
      method: 'PUT',
      body: payload,
      onResponseError({ response }) {
        if (response._data?.errors)
          editErrors.value = response._data.errors
      },
    })

    if (res?.success) {
      isEditDialogVisible.value = false
      await fetchSubscriber()
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isSaving.value = false
  }
}

const toggleSuspension = async () => {
  const action = subscriber.value.status === 'SUSPENDED' ? 'reactivate' : 'suspend'
  try {
    const res = await $api(`/admin/subscribers/${route.params.id}/${action}`, { method: 'POST' })
    if (res?.success)
      await fetchSubscriber()
  }
  catch (err) {
    console.error(err)
  }
}

const openCancelDialog = (assignment: any) => {
  assignmentToCancel.value = assignment
  cancelReason.value = ''
  isCancelDialogVisible.value = true
}

const submitCancel = async () => {
  isCancelling.value = true
  try {
    const res = await $api(`/admin/subscribers/${route.params.id}/assignments/${assignmentToCancel.value.id}/cancel`, {
      method: 'POST',
      body: { reason: cancelReason.value || null },
    })
    if (res?.success) {
      isCancelDialogVisible.value = false
      await fetchSubscriber()
    }
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isCancelling.value = false
  }
}

/** `YYYY-MM-DD` shifted by `days`, parsed as local time so the day never drifts. */
const shiftIsoDate = (iso: string, days: number): string => {
  const date = new Date(`${iso}T00:00:00`)

  date.setDate(date.getDate() + days)

  const pad = (n: number) => String(n).padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

const dayNumber = (iso: string) => Number(iso.slice(8, 10))

/** Attention is stored in seconds; the report reads in minutes with one decimal. */
const formatMinutes = (seconds?: number | null) => ((seconds ?? 0) / 60).toFixed(1)

/** ▲ / ▼ / — for a change against the previous week; a plain dash when there is none. */
const formatDelta = (value?: number | null, unit = '') => {
  if (value === null || value === undefined)
    return { class: 'text-muted', text: '—' }

  const rounded = Math.round(value * 10) / 10

  if (rounded > 0)
    return { class: 'text-success', text: `▲ +${rounded}${unit}` }
  if (rounded < 0)
    return { class: 'text-error', text: `▼ −${Math.abs(rounded)}${unit}` }

  return { class: 'text-muted', text: `— 0${unit}` }
}

const perDayColor = (day: any) => {
  if (day.is_passed)
    return 'success'
  if (day.is_skipped)
    return 'warning'

  return day.attempts > 0 ? 'primary' : 'muted'
}

const perDayTitle = (day: any) => {
  const score = day.best_score !== null && day.best_score !== undefined ? ` · ${day.best_score} / 10` : ''

  return `${day.date} · ${day.attempts} محاولة${score}`
}

const gameSubtitle = (game: any) =>
  [game.game?.axis?.name, game.game?.skill?.name].filter(Boolean).join(' · ')

const attemptsCaption = (game: any) => [
  game.failed ? `${game.failed} فاشلة` : '',
  game.short ? `${game.short} قصيرة` : '',
].filter(Boolean).join(' · ')

const playSummarySubtitle = computed(() => {
  const summary = subscriber.value?.play_summary

  return `${summary?.attempts ?? 0} محاولة · ${summary?.games_passed ?? 0} لعبة ناجحة · ${summary?.skipped_games ?? 0} تخطّي`
})

/** "22 أغسطس – 28 أغسطس 2026"; the calendar is pinned so ar-SA never falls back to Hijri. */
const weekRangeLabel = computed(() => {
  const week = weekly.value?.week
  if (!week)
    return ''

  const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', calendar: 'gregory' }
  const start = formatDate(`${week.start}T00:00:00`, options)
  const end = formatDate(`${week.end}T00:00:00`, { ...options, year: 'numeric' })

  return `${start} – ${end}`
})

const attentionDelta = computed(() =>
  formatDelta(weekly.value?.delta ? weekly.value.delta.attention_seconds / 60 : null, ' د'))

const averageBestDelta = computed(() => formatDelta(weekly.value?.delta?.average_best_score))

const hasSkippedDays = computed(() =>
  (weekly.value?.games ?? []).some((game: any) => game.days_skipped > 0))

const fetchWeekly = async (week: string) => {
  isWeeklyLoading.value = true
  try {
    const res = await $api(`/admin/subscribers/${route.params.id}/weekly`, { query: { week } })
    if (res?.success)
      weekly.value = res.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isWeeklyLoading.value = false
  }
}

const goToPreviousWeek = () => {
  if (weekly.value)
    fetchWeekly(shiftIsoDate(weekly.value.week.start, -1))
}

const goToNextWeek = () => {
  if (weekly.value && !weekly.value.week.is_current)
    fetchWeekly(shiftIsoDate(weekly.value.week.end, 1))
}

onMounted(() => {
  fetchSubscriber()
})
</script>

<template>
  <div v-if="isLoading" class="d-flex justify-center py-16">
    <VProgressCircular indeterminate color="primary" />
  </div>

  <div v-else-if="subscriber">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div class="d-flex align-center gap-3">
        <VBtn icon variant="tonal" color="secondary" size="small" @click="router.push('/subscribers')">
          <VIcon icon="tabler-arrow-right" />
        </VBtn>
        <div>
          <h2 class="text-h4 font-weight-bold d-flex align-center gap-3">
            {{ subscriber.name }}
            <VChip size="small" :color="statusColor(SUBSCRIBER_STATUS, subscriber.status)" variant="tonal">
              {{ statusLabel(SUBSCRIBER_STATUS, subscriber.status) }}
            </VChip>
          </h2>
          <p class="text-muted mb-0" dir="ltr">{{ subscriber.phone }}</p>
        </div>
      </div>
      <div class="d-flex gap-3">
        <VBtn variant="tonal" color="primary" prepend-icon="tabler-edit" @click="openEditDialog">
          تعديل البيانات
        </VBtn>
        <VBtn
          variant="tonal"
          :color="subscriber.status === 'SUSPENDED' ? 'success' : 'error'"
          :prepend-icon="subscriber.status === 'SUSPENDED' ? 'tabler-player-play' : 'tabler-player-pause'"
          @click="toggleSuspension"
        >
          {{ subscriber.status === 'SUSPENDED' ? 'إعادة تفعيل الحساب' : 'إيقاف الحساب' }}
        </VBtn>
      </div>
    </div>

    <VRow>
      <VCol cols="12" md="8">
        <!-- 1. Account Info -->
        <VCard class="mb-6">
          <VCardItem title="بيانات الحساب" />
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">العنوان</div>
                <div class="font-weight-medium">{{ subscriber.address || '-' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">تاريخ التسجيل</div>
                <div class="font-weight-medium">{{ formatDateTime(subscriber.created_at) }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">آخر تسجيل دخول</div>
                <div class="font-weight-medium">{{ formatDateTime(subscriber.last_login_at) }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">آخر نشاط</div>
                <div class="font-weight-medium">{{ formatDateTime(subscriber.last_activity_at) }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- 2. Current Subscription -->
        <VCard class="mb-6">
          <VCardItem title="الاشتراك الحالي" />
          <VDivider />
          <VCardText v-if="subscriber.current_subscription">
            <VRow>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">الكورس</div>
                <div class="font-weight-medium">{{ subscriber.current_subscription.curriculum?.name || '-' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">السيريال</div>
                <div class="font-weight-medium" dir="ltr">{{ subscriber.current_subscription.serial || '-' }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">تاريخ الانتهاء</div>
                <div class="font-weight-medium">{{ formatDateTime(subscriber.current_subscription.ends_at) }}</div>
              </VCol>
              <VCol cols="6" md="3">
                <div class="text-caption text-muted">الأيام المتبقية</div>
                <div class="font-weight-bold text-primary">{{ subscriber.current_subscription.days_remaining ?? '-' }}</div>
              </VCol>
            </VRow>
          </VCardText>
          <VCardText v-else class="text-muted">
            لا يوجد اشتراك فعال حالياً.
          </VCardText>
        </VCard>

        <!-- 3. Subscription History -->
        <VCard class="mb-6">
          <VCardItem title="تاريخ الاشتراكات" />
          <VDivider />
          <VCardText class="pa-0">
            <VTable class="text-no-wrap">
              <thead>
                <tr>
                  <th class="text-start">الكورس</th>
                  <th class="text-start">السيريال</th>
                  <th class="text-start">البداية</th>
                  <th class="text-start">النهاية</th>
                  <th class="text-start">الحالة</th>
                  <th class="text-center">إجراء</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="assignment in subscriber.subscription_history" :key="assignment.id">
                  <td>{{ assignment.curriculum?.name || '-' }}</td>
                  <td dir="ltr">{{ assignment.serial || '-' }}</td>
                  <td>{{ formatDateTime(assignment.starts_at) }}</td>
                  <td>{{ formatDateTime(assignment.ends_at) }}</td>
                  <td>
                    <VChip size="small" :color="assignmentStatusColor(assignment.status)" variant="tonal">
                      {{ assignmentStatusLabel(assignment.status) }}
                    </VChip>
                  </td>
                  <td class="text-center">
                    <VBtn
                      v-if="assignment.status === 'ACTIVE'"
                      size="small"
                      variant="tonal"
                      color="error"
                      @click="openCancelDialog(assignment)"
                    >
                      إلغاء
                    </VBtn>
                  </td>
                </tr>
                <tr v-if="!subscriber.subscription_history?.length">
                  <td colspan="6" class="text-center py-6 text-muted">لا يوجد اشتراكات سابقة.</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <!-- 4. Serial History -->
        <VCard class="mb-6">
          <VCardItem title="تاريخ السيريالات" />
          <VDivider />
          <VCardText class="pa-0">
            <VTable class="text-no-wrap">
              <thead>
                <tr>
                  <th class="text-start">السيريال</th>
                  <th class="text-start">المنتج</th>
                  <th class="text-start">تاريخ التفعيل</th>
                  <th class="text-start">الحالة</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="serial in subscriber.serial_history" :key="serial.id">
                  <td dir="ltr">{{ serial.code }}</td>
                  <td>{{ serial.product?.name || '-' }}</td>
                  <td>{{ formatDateTime(serial.activated_at) }}</td>
                  <td>
                    <VChip size="small" color="primary" variant="tonal">{{ serial.status }}</VChip>
                  </td>
                </tr>
                <tr v-if="!subscriber.serial_history?.length">
                  <td colspan="4" class="text-center py-6 text-muted">لا يوجد سيريالات مستخدمة.</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <!-- 5. Courses -->
        <VCard class="mb-6">
          <VCardItem title="الكورسات" />
          <VDivider />
          <VCardText>
            <div v-if="subscriber.courses?.length" class="d-flex flex-wrap gap-2">
              <VChip v-for="course in subscriber.courses" :key="course.id" color="info" variant="tonal">
                {{ course.name }}
              </VChip>
            </div>
            <div v-else class="text-muted">لا يوجد كورسات مرتبطة بعد.</div>
          </VCardText>
        </VCard>

        <!-- 6. Weekly report -->
        <VCard class="mb-6">
          <VCardItem title="التقرير الأسبوعي" :subtitle="playSummarySubtitle">
            <template #append>
              <div class="d-flex align-center gap-2">
                <VChip v-if="weekly?.week?.is_current" size="small" color="primary" variant="tonal">
                  الأسبوع الحالي
                </VChip>
                <VBtn
                  icon
                  variant="tonal"
                  color="secondary"
                  size="small"
                  :disabled="!weekly || isWeeklyLoading"
                  @click="goToPreviousWeek"
                >
                  <VIcon icon="tabler-chevron-right" />
                </VBtn>
                <span class="font-weight-medium text-no-wrap">{{ weekRangeLabel || '—' }}</span>
                <VBtn
                  icon
                  variant="tonal"
                  color="secondary"
                  size="small"
                  :disabled="!weekly || isWeeklyLoading || weekly.week.is_current"
                  @click="goToNextWeek"
                >
                  <VIcon icon="tabler-chevron-left" />
                </VBtn>
              </div>
            </template>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isWeeklyLoading" indeterminate color="primary" height="2" />

          <template v-if="weekly">
            <!-- Summary strip -->
            <VCardText>
              <div class="weekly-summary d-flex flex-wrap gap-4">
                <div>
                  <div class="text-caption text-muted">أيام النشاط</div>
                  <div class="text-h6 font-weight-bold">
                    {{ weekly.summary.days_active }}<span class="text-caption text-muted"> / 7</span>
                  </div>
                </div>
                <div>
                  <div class="text-caption text-muted">المحاولات</div>
                  <div class="text-h6 font-weight-bold">{{ weekly.summary.attempts }}</div>
                  <div v-if="weekly.summary.short > 0" class="text-caption text-muted">منها {{ weekly.summary.short }} قصيرة</div>
                </div>
                <div>
                  <div class="text-caption text-muted">زمن الانتباه</div>
                  <div class="text-h6 font-weight-bold">
                    {{ formatMinutes(weekly.summary.attention_seconds) }}<span class="text-caption text-muted"> دقيقة</span>
                  </div>
                  <div v-if="weekly.delta" class="text-caption" :class="attentionDelta.class">{{ attentionDelta.text }}</div>
                </div>
                <div>
                  <div class="text-caption text-muted">ألعاب ناجحة</div>
                  <div class="text-h6 font-weight-bold text-success">{{ weekly.summary.games_passed }}</div>
                </div>
                <div>
                  <div class="text-caption text-muted">متوسط أفضل درجة</div>
                  <div class="text-h6 font-weight-bold text-primary">
                    {{ weekly.summary.average_best_score ?? '—' }}<span class="text-caption text-muted"> / 10</span>
                  </div>
                  <div v-if="weekly.delta" class="text-caption" :class="averageBestDelta.class">{{ averageBestDelta.text }}</div>
                </div>
              </div>
            </VCardText>

            <VDivider />

            <!-- 7-day strip -->
            <VCardText>
              <div class="weekly-days">
                <div
                  v-for="day in weekly.days"
                  :key="day.date"
                  class="weekly-day rounded text-center"
                  :class="{ 'is-today': day.is_today, 'is-future': day.is_future }"
                >
                  <div class="text-caption text-muted">{{ day.weekday }}</div>
                  <div class="text-body-1 font-weight-bold">{{ dayNumber(day.date) }}</div>
                  <div class="text-caption">{{ day.attempts }} محاولة</div>
                  <div class="text-caption font-weight-medium">
                    {{ day.best_score ?? '—' }}<span v-if="day.best_score !== null" class="text-muted"> / 10</span>
                  </div>
                  <div class="text-caption" :class="day.games_passed ? 'text-success' : 'text-muted'">
                    <VIcon icon="tabler-check" size="12" /> {{ day.games_passed }}
                  </div>
                </div>
              </div>
            </VCardText>

            <VDivider />

            <!-- Per-game table -->
            <VCardText v-if="!weekly.games?.length" class="text-center py-6 text-muted">
              لا يوجد لعب في هذا الأسبوع
            </VCardText>
            <VCardText v-else class="pa-0">
              <VTable class="text-no-wrap">
                <thead>
                  <tr>
                    <th class="text-start">اللعبة</th>
                    <th class="text-start">الحالة</th>
                    <th class="text-start">الأيام</th>
                    <th class="text-start">المحاولات</th>
                    <th class="text-start">الأفضل</th>
                    <th class="text-start">المتوسط</th>
                    <th class="text-start">الانتباه</th>
                    <th class="text-start">ناجحة</th>
                    <th v-if="hasSkippedDays" class="text-start">تخطّي</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="game in weekly.games" :key="game.game.id">
                    <td>
                      <div class="font-weight-medium">{{ game.game.name }}</div>
                      <div v-if="gameSubtitle(game)" class="text-caption text-muted" style="white-space: normal; max-inline-size: 200px;">{{ gameSubtitle(game) }}</div>
                      <div class="d-flex align-center gap-1 mt-1">
                        <span
                          v-for="day in game.per_day"
                          :key="day.date"
                          class="day-dot"
                          :class="`day-dot--${perDayColor(day)}`"
                          :title="perDayTitle(day)"
                        />
                      </div>
                    </td>
                    <td>
                      <VChip size="small" variant="tonal" :color="statusColor(GAME_PROGRESS_STATUS, game.status)">
                        {{ statusLabel(GAME_PROGRESS_STATUS, game.status) }}
                      </VChip>
                    </td>
                    <td>{{ game.days_played }}<span class="text-caption text-muted"> / 7</span></td>
                    <td>
                      <div>{{ game.attempts }}</div>
                      <div v-if="attemptsCaption(game)" class="text-caption text-muted">{{ attemptsCaption(game) }}</div>
                    </td>
                    <td>
                      <div class="font-weight-bold" :class="{ 'text-success': game.best_score !== null && game.best_score >= game.required_score }">
                        {{ game.best_score ?? '—' }}<span class="text-caption text-muted"> / 10</span>
                      </div>
                      <div class="text-caption text-muted">
                        المطلوب {{ game.required_score }}
                        <template v-if="game.delta">
                          · <span :class="formatDelta(game.delta.best_score).class">{{ formatDelta(game.delta.best_score).text }}</span>
                        </template>
                      </div>
                    </td>
                    <td>{{ game.average_best_score ?? '—' }}</td>
                    <td>{{ formatMinutes(game.attention_seconds) }}<span class="text-caption text-muted"> د</span></td>
                    <td>{{ game.days_passed }}</td>
                    <td v-if="hasSkippedDays">
                      <span v-if="game.days_skipped > 0" class="text-warning">{{ game.days_skipped }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>

            <!-- By axis -->
            <template v-if="weekly.axes?.length">
              <VDivider />
              <VCardText>
                <div class="text-caption text-muted mb-2">حسب المحور</div>
                <div class="d-flex flex-wrap gap-2">
                  <VChip v-for="axis in weekly.axes" :key="axis.id" size="small" variant="tonal" color="secondary">
                    {{ axis.name }} · {{ axis.games_passed }}/{{ axis.games_played }} · {{ axis.average_best_score ?? '—' }}
                  </VChip>
                </div>
              </VCardText>
            </template>
          </template>
          <VCardText v-else class="text-muted">
            لا يتوفر تقرير أسبوعي لهذا المشترك.
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4">
        <!-- 7. Supplementary details -->
        <VCard v-if="subscriber.details" class="mb-6">
          <VCardItem title="البيانات التكميلية" subtitle="أكملها المشترك من التطبيق" />
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="6">
                <div class="text-caption text-muted">المحافظة</div>
                <div class="text-body-1 font-weight-medium">{{ subscriber.details.governorate_name ?? '—' }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">الجنس</div>
                <div class="text-body-1 font-weight-medium">{{ subscriber.details.gender_label ?? '—' }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">العمر</div>
                <div class="text-body-1 font-weight-medium">
                  <template v-if="subscriber.details.age">{{ subscriber.details.age }} <span class="text-caption text-muted">سنة</span></template>
                  <template v-else>—</template>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">التسلسل في العائلة</div>
                <div class="text-body-1 font-weight-medium">{{ subscriber.details.family_order ?? '—' }}</div>
              </VCol>
              <VCol cols="12">
                <div class="text-caption text-muted">نوع الولادة</div>
                <div class="text-body-1 font-weight-medium">{{ subscriber.details.delivery_type_label ?? '—' }}</div>
              </VCol>
            </VRow>
            <VDivider class="my-3" />
            <VChip
              size="small"
              variant="tonal"
              :color="subscriber.details.is_complete ? 'success' : 'warning'"
            >
              {{ subscriber.details.is_complete ? `مكتملة · ${formatDateTime(subscriber.details.completed_at)}` : 'غير مكتملة' }}
            </VChip>
          </VCardText>
        </VCard>

        <!-- 8. Progress -->
        <VCard class="mb-6">
          <VCardItem title="التقدم" subtitle="درجة الانتباه تُقاس بالخادم من زمن المشاهدة" />
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="6">
                <div class="text-caption text-muted">متوسط الدرجة</div>
                <div class="text-h5 font-weight-bold text-primary">
                  {{ subscriber.progress?.grades?.average_score ?? '—' }}<span class="text-caption text-muted"> / 10</span>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">أفضل درجة</div>
                <div class="text-h5 font-weight-bold text-success">
                  {{ subscriber.progress?.grades?.best_score ?? '—' }}<span class="text-caption text-muted"> / 10</span>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">نسبة النجاح</div>
                <div class="text-h5 font-weight-bold text-info">
                  {{ subscriber.progress?.grades?.pass_rate ?? '—' }}<span v-if="subscriber.progress?.grades?.pass_rate !== null" class="text-caption text-muted">%</span>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">ألعاب ناجحة</div>
                <div class="text-h5 font-weight-bold text-warning">{{ subscriber.progress?.grades?.games_passed ?? 0 }}</div>
              </VCol>
            </VRow>

            <VDivider class="my-4" />

            <VRow>
              <VCol cols="6">
                <div class="text-caption text-muted">إجمالي المحاولات</div>
                <div class="text-body-1 font-weight-medium">{{ subscriber.progress?.total_attempts ?? 0 }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">زمن الانتباه</div>
                <div class="text-body-1 font-weight-medium">{{ Math.round((subscriber.progress?.grades?.attention_seconds ?? 0) / 60) }} دقيقة</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- 9. Activity Timeline -->
        <VCard>
          <VCardItem title="النشاط والسجل الزمني" />
          <VDivider />
          <VCardText style="max-block-size: 480px; overflow-y: auto;">
            <VTimeline side="end" align="start" line-inset="8" truncate-line="both" density="compact">
              <VTimelineItem v-for="(activity, index) in subscriber.activity_timeline" :key="index" dot-color="primary" size="x-small">
                <div class="d-flex justify-space-between align-center gap-2 flex-wrap mb-1">
                  <span class="font-weight-medium">{{ activity.label }}</span>
                  <span class="text-caption text-muted">{{ formatDateTime(activity.created_at) }}</span>
                </div>
              </VTimelineItem>
            </VTimeline>
            <div v-if="!subscriber.activity_timeline?.length" class="text-muted text-center py-6">
              لا يوجد نشاط مسجل بعد.
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Edit Dialog -->
    <VDialog v-model="isEditDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">تعديل بيانات المشترك</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12">
              <AppTextField v-model="editForm.name" label="الاسم" :error-messages="editErrors.name" />
            </VCol>
            <VCol cols="12">
              <AppTextField v-model="editForm.phone" label="رقم الهاتف" dir="ltr" :error-messages="editErrors.phone" />
            </VCol>
            <VCol cols="12">
              <AppTextField v-model="editForm.password" type="password" label="كلمة مرور جديدة (اختياري)" :error-messages="editErrors.password" />
            </VCol>
            <VCol cols="12">
              <AppTextField v-model="editForm.address" label="العنوان" :error-messages="editErrors.address" />
            </VCol>
            <VCol cols="12">
              <AppSelect
                v-model="editForm.status"
                label="الحالة"
                :items="[
                  { title: 'فعال', value: 'ACTIVE' },
                  { title: 'موقوف', value: 'SUSPENDED' },
                ]"
              />
            </VCol>
          </VRow>
          <VAlert v-if="editForm.phone !== subscriber.phone" type="info" variant="tonal" class="mt-2">
            الرقم الجديد يُعتمد موثّقاً مباشرة ولا يُرسل رمز تحقق. المشترك لا يستطيع تغيير رقمه من التطبيق.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isEditDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSaving" @click="submitEdit">حفظ التعديلات</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Cancel Assignment Dialog -->
    <VDialog v-model="isCancelDialogVisible" max-width="450">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إلغاء الاشتراك</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <AppTextField v-model="cancelReason" label="سبب الإلغاء (اختياري)" />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isCancelDialogVisible = false">تراجع</VBtn>
          <VBtn color="error" :loading="isCancelling" @click="submitCancel">تأكيد الإلغاء</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss" scoped>
.weekly-summary > div {
  flex: 1 1 140px;
}

.weekly-days {
  display: grid;
  gap: 8px;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.weekly-day {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding-block: 8px;
  padding-inline: 4px;

  &.is-today {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgba(var(--v-theme-primary), 0.08);
  }

  &.is-future {
    opacity: 0.45;
  }
}

.day-dot {
  display: inline-block;
  border-radius: 50%;
  background-color: rgba(var(--v-theme-on-surface), 0.12);
  block-size: 10px;
  inline-size: 10px;

  &--success {
    background-color: rgb(var(--v-theme-success));
  }

  &--warning {
    background-color: rgb(var(--v-theme-warning));
  }

  &--primary {
    background-color: rgb(var(--v-theme-primary));
  }
}
</style>
