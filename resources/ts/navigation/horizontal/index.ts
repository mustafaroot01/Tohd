import type { HorizontalNavItems } from '@layouts/types'

export default [
  {
    title: 'الرئيسية',
    to: 'root',
    icon: { icon: 'tabler-smart-home' },
  },
  {
    title: 'المحاور والمهارات',
    to: 'axes',
    icon: { icon: 'tabler-target' },
  },
  {
    title: 'الألعاب',
    to: 'games',
    icon: { icon: 'tabler-device-gamepad-2' },
  },
  {
    title: 'الوسائط',
    to: 'assets',
    icon: { icon: 'tabler-photo-spark' },
  },
  {
    title: 'المناهج',
    to: 'curriculums',
    icon: { icon: 'tabler-books' },
  },
  {
    title: 'المنتجات',
    to: 'products',
    icon: { icon: 'tabler-packages' },
  },
  {
    title: 'أكواد التفعيل',
    to: 'activation-codes',
    icon: { icon: 'tabler-key' },
  },
  {
    title: 'المستخدمون',
    to: 'users',
    icon: { icon: 'tabler-users' },
  },
] as HorizontalNavItems
