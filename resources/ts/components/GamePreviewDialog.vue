<script setup lang="ts">
/**
 * Plays a game the way the child will see it, inside a phone frame:
 *
 *   جاهز → بدء (صوت البداية) → اللعب (الرسوم + مؤقّت) → النهاية (صوت النهاية)
 *
 * The point is to let a content editor hear and see exactly what they assembled
 * before publishing, without waiting for the mobile app to be built.
 */
import { resolveAssetUrl } from '@/utils/assetUrl'

interface Props {
  modelValue: boolean
  game: any | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

type Phase = 'ready' | 'starting' | 'playing' | 'finished'

const phase = ref<Phase>('ready')
const remaining = ref(0)
const isMuted = ref(false)
const nowPlaying = ref<string | null>(null)

const lottieJson = ref('')
const isLoadingLottie = ref(false)
const lottieError = ref('')
const lottieEl = ref<any>(null)

let ticker: ReturnType<typeof setInterval> | null = null
let audioEl: HTMLAudioElement | null = null

// ── what the game is made of ────────────────────────────────────────────────
const assets = computed<any[]>(() => props.game?.assets ?? [])

/**
 * The main visual: whatever was attached in the ANIMATION role — a Lottie, a
 * video or a still image — falling back to the first visual file of any role.
 */
const VISUAL_TYPES = ['LOTTIE', 'VIDEO', 'IMAGE']

const animation = computed(() => {
  const visuals = assets.value.filter(a => VISUAL_TYPES.includes(a.type))

  return visuals.find(a => a.role === 'ANIMATION') ?? visuals[0] ?? null
})

const visualKind = computed<'LOTTIE' | 'VIDEO' | 'IMAGE' | null>(() => animation.value?.type ?? null)
const visualSrc = computed(() => (animation.value?.url ? resolveAssetUrl(animation.value.url) : ''))
const videoEl = ref<HTMLVideoElement | null>(null)

const audioByRole = (role: string) => {
  const found = assets.value.find(a => a.type === 'AUDIO' && a.role === role)

  return found ? { ...found, src: resolveAssetUrl(found.url) } : null
}

const startAudio = computed(() => audioByRole('START_AUDIO'))
const endAudio = computed(() => audioByRole('END_AUDIO'))

const otherAudio = computed(() =>
  assets.value
    .filter(a => a.type === 'AUDIO' && a.role !== 'START_AUDIO' && a.role !== 'END_AUDIO')
    .map(a => ({ ...a, src: resolveAssetUrl(a.url) })))

// ── parameters ──────────────────────────────────────────────────────────────
const durationSeconds = computed(() => Math.max(1, Number(props.game?.duration_seconds ?? 60)))
const attempts = computed(() => Number(props.game?.config?.attempts ?? 0))
const attemptsLabel = computed(() => (attempts.value === 0 ? '∞' : String(attempts.value)))
const successOutOfTen = computed(() => Math.round((props.game?.config?.success_threshold ?? 0) * 10))

/** The pass mark expressed the way it is actually measured: seconds of attention. */
const requiredSeconds = computed(() =>
  Math.round((successOutOfTen.value / 10) * durationSeconds.value))

const clock = computed(() => {
  const s = Math.max(0, remaining.value)

  return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`
})

const progress = computed(() =>
  ((durationSeconds.value - remaining.value) / durationSeconds.value) * 100)

/** Reads back in whichever unit the editor most likely typed. */
const durationLabel = computed(() => {
  const s = durationSeconds.value

  return s % 60 === 0 && s >= 60 ? `${s / 60} دقيقة` : `${s} ثانية`
})

// ── audio ───────────────────────────────────────────────────────────────────
const stopAudio = () => {
  if (audioEl) {
    audioEl.pause()
    audioEl.currentTime = 0
    audioEl = null
  }
  nowPlaying.value = null
}

/** Resolves when the clip ends, or immediately when there is nothing to play. */
const playClip = (clip: { src: string; role_label?: string; name?: string } | null) =>
  new Promise<void>(resolve => {
    stopAudio()

    if (!clip?.src || isMuted.value) {
      resolve()

      return
    }

    const el = new Audio(clip.src)

    audioEl = el
    nowPlaying.value = clip.role_label || clip.name || null

    const done = () => {
      if (audioEl === el)
        nowPlaying.value = null
      resolve()
    }

    el.addEventListener('ended', done, { once: true })
    el.addEventListener('error', done, { once: true })
    el.play().catch(() => done())
  })

// ── the session ─────────────────────────────────────────────────────────────
const clearTicker = () => {
  if (ticker) {
    clearInterval(ticker)
    ticker = null
  }
}

const finish = async () => {
  clearTicker()
  phase.value = 'finished'
  lottieEl.value?.pause?.()
  videoEl.value?.pause()
  await playClip(endAudio.value)
}

const beginPlay = () => {
  phase.value = 'playing'
  remaining.value = durationSeconds.value
  lottieEl.value?.seek?.(0)
  lottieEl.value?.play?.()
  if (videoEl.value) {
    videoEl.value.currentTime = 0
    videoEl.value.muted = isMuted.value
    videoEl.value.play().catch(() => {})
  }

  clearTicker()
  ticker = setInterval(() => {
    remaining.value -= 1
    if (remaining.value <= 0)
      finish()
  }, 1000)
}

const start = async () => {
  phase.value = 'starting'
  await playClip(startAudio.value)

  // the dialog may be closed or reset while the intro is still playing
  if (phase.value === 'starting')
    beginPlay()
}

const reset = () => {
  clearTicker()
  stopAudio()
  phase.value = 'ready'
  remaining.value = durationSeconds.value
  lottieEl.value?.stop?.()
  if (videoEl.value) {
    videoEl.value.pause()
    videoEl.value.currentTime = 0
  }
}

const skipToEnd = () => {
  if (phase.value === 'playing' || phase.value === 'starting')
    finish()
}

const toggleMute = () => {
  isMuted.value = !isMuted.value
  if (isMuted.value)
    stopAudio()
  if (videoEl.value)
    videoEl.value.muted = isMuted.value
}

// ── animation ───────────────────────────────────────────────────────────────
const loadAnimation = async () => {
  lottieJson.value = ''
  lottieError.value = ''

  // video and image play straight from their URL; only Lottie needs its JSON
  if (!animation.value?.url || visualKind.value !== 'LOTTIE')
    return

  isLoadingLottie.value = true
  try {
    const res = await fetch(resolveAssetUrl(animation.value.url))
    if (!res.ok)
      throw new Error(`HTTP ${res.status}`)

    lottieJson.value = JSON.stringify(await res.json())
  }
  catch (err) {
    lottieError.value = 'تعذّر تحميل ملف الرسوم المتحركة'
    console.error('[GamePreviewDialog]', err)
  }
  finally {
    isLoadingLottie.value = false
  }
}

watch(() => props.modelValue, async open => {
  if (open) {
    reset()
    await loadAnimation()
  }
  else {
    clearTicker()
    stopAudio()
    lottieJson.value = ''
  }
})

onBeforeUnmount(() => {
  clearTicker()
  stopAudio()
})

const close = () => emit('update:modelValue', false)

const phaseLabel = computed(() => ({
  ready: 'جاهزة للبدء',
  starting: 'صوت البداية…',
  playing: 'قيد اللعب',
  finished: 'انتهت',
}[phase.value]))
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="880"
    scrollable
    @update:model-value="close"
  >
    <VCard class="game-preview">
      <VCardItem class="pb-3">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" rounded size="42">
            <VIcon icon="tabler-device-gamepad-2" size="24" />
          </VAvatar>
        </template>
        <VCardTitle class="font-weight-bold">
          {{ game?.name || 'معاينة اللعبة' }}
        </VCardTitle>
        <VCardSubtitle dir="ltr" class="text-start">
          {{ game?.code }}
        </VCardSubtitle>
        <template #append>
          <VBtn icon variant="text" size="small" @click="close">
            <VIcon icon="tabler-x" />
          </VBtn>
        </template>
      </VCardItem>

      <VDivider />

      <VCardText class="pa-0">
        <VRow no-gutters>
          <!-- ── the phone ── -->
          <VCol cols="12" md="6" class="stage-col">
            <div class="phone">
              <div class="phone__notch" />
              <div class="phone__screen">
                <div class="hud">
                  <div class="hud__chip">
                    <VIcon icon="tabler-clock" size="13" />
                    <span class="hud__value">{{ clock }}</span>
                  </div>
                  <div class="hud__chip">
                    <VIcon icon="tabler-repeat" size="13" />
                    <span class="hud__value">{{ attemptsLabel }}</span>
                  </div>
                  <div class="hud__chip">
                    <VIcon icon="tabler-target" size="13" />
                    <span class="hud__value">{{ successOutOfTen }}/10</span>
                  </div>
                </div>

                <div class="stage">
                  <div v-if="isLoadingLottie" class="stage__msg">
                    <VProgressCircular indeterminate color="primary" size="40" />
                  </div>

                  <lottie-player
                    v-else-if="lottieJson"
                    ref="lottieEl"
                    :src="lottieJson"
                    background="transparent"
                    speed="1"
                    loop
                    class="stage__lottie"
                  />

                  <video
                    v-else-if="visualKind === 'VIDEO' && visualSrc"
                    ref="videoEl"
                    :src="visualSrc"
                    class="stage__media"
                    playsinline
                    loop
                    preload="auto"
                  />

                  <img
                    v-else-if="visualKind === 'IMAGE' && visualSrc"
                    :src="visualSrc"
                    alt=""
                    class="stage__media"
                  >

                  <div v-else class="stage__msg">
                    <VIcon
                      :icon="lottieError ? 'tabler-alert-triangle' : 'tabler-animation-off'"
                      size="30"
                      :color="lottieError ? 'error' : undefined"
                    />
                    <span class="text-caption mt-2">{{ lottieError || 'لا يوجد ملف مرئي مرتبط' }}</span>
                  </div>

                  <div v-if="phase === 'ready'" class="veil">
                    <VBtn icon size="x-large" color="primary" class="veil__play" @click="start">
                      <VIcon icon="tabler-player-play-filled" size="34" />
                    </VBtn>
                    <div class="veil__text">
                      اضغط للبدء
                    </div>
                  </div>

                  <div v-else-if="phase === 'starting'" class="veil">
                    <VIcon icon="tabler-volume" size="34" class="veil__pulse" color="primary" />
                    <div class="veil__text mt-2">
                      صوت البداية…
                    </div>
                  </div>

                  <div v-else-if="phase === 'finished'" class="veil">
                    <VAvatar color="success" variant="flat" size="58" class="mb-3">
                      <VIcon icon="tabler-flag-check" size="30" />
                    </VAvatar>
                    <div class="veil__title">
                      انتهت اللعبة
                    </div>
                    <div class="veil__text mb-4">
                      المطلوب {{ requiredSeconds }} ثانية انتباه ({{ successOutOfTen }}/10)
                    </div>
                    <VBtn size="small" color="primary" variant="flat" prepend-icon="tabler-rotate" @click="reset">
                      إعادة
                    </VBtn>
                  </div>
                </div>

                <div class="timebar">
                  <div class="timebar__fill" :style="{ inlineSize: `${progress}%` }" />
                </div>
              </div>
            </div>

            <div class="transport">
              <VBtn
                v-if="phase === 'ready'"
                color="primary"
                prepend-icon="tabler-player-play"
                @click="start"
              >
                تشغيل المحاكاة
              </VBtn>
              <VBtn
                v-else
                variant="tonal"
                color="secondary"
                prepend-icon="tabler-rotate"
                @click="reset"
              >
                إعادة
              </VBtn>

              <VBtn
                variant="tonal"
                color="secondary"
                :disabled="phase === 'ready' || phase === 'finished'"
                prepend-icon="tabler-player-track-next"
                @click="skipToEnd"
              >
                إنهاء
              </VBtn>

              <VBtn
                icon
                variant="tonal"
                size="small"
                :color="isMuted ? 'error' : 'secondary'"
                @click="toggleMute"
              >
                <VIcon :icon="isMuted ? 'tabler-volume-off' : 'tabler-volume'" size="19" />
                <VTooltip activator="parent" location="top">
                  {{ isMuted ? 'إلغاء الكتم' : 'كتم الصوت' }}
                </VTooltip>
              </VBtn>
            </div>
          </VCol>

          <!-- ── the session timeline ── -->
          <VCol cols="12" md="6" class="side-col">
            <div class="d-flex align-center flex-wrap gap-2 mb-1">
              <VChip
                size="small"
                variant="tonal"
                :color="phase === 'playing' ? 'success' : phase === 'finished' ? 'primary' : 'secondary'"
              >
                {{ phaseLabel }}
              </VChip>
              <VChip v-if="nowPlaying" size="small" color="info" variant="tonal" prepend-icon="tabler-volume">
                {{ nowPlaying }}
              </VChip>
            </div>

            <div class="text-caption text-medium-emphasis mb-4">
              مراحل الجلسة كما سيسمعها الطفل
            </div>

            <div
              class="step"
              :class="{ 'step--on': phase === 'starting', 'step--done': phase === 'playing' || phase === 'finished' }"
            >
              <div class="step__dot">
                <VIcon icon="tabler-player-play" size="15" />
              </div>
              <div class="step__body">
                <div class="step__title">
                  بدء اللعبة
                </div>
                <template v-if="startAudio">
                  <div class="step__sub" dir="ltr">
                    {{ startAudio.name }} · {{ startAudio.code }}
                  </div>
                  <audio :src="startAudio.src" controls preload="none" class="step__audio" />
                </template>
                <div v-else class="step__missing">
                  لا يوجد صوت بداية — أضفه من حوار التعديل
                </div>
              </div>
            </div>

            <div class="step" :class="{ 'step--on': phase === 'playing', 'step--done': phase === 'finished' }">
              <div class="step__dot">
                <VIcon icon="tabler-device-gamepad" size="15" />
              </div>
              <div class="step__body">
                <div class="step__title">
                  اللعب
                </div>
                <div class="step__sub">
                  {{ durationLabel }} ·
                  {{ attempts === 0 ? 'محاولات غير محدودة' : `${attempts} محاولة` }}
                </div>
                <div v-if="otherAudio.length" class="mt-2 d-flex flex-column gap-2">
                  <div v-for="clip in otherAudio" :key="clip.id">
                    <div class="step__sub mb-1">
                      {{ clip.role_label || clip.name }}
                    </div>
                    <audio :src="clip.src" controls preload="none" class="step__audio" />
                  </div>
                </div>
              </div>
            </div>

            <div class="step" :class="{ 'step--on': phase === 'finished' }">
              <div class="step__dot">
                <VIcon icon="tabler-flag-check" size="15" />
              </div>
              <div class="step__body">
                <div class="step__title">
                  نهاية اللعبة
                </div>
                <template v-if="endAudio">
                  <div class="step__sub" dir="ltr">
                    {{ endAudio.name }} · {{ endAudio.code }}
                  </div>
                  <audio :src="endAudio.src" controls preload="none" class="step__audio" />
                </template>
                <div v-else class="step__missing">
                  لا يوجد صوت نهاية — أضفه من حوار التعديل
                </div>
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn color="primary" variant="tonal" @click="close">
          إغلاق
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.stage-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 24px;
  background: rgb(var(--v-theme-background));
}

.side-col {
  padding: 24px;
  border-inline-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.phone {
  position: relative;
  padding: 10px;
  border: 2px solid rgba(var(--v-border-color), 0.35);
  border-radius: 30px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 12px 32px -14px rgba(0, 0, 0, 45%);
  inline-size: 260px;
}

.phone__notch {
  position: absolute;
  z-index: 2;
  border-radius: 0 0 10px 10px;
  background: rgba(var(--v-border-color), 0.35);
  block-size: 15px;
  inline-size: 74px;
  inset-block-start: 10px;
  inset-inline-start: 50%;
  transform: translateX(50%);
}

.phone__screen {
  position: relative;
  display: flex;
  overflow: hidden;
  border-radius: 22px;
  background: rgb(var(--v-theme-background));
  block-size: 430px;
  flex-direction: column;
}

.hud {
  display: flex;
  gap: 6px;
  justify-content: center;
  padding: 22px 8px 8px;
}

.hud__chip {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 20px;
  background: rgba(var(--v-theme-on-surface), 0.07);
  font-size: 11px;
  gap: 4px;
}

.hud__value {
  font-feature-settings: "tnum";
  font-variant-numeric: tabular-nums;
}

.stage {
  position: relative;
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  min-block-size: 0;
}

.stage__lottie {
  block-size: 100%;
  inline-size: 100%;
}

.stage__media {
  display: block;
  max-block-size: 100%;
  max-inline-size: 100%;
  object-fit: contain;
}

.stage__msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.veil {
  position: absolute;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(var(--v-theme-background), 0.82);
  inset: 0;
}

.veil__play {
  box-shadow: 0 0 0 10px rgba(var(--v-theme-primary), 0.12);
  margin-block-end: 10px;
}

.veil__title {
  font-size: 15px;
  font-weight: 700;
}

.veil__text {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 12px;
}

.veil__pulse {
  animation: veil-pulse 1s ease-in-out infinite;
}

@keyframes veil-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.88); }
}

.timebar {
  background: rgba(var(--v-theme-on-surface), 0.09);
  block-size: 4px;
}

.timebar__fill {
  background: rgb(var(--v-theme-primary));
  block-size: 100%;
  transition: inline-size 1s linear;
}

.transport {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.step {
  display: flex;
  padding: 12px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  gap: 12px;
  margin-block-end: 12px;
  transition: border-color 0.25s ease, background 0.25s ease;
}

.step--on {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
}

.step--done {
  opacity: 0.62;
}

.step__dot {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(var(--v-theme-on-surface), 0.08);
  block-size: 28px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  inline-size: 28px;
}

.step--on .step__dot {
  background: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
}

.step__body {
  flex: 1;
  min-inline-size: 0;
}

.step__title {
  font-size: 14px;
  font-weight: 600;
}

.step__sub {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 11.5px;
  overflow-wrap: anywhere;
}

.step__missing {
  color: rgb(var(--v-theme-warning));
  font-size: 11.5px;
}

.step__audio {
  block-size: 32px;
  inline-size: 100%;
  margin-block-start: 8px;
}

@media (max-width: 959px) {
  .side-col {
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-inline-start: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .veil__pulse { animation: none; }
  .timebar__fill { transition: none; }
}
</style>
