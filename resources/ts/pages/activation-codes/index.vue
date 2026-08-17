<script setup lang="ts">
const activationCodes = ref<any[]>([])
const products = ref<any[]>([])
const isLoading = ref(true)
const isGenerateDialogVisible = ref(false)
const isSubmitting = ref(false)
const isRevoking = ref(false)
const notification = ref<{ text: string; color: string } | null>(null)
const confirmRevoke = ref(false)
const pendingRevokeCode = ref<any | null>(null)

const generateForm = ref({
  product_id: '',
  quantity: 5,
  expires_at: '',
})

const fetchCodes = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/activation-codes')
    if (res?.success)
      activationCodes.value = res.data

    const prodRes = await $api('/admin/products')
    if (prodRes?.success)
      products.value = prodRes.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
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
      notification.value = { text: res.message || 'تم توليد أكواد التفعيل بنجاح!', color: 'success' }
      await fetchCodes()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل توليد الأكواد', color: 'error' }
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
  if (!pendingRevokeCode.value) return
  isRevoking.value = true
  try {
    const res = await $api(`/admin/activation-codes/${pendingRevokeCode.value.id}/revoke`, { method: 'POST' })
    if (res?.success) {
      notification.value = { text: 'تم إلغاء كود التفعيل بنجاح', color: 'info' }
      await fetchCodes()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل إلغاء الكود', color: 'error' }
  }
  finally {
    isRevoking.value = false
    confirmRevoke.value = false
    pendingRevokeCode.value = null
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'AVAILABLE': return 'success'
    case 'ACTIVATED': return 'primary'
    case 'REVOKED': return 'error'
    case 'EXPIRED': return 'secondary'
    default: return 'default'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'AVAILABLE': return 'متاح للاستخدام'
    case 'ACTIVATED': return 'تم التفعيل'
    case 'REVOKED': return 'ملغى'
    case 'EXPIRED': return 'منتهي الصلاحية'
    default: return status
  }
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  notification.value = { text: `تم نسخ الكود (${text}) إلى الحافظة`, color: 'success' }
}

onMounted(() => {
  fetchCodes()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          أكواد التفعيل والاشتراكات 🔑
        </h2>
        <p class="text-muted mb-0">
          توليد وتتبع أكواد التفعيل المخصصة للمشتركين وتفاصيل الاستهلاك والحالة
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-key"
        @click="isGenerateDialogVisible = true"
      >
        توليد دفعة أكواد جديدة
      </VBtn>
    </div>

    <!-- Notification -->
    <VAlert
      v-if="notification"
      :color="notification.color"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="notification = null"
    >
      {{ notification.text }}
    </VAlert>

    <!-- Activation Codes Table -->
    <VCard>
      <VCardText class="pa-0">
        <VTable hover class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-start">كود التفعيل</th>
              <th class="text-start">الباقة / المنتج</th>
              <th class="text-start">الحالة</th>
              <th class="text-start">المستخدم المفعّل</th>
              <th class="text-start">تاريخ الصلاحية</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="code in activationCodes" :key="code.id">
              <td>
                <div class="d-flex align-center gap-2">
                  <span class="font-weight-bold text-primary"><code>{{ code.code }}</code></span>
                  <VBtn
                    icon="tabler-copy"
                    size="x-small"
                    variant="text"
                    color="primary"
                    @click="copyToClipboard(code.code)"
                  />
                </div>
              </td>
              <td>
                <div class="font-weight-medium">{{ code.product?.name || 'غير محدد' }}</div>
                <div class="text-caption text-muted">{{ code.product?.duration_days }} يوماً</div>
              </td>
              <td>
                <VChip
                  size="small"
                  :color="getStatusColor(code.status)"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  {{ getStatusLabel(code.status) }}
                </VChip>
              </td>
              <td>
                <div v-if="code.user" class="font-weight-medium">
                  {{ code.user.name }}
                  <div class="text-caption text-muted">{{ code.user.email }}</div>
                </div>
                <span v-else class="text-muted text-caption">-</span>
              </td>
              <td>
                <span class="text-caption" v-if="code.expires_at">
                  {{ new Date(code.expires_at).toLocaleDateString('ar-SA') }}
                </span>
                <span v-else class="text-muted text-caption">غير محدد</span>
              </td>
              <td class="text-center">
                <VBtn
                  v-if="code.status === 'AVAILABLE'"
                  size="small"
                  color="error"
                  variant="tonal"
                  prepend-icon="tabler-ban"
                  @click="requestRevokeCode(code)"
                >
                  إلغاء الكود
                </VBtn>
              </td>
            </tr>

            <tr v-if="activationCodes.length === 0 && !isLoading">
              <td colspan="6" class="text-center py-8 text-muted">
                لا توجد أكواد تفعيل مضافة في النظام.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <!-- Generate Codes Dialog -->
    <VDialog v-model="isGenerateDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">توليد دفعة جديدة من أكواد التفعيل</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VSelect
            v-model="generateForm.product_id"
            :items="products"
            item-title="name"
            item-value="id"
            label="الباقة / المنتج المستهدف"
            class="mb-4"
          />

          <VTextField
            v-model.number="generateForm.quantity"
            type="number"
            min="1"
            max="100"
            label="عدد الأكواد المطلوبة (1 - 100)"
            class="mb-4"
          />

          <VTextField
            v-model="generateForm.expires_at"
            type="date"
            label="تاريخ انتهاء الصلاحية (اختياري)"
          />
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isGenerateDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="generateCodes">توليد الأكواد الآن</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    <!-- Confirm Revoke Modal -->
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
