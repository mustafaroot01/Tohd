<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()

const stats = ref<any>({
  subscribers_total: 0,
  subscribers_active: 0,
  subscribers_unverified: 0,
  subscribers_suspended: 0,
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
  total_revenue: 0,
  today_activations_count: 0,
  today_revenue: 0,
  month_activations_count: 0,
  month_revenue: 0,
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

const subscribersChartSeries = computed(() => {
  return [
    stats.value.subscribers_active || 0,
    stats.value.subscribers_unverified || 0,
    stats.value.subscribers_suspended || 0,
  ]
})

const subscribersChartConfig = computed(() => {
  return {
    labels: ['فعال', 'غير مفعّل', 'موقوف'],
    colors: ['#28c76f', '#ff9f43', '#ea5455'],
    chart: {
      fontFamily: 'Tajawal, sans-serif',
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800,
      },
    },
    stroke: {
      width: 0,
    },
    legend: {
      show: true,
      position: 'bottom',
    },
    dataLabels: {
      enabled: true,
      formatter: function (val: number) {
        return val.toFixed(1) + '%'
      },
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '14px',
              fontFamily: 'Tajawal',
              color: '#a6a4b0',
              offsetY: -10,
            },
            value: {
              show: true,
              fontSize: '20px',
              fontFamily: 'Tajawal',
              fontWeight: 'bold',
              color: '#5d596c',
              offsetY: 4,
              formatter: function (val: string) {
                return parseInt(val).toLocaleString()
              },
            },
            total: {
              show: true,
              label: 'الإجمالي',
              fontSize: '13px',
              fontFamily: 'Tajawal',
              color: '#a6a4b0',
              formatter: function (w: any) {
                return w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0).toLocaleString()
              },
            },
          },
        },
      },
    },
  }
})

onMounted(() => {
  fetchDashboardStats()
})
</script>

<template>
  <div class="dashboard-page">
    <!-- Header Title -->
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold text-primary mb-1">لوحة التحكم الرئيسية</h2>
      <p class="text-body-2 text-muted mb-0">مرحباً بك في لوحة تحكم {{ settingsStore.appName }}. تتبع مؤشرات الأداء وإدارة المناهج والألعاب التدريبية.</p>
    </div>


    <!-- KPIs Row 1: Users, Activations & Revenue -->
    <VRow class="mb-6">
      <!-- 1. Total Subscribers -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" rounded size="54">
              <VIcon icon="tabler-users" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">إجمالي المشتركين</div>
              <div class="text-h4 font-weight-bold text-primary">{{ stats.subscribers_total }}</div>
              <div class="text-caption text-muted">{{ stats.subscribers_active }} نشط حالياً</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Active Activations -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="info" variant="tonal" rounded size="54">
              <VIcon icon="tabler-key" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">التفعيلات السارية</div>
              <div class="text-h4 font-weight-bold text-info">{{ stats.active_activations_count }}</div>
              <div class="text-caption text-muted">{{ stats.available_activations_count }} كود متاح</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Subscribers Needing Attention -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="warning" variant="tonal" rounded size="54">
              <VIcon icon="tabler-user-exclamation" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">مشتركون بحاجة لمتابعة</div>
              <div class="text-h4 font-weight-bold text-warning">{{ stats.subscribers_unverified }}</div>
              <div class="text-caption text-muted">غير مفعّل (لم يُتحقق من الهاتف) · {{ stats.subscribers_suspended }} موقوف</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 4. Total Revenue (IQD) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="success" variant="tonal" rounded size="54">
              <VIcon icon="tabler-wallet" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">الواردات الإجمالية</div>
              <div class="text-h4 font-weight-bold text-success">
                {{ Number(stats.total_revenue || 0).toLocaleString() }}
              </div>
              <div class="text-caption text-muted">دينار عراقي (التفعيلات)</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- KPIs Row 2: Content & Training Stats -->
    <VRow class="mb-6">
      <!-- 5. Curriculums -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" rounded size="54">
              <VIcon icon="tabler-books" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">المناهج التدريبية</div>
              <div class="text-h4 font-weight-bold text-primary">
                {{ stats.published_curriculums_count }}
                <span class="text-caption text-muted">/ {{ stats.curriculums_count }}</span>
              </div>
              <div class="text-caption text-muted">مناهج منشورة نشطة</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 6. Published Games -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="info" variant="tonal" rounded size="54">
              <VIcon icon="tabler-device-gamepad-2" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">الألعاب التفاعلية</div>
              <div class="text-h4 font-weight-bold text-info">
                {{ stats.published_games_count }}
                <span class="text-caption text-muted">/ {{ stats.games_count }}</span>
              </div>
              <div class="text-caption text-muted">ألعاب معتمدة وجاهزة</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 7. Average Accuracy -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="warning" variant="tonal" rounded size="54">
              <VIcon icon="tabler-percentage" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">متوسط دقة الأداء</div>
              <div class="text-h4 font-weight-bold text-warning">{{ stats.average_accuracy }}%</div>
              <div class="text-caption text-muted">دقة إجابات الأطفال</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 8. Sessions Completed Today -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="success" variant="tonal" rounded size="54">
              <VIcon icon="tabler-activity" size="30" />
            </VAvatar>
            <div>
              <div class="text-caption text-muted font-weight-medium">جلسات اليوم</div>
              <div class="text-h4 font-weight-bold text-success">{{ stats.today_sessions_count }}</div>
              <div class="text-caption text-muted">{{ stats.today_completed_sessions_count }} مكتملة بنجاح</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Analytics & Comparisons Section -->
    <VRow class="match-height">
      <!-- 1. Subscription Comparison Chart -->
      <VCol cols="12" md="6">
        <VCard class="h-100 pa-6 d-flex flex-column">
          <h3 class="text-h5 font-weight-bold mb-4 text-primary d-flex align-center gap-2">
            <VIcon icon="tabler-chart-pie" />
            توزيع حالات المشتركين
          </h3>
          <div class="d-flex align-center justify-center py-4 flex-grow-1">
            <VueApexCharts
              type="donut"
              height="280"
              width="100%"
              :options="subscribersChartConfig"
              :series="subscribersChartSeries"
            />
          </div>
        </VCard>
      </VCol>

      <!-- 2. Today's & Monthly Financial & Activation Stats -->
      <VCol cols="12" md="6">
        <VRow class="match-height">
          <!-- Card 1: Today's Activations -->
          <VCol cols="12" sm="6" class="pb-3">
            <VCard class="pa-4 text-center hover-card position-relative overflow-hidden d-flex flex-column justify-center align-center">
              <div class="mb-2 pa-3 rounded-circle bg-light-primary text-primary d-inline-flex animate-pulse">
                <VIcon icon="tabler-key" size="32" />
              </div>
              <h4 class="text-caption text-muted mb-1 font-weight-medium">تفعيلات اليوم</h4>
              <div class="text-h4 font-weight-bold text-primary mb-1">
                {{ stats.today_activations_count || 0 }}
              </div>
              <p class="text-caption text-muted mb-0">أكواد تفعيل اليوم</p>
            </VCard>
          </VCol>

          <!-- Card 2: Today's Revenue -->
          <VCol cols="12" sm="6" class="pb-3">
            <VCard class="pa-4 text-center hover-card position-relative overflow-hidden d-flex flex-column justify-center align-center">
              <div class="mb-2 pa-3 rounded-circle bg-light-success text-success d-inline-flex animate-pulse">
                <VIcon icon="tabler-coin" size="32" />
              </div>
              <h4 class="text-caption text-muted mb-1 font-weight-medium">واردات اليوم</h4>
              <div class="text-h5 font-weight-bold text-success mb-1">
                {{ Number(stats.today_revenue || 0).toLocaleString() }}
              </div>
              <span class="text-caption font-weight-medium text-success bg-light-success px-2 py-0.5 rounded-pill mb-0">
                دينار عراقي
              </span>
            </VCard>
          </VCol>

          <!-- Card 3: Month's Activations -->
          <VCol cols="12" sm="6">
            <VCard class="pa-4 text-center hover-card position-relative overflow-hidden d-flex flex-column justify-center align-center">
              <div class="mb-2 pa-3 rounded-circle bg-light-info text-info d-inline-flex animate-pulse">
                <VIcon icon="tabler-calendar-stats" size="32" />
              </div>
              <h4 class="text-caption text-muted mb-1 font-weight-medium">تفعيلات الشهر</h4>
              <div class="text-h4 font-weight-bold text-info mb-1">
                {{ stats.month_activations_count || 0 }}
              </div>
              <p class="text-caption text-muted mb-0">أكواد تفعيل الشهر</p>
            </VCard>
          </VCol>

          <!-- Card 4: Month's Revenue -->
          <VCol cols="12" sm="6">
            <VCard class="pa-4 text-center hover-card position-relative overflow-hidden d-flex flex-column justify-center align-center">
              <div class="mb-2 pa-3 rounded-circle bg-light-warning text-warning d-inline-flex animate-pulse">
                <VIcon icon="tabler-wallet" size="32" />
              </div>
              <h4 class="text-caption text-muted mb-1 font-weight-medium">واردات الشهر</h4>
              <div class="text-h5 font-weight-bold text-warning mb-1">
                {{ Number(stats.month_revenue || 0).toLocaleString() }}
              </div>
              <span class="text-caption font-weight-medium text-warning bg-light-warning px-2 py-0.5 rounded-pill mb-0">
                دينار عراقي
              </span>
            </VCard>
          </VCol>
        </VRow>
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
@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(var(--v-theme-primary), 0.2);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 10px rgba(var(--v-theme-primary), 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(var(--v-theme-primary), 0);
  }
}
.animate-pulse {
  animation: pulse 2.5s infinite ease-in-out;
}
.bg-light-primary {
  background-color: rgba(var(--v-theme-primary), 0.1) !important;
}
.bg-light-success {
  background-color: rgba(var(--v-theme-success), 0.1) !important;
}
.bg-light-info {
  background-color: rgba(var(--v-theme-info), 0.1) !important;
}
.bg-light-warning {
  background-color: rgba(var(--v-theme-warning), 0.1) !important;
}
</style>
