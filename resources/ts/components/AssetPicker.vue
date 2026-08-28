<script setup lang="ts">
/**
 * Pick a file out of the media library by typing its name or its code.
 *
 * The search runs on the server (`GET /admin/assets?search=…&type=…`), so it
 * reaches the whole library rather than the first page the dialog happened to
 * preload — the old picker was a plain select capped at 100 rows.
 */
interface Props {
  /** Selected asset id, or null. */
  modelValue: string | null
  label: string
  /** Restrict the search to one asset type, e.g. 'AUDIO' or 'LOTTIE'. */
  type?: string
  hint?: string
  placeholder?: string
  prependIcon?: string
  clearable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: '',
  hint: '',
  placeholder: 'اكتب الاسم أو الكود للبحث…',
  prependIcon: '',
  clearable: true,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const items = ref<any[]>([])
const isLoading = ref(false)
const search = ref('')

/** Keeps the chosen row visible even when it is not in the latest results. */
const selected = ref<any | null>(null)

const query = (term?: string) => {
  const q: Record<string, any> = { per_page: 25 }

  if (props.type)
    q.type = props.type
  if (term)
    q.search = term

  return q
}

const fetchAssets = async (term?: string) => {
  isLoading.value = true
  try {
    const res = await $api('/admin/assets', { query: query(term) })

    if (res?.success) {
      const rows = res.data as any[]

      // never drop the current selection out of the list
      items.value = selected.value && !rows.some(r => r.id === selected.value.id)
        ? [selected.value, ...rows]
        : rows
    }
  }
  catch (err) {
    console.error('[AssetPicker]', err)
  }
  finally {
    isLoading.value = false
  }
}

/** Resolve an id we were handed into a full row, so the field shows its name. */
const hydrate = async (id: string | null) => {
  if (!id) {
    selected.value = null

    return
  }

  const known = items.value.find(a => a.id === id)
  if (known) {
    selected.value = known

    return
  }

  try {
    const res = await $api(`/admin/assets/${id}`)
    if (res?.success) {
      selected.value = res.data
      if (!items.value.some(a => a.id === id))
        items.value = [res.data, ...items.value]
    }
  }
  catch {
    // asset was deleted — leave the field empty rather than showing a raw id
    selected.value = null
    emit('update:modelValue', null)
  }
}

watchDebounced(search, term => {
  // typing the label back of an already-picked row shouldn't re-query
  if (term && term === selected.value?.name)
    return

  fetchAssets(term || undefined)
}, { debounce: 350 })

watch(() => props.modelValue, id => hydrate(id), { immediate: true })

onMounted(() => fetchAssets())

const onSelect = (id: string | null) => {
  selected.value = items.value.find(a => a.id === id) ?? null
  emit('update:modelValue', id ?? null)
}

const subtitleOf = (asset: any) =>
  [asset.code, asset.type].filter(Boolean).join(' · ')
</script>

<template>
  <AppAutocomplete
    :model-value="modelValue"
    v-model:search="search"
    :items="items"
    item-title="name"
    item-value="id"
    :label="label"
    :placeholder="placeholder"
    :loading="isLoading"
    :clearable="clearable"
    :prepend-inner-icon="prependIcon || undefined"
    :hint="hint"
    :persistent-hint="!!hint"
    no-filter
    :menu-props="{ maxHeight: 300 }"
    @update:model-value="onSelect"
  >
    <template #item="{ props: itemProps, item }">
      <VListItem v-bind="itemProps" :title="item.raw.name">
        <template #prepend>
          <VAvatar
            size="32"
            rounded
            variant="tonal"
            :color="statusColor(ASSET_TYPE, item.raw.type)"
          >
            <VIcon
              size="18"
              :icon="item.raw.type === 'AUDIO' ? 'tabler-volume'
                : item.raw.type === 'LOTTIE' ? 'tabler-animation'
                  : item.raw.type === 'VIDEO' ? 'tabler-video' : 'tabler-photo'"
            />
          </VAvatar>
        </template>
        <VListItemSubtitle dir="ltr" class="text-start">
          {{ subtitleOf(item.raw) }}
        </VListItemSubtitle>
      </VListItem>
    </template>

    <template #no-data>
      <div class="pa-4 text-center text-body-2 text-medium-emphasis">
        {{ search ? 'لا توجد ملفات مطابقة' : 'ابدأ الكتابة للبحث في مكتبة الوسائط' }}
      </div>
    </template>
  </AppAutocomplete>
</template>
