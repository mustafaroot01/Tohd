/**
 * One place that says what every domain status looks like.
 *
 * Each map turns a backend enum value into the Arabic label and the Vuetify
 * colour the UI should use, and `statusOptions()` turns the same map into the
 * options a data-table column filter expects — so a label can never drift
 * between a chip and its filter.
 */

export interface StatusMeta {
  label: string
  color: string
}

export type StatusMap = Record<string, StatusMeta>

/** App\Enums\SubscriberStatus */
export const SUBSCRIBER_STATUS: StatusMap = {
  ACTIVE: { label: 'فعال', color: 'success' },
  UNVERIFIED: { label: 'غير مفعل', color: 'warning' },
  SUSPENDED: { label: 'موقوف', color: 'error' },
}

/** App\Enums\UserStatus */
export const USER_STATUS: StatusMap = {
  ACTIVE: { label: 'نشط', color: 'success' },
  INACTIVE: { label: 'معطل', color: 'secondary' },
  SUSPENDED: { label: 'موقوف', color: 'error' },
}

/** App\Enums\GameStatus */
export const GAME_STATUS: StatusMap = {
  DRAFT: { label: 'مسودة', color: 'primary' },
  TESTING: { label: 'قيد الاختبار', color: 'warning' },
  APPROVED: { label: 'معتمدة', color: 'info' },
  PUBLISHED: { label: 'فعالة', color: 'success' },
  ARCHIVED: { label: 'مؤرشفة', color: 'secondary' },
}

/** App\Enums\CurriculumStatus */
export const CURRICULUM_STATUS: StatusMap = {
  DRAFT: { label: 'مسودة', color: 'warning' },
  PUBLISHED: { label: 'منشور', color: 'success' },
  ARCHIVED: { label: 'مؤرشف', color: 'secondary' },
}

/** App\Enums\ProductStatus */
export const PRODUCT_STATUS: StatusMap = {
  ACTIVE: { label: 'نشطة', color: 'success' },
  INACTIVE: { label: 'غير نشطة', color: 'secondary' },
}

/** App\Enums\ActivationStatus */
export const ACTIVATION_STATUS: StatusMap = {
  AVAILABLE: { label: 'متاح للاستخدام', color: 'success' },
  ACTIVATED: { label: 'تم التفعيل', color: 'primary' },
  REVOKED: { label: 'ملغى', color: 'error' },
  EXPIRED: { label: 'منتهي الصلاحية', color: 'secondary' },
}

/** App\Enums\AssignmentStatus */
export const ASSIGNMENT_STATUS: StatusMap = {
  ACTIVE: { label: 'فعال', color: 'success' },
  EXPIRED: { label: 'منتهي', color: 'secondary' },
  CANCELLED: { label: 'ملغى', color: 'error' },
}

/** App\Enums\GameProgressStatus */
export const GAME_PROGRESS_STATUS: StatusMap = {
  NOT_STARTED: { label: 'لم تبدأ', color: 'secondary' },
  IN_PROGRESS: { label: 'قيد المحاولة', color: 'info' },
  PASSED: { label: 'ناجحة', color: 'success' },
  SKIPPED: { label: 'تم تخطّيها', color: 'warning' },
}

/** App\Enums\AssetType */
export const ASSET_TYPE: StatusMap = {
  LOTTIE: { label: 'رسوم Lottie المتحركة (JSON)', color: 'warning' },
  IMAGE: { label: 'صورة (PNG / JPG / SVG)', color: 'success' },
  AUDIO: { label: 'ملف صوتي (MP3 / WAV)', color: 'info' },
  VIDEO: { label: 'فيديو توضيحي (MP4)', color: 'secondary' },
}

/** games.difficulty */
export const GAME_DIFFICULTY: StatusMap = {
  easy: { label: 'سهل', color: 'success' },
  medium: { label: 'متوسط', color: 'warning' },
  hard: { label: 'صعب', color: 'error' },
}

/** The Arabic label for a value, falling back to the raw value. */
export const statusLabel = (map: StatusMap, value?: string | null) =>
  (value ? map[value]?.label ?? value : '—')

/** The Vuetify colour for a value. */
export const statusColor = (map: StatusMap, value?: string | null, fallback = 'secondary') =>
  (value ? map[value]?.color ?? fallback : fallback)

/** The same map as `{ title, value }` options for a table filter or a select. */
export const statusOptions = (map: StatusMap) =>
  Object.entries(map).map(([value, meta]) => ({ title: meta.label, value }))
