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
    if (res?.success)
      subscriber.value = res.data
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

        <!-- 6. Sessions -->
        <VCard class="mb-6">
          <VCardItem title="الجلسات" :subtitle="`إجمالي ${subscriber.sessions_summary?.total ?? 0} جلسة، منها ${subscriber.sessions_summary?.completed ?? 0} مكتملة`" />
          <VDivider />
          <VCardText class="pa-0">
            <VTable class="text-no-wrap">
              <thead>
                <tr>
                  <th class="text-start">اللعبة</th>
                  <th class="text-start">النتيجة</th>
                  <th class="text-start">الدقة</th>
                  <th class="text-start">الحالة</th>
                  <th class="text-start">التاريخ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in subscriber.sessions" :key="session.id">
                  <td>{{ session.game_name || '-' }}</td>
                  <td>{{ session.score }}</td>
                  <td>{{ session.accuracy }}%</td>
                  <td>
                    <VChip size="small" color="secondary" variant="tonal">{{ session.status }}</VChip>
                  </td>
                  <td>{{ formatDateTime(session.started_at) }}</td>
                </tr>
                <tr v-if="!subscriber.sessions?.length">
                  <td colspan="5" class="text-center py-6 text-muted">لا يوجد جلسات بعد.</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4">
        <!-- 7. Progress -->
        <VCard class="mb-6">
          <VCardItem title="التقدم" />
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="6">
                <div class="text-caption text-muted">إجمالي الجلسات</div>
                <div class="text-h5 font-weight-bold text-primary">{{ subscriber.progress?.total_sessions ?? 0 }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">الألعاب المكتملة</div>
                <div class="text-h5 font-weight-bold text-info">{{ subscriber.progress?.total_games_completed ?? 0 }}</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">متوسط الدقة</div>
                <div class="text-h5 font-weight-bold text-success">{{ subscriber.progress?.average_accuracy ?? 0 }}%</div>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-muted">مجموع النقاط</div>
                <div class="text-h5 font-weight-bold text-warning">{{ subscriber.progress?.total_score ?? 0 }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- 8. Activity Timeline -->
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
                  { title: 'غير مفعل', value: 'UNVERIFIED' },
                  { title: 'موقوف', value: 'SUSPENDED' },
                ]"
              />
            </VCol>
          </VRow>
          <VAlert v-if="editForm.phone !== subscriber.phone" type="warning" variant="tonal" class="mt-2">
            تغيير رقم الهاتف سيعيد الحساب إلى حالة "غير مفعل" ويتطلب تحقق OTP جديد.
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
