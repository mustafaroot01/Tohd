<script setup lang="ts">
// State & User Session
const activeTab = ref<'home' | 'game' | 'progress' | 'activation'>('home')
const user = ref<any>(null)
const activation = ref<any>(null)
const todayPlan = ref<any>(null)
const progress = ref<any>(null)
const isLoading = ref(true)
const activationCodeInput = ref('DEMO-2026-RIHL-0001')
const alertMessage = ref<{ text: string; color: string } | null>(null)

// Game Simulator State
const activeGame = ref<any | null>(null)
const currentSession = ref<any | null>(null)
const gameTimer = ref(0)
const gameTimerInterval = ref<any>(null)
const gameScore = ref(0)
const gameAttempts = ref(0)
const gameCorrect = ref(0)
const gameIncorrect = ref(0)
const isPlaying = ref(false)
const gameFinished = ref(false)

// Interactive Star Game Canvas Items
const stars = ref<{ id: number; x: number; y: number; size: number; isGold: boolean; caught: boolean }[]>([])

const fetchAppData = async () => {
  isLoading.value = true
  try {
    const res = await $api('/app/home')
    if (res?.success && res?.data) {
      user.value = res.data.user
      activation.value = res.data.activation
      todayPlan.value = res.data.today
      progress.value = res.data.progress
    }
  }
  catch (err: any) {
    console.error('Home API fetch error:', err)
  }
  finally {
    isLoading.value = false
  }
}

const redeemCode = async () => {
  if (!activationCodeInput.value) return
  try {
    const res = await $api('/app/activations/redeem', {
      method: 'POST',
      body: { code: activationCodeInput.value },
    })
    if (res?.success) {
      alertMessage.value = { text: 'تم تفعيل كود الاشتراك والمنهج بنجاح! 🎉', color: 'success' }
      await fetchAppData()
      activeTab.value = 'home'
    }
  }
  catch (err: any) {
    alertMessage.value = { text: err?.data?.message || 'فشل تفعيل الكود', color: 'error' }
  }
}

const startGame = async (game: any) => {
  activeGame.value = game
  activeTab.value = 'game'
  isPlaying.value = true
  gameFinished.value = false
  gameScore.value = 0
  gameAttempts.value = 0
  gameCorrect.value = 0
  gameIncorrect.value = 0
  gameTimer.value = game.duration_seconds || 30

  // Generate interactive targets
  stars.value = Array.from({ length: 8 }, (_, i) => ({
    id: i,
    x: Math.floor(Math.random() * 80) + 10,
    y: Math.floor(Math.random() * 70) + 15,
    size: Math.floor(Math.random() * 20) + 36,
    isGold: i % 4 !== 0,
    caught: false,
  }))

  try {
    const sessionRes = await $api(`/app/games/${game.id}/sessions`, {
      method: 'POST',
      body: { curriculum_day_id: todayPlan.value?.day?.id },
    })
    if (sessionRes?.success)
      currentSession.value = sessionRes.data
  }
  catch (err) {
    console.error('Session start error:', err)
  }

  // Timer loop
  clearInterval(gameTimerInterval.value)
  gameTimerInterval.value = setInterval(() => {
    if (gameTimer.value > 0) {
      gameTimer.value--
    }
    else {
      finishGame()
    }
  }, 1000)
}

const catchStar = (star: any) => {
  if (star.caught || !isPlaying.value) return
  star.caught = true
  gameAttempts.value++

  if (star.isGold) {
    gameCorrect.value++
    gameScore.value += 15
  }
  else {
    gameIncorrect.value++
    gameScore.value = Math.max(0, gameScore.value - 5)
  }

  // Check if all gold stars are caught
  if (stars.value.filter(s => s.isGold && !s.caught).length === 0) {
    finishGame()
  }
}

const finishGame = async () => {
  clearInterval(gameTimerInterval.value)
  isPlaying.value = false
  gameFinished.value = true

  const totalAttempts = Math.max(1, gameAttempts.value)
  const duration = (activeGame.value?.duration_seconds || 30) - gameTimer.value

  if (currentSession.value) {
    try {
      await $api(`/app/sessions/${currentSession.value.id}/complete`, {
        method: 'POST',
        body: {
          attempts: totalAttempts,
          correct_attempts: gameCorrect.value,
          incorrect_attempts: gameIncorrect.value,
          duration_seconds: Math.max(5, duration),
          score: gameScore.value,
        },
      })
      await fetchAppData()
    }
    catch (err) {
      console.error('Complete session error:', err)
    }
  }
}

onMounted(() => {
  fetchAppData()
})

onUnmounted(() => {
  clearInterval(gameTimerInterval.value)
})
</script>

<template>
  <div class="simulation-container">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          محاكي تجربة تطبيق الطفل التفاعلي 📱✨
        </h2>
        <p class="text-muted mb-0">
          معاينة وتجربة الألعاب والتمارين التفاعلية كما تظهر للطفل في تطبيق الجوال مع استقبال القياسات اللحظية
        </p>
      </div>
      <div class="d-flex gap-2">
        <VBtn
          :color="activeTab === 'home' ? 'primary' : 'default'"
          variant="tonal"
          prepend-icon="tabler-smart-home"
          @click="activeTab = 'home'"
        >
          شاشة اليوم
        </VBtn>
        <VBtn
          :color="activeTab === 'progress' ? 'primary' : 'default'"
          variant="tonal"
          prepend-icon="tabler-chart-pie"
          @click="activeTab = 'progress'"
        >
          لوحة الإنجاز
        </VBtn>
        <VBtn
          :color="activeTab === 'activation' ? 'primary' : 'default'"
          variant="tonal"
          prepend-icon="tabler-key"
          @click="activeTab = 'activation'"
        >
          تفعيل كود
        </VBtn>
      </div>
    </div>

    <!-- Alert -->
    <VAlert
      v-if="alertMessage"
      :color="alertMessage.color"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="alertMessage = null"
    >
      {{ alertMessage.text }}
    </VAlert>

    <!-- Mobile Device Frame Container -->
    <div class="d-flex justify-center my-6">
      <div class="mobile-device-frame elevation-12">
        <!-- Phone Top Speaker & Camera -->
        <div class="phone-notch">
          <div class="speaker"></div>
          <div class="camera"></div>
        </div>

        <!-- Phone Screen Content -->
        <div class="phone-screen">
          <!-- 1. Home / Daily Training Tab -->
          <div v-if="activeTab === 'home'" class="pa-4 app-home-view">
            <!-- App Header -->
            <div class="d-flex justify-space-between align-center mb-4">
              <div class="d-flex align-center gap-2">
                <VAvatar color="primary" size="40">
                  <VIcon icon="tabler-mood-happy" color="white" size="24" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold text-subtitle-1">مرحباً يا بطل! 🌟</div>
                  <div class="text-caption text-muted">فارس الصغير</div>
                </div>
              </div>
              <VChip size="small" color="primary" variant="flat">
                🔥 3 أيام متتالية
              </VChip>
            </div>

            <!-- Active Subscription Card -->
            <VCard
              v-if="todayPlan"
              class="mb-4 bg-gradient-primary text-white pa-4 rounded-xl"
              elevation="0"
            >
              <div class="d-flex justify-space-between align-center mb-1">
                <span class="text-caption opacity-90">{{ todayPlan.curriculum?.name }}</span>
                <VChip size="x-small" color="white" class="text-primary font-weight-bold">
                  {{ todayPlan.day?.name }}
                </VChip>
              </div>
              <h3 class="text-h6 font-weight-bold text-white mb-2">خطة تدريب اليوم 🎯</h3>
              <p class="text-caption opacity-90 mb-3">
                أكمل الألعاب التدريبية لكسب النجوم وفتح الأوسمة!
              </p>
              <VProgressLinear
                :model-value="todayPlan.day?.completion_rate || 0"
                color="white"
                height="8"
                rounded
              />
            </VCard>

            <VCard v-else class="mb-4 pa-4 rounded-xl border text-center">
              <VIcon icon="tabler-lock" color="warning" size="36" class="mb-2" />
              <h4 class="font-weight-bold">لا يوجد اشتراك مفعل</h4>
              <p class="text-caption text-muted mb-3">قم بتفعيل كود الاشتراك لبدء رحلة التدريب</p>
              <VBtn size="small" color="primary" @click="activeTab = 'activation'">
                تفعيل كود الآن
              </VBtn>
            </VCard>

            <!-- Playable Games List -->
            <h4 class="font-weight-bold text-subtitle-2 mb-3 text-primary">
              ألعاب وتحديات اليوم ({{ todayPlan?.games?.length || 0 }}):
            </h4>

            <div class="d-flex flex-column gap-3">
              <VCard
                v-for="game in todayPlan?.games"
                :key="game.id"
                class="pa-3 rounded-xl game-item-card border"
                elevation="0"
                @click="startGame(game)"
              >
                <div class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center gap-3">
                    <VAvatar color="primary" variant="tonal" size="46" rounded="lg">
                      <VIcon icon="tabler-device-gamepad-2" size="26" />
                    </VAvatar>
                    <div>
                      <div class="font-weight-bold text-subtitle-2">{{ game.name }}</div>
                      <div class="text-caption text-muted">{{ game.axis?.name }} • {{ game.duration_seconds }}ث</div>
                    </div>
                  </div>
                  <VBtn
                    size="small"
                    color="primary"
                    variant="flat"
                    icon="tabler-player-play"
                    rounded="circle"
                  />
                </div>
              </VCard>

              <div v-if="!todayPlan?.games || todayPlan.games.length === 0" class="text-center py-6 text-muted text-caption">
                لا توجد ألعاب متبقية لليوم! أحسنت يا بطل 👏
              </div>
            </div>
          </div>

          <!-- 2. Interactive Game Playground -->
          <div v-if="activeTab === 'game'" class="h-100 d-flex flex-column game-screen-view">
            <!-- Game Top Bar -->
            <div class="pa-3 bg-surface border-b d-flex justify-space-between align-center">
              <div class="d-flex align-center gap-2">
                <VBtn icon="tabler-arrow-right" size="small" variant="text" @click="activeTab = 'home'; finishGame()" />
                <span class="font-weight-bold text-subtitle-2">{{ activeGame?.name }}</span>
              </div>
              <div class="d-flex align-center gap-2">
                <VChip size="small" color="warning" prepend-icon="tabler-star">
                  {{ gameScore }}
                </VChip>
                <VChip size="small" color="error" prepend-icon="tabler-clock">
                  {{ gameTimer }}ث
                </VChip>
              </div>
            </div>

            <!-- Canvas Interactive Game Area -->
            <div v-if="isPlaying" class="flex-grow-1 position-relative game-canvas overflow-hidden">
              <div class="canvas-instruction pa-2 text-center text-caption font-weight-bold">
                👆 انقر على النجوم اللامعة بأسرع ما يمكن!
              </div>

              <!-- Animated Targets -->
              <div
                v-for="star in stars"
                :key="star.id"
                class="game-target"
                :class="{ 'is-caught': star.caught, 'is-gold': star.isGold, 'is-cloud': !star.isGold }"
                :style="{ top: `${star.y}%`, left: `${star.x}%`, width: `${star.size}px`, height: `${star.size}px` }"
                @click="catchStar(star)"
              >
                <VIcon
                  :icon="star.isGold ? 'tabler-star-filled' : 'tabler-cloud-filled'"
                  :color="star.isGold ? 'amber' : 'blue-grey'"
                  :size="star.size"
                />
              </div>
            </div>

            <!-- Game Finished Result View -->
            <div v-if="gameFinished" class="flex-grow-1 d-flex flex-column align-center justify-center pa-6 text-center">
              <VAvatar color="warning" variant="tonal" size="80" class="mb-4">
                <VIcon icon="tabler-trophy" size="48" color="amber" />
              </VAvatar>
              <h3 class="text-h5 font-weight-bold text-primary mb-1">أحسنت يا بطل! 🎉</h3>
              <p class="text-caption text-muted mb-4">تم إكمال جلسة اللعبة وإرسال النتائج بنجاح</p>

              <div class="bg-background pa-4 rounded-xl w-100 text-caption mb-6">
                <div class="d-flex justify-space-between mb-2">
                  <span>النقاط المكتسبة:</span>
                  <span class="font-weight-bold text-warning">{{ gameScore }} نقطة</span>
                </div>
                <div class="d-flex justify-space-between mb-2">
                  <span>المحاولات الصحيحة:</span>
                  <span class="font-weight-bold text-success">{{ gameCorrect }} من {{ gameAttempts }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span>نسبة الدقة:</span>
                  <span class="font-weight-bold text-primary">
                    {{ gameAttempts > 0 ? Math.round((gameCorrect / gameAttempts) * 100) : 100 }}%
                  </span>
                </div>
              </div>

              <VBtn block color="primary" size="large" @click="activeTab = 'home'">
                العودة لليوم التدريبي
              </VBtn>
            </div>
          </div>

          <!-- 3. Progress Tab -->
          <div v-if="activeTab === 'progress'" class="pa-4">
            <h3 class="text-h6 font-weight-bold mb-4 text-primary">لوحة الإنجاز والتقدم 📊</h3>

            <VRow class="mb-4">
              <VCol cols="6">
                <VCard class="pa-3 bg-surface rounded-xl border text-center">
                  <div class="text-caption text-muted">الجلسات المكتملة</div>
                  <div class="text-h5 font-weight-bold text-primary">
                    {{ progress?.total_games_completed || 0 }}
                  </div>
                </VCard>
              </VCol>
              <VCol cols="6">
                <VCard class="pa-3 bg-surface rounded-xl border text-center">
                  <div class="text-caption text-muted">متوسط الدقة</div>
                  <div class="text-h5 font-weight-bold text-success">
                    {{ progress?.average_accuracy || 0 }}%
                  </div>
                </VCard>
              </VCol>
            </VRow>

            <h4 class="font-weight-bold text-subtitle-2 mb-3">الأداء حسب المحاور:</h4>
            <div class="d-flex flex-column gap-3">
              <div
                v-for="ax in progress?.axes_progress"
                :key="ax.axis_id"
                class="pa-3 bg-surface border rounded-xl"
              >
                <div class="d-flex justify-space-between text-caption font-weight-bold mb-1">
                  <span>{{ ax.name }}</span>
                  <span>{{ ax.average_accuracy }}%</span>
                </div>
                <VProgressLinear
                  :model-value="ax.average_accuracy"
                  color="primary"
                  height="6"
                  rounded
                />
              </div>
            </div>

          </div>

          <!-- 4. Activation Tab -->
          <div v-if="activeTab === 'activation'" class="pa-6 text-center">
            <VAvatar color="primary" variant="tonal" size="64" class="mb-3">
              <VIcon icon="tabler-key" size="36" />
            </VAvatar>
            <h3 class="text-h6 font-weight-bold mb-1">تفعيل كود الاشتراك</h3>
            <p class="text-caption text-muted mb-4">
              أدخل كود التفعيل الممنوح لك لفتح المنهج التدريبي
            </p>

            <VTextField
              v-model="activationCodeInput"
              label="كود التفعيل"
              placeholder="DEMO-2026-RIHL-0001"
              class="mb-4"
              dir="ltr"
            />

            <VBtn block color="primary" size="large" @click="redeemCode">
              تفعيل الاشتراك الآن
            </VBtn>
          </div>
        </div>

        <!-- Phone Bottom Bar -->
        <div class="phone-bottom-bar">
          <div class="home-indicator"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mobile-device-frame {
  width: 380px;
  height: 720px;
  background: #1e1e24;
  border-radius: 44px;
  padding: 12px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
  display: flex;
  flex-direction: column;
  position: relative;
  border: 4px solid #333340;
}

.phone-notch {
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.phone-notch .speaker {
  width: 50px;
  height: 4px;
  background: #444;
  border-radius: 4px;
}

.phone-notch .camera {
  width: 8px;
  height: 8px;
  background: #444;
  border-radius: 50%;
}

.phone-screen {
  flex-grow: 1;
  background: #f8f7fa;
  border-radius: 32px;
  overflow-y: auto;
  position: relative;
}

.phone-bottom-bar {
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.home-indicator {
  width: 120px;
  height: 4px;
  background: #666;
  border-radius: 4px;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, #7367f0 0%, #a855f7 100%);
}

.game-item-card {
  transition: transform 0.2s ease;
  cursor: pointer;
}
.game-item-card:hover {
  transform: scale(1.02);
}

.game-canvas {
  background: radial-gradient(circle, #1a103c 0%, #0d0722 100%);
}

.canvas-instruction {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  backdrop-filter: blur(4px);
}

.game-target {
  position: absolute;
  cursor: pointer;
  transform: translate(-50%, -50%);
  transition: transform 0.15s ease, opacity 0.2s ease;
  animation: floatTarget 2.5s ease-in-out infinite alternate;
}

.game-target.is-caught {
  transform: translate(-50%, -50%) scale(0);
  opacity: 0;
  pointer-events: none;
}

@keyframes floatTarget {
  0% { transform: translate(-50%, -50%) translateY(0px); }
  100% { transform: translate(-50%, -50%) translateY(-12px); }
}
</style>
