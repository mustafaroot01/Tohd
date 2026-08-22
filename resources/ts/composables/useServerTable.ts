/**
 * useServerTable — the brain behind every admin list screen.
 *
 * Holds the whole table state (page, page size, search, sort, filters), talks to
 * the API, and re-fetches by itself whenever any of it changes. Pages only declare
 * their columns and hand the returned object to <AppDataTableServer>.
 *
 * Wire format (see App\Support\TableQuery on the backend):
 *   request  -> page, per_page, search, sort_by, order_by, ...filters
 *   response -> { success, data: [...], meta: { current_page, per_page, total, last_page } }
 */

export type SortDirection = 'asc' | 'desc'

export interface ServerTableOptions {
  /** Rows per page. Backend caps this at 100. */
  perPage?: number
  /** Column key to sort by before the user picks one. */
  defaultSort?: string
  /** Direction for `defaultSort`. */
  defaultOrder?: SortDirection
  /** Filter keys and their initial values. `null` means "no filter". */
  filters?: Record<string, any>
  /** Extra query params always sent (static object or a getter for reactive ones). */
  params?: Record<string, any> | (() => Record<string, any>)
  /** Fetch on creation. Default true. */
  immediate?: boolean
  /**
   * Mirror state into the URL so a filtered table can be linked to and reloaded.
   * Default true. Turn off for a second table on the same page.
   */
  syncQuery?: boolean
  /** Reshape rows right after they arrive. */
  transform?: (rows: any[]) => any[]
}

const RESERVED_QUERY_KEYS = ['page', 'per_page', 'search', 'sort_by', 'order_by']

const isBlank = (value: any) =>
  value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length)

export const useServerTable = (endpoint: string, options: ServerTableOptions = {}) => {
  const {
    perPage: initialPerPage = 20,
    defaultSort = '',
    defaultOrder = 'desc',
    filters: initialFilters = {},
    params: extraParams,
    immediate = true,
    syncQuery = true,
    transform,
  } = options

  const route = useRoute()
  const router = useRouter()

  const filterKeys = Object.keys(initialFilters)

  // ── state ────────────────────────────────────────────────────────────────
  const items = ref<any[]>([])
  const total = ref(0)
  const page = ref(1)
  const perPage = ref(initialPerPage)
  const search = ref('')
  const sortBy = ref(defaultSort)
  const orderBy = ref<SortDirection>(defaultOrder)
  const filters = ref<Record<string, any>>({ ...initialFilters })
  const isLoading = ref(false)
  const errorMessage = ref<string | null>(null)

  // ── seed from the URL, so /admin/games?status=PUBLISHED opens filtered ────
  if (syncQuery) {
    const q = route.query

    if (q.page)
      page.value = Math.max(1, Number(q.page) || 1)
    if (q.per_page)
      perPage.value = Number(q.per_page) || initialPerPage
    if (typeof q.search === 'string')
      search.value = q.search
    if (typeof q.sort_by === 'string')
      sortBy.value = q.sort_by
    if (q.order_by === 'asc' || q.order_by === 'desc')
      orderBy.value = q.order_by

    filterKeys.forEach(key => {
      if (q[key] !== undefined && q[key] !== null)
        filters.value[key] = q[key]
    })
  }

  // ── derived ──────────────────────────────────────────────────────────────
  const lastPage = computed(() => Math.max(1, Math.ceil(total.value / (perPage.value || 1))))

  /** Rows before this page — the running "#" column counts on from here. */
  const startIndex = computed(() => (page.value - 1) * perPage.value)

  const isEmpty = computed(() => !isLoading.value && items.value.length === 0)

  const activeFilterCount = computed(() =>
    filterKeys.filter(key => !isBlank(filters.value[key])).length)

  const hasActiveQuery = computed(() => !!search.value || activeFilterCount.value > 0)

  // ── request ──────────────────────────────────────────────────────────────
  const buildQuery = () => {
    const query: Record<string, any> = {
      page: page.value,
      per_page: perPage.value,
    }

    if (search.value)
      query.search = search.value

    if (sortBy.value) {
      query.sort_by = sortBy.value
      query.order_by = orderBy.value
    }

    filterKeys.forEach(key => {
      if (!isBlank(filters.value[key]))
        query[key] = filters.value[key]
    })

    const extra = typeof extraParams === 'function' ? extraParams() : extraParams
    if (extra) {
      Object.entries(extra).forEach(([key, value]) => {
        if (!isBlank(value))
          query[key] = value
      })
    }

    return query
  }

  const syncUrl = () => {
    if (!syncQuery)
      return

    const query = buildQuery()
    const next: Record<string, any> = { ...route.query }

    // drop the keys we own, then re-add only the meaningful ones
    ;[...RESERVED_QUERY_KEYS, ...filterKeys].forEach(key => delete next[key])

    Object.entries(query).forEach(([key, value]) => {
      if (key === 'page' && value === 1)
        return
      if (key === 'per_page' && value === initialPerPage)
        return
      next[key] = String(value)
    })

    router.replace({ query: next }).catch(() => {})
  }

  let requestId = 0

  const refresh = async () => {
    const currentRequest = ++requestId

    isLoading.value = true
    errorMessage.value = null

    try {
      const response = await $api(endpoint, { query: buildQuery() })

      // a slower earlier request must not overwrite a newer one
      if (currentRequest !== requestId)
        return

      if (response?.success) {
        const rows = Array.isArray(response.data) ? response.data : []

        items.value = transform ? transform(rows) : rows
        total.value = response.meta?.total ?? rows.length
      }
      else {
        items.value = []
        total.value = 0
        errorMessage.value = response?.message || 'تعذّر تحميل البيانات'
      }
    }
    catch (error: any) {
      if (currentRequest !== requestId)
        return

      items.value = []
      total.value = 0
      errorMessage.value = error?.data?.message || 'حدث خطأ أثناء الاتصال بالخادم'
      console.error(`[useServerTable] ${endpoint}`, error)
    }
    finally {
      if (currentRequest === requestId)
        isLoading.value = false
    }
  }

  const reload = () => {
    syncUrl()

    return refresh()
  }

  // ── reactions ────────────────────────────────────────────────────────────
  // Typing shouldn't fire a request per keystroke.
  watchDebounced(search, () => {
    page.value = 1
    reload()
  }, { debounce: 450 })

  watch(page, reload)

  watch([perPage, sortBy, orderBy], () => {
    page.value = 1
    reload()
  })

  watch(filters, () => {
    page.value = 1
    reload()
  }, { deep: true })

  // ── actions pages call ───────────────────────────────────────────────────
  const setSort = (key: string, direction?: SortDirection) => {
    if (sortBy.value === key && !direction) {
      orderBy.value = orderBy.value === 'asc' ? 'desc' : 'asc'

      return
    }

    sortBy.value = key
    orderBy.value = direction ?? 'asc'
  }

  const clearSort = () => {
    sortBy.value = defaultSort
    orderBy.value = defaultOrder
  }

  const setFilter = (key: string, value: any) => {
    filters.value = { ...filters.value, [key]: value }
  }

  const resetFilters = () => {
    search.value = ''
    filters.value = { ...initialFilters }
  }

  /**
   * Call after a successful delete. Deleting the last row of a page walks back
   * one page instead of leaving the user staring at an empty table.
   */
  const afterDelete = () => {
    if (items.value.length <= 1 && page.value > 1) {
      page.value -= 1

      return
    }

    return reload()
  }

  if (immediate)
    refresh()

  // reactive() so pages and the table component can read `table.items` /
  // write `table.page = 2` without touching `.value` anywhere.
  return reactive({
    // state
    items,
    total,
    page,
    perPage,
    search,
    sortBy,
    orderBy,
    filters,
    isLoading,
    errorMessage,

    // derived
    lastPage,
    startIndex,
    isEmpty,
    activeFilterCount,
    hasActiveQuery,

    // actions
    refresh,
    reload,
    setSort,
    clearSort,
    setFilter,
    resetFilters,
    afterDelete,
    buildQuery,
  })
}

export type ServerTable = ReturnType<typeof useServerTable>
