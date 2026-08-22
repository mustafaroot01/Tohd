<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const curriculums = ref<any[]>([])
const isAddProductDialogVisible = ref(false)
const isSubmitting = ref(false)
const { notification, notifySuccess, notifyInfo, notifyError } = useNotification()
const editingProductId = ref<string | null>(null)

const newProduct = ref({
  code: '',
  name: '',
  description: '',
  curriculum_id: '',
  duration_days: 30,
  price: 25000,
})

const table = useServerTable('/admin/products', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null, curriculum_id: null },
})

const headers = computed<DataTableHeader[]>(() => [
  { title: 'الباقة', key: 'name', sortable: true, hideable: false },
  { title: 'الكود', key: 'code', sortable: true },
  { title: 'السعر', key: 'price', sortable: true, align: 'start' },
  { title: 'المدة', key: 'duration_days', sortable: true },
  {
    title: 'المنهج المرتبط',
    key: 'curriculum_name',
    filter: {
      key: 'curriculum_id',
      anyLabel: 'كل المناهج',
      options: curriculums.value.map(c => ({ title: c.name, value: c.id })),
    },
  },
  {
    title: 'الحالة',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(PRODUCT_STATUS) },
  },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
])

const generateProductCode = () => {
  const random = Array.from({ length: 6 }, () => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 36)]).join('')

  return `PROD-${random}`
}

const openAddProductDialog = () => {
  editingProductId.value = null
  newProduct.value = {
    code: generateProductCode(),
    name: '',
    description: '',
    curriculum_id: '',
    duration_days: 30,
    price: 25000,
  }
  isAddProductDialogVisible.value = true
}

const openEditProductDialog = (prod: any) => {
  editingProductId.value = prod.id
  newProduct.value = {
    code: prod.code,
    name: prod.name,
    description: prod.description || '',
    curriculum_id: prod.curriculum_id || '',
    duration_days: prod.duration_days,
    price: prod.price,
  }
  isAddProductDialogVisible.value = true
}

const fetchCurriculums = async () => {
  try {
    const res = await $api('/admin/curriculums', { query: { per_page: 100 } })
    if (res?.success)
      curriculums.value = res.data
  }
  catch (err) {
    console.error(err)
  }
}

const saveProduct = async () => {
  if (!newProduct.value.name || !newProduct.value.code || !newProduct.value.curriculum_id)
    return

  isSubmitting.value = true
  try {
    const isEdit = !!editingProductId.value
    const url = isEdit ? `/admin/products/${editingProductId.value}` : '/admin/products'
    const method = isEdit ? 'PUT' : 'POST'

    const res = await $api(url, { method, body: newProduct.value })

    if (res?.success) {
      isAddProductDialogVisible.value = false
      notifySuccess(isEdit ? 'تم تحديث بيانات الباقة بنجاح!' : 'تمت إضافة الباقة بنجاح!')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل حفظ الباقة')
  }
  finally {
    isSubmitting.value = false
  }
}

const toggleStatus = async (product: any) => {
  try {
    const endpoint = product.status === 'ACTIVE'
      ? `/admin/products/${product.id}/deactivate`
      : `/admin/products/${product.id}/activate`

    const res = await $api(endpoint, { method: 'POST' })
    if (res?.success) {
      notifyInfo('تم تحديث حالة الباقة بنجاح')
      await table.reload()
    }
  }
  catch (err) {
    console.error(err)
  }
}

onMounted(fetchCurriculums)
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        الباقات والمنتجات التدريبية 📦
      </h2>
      <p class="text-muted mb-0">
        إدارة باقات الاشتراك، مدة الصلاحية بالأيام، وربطها بالمناهج التدريبية
      </p>
    </div>

    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="الباقات"
      icon="tabler-package"
      search-placeholder="ابحث بالاسم أو الكود…"
      add-label="إضافة باقة جديدة"
      empty-text="لا توجد باقات مضافة بعد."
      empty-icon="tabler-package-off"
      @add="openAddProductDialog"
    >
      <template #item.name="{ item }">
        <div>
          <div class="font-weight-bold text-high-emphasis">
            {{ item.name }}
          </div>
          <div v-if="item.description" class="text-caption text-muted text-truncate" style="max-inline-size: 20rem;">
            {{ item.description }}
          </div>
        </div>
      </template>

      <template #item.code="{ item }">
        <span dir="ltr" class="text-caption font-weight-medium">{{ item.code }}</span>
      </template>

      <template #item.price="{ item }">
        <span class="font-weight-bold text-primary">{{ formatNumber(item.price) }}</span>
        <span class="text-caption text-muted ms-1">{{ item.currency === 'IQD' ? 'د.ع' : item.currency }}</span>
      </template>

      <template #item.duration_days="{ item }">
        <VChip size="small" color="info" variant="tonal">
          {{ item.duration_days }} يوماً
        </VChip>
      </template>

      <template #item.curriculum_name="{ item }">
        <span v-if="item.curriculum_name" class="text-body-2">{{ item.curriculum_name }}</span>
        <span v-else class="text-muted text-caption">غير مرتبط</span>
      </template>

      <template #item.status="{ item }">
        <VChip
          size="small"
          :color="statusColor(PRODUCT_STATUS, item.status)"
          variant="tonal"
        >
          {{ statusLabel(PRODUCT_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex justify-center gap-1">
          <VBtn
            icon
            size="small"
            variant="text"
            color="warning"
            @click="openEditProductDialog(item)"
          >
            <VIcon icon="tabler-edit" size="20" />
            <VTooltip activator="parent" location="top">
              تعديل
            </VTooltip>
          </VBtn>
          <VBtn
            icon
            size="small"
            variant="text"
            :color="item.status === 'ACTIVE' ? 'warning' : 'success'"
            @click="toggleStatus(item)"
          >
            <VIcon :icon="item.status === 'ACTIVE' ? 'tabler-player-pause' : 'tabler-player-play'" size="20" />
            <VTooltip activator="parent" location="top">
              {{ item.status === 'ACTIVE' ? 'إيقاف الباقة' : 'تفعيل الباقة' }}
            </VTooltip>
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Create/Edit Product Dialog -->
    <VDialog v-model="isAddProductDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          {{ editingProductId ? 'تعديل باقة الاشتراك التدريبية' : 'إضافة باقة / منتج تدريبي' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="newProduct.code"
                label="كود المنتج"
                readonly
                dir="ltr"
                hint="يُولَّد تلقائياً"
                persistent-hint
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="newProduct.name"
                label="اسم الباقة"
                placeholder="مثال: باقة الشهرين التأسيسية"
              />
            </VCol>

            <VCol cols="12">
              <AppSelect
                v-model="newProduct.curriculum_id"
                :items="curriculums"
                item-title="name"
                item-value="id"
                label="المنهج التدريبي المرتبط"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="newProduct.duration_days"
                type="number"
                label="مدة الاشتراك (بالأيام)"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppTextField
                v-model.number="newProduct.price"
                type="number"
                label="السعر (بالدينار العراقي)"
              />
            </VCol>

            <VCol cols="12">
              <AppTextarea
                v-model="newProduct.description"
                label="وصف الباقة"
                rows="2"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddProductDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveProduct">
            حفظ الباقة
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
