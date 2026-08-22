<script setup lang="ts">
import type { DataTableHeader } from '@/components/AppDataTableServer.vue'

const headers: DataTableHeader[] = [
  { title: 'المستخدم', key: 'name', sortable: true, hideable: false },
  { title: 'البريد الإلكتروني', key: 'email', sortable: true },
  {
    title: 'حالة الحساب',
    key: 'status',
    sortable: true,
    filter: { options: statusOptions(USER_STATUS) },
  },
  { title: 'آخر دخول', key: 'last_login_at', sortable: true },
  { title: 'تاريخ التسجيل', key: 'created_at', sortable: true },
  { title: 'التفاصيل', key: 'actions', align: 'center', hideable: false },
]

const table = useServerTable('/admin/users', {
  defaultSort: 'created_at',
  defaultOrder: 'desc',
  filters: { status: null },
})

const selectedUser = ref<any | null>(null)
const isDetailsDialogVisible = ref(false)


const showUserDetails = async (user: any) => {
  try {
    const res = await $api(`/admin/users/${user.id}`)
    if (res?.success) {
      selectedUser.value = res.data
      isDetailsDialogVisible.value = true
    }
  }
  catch (err) {
    console.error(err)
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold">
        مستخدمو النظام 🛡️
      </h2>
      <p class="text-muted mb-0">
        استعراض حسابات مدراء لوحة التحكم الذين يديرون المنصة
      </p>
    </div>

    <AppDataTableServer
      :table="table"
      :headers="headers"
      title="قائمة المدراء"
      icon="tabler-shield-lock"
      search-placeholder="ابحث بالاسم أو البريد…"
      empty-text="لا يوجد مستخدمو نظام مسجلون بعد."
      empty-icon="tabler-user-off"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-user" size="20" />
          </VAvatar>
          <span class="font-weight-bold text-high-emphasis">{{ item.name }}</span>
        </div>
      </template>

      <template #item.email="{ item }">
        <span class="text-body-2">{{ item.email }}</span>
      </template>

      <template #item.status="{ item }">
        <VChip
          size="small"
          :color="statusColor(USER_STATUS, item.status)"
          variant="tonal"
        >
          {{ statusLabel(USER_STATUS, item.status) }}
        </VChip>
      </template>

      <template #item.last_login_at="{ item }">
        <span class="text-caption">{{ formatDate(item.last_login_at) }}</span>
      </template>

      <template #item.created_at="{ item }">
        <span class="text-caption">{{ formatDate(item.created_at) }}</span>
      </template>

      <template #item.actions="{ item }">
        <div class="text-center">
          <VBtn
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="tabler-eye"
            @click="showUserDetails(item)"
          >
            استعراض
          </VBtn>
        </div>
      </template>
    </AppDataTableServer>

    <!-- User Details Dialog -->
    <VDialog v-model="isDetailsDialogVisible" max-width="500">
      <VCard v-if="selectedUser">
        <VCardTitle class="pa-4 font-weight-bold">
          بيانات المستخدم
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <div class="d-flex align-center gap-3 mb-4">
            <VAvatar color="primary" variant="tonal" size="50">
              <VIcon icon="tabler-user" size="28" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold">
                {{ selectedUser.name }}
              </h3>
              <p class="text-caption text-muted mb-0">
                {{ selectedUser.email }}
              </p>
            </div>
          </div>

          <VDivider class="mb-4" />

          <div class="bg-background pa-3 rounded text-caption mb-3">
            <div class="d-flex justify-space-between mb-2">
              <span class="text-muted">الحالة:</span>
              <span>{{ selectedUser.status }}</span>
            </div>
            <div class="d-flex justify-space-between">
              <span class="text-muted">تاريخ الانضمام:</span>
              <span>{{ formatDate(selectedUser.created_at) }}</span>
            </div>
          </div>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn color="primary" @click="isDetailsDialogVisible = false">
            إغلاق
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
