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
    heading: 'المستخدمون والتجربة',
  },
  {
    title: 'المستخدمون والمشتركون',
    to: 'users',
    icon: { icon: 'tabler-users' },
  },
  {
    title: 'تجربة التطبيق (Live App)',
    to: 'app-simulation',
    icon: { icon: 'tabler-device-mobile-bolt' },
    badgeContent: 'مباشر',
    badgeClass: 'bg-primary text-white',
  },
] as VerticalNavItems
