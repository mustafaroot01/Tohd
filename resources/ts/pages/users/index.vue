<script setup lang="ts">
const users = ref<any[]>([])
const isLoading = ref(true)
const selectedUser = ref<any | null>(null)
const isDetailsDialogVisible = ref(false)

const fetchUsers = async () => {
  isLoading.value = true
  try {
    const res = await $api('/admin/users')
    if (res?.success)
      users.value = res.data
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

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

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold">
          المستخدمون والمشتركون 👥
        </h2>
        <p class="text-muted mb-0">
          استعراض الحسابات المسجلة، حالة الاشتراك، وبيانات المناهج المفعلة
        </p>
      </div>
    </div>

    <!-- Users Table Card -->
    <VCard>
      <VCardText class="pa-0">
        <VTable hover class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-start">المستخدم</th>
              <th class="text-start">البريد الإلكتروني</th>
              <th class="text-start">الصلاحية (الدور)</th>
              <th class="text-start">حالة الحساب</th>
              <th class="text-start">تاريخ التسجيل</th>
              <th class="text-center">التفاصيل</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>
                <div class="d-flex align-center gap-3">
                  <VAvatar color="primary" variant="tonal" size="38">
                    <VIcon icon="tabler-user" size="20" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold">{{ user.name }}</div>
                  </div>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>
                <VChip
                  size="small"
                  :color="user.role === 'ADMIN' ? 'error' : 'primary'"
                  variant="tonal"
                >
                  {{ user.role === 'ADMIN' ? 'مدير نظام' : 'مستخدم تطبيق' }}
                </VChip>
              </td>
              <td>
                <VChip
                  size="small"
                  :color="user.status === 'ACTIVE' ? 'success' : 'secondary'"
                  variant="tonal"
                >
                  {{ user.status === 'ACTIVE' ? 'نشط' : 'معطل' }}
                </VChip>
              </td>
              <td>
                <span class="text-caption">
                  {{ user.created_at ? new Date(user.created_at).toLocaleDateString('ar-SA') : '-' }}
                </span>
              </td>
              <td class="text-center">
                <VBtn
                  size="small"
                  variant="tonal"
                  color="primary"
                  prepend-icon="tabler-eye"
                  @click="showUserDetails(user)"
                >
                  استعراض
                </VBtn>
              </td>
            </tr>

            <tr v-if="users.length === 0 && !isLoading">
              <td colspan="6" class="text-center py-8 text-muted">
                لا يوجد مستخدمون مسجلون بعد.
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <!-- User Details Dialog -->
    <VDialog v-model="isDetailsDialogVisible" max-width="500">
      <VCard v-if="selectedUser">
        <VCardTitle class="pa-4 font-weight-bold">بيانات المشترك</VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <div class="d-flex align-center gap-3 mb-4">
            <VAvatar color="primary" variant="tonal" size="50">
              <VIcon icon="tabler-user" size="28" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold">{{ selectedUser.name }}</h3>
              <p class="text-caption text-muted mb-0">{{ selectedUser.email }}</p>
            </div>
          </div>

          <VDivider class="mb-4" />

          <div class="bg-background pa-3 rounded text-caption mb-3">
            <div class="d-flex justify-space-between mb-2">
              <span class="text-muted">نوع الحساب:</span>
              <span class="font-weight-bold">{{ selectedUser.role }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-muted">الحالة:</span>
              <span>{{ selectedUser.status }}</span>
            </div>
            <div class="d-flex justify-space-between">
              <span class="text-muted">تاريخ الانضمام:</span>
              <span>{{ new Date(selectedUser.created_at).toLocaleDateString('ar-SA') }}</span>
            </div>
          </div>
        </VCardText>
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn color="primary" @click="isDetailsDialogVisible = false">إغلاق</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
