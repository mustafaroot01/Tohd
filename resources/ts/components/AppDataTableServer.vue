<script setup lang="ts">
/**
 * AppDataTableServer — the one table every admin list screen renders.
 *
 * Pages hand it a `headers` array and the object returned by `useServerTable`.
 * It draws the toolbar, the running "#" column, per-column filters, sorting,
 * the empty/error states and the pager. Cell markup stays in the page via
 * `#item.<key>` slots, which are forwarded straight through.
 */
import type { ServerTable } from '@/composables/useServerTable'

export interface DataTableFilterOption {
  title: string
  value: any
}

export interface DataTableFilter {
  /** Query key sent to the API. Defaults to the column key. */
  key?: string
  options: DataTableFilterOption[]
  /** Label for the "no filter" entry. */
  anyLabel?: string
}

export interface DataTableHeader {
  title: string
  key: string
  /** Opt in explicitly — only columns the API can sort are sortable. */
  sortable?: boolean
  align?: 'start' | 'center' | 'end'
  width?: string | number
  nowrap?: boolean
  /** Adds a filter funnel to this column's header. */
  filter?: DataTableFilter
  /** Exclude from the show/hide columns menu. */
  hideable?: boolean
  /** Start hidden. */
  hidden?: boolean
}

interface Props {
  table: ServerTable
  headers: DataTableHeader[]
  title?: string
  subtitle?: string
  icon?: string
  searchPlaceholder?: string
  /** Text of the primary action button. Omit to hide it. */
  addLabel?: string
  addIcon?: string
  /** Hide the leading serial-number column. */
  hideIndex?: boolean
  emptyText?: string
  emptyIcon?: string
  density?: 'default' | 'comfortable' | 'compact'
  itemsPerPageOptions?: number[]
  /** Card is flat when embedded inside another card. */
  flat?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  icon: '',
  searchPlaceholder: 'بحث…',
  addLabel: '',
  addIcon: 'tabler-plus',
  hideIndex: false,
  emptyText: 'لا توجد بيانات لعرضها',
  emptyIcon: 'tabler-database-off',
  density: 'comfortable',
  itemsPerPageOptions: () => [10, 20, 50, 100],
  flat: false,
})

const emit = defineEmits<{
  (e: 'add'): void
}>()

const table = computed(() => props.table)

// ── show / hide columns ────────────────────────────────────────────────────
const hiddenKeys = ref<string[]>(props.headers.filter(h => h.hidden).map(h => h.key))

const toggleColumn = (key: string) => {
  hiddenKeys.value = hiddenKeys.value.includes(key)
    ? hiddenKeys.value.filter(k => k !== key)
    : [...hiddenKeys.value, key]
}

const hideableHeaders = computed(() => props.headers.filter(h => h.hideable !== false))

// ── headers handed to Vuetify ──────────────────────────────────────────────
const INDEX_KEY = '__index'

const tableHeaders = computed(() => {
  const visible = props.headers
    .filter(h => !hiddenKeys.value.includes(h.key))
    .map(h => ({
      title: h.title,
      key: h.key,
      sortable: h.sortable === true,
      align: h.align ?? 'start',
      width: h.width,
      nowrap: h.nowrap,
    }))

  if (props.hideIndex)
    return visible

  return [
    { title: '#', key: INDEX_KEY, sortable: false, align: 'center' as const, width: 68, nowrap: true },
    ...visible,
  ]
})

const filterableHeaders = computed(() =>
  props.headers.filter(h => h.filter && !hiddenKeys.value.includes(h.key)))

const filterKeyOf = (header: DataTableHeader) => header.filter?.key ?? header.key

const isFilterActive = (header: DataTableHeader) => {
  const value = table.value.filters[filterKeyOf(header)]

  return value !== null && value !== undefined && value !== ''
}

const applyFilter = (header: DataTableHeader, value: any) => {
  table.value.setFilter(filterKeyOf(header), value)
}

// ── sorting bridge: Vuetify uses [{ key, order }], the composable uses two refs ──
const vuetifySortBy = computed(() =>
  table.value.sortBy ? [{ key: table.value.sortBy, order: table.value.orderBy }] : [])

const onSortUpdate = (value: any) => {
  const entry = Array.isArray(value) ? value[0] : null

  if (!entry?.key) {
    table.value.clearSort()

    return
  }

  table.value.sortBy = entry.key
  table.value.orderBy = entry.order === 'desc' ? 'desc' : 'asc'
}

// ── footer numbers ─────────────────────────────────────────────────────────
const rangeStart = computed(() => (table.value.total === 0 ? 0 : table.value.startIndex + 1))
const rangeEnd = computed(() => table.value.startIndex + table.value.items.length)
</script>

<template>
  <VCard :flat="flat" class="app-data-table">
    <!-- ─────────── toolbar ─────────── -->
    <VCardText class="app-data-table__toolbar">
      <div class="d-flex flex-wrap align-center justify-space-between gap-4">
        <div
          v-if="title || $slots.title"
          class="d-flex align-center gap-3"
        >
          <VAvatar
            v-if="icon"
            variant="tonal"
            color="primary"
            rounded
            size="40"
          >
            <VIcon :icon="icon" size="22" />
          </VAvatar>
          <div>
            <slot name="title">
              <h3 class="text-h5 font-weight-bold mb-0">
                {{ title }}
              </h3>
            </slot>
            <p
              v-if="subtitle"
              class="text-body-2 text-medium-emphasis mb-0"
            >
              {{ subtitle }}
            </p>
          </div>
        </div>

        <div class="d-flex flex-wrap align-center gap-3 app-data-table__actions">
          <AppTextField
            v-model="table.search"
            :placeholder="searchPlaceholder"
            prepend-inner-icon="tabler-search"
            density="compact"
            clearable
            hide-details
            class="app-data-table__search"
          />

          <slot name="actions" />

          <!-- columns -->
          <VBtn
            v-if="hideableHeaders.length"
            icon
            variant="tonal"
            color="secondary"
            size="small"
          >
            <VIcon icon="tabler-columns-3" size="20" />
            <VTooltip activator="parent" location="top">
              الأعمدة الظاهرة
            </VTooltip>
            <VMenu activator="parent" :close-on-content-click="false" location="bottom end">
              <VList density="compact" min-width="200">
                <VListSubheader>إظهار / إخفاء الأعمدة</VListSubheader>
                <VListItem
                  v-for="header in hideableHeaders"
                  :key="header.key"
                  @click="toggleColumn(header.key)"
                >
                  <template #prepend>
                    <VCheckboxBtn
                      :model-value="!hiddenKeys.includes(header.key)"
                      density="compact"
                      @click.stop="toggleColumn(header.key)"
                    />
                  </template>
                  <VListItemTitle>{{ header.title }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>

          <!-- refresh -->
          <VBtn
            icon
            variant="tonal"
            color="secondary"
            size="small"
            :loading="table.isLoading"
            @click="table.refresh()"
          >
            <VIcon icon="tabler-refresh" size="20" />
            <VTooltip activator="parent" location="top">
              تحديث
            </VTooltip>
          </VBtn>

          <VBtn
            v-if="addLabel"
            color="primary"
            :prepend-icon="addIcon"
            @click="emit('add')"
          >
            {{ addLabel }}
          </VBtn>
        </div>
      </div>

      <!-- active filters -->
      <div
        v-if="table.hasActiveQuery"
        class="d-flex flex-wrap align-center gap-2 mt-4"
      >
        <span class="text-caption text-medium-emphasis">النتائج مفلترة:</span>
        <VChip
          v-if="table.search"
          size="small"
          color="primary"
          variant="tonal"
          closable
          @click:close="table.search = ''"
        >
          بحث: {{ table.search }}
        </VChip>
        <VChip
          v-for="header in filterableHeaders.filter(isFilterActive)"
          :key="`chip-${header.key}`"
          size="small"
          color="primary"
          variant="tonal"
          closable
          @click:close="applyFilter(header, null)"
        >
          {{ header.title }}:
          {{
            header.filter?.options.find(o => o.value === table.filters[filterKeyOf(header)])?.title
              ?? table.filters[filterKeyOf(header)]
          }}
        </VChip>
        <VBtn
          size="x-small"
          variant="text"
          color="secondary"
          @click="table.resetFilters()"
        >
          مسح الكل
        </VBtn>
      </div>
    </VCardText>

    <VDivider />

    <!-- ─────────── table ─────────── -->
    <VDataTableServer
      :headers="tableHeaders"
      :items="table.items"
      :items-length="table.total"
      :loading="table.isLoading"
      :page="table.page"
      :items-per-page="table.perPage"
      :sort-by="vuetifySortBy"
      :density="density"
      class="text-no-wrap app-data-table__grid"
      hover
      @update:sort-by="onSortUpdate"
    >
      <!-- running serial number -->
      <template #[`item.${INDEX_KEY}`]="{ index }">
        <span class="app-data-table__index">{{ table.startIndex + index + 1 }}</span>
      </template>

      <!-- per-column filter funnels -->
      <template
        v-for="header in filterableHeaders"
        #[`header.${header.key}`]="{ column, isSorted, getSortIcon }"
      >
        <div class="d-flex align-center gap-1 app-data-table__th">
          <span>{{ header.title }}</span>

          <VIcon
            v-if="column.sortable"
            size="16"
            class="app-data-table__sort-icon"
            :class="{ 'app-data-table__sort-icon--active': isSorted(column) }"
            :icon="getSortIcon(column)"
          />

          <VBtn
            icon
            size="x-small"
            variant="text"
            :color="isFilterActive(header) ? 'primary' : 'default'"
            class="app-data-table__filter-btn"
            @click.stop
          >
            <VIcon :icon="isFilterActive(header) ? 'tabler-filter-filled' : 'tabler-filter'" size="16" />
            <VMenu activator="parent" location="bottom end">
              <VList density="compact" min-width="180">
                <VListItem
                  :active="!isFilterActive(header)"
                  @click="applyFilter(header, null)"
                >
                  <VListItemTitle>{{ header.filter?.anyLabel ?? 'الكل' }}</VListItemTitle>
                </VListItem>
                <VDivider />
                <VListItem
                  v-for="option in header.filter?.options"
                  :key="String(option.value)"
                  :active="table.filters[filterKeyOf(header)] === option.value"
                  @click="applyFilter(header, option.value)"
                >
                  <VListItemTitle>{{ option.title }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </div>
      </template>

      <!-- forward every cell slot the page defined -->
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot :name="name" v-bind="slotProps ?? {}" />
      </template>

      <!-- empty / error -->
      <template #no-data>
        <div class="d-flex flex-column align-center justify-center py-12 px-4 text-center">
          <VAvatar
            :color="table.errorMessage ? 'error' : 'secondary'"
            variant="tonal"
            size="64"
            class="mb-4"
          >
            <VIcon :icon="table.errorMessage ? 'tabler-alert-triangle' : emptyIcon" size="32" />
          </VAvatar>

          <p class="text-body-1 font-weight-medium mb-1">
            {{ table.errorMessage || (table.hasActiveQuery ? 'لا توجد نتائج مطابقة' : emptyText) }}
          </p>

          <p
            v-if="!table.errorMessage && table.hasActiveQuery"
            class="text-body-2 text-medium-emphasis mb-3"
          >
            جرّب تعديل البحث أو الفلاتر.
          </p>

          <VBtn
            v-if="table.errorMessage"
            size="small"
            variant="tonal"
            color="error"
            prepend-icon="tabler-refresh"
            @click="table.refresh()"
          >
            إعادة المحاولة
          </VBtn>
          <VBtn
            v-else-if="table.hasActiveQuery"
            size="small"
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-filter-off"
            @click="table.resetFilters()"
          >
            مسح الفلاتر
          </VBtn>
        </div>
      </template>

      <!-- ─────────── pager ─────────── -->
      <template #bottom>
        <VDivider />
        <div class="d-flex flex-wrap align-center justify-space-between gap-4 pa-4">
          <div class="d-flex align-center gap-3">
            <span class="text-body-2 text-medium-emphasis">عدد الصفوف</span>
            <AppSelect
              v-model="table.perPage"
              :items="itemsPerPageOptions"
              density="compact"
              hide-details
              style="inline-size: 5.5rem;"
            />
            <span class="text-body-2 text-medium-emphasis d-none d-sm-inline">
              عرض {{ rangeStart }}–{{ rangeEnd }} من {{ table.total }}
            </span>
          </div>

          <VPagination
            v-model="table.page"
            :length="table.lastPage"
            :total-visible="5"
            :disabled="table.isLoading"
            density="comfortable"
            active-color="primary"
          />
        </div>
      </template>
    </VDataTableServer>
  </VCard>
</template>

<style lang="scss">
.app-data-table {
  .app-data-table__toolbar {
    padding-block: 1.25rem;
  }

  .app-data-table__search {
    min-inline-size: 15rem;
  }

  .app-data-table__index {
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    font-feature-settings: "tnum";
    font-size: 0.8125rem;
    font-variant-numeric: tabular-nums;
  }

  .app-data-table__th {
    inline-size: 100%;
  }

  .app-data-table__sort-icon {
    opacity: 0.35;
    transition: opacity 0.2s ease;
  }

  .app-data-table__sort-icon--active {
    color: rgb(var(--v-theme-primary));
    opacity: 1;
  }

  .app-data-table__filter-btn {
    margin-inline-start: auto;
  }

  .v-data-table__th--sortable:hover .app-data-table__sort-icon {
    opacity: 0.7;
  }

  // the first column is the running number — keep it narrow and quiet
  .v-data-table__td:first-child,
  .v-data-table__th:first-child {
    inline-size: 4.25rem;
  }
}

@media (max-width: 37.5rem) {
  .app-data-table .app-data-table__actions {
    inline-size: 100%;

    .app-data-table__search {
      flex: 1 1 100%;
    }
  }
}
</style>
