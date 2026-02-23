<script setup>
  import { computed, ref, watch, onMounted, nextTick } from "vue";
  import axios from "axios";
  import VueSelect from "vue3-select-component";
  /**
   * Props
   */
  const props = defineProps({
    modelValue: { type: [String, Number, Array, Object, null], default: null },
  
    // static options (if endpoint is empty)
    data: { type: Array, default: () => [] },
  
    // ajax mode when endpoint is set
    endpoint: { type: String, default: "" },
    queryParam: { type: String, default: "q" },
    resultsKey: { type: String, default: "items" },
  
    multiple: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
    placeholder: { type: String, default: "Select…" },
  
    /** When true, close menu after selecting an option. Defaults to !multiple. */
    closeOnSelect: { type: Boolean, default: undefined },
  
    minChars: { type: Number, default: 1 },
    debounceMs: { type: Number, default: 250 },
  
    // If true, emit raw objects (NOT {label,value} objects)
    emitObjects: { type: Boolean, default: false },

    // If true, expect modelValue to contain raw objects (extract IDs and cache for labels)
    loadsObjects: { type: Boolean, default: false },
  
    /**
     * dataType:
     *  - 'list'  : data = ['a','b'] => value='a', label='a'
     *  - 'array' : data = [{key:'Label'}] => value='key', label='Label'
     *  - 'model' : data = [{id,...}] => value=item[valueIndex], label=item[labelIndex]
     *  - 'object': data = [{...}] => value=item[valueIndex], label=item[labelIndex]
     */
    dataType: {
      type: String,
      default: "model",
      validator: (v) => ["list", "array", "model", "object"].includes(v),
    },
  
    labelIndex: { type: String, default: "name" },
    keyIndex: { type: String, default: "id" },
    valueIndex: { type: String, default: "id" },
  });
  
  const emit = defineEmits(["update:modelValue", "change"]);
  
  const isAjax = computed(() => !!props.endpoint && props.endpoint.trim().length > 0);
  
  /** Close menu after select; default !multiple. */
  const effectiveCloseOnSelect = computed(() =>
    props.closeOnSelect !== undefined ? props.closeOnSelect : !props.multiple
  );
  
  const selectContainerRef = ref(null);
  const vueSelectRef = ref(null);
  
  function onMenuOpened() {
    // Always mark menu as open and clear any stale flags when menu opens
    isMenuOpen = true;
    
    // If menu reopens right after selection (auto-reopen), close it immediately
    // This only happens if both flags are true (user just selected AND we're preventing reopen)
    if (preventMenuReopen && justSelected && effectiveCloseOnSelect.value && !props.multiple) {
      nextTick(() => {
        // Try to close the menu by blurring the input
        const input = selectContainerRef.value?.querySelector?.('input[type="text"]');
        if (input) {
          input.blur();
        }
        // Keep menuOptions empty to prevent showing all options
        menuOptions.value = [];
      });
      // Reset flags since we handled it
      justSelected = false;
      preventMenuReopen = false;
      isMenuOpen = false;
      return;
    }
    
    // User manually opened menu (by clicking) - ALWAYS clear flags and allow normal behavior
    // This ensures first interaction works correctly
    preventMenuReopen = false;
    justSelected = false;
    
    // If menuOptions is empty and minChars is 0, trigger initial fetch to populate options
    // This ensures there are options available when user starts typing
    if (isAjax.value && menuOptions.value.length === 0 && props.minChars === 0) {
      // Use nextTick to ensure this happens after VueSelect has fully opened
      nextTick(() => {
        doFetch('');
      });
    }
    
    // Also ensure allOptions are available in menuOptions for immediate display
    // This helps when user types before the fetch completes
    if (isAjax.value && menuOptions.value.length === 0 && allOptions.value.length > 0) {
      // Show all available options initially
      menuOptions.value = [...allOptions.value];
    }
    
    if (props.multiple) return;
    
    // Ensure selected item is in menuOptions so it can be scrolled to
    const currentValue = normalizeForSelect(props.modelValue);
    if (currentValue != null) {
      // Check if selected value is in menuOptions
      const selectedInMenu = menuOptions.value.some(o => String(o.value) === String(currentValue));
      
      if (!selectedInMenu) {
        // Find the selected option in allOptions
        const selectedOption = allOptions.value.find(o => String(o.value) === String(currentValue));
        
        if (selectedOption) {
          // Add selected option to menuOptions so it can be scrolled to
          menuOptions.value = uniqByValue([selectedOption, ...menuOptions.value]);
        }
      }
    }
    
    // Scroll to selected item
    nextTick(() => {
      const el = selectContainerRef.value?.querySelector?.(".menu-option.selected");
      if (el) {
        el.scrollIntoView({ block: "nearest", behavior: "auto" });
      }
    });
  }
  
  function onMenuClosed() {
    isMenuOpen = false;
  }
  
  /**
   * Tailwind classes to match the "shadcn / Laravel starter" input you showed.
   * We style the select "control" to look like your input.
   */
   const controlBase =
      'border-input !bg-transparent text-foreground ' +
      'dark:!bg-input/30 ' +
      'w-full min-w-0 rounded-md border pl-2 pr-0 shadow-xs ' +
      'transition-[color,box-shadow] outline-none ' +
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] ' +
      'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm'


const classes = computed(() => ({
  container: 'w-full',

  // ✅ key change: in multi mode, allow height to grow + wrap
  control: props.multiple
    ? controlBase + ' min-h-9 py-1 flex flex-wrap items-center gap-1 py-0'
    : controlBase + ' h-9',

  valueContainer: props.multiple
    ? 'min-w-0 flex flex-wrap items-center gap-1 flex-1'
    : 'min-w-0 flex-1',

  // tags
  multiValue: 'inline-flex items-center rounded-md border border-input bg-background px-2 py-0.5 text-xs text-foreground',
  multiValueLabel: 'truncate',
  multiValueRemove: 'ml-1 text-muted-foreground hover:text-foreground',

  placeholder: 'text-muted-foreground',
  singleValue: 'text-foreground',
  inputContainer: 'min-w-0 flex-1',
  searchInput: 'min-w-[6ch] flex-1 bg-transparent text-foreground outline-none placeholder:text-muted-foreground',

  indicators: 'gap-1',

  menuContainer:
    'mt-1 w-full max-h-60 overflow-auto rounded-md border border-border bg-popover text-popover-foreground shadow-md',

  menuOption:
    'relative flex cursor-default select-none items-center px-3 py-2 text-sm ' +
    'data-[focused=true]:bg-accent data-[focused=true]:text-accent-foreground',

  noResults: 'px-3 py-2 text-sm text-muted-foreground',
}))

onMounted(async () => {
  if (isAjax.value) {
    // Wait for parent's onMounted to potentially update the value
    await nextTick();
    await nextTick(); // Double nextTick to ensure parent's onMounted has run
    
    // If loadsObjects is true, pre-populate allOptions with the objects from modelValue
    // This avoids needing to fetch labels from the server
    if (props.loadsObjects) {
      preloadOptionsFromModelValue(props.modelValue);
      
      // Emit normalized IDs back to parent so form data is always IDs (not objects)
      // This ensures saving without changes will submit IDs, not objects
      const normalizedIds = normalizeForSelect(props.modelValue);
      if (normalizedIds != null && (Array.isArray(normalizedIds) ? normalizedIds.length > 0 : true)) {
        emit('update:modelValue', normalizedIds);
      }
    }
    
    // Get the current value (which may have been updated by parent's onMounted)
    let currentValue = normalizeForSelect(props.modelValue);
    
    // If we have a value, fetch it first (unless loadsObjects already provided it)
    if (currentValue != null && !props.loadsObjects) {
      const valuesToFetch = props.multiple 
        ? (Array.isArray(currentValue) ? currentValue : [])
        : [currentValue];
      
      // Filter out null values and check if they're missing
      const validValues = valuesToFetch.filter(v => 
        v != null && !allOptions.value.some(o => String(o.value) === String(v))
      );
      
      // Fetch selected value(s) by ids first
      if (validValues.length > 0) {
        await doFetch('', validValues);
      }
    }
    
    // Mark as ready - the watch will handle any value changes after this
    selectedValueLoaded.value = true;
    
    // Then do the initial fetch for dropdown options (if minChars allows)
    // This will merge into allOptions, preserving the selected value(s) we just fetched
    if (props.minChars === 0) {
      doFetch(''); // Don't await - let it run in background
    }
  }
})

  
  /**
   * Normalize whatever you have into { label, value, raw }
   * IMPORTANT: value MUST be non-null and unique, or you get "Canada selects Israel" bugs.
   */
  function toOption(raw, index) {
    if (props.dataType === "list") {
      const label = String(raw ?? "");
      return { label, value: raw, raw };
    }
  
    if (props.dataType === "array") {
      // Handle {key: 'Label'} format - key becomes value, property value becomes label
      if (raw && typeof raw === 'object') {
        const key = Object.keys(raw)[0];
        const label = String(raw[key] ?? "");
        return { label, value: key, raw };
      }
      // Fallback for simple values
      const label = String(raw ?? "");
      return { label, value: index, raw };
    }
  
    // model / object: use labelIndex + valueIndex (NOT hard-coded `id`)
    const label = String(raw?.[props.labelIndex] ?? "");
    const value = raw?.[props.valueIndex];
  
    // If value is missing, this option is unsafe (will cause collisions)
    if (value === undefined || value === null) return null;
  
    return { label, value, raw };
  }
  
  function uniqByValue(options) {
    const seen = new Set();
    const out = [];
    for (const o of options) {
      if (!o) continue;
      const k = typeof o.value === "string" || typeof o.value === "number" ? String(o.value) : JSON.stringify(o.value);
      if (seen.has(k)) continue;
      seen.add(k);
      out.push(o);
    }
    return out;
  }
  
  /**
   * Option stores:
   * - allOptions: everything we know about (used to resolve selected label)
   * - menuOptions: current dropdown options (ajax results or filtered static)
   */
  const allOptions = ref([]);
  const menuOptions = ref([]);
  const loading = ref(false);
  const error = ref(null);
  
  // Track when selected value is added to allOptions (for forcing VueSelect re-render)
  const selectedValueLoaded = ref(false);
  
  
  function setStaticOptions() {
    let items = props.data ?? [];
    // If data is a plain object (e.g. PHP associative array), convert to array of {key: value} entries
    if (items && typeof items === 'object' && !Array.isArray(items)) {
      items = Object.entries(items).map(([k, v]) => ({ [k]: v }));
    }
    const normalized = items.map((x, i) => toOption(x, i)).filter(Boolean);
    allOptions.value = uniqByValue(normalized);
    menuOptions.value = allOptions.value;
  }
  
  watch(
    () => props.data,
    () => {
      if (!isAjax.value) setStaticOptions();
    },
    { deep: true, immediate: true }
  );
  
  /**
   * Find options by value (used to map values -> raw objects)
   */
  function findOptionByValue(val) {
    return allOptions.value.find((o) => String(o.value) === String(val));
  }
  
  /**
   * v-model mapping:
   * - component v-model should be value(s)
   * - parent can optionally want raw object(s)
   */
  function normalizeForSelect(v) {
    if (props.multiple) {
      const arr = Array.isArray(v) ? v : [];
      // If loadsObjects is true, extract IDs from objects
      if (props.loadsObjects && arr.length > 0 && typeof arr[0] === 'object' && arr[0] !== null) {
        return arr.map(item => item?.[props.valueIndex]).filter(val => val != null);
      }
      return arr;
    }
    // Single value
    if (props.loadsObjects && v != null && typeof v === 'object') {
      return v[props.valueIndex] ?? null;
    }
    return v ?? null;
  }

  /**
   * Pre-populate allOptions with objects from modelValue when loadsObjects is true.
   * This allows displaying labels without fetching from the server.
   */
  function preloadOptionsFromModelValue(v) {
    if (!props.loadsObjects || v == null) return;
    
    const items = props.multiple ? (Array.isArray(v) ? v : []) : [v];
    const objectItems = items.filter(item => item != null && typeof item === 'object');
    
    if (objectItems.length === 0) return;
    
    // Convert objects to options and merge into allOptions
    const normalized = objectItems.map((x, i) => toOption(x, i)).filter(Boolean);
    allOptions.value = uniqByValue([...allOptions.value, ...normalized]);
  }

const internalValue = computed({
  get() {
    if (!props.emitObjects) return normalizeForSelect(props.modelValue)

    if (props.multiple) {
      const arr = Array.isArray(props.modelValue) ? props.modelValue : []
      return arr
        .map((raw, i) => toOption(raw, i))
        .filter(Boolean)
        .map((o) => o.value)
    }

    const o = toOption(props.modelValue, 0)
    return o?.value ?? null
  },
  set(v) {
    // Only set flags if menu was open (user actually selected something)
    // Don't set flags if value changes externally or during initialization
    if (!props.multiple && effectiveCloseOnSelect.value && v != null && isMenuOpen) {
      justSelected = true
      preventMenuReopen = true
      isMenuOpen = false // Menu will close after selection
      // Cancel any pending search requests
      clearTimeout(t)
      // Clear menuOptions to prevent showing all options if menu reopens
      menuOptions.value = []
      // Clear flags after a short delay - they'll be cleared immediately if user interacts
      nextTick(() => {
        setTimeout(() => {
          // Only clear if still set (user hasn't interacted)
          if (justSelected) justSelected = false
          if (preventMenuReopen) preventMenuReopen = false
        }, 300)
      })
    }
    
    // always emit correct shape
    if (!props.emitObjects) {
      const out = normalizeForSelect(v)
      emit('update:modelValue', out)
      emit('change', out)
      return
    }

    if (props.multiple) {
      const vals = Array.isArray(v) ? v : []
      const raws = vals.map((val) => findOptionByValue(val)?.raw).filter(Boolean)
      emit('update:modelValue', raws)
      emit('change', raws)
      return
    }

    const raw = v == null ? null : findOptionByValue(v)?.raw ?? null
    emit('update:modelValue', raw)
    emit('change', raw)
  },
})

  
  /**
   * Ajax search (triggered by @search)
   * NOTE: component emits empty string when menu closes, so we ignore it unless minChars==0
   */
  let t = null;
  let reqId = 0;
  let justSelected = false;
  let preventMenuReopen = false;
  let isMenuOpen = false; // Track if menu is currently open
  let userIsTyping = false; // Track if user is actively typing
  
  async function doFetch(search, ids = null) {
    if (!isAjax.value || props.disabled) return;
    
    const trimmed = (search ?? "").trim();
    
    // If user is actively searching (non-empty) or fetching by ids, ALWAYS allow and clear flags
    if (trimmed.length > 0 || ids) {
      preventMenuReopen = false;
      justSelected = false;
    }
    
    // Prevent fetching ONLY if we just selected AND it's an empty search (the clear event after selection)
    // Allow all other fetches (user searching, fetching by ids, etc.)
    if (preventMenuReopen && justSelected && effectiveCloseOnSelect.value && !ids && trimmed.length === 0) {
      // This is the clear event after selection - prevent it
      menuOptions.value = [];
      loading.value = false;
      return;
    }
  
    // ignore close-clear events unless minChars==0 or we're fetching by ids
    // NEVER clear menuOptions if user is actively typing
    if (!ids && trimmed.length < props.minChars && props.minChars > 0) {
      // If user is typing, don't clear menuOptions - keep it open
      if (userIsTyping || isMenuOpen) {
        loading.value = false;
        return; // Don't fetch, but don't clear menuOptions either
      }
      // Only clear if menu is closed and user isn't typing
      menuOptions.value = [];
      loading.value = false;
      return;
    }
  
    loading.value = true;
    error.value = null;
    const my = ++reqId;
    
    // Save previous menuOptions before replacing - we'll restore if results are empty but menu is open
    // If menuOptions is empty but allOptions has items, use allOptions as the "previous" to ensure menu stays open
    let previousMenuOptions = [];
    if (isMenuOpen || userIsTyping) {
      previousMenuOptions = menuOptions.value.length > 0 
        ? [...menuOptions.value] 
        : (allOptions.value.length > 0 ? [...allOptions.value] : []);
    }
  
    try {
      const params = {};
      if (ids) {
        params.ids = Array.isArray(ids) ? ids.join(',') : ids;
      }
      if (trimmed) {
        params[props.queryParam] = trimmed;
      }
      
      const { data } = await axios.get(props.endpoint, { params });
      if (my !== reqId) return;
  
      const arr = Array.isArray(data) ? data : (props.resultsKey ? data?.[props.resultsKey] : data);
      const normalized = (Array.isArray(arr) ? arr : []).map((x, i) => toOption(x, i)).filter(Boolean);
  
      // merge into cache so selected values can always resolve their label
      allOptions.value = uniqByValue([...allOptions.value, ...normalized]);
      
      // When fetching by ids, mark that selected value is now loaded
      if (ids) {
        const currentValue = normalizeForSelect(props.modelValue);
        const found = allOptions.value.find(o => String(o.value) === String(currentValue));
        if (found) {
          selectedValueLoaded.value = true;
        }
        // Force Vue to process the update and ensure VueSelect sees the new option
        await nextTick();
      }
      
      // Only update menuOptions if we're doing a search (not just fetching by ids)
      // Always update menuOptions for search results to keep menu open
      if (!ids || trimmed) {
        // If we have results, use them. If empty but menu is open, keep previous options
        if (normalized.length > 0) {
          menuOptions.value = uniqByValue(normalized);
        } else if (isMenuOpen || userIsTyping) {
          // Keep previous options to prevent menu from closing when results are empty
          // Use allOptions as fallback if both previous and current are empty
          if (previousMenuOptions.length > 0) {
            menuOptions.value = previousMenuOptions;
          } else if (allOptions.value.length > 0) {
            menuOptions.value = [...allOptions.value];
          } else {
            // Keep current menuOptions (don't clear it)
            menuOptions.value = menuOptions.value;
          }
        } else {
          menuOptions.value = uniqByValue(normalized);
        }
      }
    } catch (e) {
      if (my !== reqId) return;
      error.value = "Failed to load results";
      // NEVER clear menuOptions on error if user is typing or menu is open
      // Restore previous options to prevent menu from closing
      if (userIsTyping || isMenuOpen) {
        if (previousMenuOptions.length > 0) {
          menuOptions.value = previousMenuOptions;
        } else if (allOptions.value.length > 0) {
          menuOptions.value = [...allOptions.value];
        } else {
          // Keep current menuOptions (don't clear it)
          menuOptions.value = menuOptions.value;
        }
      } else {
        menuOptions.value = [];
      }
    } finally {
      if (my === reqId) loading.value = false;
    }
  }
  
  function onSearch(search) {
    if (!isAjax.value) return;
    
    const searchTrimmed = search ? search.trim() : '';
    
    // If user is typing (non-empty search), ALWAYS clear flags and allow search
    // This ensures the menu stays open when user types, even on first interaction
    if (searchTrimmed.length > 0) {
      // Mark that user is actively typing
      userIsTyping = true;
      // Force clear all flags when user is actively typing
      preventMenuReopen = false;
      justSelected = false;
      isMenuOpen = true; // Ensure menu is marked as open when user types
      
      // Cancel any pending fetches and start a new one
      clearTimeout(t);
      t = setTimeout(() => {
        // Double-check flags are cleared before fetching
        preventMenuReopen = false;
        justSelected = false;
        doFetch(search);
        // Clear typing flag after a delay
        setTimeout(() => {
          userIsTyping = false;
        }, 500);
      }, props.debounceMs);
      return;
    }
    
    // Empty search - user stopped typing
    userIsTyping = false;
    
    // Empty search - only prevent if we just selected (the clear event after selection)
    if (justSelected && preventMenuReopen && effectiveCloseOnSelect.value) {
      justSelected = false;
      // Let doFetch handle the prevent logic
      return;
    }
    
    // Ignore empty searches when menu is open - VueSelect emits these when user starts typing
    // to clear the input, but we don't want to process them as they can close the menu
    if (isMenuOpen) {
      return;
    }
    
    // Normal empty search (menu closing, etc.) - allow it
    clearTimeout(t);
    t = setTimeout(() => doFetch(search), props.debounceMs);
  }
  
  /**
   * Ensure selected values can always render labels:
   * when parent sets modelValue (e.g. initial page load), if the value isn’t in allOptions yet,
   * the select will show the raw value. So we keep allOptions intact and only replace menuOptions.
   */
  watch(
    () => props.modelValue,
    async (v) => {
      if (!isAjax.value || v == null) return;
      
      // If loadsObjects is true, pre-populate allOptions with the objects
      if (props.loadsObjects) {
        preloadOptionsFromModelValue(v);
        selectedValueLoaded.value = true;
        
        // Check if incoming value contains objects (not already normalized to IDs)
        const items = props.multiple ? (Array.isArray(v) ? v : []) : [v];
        const hasObjects = items.some(item => item != null && typeof item === 'object');
        
        // If it contains objects, emit normalized IDs back to parent
        if (hasObjects) {
          const normalizedIds = normalizeForSelect(v);
          emit('update:modelValue', normalizedIds);
        }
        return;
      }
      
      const currentValue = normalizeForSelect(v);
      if (currentValue == null) return;
      
      const valuesToCheck = props.multiple 
        ? (Array.isArray(currentValue) ? currentValue : [])
        : [currentValue];
      
      // Check if any values are missing from allOptions
      const missingValues = valuesToCheck.filter(val => {
        if (val == null) return false;
        return !allOptions.value.some(o => String(o.value) === String(val));
      });
      
      // Fetch missing values using ids parameter
      if (missingValues.length > 0) {
        await doFetch('', missingValues);
        selectedValueLoaded.value = true;
      } else {
        // Value already in allOptions
        selectedValueLoaded.value = true;
      }
    },
    { immediate: false }
  );
  </script>
  <style>
    /* vue3-select-component: let Tailwind tokens control colors */
.v-select,
.v-select * {
  color: inherit;
}

.v-select .v-select__control {
  background: transparent;
}

  </style>
  <template>
    <div ref="selectContainerRef" class="w-full">
      <VueSelect
        ref="vueSelectRef"
        :key="`select-${selectedValueLoaded}-${allOptions.length}`"
        class="smart-select"
        v-model="internalValue"
        :options="allOptions"
        :displayed-options="menuOptions"
        :is-multi="multiple"
        :is-disabled="disabled"
        :is-clearable="clearable"
        :placeholder="placeholder"
        :classes="classes"
        :close-on-select="effectiveCloseOnSelect"
        :select-on-blur="false"
        @search="onSearch"
        @menu-opened="onMenuOpened"
        @menu-closed="onMenuClosed"
      />


  
      <p v-if="loading" class="mt-1 text-sm text-muted-foreground">Searching…</p>
      <p v-else-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
    </div>
  </template>
  