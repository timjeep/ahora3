<script setup>
    import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import Button from '@/components/ui/button/Button.vue';
    import { ChevronLeft, ChevronRight, Grid3X3, List, Loader2, RefreshCw, Map, Calendar, Clock } from 'lucide-vue-next';
    
    const page = usePage();
    
    const props = defineProps({
      // Data to display (can be static or loaded via AJAX)
      items: {
        type: Array,
        required: false,
        default: () => []
      },
      
      // AJAX configuration for dynamic data loading
      ajax: {
        type: Object,
        default: () => ({
          enabled: false,
          url: '',
          method: 'GET',
          params: {},
          headers: {},
          transformResponse: null,
          onError: null
        })
      },
      
      // Initial display mode
      mode: {
        type: String,
        default: 'list'
      },
      
      // Available view modes configuration
      viewModes: {
        type: Object,
        default: () => ({
          list: { enabled: true, label: 'List', icon: List },
          tiles: { enabled: true, label: 'Tiles', icon: Grid3X3 },
          map: { enabled: false, label: 'Map', icon: Map },
          calendar: { enabled: false, label: 'Calendar', icon: Calendar },
          timeline: { enabled: false, label: 'Timeline', icon: Clock }
        })
      },
      
      // Pagination options
      pagination: {
        type: Object,
        default: () => ({
          enabled: false,
          per_page: 10,
          current_page: 1
        })
      },
      
      // Infinite scroll options
      infiniteScroll: {
        type: Object,
        default: () => ({
          enabled: false,
          threshold: 100,
          loading: false
        })
      },
      
      // Item template function or slot
      itemTemplate: {
        type: Function,
        default: null
      },
      
      // Empty state message
      emptyMessage: {
        type: String,
        default: 'No items found'
      },
      
      // Loading state
      loading: {
        type: Boolean,
        default: false
      },
      
      // Custom classes
      containerClass: {
        type: String,
        default: ''
      },
      
      // Item classes
      itemClass: {
        type: String,
        default: ''
      }
    });
    
    const emit = defineEmits([
      'page-change',
      'load-more',
      'item-click',
      'mode-change',
      'data-loaded',
      'data-error',
      'refresh',
    ]);
    
    // Local state
    const currentPage = ref(props.pagination.current_page ?? props.pagination.currentPage ?? 1);
    const observer = ref(null);
    const dataItems = ref([]);
    const isLoading = ref(false);
    const error = ref(null);
    const totalItems = ref(0);
    const ajaxDebounceTimeout = ref(null);
    const activeRequestId = ref(0);
    const inFlightSignature = ref(null);
    const lastCompletedSignature = ref(null);
    const lastCompletedAtMs = ref(0);
    const activeAbortController = ref(null);
    
    // Get enabled view modes
    const enabledViewModes = computed(() => {
      return Object.entries(props.viewModes)
        .filter(([key, config]) => config.enabled)
        .map(([key, config]) => ({ key, ...config }));
    });
    
    // Validate that the initial mode is enabled
    const initialMode = computed(() => {
      const enabled = enabledViewModes.value.map(mode => mode.key);
      return enabled.includes(props.mode) ? props.mode : (enabled[0] || 'list');
    });
    
    // Initialize display mode after computed properties are defined
    const displayMode = ref(initialMode.value);
    /*
    // Watch for mode prop changes and sync internal state
    watch(() => props.mode, (newMode) => {
      const enabled = enabledViewModes.value.map(m => m.key);
      if (enabled.includes(newMode)) {
        displayMode.value = newMode;
      }
    });
    */
    // Computed properties
    const paginatedItems = computed(() => {
      if (!props.pagination.enabled || props.ajax.enabled) return dataItems.value;
      
      const perPage = props.pagination.per_page ?? props.pagination.perPage ?? 10;
      const start = (currentPage.value - 1) * perPage;
      const end = start + perPage;
      return dataItems.value.slice(start, end);
    });
    
    const effectiveItems = computed(() => {
      return props.ajax.enabled ? dataItems.value : props.items;
    });
    
    const effectiveTotalItems = computed(() => {
      return props.ajax.enabled ? totalItems.value : props.items.length;
    });
    
    const totalPages = computed(() => {
      if (!props.pagination.enabled) return 1;
      const perPage = props.pagination.per_page ?? props.pagination.perPage ?? 10;
      return Math.ceil(effectiveTotalItems.value / perPage);
    });
    
    const hasNextPage = computed(() => {
      if (!props.pagination.enabled) return false;
      return currentPage.value < totalPages.value;
    });
    
    const hasPrevPage = computed(() => {
      if (!props.pagination.enabled) return false;
      return currentPage.value > 1;
    });
    
    // Slots to show in pagination (numbers only when totalPages is small; otherwise prev/next only)
    const maxPageButtons = 11;
    const visiblePageSlots = computed(() => {
      const total = totalPages.value;
      if (total <= 0) return [];
      if (total <= maxPageButtons) {
        return Array.from({ length: total }, (_, i) => ({ type: 'page', value: i + 1 }));
      }
      const current = currentPage.value;
      const slots = [];
      slots.push({ type: 'page', value: 1 });
      if (current > 3) slots.push({ type: 'ellipsis' });
      const start = Math.max(2, current - 1);
      const end = Math.min(total - 1, current + 1);
      for (let p = start; p <= end; p++) {
        if (p !== 1 && p !== total) slots.push({ type: 'page', value: p });
      }
      if (current < total - 2) slots.push({ type: 'ellipsis' });
      if (total > 1) slots.push({ type: 'page', value: total });
      return slots;
    });
    
    // Methods
    const changePage = (page) => {
      if (page < 1 || page > totalPages.value) return;
      currentPage.value = page;
      emit('page-change', page);
    };
    
    const loadMore = () => {
      if (props.infiniteScroll.enabled && !props.infiniteScroll.loading) {
        if (props.ajax.enabled) {
          loadMoreData();
        } else {
          emit('load-more');
        }
      }
    };
    
    const changeMode = (mode) => {
      // Only allow changing to enabled modes
      const enabled = enabledViewModes.value.map(m => m.key);
      if (enabled.includes(mode)) {
        displayMode.value = mode;
        emit('mode-change', mode);
      }
    };
    
    const handleItemClick = (item, index) => {
      emit('item-click', item, index);
    };
    
    // Helper function to format dates for timeline
    const formatDate = (date) => {
      if (!date) return '';
      
      try {
        const dateObj = new Date(date);
        return dateObj.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch (e) {
        return date;
      }
    };
    
    // Helper function to get status classes for timeline
    const getStatusClasses = (status) => {
      const statusLower = status?.toLowerCase() || '';
      
      switch (statusLower) {
        case 'completed':
        case 'done':
        case 'success':
          return 'bg-green-100 text-green-800';
        case 'pending':
        case 'waiting':
        case 'in-progress':
          return 'bg-yellow-100 text-yellow-800';
        case 'failed':
        case 'error':
        case 'cancelled':
          return 'bg-red-100 text-red-800';
        case 'info':
        case 'active':
          return 'bg-blue-100 text-blue-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    };
    
    // AJAX data loading
    const loadData = async (page = 1, append = false, force = false) => {
      if (!props.ajax.enabled) return;
      
      // Prepare request parameters/signature early so we can dedupe/abort correctly
      const requestParams = {
        ...props.ajax.params,
        page: page,
        per_page: props.pagination.enabled ? (props.pagination.per_page ?? props.pagination.perPage) : undefined
      };
      
      const requestSignature = JSON.stringify({
        url: props.ajax.url,
        method: props.ajax.method,
        params: requestParams
      });
      
      // If multiple triggers try to load the same thing while a request is in-flight,
      // skip duplicates (common when both parent + internal watchers fire).
      if (!force && !append) {
        if (isLoading.value && inFlightSignature.value === requestSignature) return;
        if (
          lastCompletedSignature.value === requestSignature &&
          Date.now() - lastCompletedAtMs.value < 500
        ) {
          return;
        }
      }
      
      let requestId = 0;
      try {
        requestId = ++activeRequestId.value;
        isLoading.value = true;
        error.value = null;
        
        inFlightSignature.value = requestSignature;
        
        // Abort any in-flight request if a new one starts
        if (activeAbortController.value) {
          activeAbortController.value.abort();
        }
        const abortController = new AbortController();
        activeAbortController.value = abortController;
        
        // Make the request
        let url = props.ajax.url;
        
        // For GET requests, append query parameters to URL
        if (props.ajax.method === 'GET' && Object.keys(requestParams).length > 0) {
          const queryString = new URLSearchParams(requestParams).toString();
          url = `${url}?${queryString}`;
        }
        
        const response = await fetch(url, {
          method: props.ajax.method,
          headers: {
            'Content-Type': 'application/json',
            ...props.ajax.headers
          },
          body: props.ajax.method !== 'GET' ? JSON.stringify(requestParams) : undefined,
          signal: abortController.signal
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let data = await response.json();
        
        // Transform response if custom function provided
        if (props.ajax.transformResponse) {
          data = props.ajax.transformResponse(data);
        }
        
        // Handle pagination data - use filtered count when present (filtered list), otherwise total
        if (data.pagination) {
          totalItems.value = (data.pagination.filtered != null ? data.pagination.filtered : data.pagination.total) || data.data?.length || 0;
          if (data.pagination.current_page) {
            currentPage.value = data.pagination.current_page;
          }
        } else {
          totalItems.value = data.data?.length || data.length || 0;
        }
        
        // Extract items from response
        const items = data.data || data.items || data;
        
        if (append) {
          dataItems.value.push(...items);
        } else {
          dataItems.value = items;
        }
        
        if(usePage().props.debug) {
          console.log('DataDisplay data-loaded', dataItems.value, data);
        }
        emit('data-loaded', dataItems.value, data);
        lastCompletedSignature.value = requestSignature;
        lastCompletedAtMs.value = Date.now();
        
      } catch (err) {
        // Ignore aborted requests (they are expected during rapid changes)
        if (err?.name === 'AbortError') return;
        
        error.value = err.message;
        if (props.ajax.onError) {
          props.ajax.onError(err);
        }
        emit('data-error', err);
      } finally {
        // Only clear loading state if this is still the latest request
        if (requestId === activeRequestId.value) {
          isLoading.value = false;
          inFlightSignature.value = null;
          activeAbortController.value = null;
        }
      }
    };
    
    const refreshData = () => {
      if (props.ajax.enabled) {
        loadData(currentPage.value, false, true);
      }
    };
    
    // Expose refresh method to parent
    defineExpose({
      refresh: refreshData
    });
    
    const loadMoreData = () => {
      if (props.ajax.enabled && props.infiniteScroll.enabled) {
        const perPage = props.pagination.per_page ?? props.pagination.perPage ?? 10;
        const nextPage = Math.ceil(dataItems.value.length / perPage) + 1;
        loadData(nextPage, true);
      }
    };
    
    // Infinite scroll setup
    const setupInfiniteScroll = () => {
      if (!props.infiniteScroll.enabled) return;
      
      // Clean up existing observer
      if (observer.value) {
        observer.value.disconnect();
        observer.value = null;
      }
      
      // Wait for next tick to ensure DOM is ready
      nextTick(() => {
        // Create new intersection observer
        observer.value = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                loadMore();
              }
            });
          },
          {
            rootMargin: `0px 0px ${props.infiniteScroll.threshold}px 0px`
          }
        );
        
        // Observe the last item
        const lastItem = document.querySelector('[data-infinite-trigger]');
        if (lastItem) {
          observer.value.observe(lastItem);
        }
      });
    };
    
    // Watchers
    watch(() => (props.pagination.current_page ?? props.pagination.currentPage), (newPage) => {
      currentPage.value = newPage;
      if (props.ajax.enabled) {
        loadData(newPage, false);
      }
    });
    
    watch(() => props.mode, (newMode) => {
      displayMode.value = newMode;
    });
    
    watch(() => props.items, () => {
      if (!props.ajax.enabled) {
        dataItems.value = props.items;
      }
      if (props.infiniteScroll.enabled) {
        nextTick(() => {
          setupInfiniteScroll();
        });
      }
    }, { deep: true });
    
    watch(() => props.ajax, (newAjax, oldAjax) => {
      if (newAjax.enabled && newAjax.url) {
        // Clear any existing timeout
        if (ajaxDebounceTimeout.value) {
          clearTimeout(ajaxDebounceTimeout.value);
        }
        
        // Check if params changed (especially search)
        const paramsChanged = JSON.stringify(newAjax.params) !== JSON.stringify(oldAjax?.params || {});
        
        if (paramsChanged) {
          // Debounce param changes (especially search) by 300ms
          ajaxDebounceTimeout.value = setTimeout(() => {
            loadData(1, false);
          }, 300);
        } else if (JSON.stringify(newAjax) !== JSON.stringify(oldAjax)) {
          // For non-param changes (like URL), load immediately
          loadData(1, false);
        }
      }
    }, { deep: true });
    
    // Lifecycle
    onMounted(() => {
      // Initialize data
      if (props.ajax.enabled && props.ajax.url) {
        loadData(1, false);
      } else {
        dataItems.value = props.items;
      }
      
      if (props.infiniteScroll.enabled) {
        setupInfiniteScroll();
      }
    });
    
    onUnmounted(() => {
      if (observer.value) {
        observer.value.disconnect();
      }
      if (ajaxDebounceTimeout.value) {
        clearTimeout(ajaxDebounceTimeout.value);
      }
      if (activeAbortController.value) {
        activeAbortController.value.abort();
      }
    });
    </script>
    
    <template>
      <div :class="['data-display', containerClass]">
        <!-- Header with mode toggle and pagination info -->
        <div class="flex items-center justify-between mb-4">
          <!-- Mode toggle -->
          <div class="flex items-center space-x-2">
            <Button
              v-for="mode in enabledViewModes"
              :key="mode.key"
              type="button"
              variant="outline"
              size="sm"
              :class="{ 
                'bg-blue-600 dark:bg-blue-600 text-white dark:text-white border-blue-600 dark:border-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700': displayMode === mode.key, 
                'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700': displayMode !== mode.key 
              }"
              @click="changeMode(mode.key)"
            >
              <component :is="mode.icon" class="w-4 h-4 mr-1" />
              {{ mode.label }}
            </Button>
          </div>
          
          <!-- Pagination info -->
          <div v-if="pagination.enabled" class="text-sm text-gray-600 dark:text-gray-300">
            Page {{ currentPage }} of {{ totalPages }}
            ({{ effectiveTotalItems }} total items)
          </div>
          
          <!-- Refresh button for AJAX -->
          <Button
            v-if="ajax.enabled"
            variant="outline"
            size="sm"
            @click="refreshData"
            :disabled="isLoading"
          >
            <RefreshCw class="w-4 h-4 mr-1" :class="{ 'animate-spin': isLoading }" />
            Refresh
          </Button>
        </div>
    
        <!-- Error state -->
        <div v-if="error" class="text-center py-8 text-red-500">
          <div class="mb-4">
            <p class="text-lg font-medium">Error loading data</p>
            <p class="text-sm">{{ error }}</p>
          </div>
          <Button variant="outline" @click="refreshData">
            Try Again
          </Button>
        </div>
    
        <!-- Content area with optional loading overlay -->
        <div v-else class="relative">
          <!-- Loading overlay (doesn't destroy DOM - preserves map instances) -->
          <div v-if="loading || isLoading" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex justify-center items-center z-10 rounded-lg">
            <div class="flex items-center">
              <Loader2 class="w-6 h-6 animate-spin mr-2" />
              <span>Loading...</span>
            </div>
          </div>
    
          <!-- Calendar mode (always show, even when empty) -->
          <div v-if="displayMode === 'calendar'" class="w-full">
            <slot name="calendar-view" :items="paginatedItems">
              <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <Calendar class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" />
                <p class="text-lg font-medium">Calendar View</p>
                <p class="text-sm">Use the calendar-view slot to customize the calendar display</p>
              </div>
            </slot>
          </div>
    
          <!-- Map mode (always show, even when empty, to ensure ref initialization) -->
          <div v-else-if="displayMode === 'map'" class="w-full">
            <slot name="map-view" :items="paginatedItems">
              <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <Map class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" />
                <p class="text-lg font-medium">Map View</p>
                <p class="text-sm">Use the map-view slot to customize the map display</p>
              </div>
            </slot>
          </div>
    
          <!-- Empty state -->
          <div v-else-if="effectiveItems.length === 0 && !(loading || isLoading)" class="text-center py-8 text-gray-500 dark:text-gray-400">
            {{ emptyMessage }}
          </div>
    
          <!-- Data display -->
          <div v-else>
          <!-- List mode -->
          <div v-if="displayMode === 'list'" class="space-y-1">
            <div
              v-for="(item, index) in paginatedItems"
              :key="index"
              :class="['list-item p-2 border rounded-lg  hover:shadow-md cursor-pointer transition-colors', itemClass]"
              @click="handleItemClick(item, index)"
            >
              <slot name="list-item" :item="item" :index="index">
                <div v-if="itemTemplate" v-html="itemTemplate(item, index)"></div>
                <div v-else class="text-gray-900 dark:text-gray-100">{{ item.name || item.title || JSON.stringify(item) }}</div>
              </slot>
            </div>
          </div>
    
          <!-- Tiles mode -->
          <div v-else-if="displayMode === 'tiles'" class="flex flex-wrap gap-3">
            <div
              v-for="(item, index) in paginatedItems"
              :key="index"
              :class="['tile-item w-48 p-3 border rounded-lg hover:shadow-md cursor-pointer transition-all', itemClass]"
              @click="handleItemClick(item, index)"
            >
              <slot name="tile-item" :item="item" :index="index">
                <div v-if="itemTemplate" v-html="itemTemplate(item, index)"></div>
                <div v-else class="text-center">
                  <div class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    {{ item.name || item.title || 'Untitled' }}
                  </div>
                  <div v-if="item.description" class="text-sm text-gray-600 dark:text-gray-300">
                    {{ item.description }}
                  </div>
                </div>
              </slot>
            </div>
          </div>
    
          <!-- Timeline mode -->
          <div v-else-if="displayMode === 'timeline'" class="w-full">
            <slot name="timeline-view" :items="paginatedItems">
              <div class="relative">
                <!-- Timeline line -->
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                
                <!-- Timeline items -->
                <div class="space-y-6">
                  <div
                    v-for="(item, index) in paginatedItems"
                    :key="index"
                    class="relative flex items-start"
                    @click="handleItemClick(item, index)"
                  >
                    <!-- Timeline dot -->
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium z-10 relative">
                      {{ index + 1 }}
                    </div>
                    
                    <!-- Content card -->
                    <div class="ml-6 flex-1">
                      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer">
                        <!-- Timeline item header -->
                        <div class="flex items-start justify-between mb-2">
                          <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                              {{ item.title || item.name || `Item ${index + 1}` }}
                            </h3>
                            <p v-if="item.date || item.created_at" class="text-sm text-gray-500 dark:text-gray-400">
                              {{ formatDate(item.date || item.created_at) }}
                            </p>
                          </div>
                          <div v-if="item.status" class="ml-4">
                            <span 
                              :class="getStatusClasses(item.status)"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                              {{ item.status }}
                            </span>
                          </div>
                        </div>
                        
                        <!-- Timeline item content -->
                        <div v-if="item.description || item.content" class="text-gray-600 dark:text-gray-300 mb-3">
                          {{ item.description || item.content }}
                        </div>
                        
                        <!-- Timeline item metadata -->
                        <div v-if="item.tags || item.category" class="flex flex-wrap gap-2 mb-3">
                          <span 
                            v-for="tag in (item.tags || [])" 
                            :key="tag"
                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800"
                          >
                            {{ tag }}
                          </span>
                          <span 
                            v-if="item.category"
                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800"
                          >
                            {{ item.category }}
                          </span>
                        </div>
                        
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Empty state -->
                <div v-if="paginatedItems.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                  <Clock class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-500" />
                  <p class="text-lg font-medium">No timeline items</p>
                  <p class="text-sm">No items to display in timeline view</p>
                </div>
              </div>
            </slot>
          </div>
    
          <!-- Infinite scroll trigger -->
          <div
            v-if="infiniteScroll.enabled && hasNextPage"
            ref="infiniteTrigger"
            data-infinite-trigger
            class="flex justify-center py-4"
          >
            <Button
              v-if="!infiniteScroll.loading"
              variant="outline"
              @click="loadMore"
            >
              Load More
            </Button>
            <div v-else class="flex items-center">
              <Loader2 class="w-4 h-4 animate-spin mr-2" />
              Loading...
            </div>
          </div>
        </div>
        </div>
    
        <!-- Pagination display at bottom (only when not using infinite scroll) -->
        <div
          v-if="pagination.enabled && !infiniteScroll.enabled"
          class="flex flex-col items-center gap-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700"
        >
          <div class="text-sm text-gray-600 dark:text-gray-300">
            Page {{ currentPage }} of {{ totalPages }}
            ({{ effectiveTotalItems }} total items)
          </div>
          <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 flex-wrap">
            <Button
              variant="outline"
              size="sm"
              :disabled="!hasPrevPage"
              @click="changePage(parseInt(currentPage) - 1)"
            >
              <ChevronLeft class="w-4 h-4 mr-1" />
              Previous
            </Button>
            <div class="flex items-center gap-1">
              <template v-for="(slot, idx) in visiblePageSlots" :key="slot.type === 'page' ? slot.value : `ellipsis-${idx}`">
                <Button
                  v-if="slot.type === 'page'"
                  :variant="slot.value === currentPage ? 'default' : 'outline'"
                  size="sm"
                  @click="changePage(slot.value)"
                >
                  {{ slot.value }}
                </Button>
                <span v-else class="px-2 text-gray-500 dark:text-gray-400">…</span>
              </template>
            </div>
            <Button
              variant="outline"
              size="sm"
              :disabled="!hasNextPage"
              @click="changePage(parseInt(currentPage) + 1)"
            >
              Next
              <ChevronRight class="w-4 h-4 ml-1" />
            </Button>
          </div>
        </div>
      </div>
    </template>
    
    <style scoped lang="postcss">
    @reference "tailwindcss";
    
    .data-display {
      @apply w-full;
    }
    
    .list-item:hover, .tile-item:hover {
      @apply border-gray-300;
      @apply cursor-pointer;
      @apply shadow-md;
    }
    
    /* Remove default list styling */
    .list-item {
      list-style: none;
      list-style-type: none;
    }
    </style>
    