<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const products = ref<any[]>([])
const isGenerateDialogVisible = ref(false)
const isSubmitting = ref(false)
const isRevoking = ref(false)
const { notification, notifySuccess, notifyInfo, notifyError } = useNotification()
const confirmRevoke = ref(false)
const pendingRevokeCode = ref<any | null>(null)

const generateForm = ref({
  product_id: '',
  quantity: 5,
  expires_at: '',
})

const table = useServerTable('/admin/activation-codes', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null, product_id: null },
})

const headers = computed<DataTableHeader[]>(() => [
  { title: 'كود التفعيل', key: 'code', sortable: true, hideable: false },
  {
    title: 'الباقة / المنتج',
    key: 'product',
    filter: {
      key: 'product_id',
      anyLabel: 'كل الباقات',
      options: products.value.map(p => ({ title: p.name, value: p.id })),
    },
  },
  {
    title: 'الحالة',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(ACTIVATION_STATUS) },
  },
  { title: 'المشترك المفعّل', key: 'activated_user' },
  { title: 'تاريخ التفعيل', key: 'activated_at', sortable: true, hidden: true },
  { title: 'تاريخ الصلاحية', key: 'expires_at', sortable: true },
  { title: 'الإجراءات', key: 'actions', align: 'center', hideable: false },
])

const fetchProducts = async () => {
  try {
    const res = await $api('/admin/products', { query: { per_page: 100 } })
    if (res?.success)
      products.value = res.data
  }
  catch (err) {
    console.error(err)
  }
}

const generateCodes = async () => {
  if (!generateForm.value.product_id || generateForm.value.quantity < 1)
    return

  isSubmitting.value = true
  try {
    const res = await $api('/admin/activation-codes/generate', {
      method: 'POST',
      body: generateForm.value,
    })
    if (res?.success) {
      isGenerateDialogVisible.value = false
      notifySuccess(res.message || 'تم توليد أكواد التفعيل بنجاح!')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل توليد الأكواد')
  }
  finally {
    isSubmitting.value = false
  }
}

const requestRevokeCode = (code: any) => {
  pendingRevokeCode.value = code
  confirmRevoke.value = true
}

const revokeCode = async () => {
  if (!pendingRevokeCode.value)
    return

  isRevoking.value = true
  try {
    const res = await $api(`/admin/activation-codes/${pendingRevokeCode.value.id}/revoke`, { method: 'POST' })
    if (res?.success) {
      notifyInfo('تم إلغاء كود التفعيل بنجاح')
      await table.reload()
    }
  }
  catch (err: any) {
    notifyError(err, 'فشل إلغاء الكود')
  }
  finally {
    isRevoking.value = false
    confirmRevoke.value = false
    pendingRevokeCode.value = null
  }
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  notifySuccess(`تم نسخ الكود (${text}) إلى الحافظة`)
}

onMounted(fetchProducts)
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        أكواد التفعيل والاشتراكات 🔑
      </h2>
      <p class="text-muted mb-0">
        توليد وتتبع أكواد التفعيل المخصصة للمشتركين وتفاصيل الاستهلاك والحالة
      </p>
    </div>

    <AppNotification v-model="notification" />

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="أكواد التفعيل"
      icon="tabler-key"
      search-placeholder="ابحث برقم الكود…"
      add-label="توليد دفعة أكواد"
      add-icon="tabler-key"
      empty-text="لا توجد أكواد تفعيل مضافة في النظام."
      empty-icon="tabler-key-off"
      @add="isGenerateDialogVisible = true"
    >
      <template #item.code="{ item }">
        <div class="d-flex align-center gap-2">
          <span class="font-weight-bold text-primary" dir="ltr">{{ item.code }}</span>
          <VBtn
            icon="tabler-copy"
            size="x-small"
            variant="text"
            color="primary"
            @click="copyToClipboard(item.code)"
          />
        </div>
      </template>

      <template #item.product="{ item }">
        <div class="font-weight-medium">
          {{ item.product?.name || 'غير محدد' }}
        </div>
        <div v-if="item.product?.duration_days" class="text-caption text-muted">
          {{ item.product.duration_days }} يوماً
        </div>
      </template>

      <template #item.status="{ item }">
        <VChip
          size="small"
          :color="statusColor(ACTIVATION_STATUS, item.status)"
          variant="tonal"
          class="font-weight-medium"
        >
          {{ statusLabel(ACTIVATION_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.activated_user="{ item }">
        <div v-if="item.activated_user?.name">
          <div class="font-weight-medium">
            {{ item.activated_user.name }}
          </div>
          <div v-if="item.activated_user.phone" class="text-caption text-muted" dir="ltr">
            {{ item.activated_user.phone }}
          </div>
        </div>
        <span v-else class="text-muted text-caption">—</span>
      </template>

      <template #item.activated_at="{ item }">
        <span class="text-caption">{{ formatDate(item.activated_at) }}</span>
      </template>

      <template #item.expires_at="{ item }">
        <span v-if="item.expires_at" class="text-caption">{{ formatDate(item.expires_at) }}</span>
        <span v-else class="text-muted text-caption">غير محدد</span>
      </template>

      <template #item.actions="{ item }">
        <div class="text-center">
          <VBtn
            v-if="item.status === 'AVAILABLE'"
            size="small"
            color="error"
            variant="tonal"
            prepend-icon="tabler-ban"
            @click="requestRevokeCode(item)"
          >
            إلغاء الكود
          </VBtn>
          <span v-else class="text-muted text-caption">—</span>
        </div>
      </template>
    </AppDataTableServer>

    <!-- Generate Codes Dialog -->
    <VDialog v-model="isGenerateDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">
          توليد دفعة جديدة من أكواد التفعيل
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <AppSelect
            v-model="generateForm.product_id"
            :items="products"
            item-title="name"
            item-value="id"
            label="الباقة / المنتج المستهدف"
            class="mb-4"
          />

          <AppTextField
            v-model.number="generateForm.quantity"
            type="number"
            min="1"
            max="100"
            label="عدد الأكواد المطلوبة (1 - 100)"
            class="mb-4"
          />

          <AppTextField
            v-model="generateForm.expires_at"
            type="date"
            label="تاريخ انتهاء الصلاحية (اختياري)"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isGenerateDialogVisible = false">
            إلغاء
          </VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="generateCodes">
            توليد الأكواد الآن
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ConfirmDeleteDialog
      v-model="confirmRevoke"
      title="تأكيد إلغاء الكود"
      :item-name="pendingRevokeCode?.code"
      confirm-label="نعم، ألغي الكود"
      :loading="isRevoking"
      @confirm="revokeCode"
    />
  </div>
</template>
