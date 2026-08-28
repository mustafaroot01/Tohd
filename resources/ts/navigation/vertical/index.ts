import type { VerticalNavItems } from '@layouts/types'

export default [
  {
    heading: 'لوحة التحكم الرئيسية',
  },
  {
    title: 'الرئيسية والإحصائيات',
    to: 'root',
    icon: { icon: 'tabler-smart-home' },
  },
  {
    heading: 'إدارة المحتوى التدريبي',
  },
  {
    title: 'المحاور والمهارات',
    to: 'axes',
    icon: { icon: 'tabler-target' },
  },
  {
    title: 'مستويات الألعاب',
    to: 'levels',
    icon: { icon: 'tabler-award' },
  },
  {
    title: 'الألعاب التفاعلية',
    to: 'games',
    icon: { icon: 'tabler-device-gamepad-2' },
  },
  {
    title: 'مكتبة الوسائط و Lottie',
    to: 'assets',
    icon: { icon: 'tabler-photo-spark' },
  },
  {
    title: 'المناهج والخطط',
    to: 'curriculums',
    icon: { icon: 'tabler-books' },
  },
  {
    heading: 'الاشتراكات والتفعيل',
  },
  {
    title: 'الباقات والمنتجات',
    to: 'products',
    icon: { icon: 'tabler-packages' },
  },
  {
    title: 'أكواد التفعيل',
    to: 'activation-codes',
    icon: { icon: 'tabler-key' },
  },
  {
    heading: 'المستخدمون',
  },
  {
    title: 'مستخدمو النظام',
    to: 'users',
    icon: { icon: 'tabler-user-shield' },
  },
  {
    title: 'المشتركون',
    to: 'subscribers',
    icon: { icon: 'tabler-users' },
  },
  {
    title: 'المحافظات',
    to: 'governorates',
    icon: { icon: 'tabler-map-pin' },
  },
  {
    heading: 'إعدادات النظام والتهيئة',
  },
  {
    title: 'إعدادات النظام',
    to: 'settings',
    icon: { icon: 'tabler-settings-automation' },
  },
] as VerticalNavItems
