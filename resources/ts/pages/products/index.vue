<script setup lang="ts">
const products = ref<any[]>([])
const curriculums = ref<any[]>([])
const isLoading = ref(true)
const isAddProductDialogVisible = ref(false)
const isSubmitting = ref(false)
const notification = ref<{ text: string; color: string } | null>(null)

const newProduct = ref({
  code: '',
  name: '',
  description: '',
  curriculum_id: '',
  duration_days: 30,
  price: 199,
  currency: 'SAR',
})

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/products')
    if (res?.success)
      products.value = res.data

    const currRes = await $api('/admin/curriculums')
    if (currRes?.success)
      curriculums.value = currRes.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const saveProduct = async () => {
  if (!newProduct.value.name || !newProduct.value.code || !newProduct.value.curriculum_id)
    return

  isSubmitting.value = true
  try {
    const res = await $api('/admin/products', {
      method: 'POST',
      body: newProduct.value,
    })
    if (res?.success) {
      isAddProductDialogVisible.value = false
      newProduct.value = { code: '', name: '', description: '', curriculum_id: '', duration_days: 30, price: 199, currency: 'SAR' }
      notification.value = { text: 'تمت إضافة الباقة بنجاح!', color: 'success' }
      await fetchProducts()
    }
  }
  catch (err: any) {
    notification.value = { text: err?.data?.message || 'فشل حفظ الباقة', color: 'error' }
  }
  finally {
    isSubmitting.value = false
  }
}

const toggleStatus = async (product: any) => {
  try {
    const endpoint = product.status === 'ACTIVE' ? `/admin/products/${product.id}/deactivate` : `/admin/products/${product.id}/activate`
    const res = await $api(endpoint, { method: 'POST' })
    if (res?.success) {
      notification.value = { text: 'تم تحديث حالة الباقة بنجاح', color: 'info' }
      await fetchProducts()
    }
  }
  catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          الباقات والمنتجات التدريبية 📦
        </h2>
        <p class="text-muted mb-0">
          إدارة باقات الاشتراك، مدة الصلاحية بالأيام، وربطها بالمناهج التدريبية
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="tabler-plus"
        @click="isAddProductDialogVisible = true"
      >
        إضافة باقة جديدة
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

    <!-- Products Grid -->
    <VRow v-if="!isLoading">
      <VCol
        v-for="prod in products"
        :key="prod.id"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard class="h-100">
          <VCardItem>
            <template #prepend>
              <VAvatar color="primary" variant="tonal" rounded size="48">
                <VIcon icon="tabler-package" size="28" />
              </VAvatar>
            </template>
            <VCardTitle class="font-weight-bold">
              {{ prod.name }}
            </VCardTitle>
            <VCardSubtitle>
              <code>{{ prod.code }}</code>
            </VCardSubtitle>
            <template #append>
              <VChip
                size="small"
                :color="prod.status === 'ACTIVE' ? 'success' : 'secondary'"
                variant="tonal"
              >
                {{ prod.status === 'ACTIVE' ? 'نشطة' : 'غير نشطة' }}
              </VChip>
            </template>
          </VCardItem>

          <VCardText>
            <div class="d-flex align-center gap-2 mb-4">
              <span class="text-h4 font-weight-bold text-primary">{{ prod.price }}</span>
              <span class="text-subtitle-1 text-muted">{{ prod.currency }}</span>
              <VChip size="small" color="info" variant="tonal" class="ms-auto">
                {{ prod.duration_days }} يوماً
              </VChip>
            </div>

            <p class="text-body-2 text-muted mb-4">
              {{ prod.description || 'لا يوجد وصف تفصيلي للباقة' }}
            </p>

            <div class="bg-background pa-3 rounded text-caption mb-4">
              <div class="d-flex justify-space-between mb-1">
                <span class="text-muted">المنهج المرتبط:</span>
                <span class="font-weight-bold">{{ prod.curriculum?.name || 'غير مرتبط' }}</span>
              </div>
            </div>

            <div class="d-flex gap-2">
              <VBtn
                size="small"
                variant="tonal"
                :color="prod.status === 'ACTIVE' ? 'warning' : 'success'"
                block
                @click="toggleStatus(prod)"
              >
                {{ prod.status === 'ACTIVE' ? 'إيقاف الباقة' : 'تفعيل الباقة' }}
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <div v-else class="text-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- Create Product Dialog -->
    <VDialog v-model="isAddProductDialogVisible" max-width="550">
      <VCard>
        <VCardTitle class="pa-4 font-weight-bold">إضافة باقة / منتج تدريبي</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="newProduct.code"
                label="كود المنتج"
                placeholder="مثال: PROD-60D"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="newProduct.name"
                label="اسم الباقة"
                placeholder="مثال: باقة الشهرين التأسيسية"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                v-model="newProduct.curriculum_id"
                :items="curriculums"
                item-title="name"
                item-value="id"
                label="المنهج التدريبي المرتبط"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="newProduct.duration_days"
                type="number"
                label="مدة الاشتراك (بالأيام)"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="newProduct.price"
                type="number"
                label="السعر (بالريال)"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="newProduct.description"
                label="وصف الباقة"
                rows="2"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="tonal" color="secondary" @click="isAddProductDialogVisible = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="isSubmitting" @click="saveProduct">حفظ الباقة</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
