<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { ChevronLeft, ChevronRight, ChevronUp, ChevronDown, Loader2, RefreshCw } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';

function useDebounce<T extends (...args: unknown[]) => void>(fn: T, ms: number) {
  let timeout: ReturnType<typeof setTimeout>;
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn(...args), ms);
  };
}

export interface SmartTableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  searchable?: boolean;
  format?: (row: Record<string, unknown>, value: unknown) => string | number;
}

const props = withDefaults(
  defineProps<{
    url: string;
    columns: SmartTableColumn[];
    perPage?: number;
    emptyMessage?: string;
  }>(),
  {
    perPage: 20,
    emptyMessage: 'No data found',
  }
);

const emit = defineEmits<{
  (e: 'row-click', row: Record<string, unknown>, index: number): void;
}>();

const data = ref<Record<string, unknown>[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const currentPage = ref(1);
const totalItems = ref(0);
const lastPage = ref(1);
const sortBy = ref<string | null>(null);
const sortOrder = ref<'asc' | 'desc'>('asc');
const searchValues = ref<Record<string, string>>({});
const abortController = ref<AbortController | null>(null);

const totalPages = computed(() => Math.max(1, lastPage.value));
const hasPrevPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < totalPages.value);

const requestParams = computed(() => {
  const params: Record<string, string | number | undefined> = {
    page: currentPage.value,
    per_page: props.perPage,
  };
  if (sortBy.value) {
    params.sortBy = sortBy.value;
    params.sortOrder = sortOrder.value;
  }
  const searchEntries = Object.entries(searchValues.value).filter(([, v]) => v?.trim());
  searchEntries.forEach(([key, val]) => {
    params[`search_${key}`] = val!.trim();
  });
  if (searchEntries.length === 1) {
    params.search = searchEntries[0][1]!.trim();
  }
  return params;
});

const loadData = async (force = false) => {
  isLoading.value = true;
  error.value = null;

  if (abortController.value) {
    abortController.value.abort();
  }
  abortController.value = new AbortController();

  const url = new URL(props.url, window.location.origin);
  Object.entries(requestParams.value).forEach(([k, v]) => {
    if (v !== undefined && v !== '') {
      url.searchParams.set(k, String(v));
    }
  });

  try {
    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: { Accept: 'application/json' },
      signal: abortController.value.signal,
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const json = await response.json();
    data.value = json.data ?? [];
    const pag = json.pagination;
    if (pag) {
      totalItems.value = pag.filtered ?? pag.total ?? data.value.length;
      currentPage.value = pag.current_page ?? 1;
      lastPage.value = pag.last_page ?? 1;
    } else {
      totalItems.value = data.value.length;
      lastPage.value = 1;
    }
  } catch (err) {
    if ((err as Error)?.name === 'AbortError') return;
    error.value = (err as Error)?.message ?? 'Failed to load data';
  } finally {
    isLoading.value = false;
    abortController.value = null;
  }
};

const changePage = (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
  loadData();
};

const handleSort = (key: string) => {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = 'asc';
  }
  currentPage.value = 1;
  loadData();
};

const handleSearch = () => {
  currentPage.value = 1;
  loadData();
};

const debouncedSearch = useDebounce(handleSearch, 300);

const updateSearch = (key: string, v: string | number) => {
  searchValues.value = { ...searchValues.value, [key]: String(v) };
  debouncedSearch();
};

const cellValue = (row: Record<string, unknown>, col: SmartTableColumn) => {
  const val = row[col.key];
  return col.format ? col.format(row, val) : (val ?? '');
};

const refresh = () => loadData(true);

defineExpose({ refresh });

watch(
  () => props.url,
  () => {
    currentPage.value = 1;
    loadData();
  }
);

onMounted(() => loadData());
</script>

<template>
  <div class="smart-tables w-full overflow-x-auto">
    <div class="flex justify-between items-center mb-4">
      <div class="text-sm text-muted-foreground">
        Page {{ currentPage }} of {{ totalPages }} ({{ totalItems }} items)
      </div>
      <Button variant="outline" size="sm" :disabled="isLoading" @click="refresh">
        <RefreshCw class="w-4 h-4 mr-1" :class="{ 'animate-spin': isLoading }" />
        Refresh
      </Button>
    </div>

    <div v-if="error" class="text-destructive text-sm mb-4">{{ error }}</div>

    <table class="w-full border-collapse border border-border">
      <thead>
        <tr class="bg-muted/50">
          <th
            v-for="col in columns"
            :key="col.key"
            class="border border-border p-2 text-left text-sm font-medium"
          >
            <div class="space-y-1">
              <button
                v-if="col.sortable"
                type="button"
                class="flex items-center gap-1 hover:underline"
                @click="handleSort(col.key)"
              >
                {{ col.label }}
                <ChevronUp
                  v-if="sortBy === col.key && sortOrder === 'asc'"
                  class="w-4 h-4"
                />
                <ChevronDown
                  v-else-if="sortBy === col.key && sortOrder === 'desc'"
                  class="w-4 h-4"
                />
              </button>
              <span v-else>{{ col.label }}</span>
              <Input
                v-if="col.searchable"
                :model-value="searchValues[col.key] ?? ''"
                placeholder="Search..."
                class="mt-1 h-8 text-sm"
                @update:model-value="(v) => updateSearch(col.key, v)"
                @keyup.enter="handleSearch"
              />
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="isLoading" class="bg-muted/20">
          <td :colspan="columns.length" class="border border-border p-8 text-center">
            <Loader2 class="w-8 h-8 animate-spin mx-auto text-muted-foreground" />
          </td>
        </tr>
        <tr
          v-else-if="data.length === 0"
          class="bg-muted/20"
        >
          <td :colspan="columns.length" class="border border-border p-8 text-center text-muted-foreground">
            {{ emptyMessage }}
          </td>
        </tr>
        <tr
          v-else
          v-for="(row, idx) in data"
          :key="idx"
          class="hover:bg-muted/30 cursor-pointer transition-colors"
          @click="emit('row-click', row, idx)"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            class="border border-border p-2 text-sm"
          >
            {{ cellValue(row, col) }}
          </td>
        </tr>
      </tbody>
    </table>

    <div
      v-if="totalPages > 1"
      class="flex justify-center items-center gap-2 mt-4 pt-4 border-t border-border"
    >
      <Button
        variant="outline"
        size="sm"
        :disabled="!hasPrevPage"
        @click="changePage(currentPage - 1)"
      >
        <ChevronLeft class="w-4 h-4 mr-1" />
        Previous
      </Button>
      <span class="text-sm text-muted-foreground px-2">
        {{ currentPage }} / {{ totalPages }}
      </span>
      <Button
        variant="outline"
        size="sm"
        :disabled="!hasNextPage"
        @click="changePage(currentPage + 1)"
      >
        Next
        <ChevronRight class="w-4 h-4 ml-1" />
      </Button>
    </div>
  </div>
</template>
