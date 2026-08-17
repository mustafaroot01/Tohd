<script setup lang="ts">
const stats = ref<any>({
  users_count: 0,
  active_users_count: 0,
  active_activations_count: 0,
  available_activations_count: 0,
  expired_activations_count: 0,
  games_count: 0,
  published_games_count: 0,
  curriculums_count: 0,
  published_curriculums_count: 0,
  today_sessions_count: 0,
  today_completed_sessions_count: 0,
  average_accuracy: 0,
})

const isLoading = ref(true)

const fetchDashboardStats = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/dashboard')
    if (res?.success && res?.data) {
      stats.value = res.data
    }
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardStats()
})
</script>

<template>
  <div class="dashboard-page">
    <!-- Welcome Header Banner -->
    <VCard class="mb-6 bg-primary text-white overflow-hidden position-relative">
      <VCardText class="pa-6 pa-md-8">
        <VRow align="center">
          <VCol cols="12" md="8">
            <h2 class="text-h3 font-weight-bold text-white mb-2">
              لوحة تحكم منصة رحلة فارس 🌟
            </h2>
            <p class="text-body-1 text-white opacity-90 mb-4">
              نظام إدارة المحتوى والتدريب التفاعلي للأطفال - تتبع المؤشرات، إدارة الألعاب، وبناء المناهج في بيئة متكاملة.
            </p>
            <div class="d-flex gap-3 flex-wrap">
              <VBtn
                color="white"
                class="text-primary font-weight-bold"
                to="/app-simulation"
                prepend-icon="tabler-player-play"
              >
                تجربة المنصة مباشرة
              </VBtn>
              <VBtn
                variant="outlined"
                color="white"
                to="/games"
                prepend-icon="tabler-plus"
              >
                إدارة الألعاب
              </VBtn>
            </div>
          </VCol>
          <VCol cols="12" md="4" class="text-center d-none d-md-block">
            <VIcon
              icon="tabler-sparkles"
              size="140"
              class="opacity-30"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- KPI Metric Cards Grid -->
    <VRow class="mb-6">
      <!-- 1. Active Users -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="primary"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon icon="tabler-users" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">
                المستخدمون النشطون
              </div>
              <div class="text-h4 font-weight-bold text-primary">
                {{ stats.active_users_count }}
                <span class="text-caption text-muted">/ {{ stats.users_count }}</span>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Active Activations -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="success"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon icon="tabler-key" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">
                التفعيلات السارية
              </div>
              <div class="text-h4 font-weight-bold text-success">
                {{ stats.active_activations_count }}
              </div>
              <div class="text-caption text-muted">
                {{ stats.available_activations_count }} كود متاح
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Published Games -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="info"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon icon="tabler-device-gamepad-2" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">
                الألعاب المنشورة
              </div>
              <div class="text-h4 font-weight-bold text-info">
                {{ stats.published_games_count }}
                <span class="text-caption text-muted">/ {{ stats.games_count }}</span>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 4. Average Accuracy -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="warning"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon icon="tabler-percentage" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">
                متوسط دقة الأداء
              </div>
              <div class="text-h4 font-weight-bold text-warning">
                {{ stats.average_accuracy }}%
              </div>
              <div class="text-caption text-muted">
                {{ stats.today_completed_sessions_count }} جلسة اليوم
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Quick Navigation & Highlights -->
    <VRow>
      <!-- Quick Sections -->
      <VCol cols="12" md="8">
        <VCard class="mb-6">
          <VCardItem title="أقسام المنصة الرئيسية" subtitle="روابط سريعة للتحكم بالمحتوى والعمليات">
            <template #append>
              <VChip color="primary" size="small">
                Laravel 13 API Backend
              </VChip>
            </template>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-6">
            <VRow>
              <VCol cols="12" sm="6">
                <VCard variant="tonal" color="primary" to="/axes" class="pa-4 cursor-pointer hover-card">
                  <div class="d-flex align-center gap-3">
                    <VIcon icon="tabler-target" size="36" />
                    <div>
                      <h4 class="font-weight-bold">المحاور والمهارات</h4>
                      <p class="text-caption mb-0 text-muted">إدارة محاور التركيز والانتباه والتواصل</p>
                    </div>
                  </div>
                </VCard>
              </VCol>

              <VCol cols="12" sm="6">
                <VCard variant="tonal" color="info" to="/games" class="pa-4 cursor-pointer hover-card">
                  <div class="d-flex align-center gap-3">
                    <VIcon icon="tabler-device-gamepad-2" size="36" />
                    <div>
                      <h4 class="font-weight-bold">الألعاب التفاعلية</h4>
                      <p class="text-caption mb-0 text-muted">إدارة الألعاب ودورة الاعتماد والنشر</p>
                    </div>
                  </div>
                </VCard>
              </VCol>

              <VCol cols="12" sm="6">
                <VCard variant="tonal" color="success" to="/curriculums" class="pa-4 cursor-pointer hover-card">
                  <div class="d-flex align-center gap-3">
                    <VIcon icon="tabler-books" size="36" />
                    <div>
                      <h4 class="font-weight-bold">المناهج والخطط</h4>
                      <p class="text-caption mb-0 text-muted">بناء المنهج التدريبي وتوزيع الأيام</p>
                    </div>
                  </div>
                </VCard>
              </VCol>

              <VCol cols="12" sm="6">
                <VCard variant="tonal" color="warning" to="/activation-codes" class="pa-4 cursor-pointer hover-card">
                  <div class="d-flex align-center gap-3">
                    <VIcon icon="tabler-key" size="36" />
                    <div>
                      <h4 class="font-weight-bold">أكواد التفعيل</h4>
                      <p class="text-caption mb-0 text-muted">توليد وإدارة تراخيص وتفعيلات المستخدمين</p>
                    </div>
                  </div>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- System Status -->
      <VCol cols="12" md="4">
        <VCard class="mb-6 h-100">
          <VCardItem title="حالة النظام والبيئة" subtitle="معلومات خادم الـ API">
            <template #append>
              <VBadge dot color="success" />
            </template>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-6">
            <VList density="compact" class="py-0">
              <VListItem>
                <template #prepend>
                  <VIcon icon="tabler-check" color="success" class="me-2" />
                </template>
                <VListItemTitle class="font-weight-medium">Laravel API V1</VListItemTitle>
                <template #append>
                  <VChip size="x-small" color="success">متصل</VChip>
                </template>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon icon="tabler-shield-check" color="success" class="me-2" />
                </template>
                <VListItemTitle class="font-weight-medium">Sanctum Auth</VListItemTitle>
                <template #append>
                  <VChip size="x-small" color="primary">نشط</VChip>
                </template>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon icon="tabler-file-code" color="info" class="me-2" />
                </template>
                <VListItemTitle class="font-weight-medium">Lottie Validator</VListItemTitle>
                <template #append>
                  <VChip size="x-small" color="info">جاهز</VChip>
                </template>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon icon="tabler-chart-bar" color="warning" class="me-2" />
                </template>
                <VListItemTitle class="font-weight-medium">Telemetry Engine</VListItemTitle>
                <template #append>
                  <VChip size="x-small" color="warning">جاهز</VChip>
                </template>
              </VListItem>
            </VList>

            <VDivider class="my-4" />

            <VBtn
              block
              color="primary"
              variant="flat"
              to="/app-simulation"
              prepend-icon="tabler-device-mobile"
            >
              فتح شاشة محاكاة الطفل
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.hover-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}
</style>
