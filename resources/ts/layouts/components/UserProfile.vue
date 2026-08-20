<script setup lang="ts">
const router = useRouter()
const ability = useAbility()

const userData = useCookie<any>('userData')

const logout = async () => {
  useCookie('accessToken').value = null
  userData.value = null
  useCookie('userAbilityRules').value = null
  ability.update([])
  await router.push('/login')
}
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      />
      <VIcon
        v-else
        icon="tabler-user"
      />

      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList class="pa-0">
          <!-- User Info -->
          <VListItem class="pa-4">
            <div class="d-flex gap-3 align-center">
              <VBadge
                dot
                location="bottom right"
                offset-x="3"
                offset-y="3"
                color="success"
                bordered
              >
                <VAvatar
                  size="44"
                  :color="!(userData && userData.avatar) ? 'primary' : undefined"
                  :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                >
                  <VImg
                    v-if="userData && userData.avatar"
                    :src="userData.avatar"
                  />
                  <VIcon
                    v-else
                    icon="tabler-user"
                    size="24"
                  />
                </VAvatar>
              </VBadge>
              <div>
                <h6 class="text-base font-weight-semibold">
                  {{ userData.fullName || userData.username }}
                </h6>
                <span class="text-sm text-disabled text-capitalize">
                  {{ userData.role === 'admin' ? 'مدير النظام' : userData.role }}
                </span>
              </div>
            </div>
          </VListItem>

          <VDivider />

          <!-- Account Settings -->
          <VListItem
            :to="{ name: 'pages-account-settings-tab', params: { tab: 'account' } }"
            class="px-3 py-2"
          >
            <template #prepend>
              <VIcon icon="tabler-settings" size="20" class="me-2" />
            </template>
            <VListItemTitle class="text-sm">إعدادات الحساب</VListItemTitle>
          </VListItem>

          <VDivider />

          <!-- Logout -->
          <div class="px-3 py-3">
            <VBtn
              block
              variant="tonal"
              color="error"
              size="small"
              prepend-icon="tabler-logout"
              @click="logout"
            >
              تسجيل الخروج
            </VBtn>
          </div>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
