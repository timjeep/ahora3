<script setup>
    import { computed } from 'vue'
    
    const props = defineProps({
      options: {
        type: Array,
        required: true,
      },
      dataType: {
        type: String,
        required: false,
        default: 'list'
      },
      keyIndex: {
        type: String,
        required: false,
        default: 'id'
      },
      valueIndex: {
        type: String,
        required: false,
        default: 'name'
      },
      buttonClass: {
        type: String,
        required: false,
        default: 'py-2'
      },
      modelValue: { required: false },
      disabled: {
        type: Boolean,
        required: false,
        default: false
      }
    })
    const emit = defineEmits(['update:modelValue'])
    
    // Variant → color map matching Button variants
    const variantColors = {
      default:     { bg: 'bg-primary',      hover: 'hover:bg-primary/90',      border: 'border-primary',      ring: 'focus:ring-primary/50' },
      destructive: { bg: 'bg-destructive',   hover: 'hover:bg-destructive/90',  border: 'border-destructive',  ring: 'focus:ring-destructive/50' },
      success:     { bg: 'bg-green-400',     hover: 'hover:bg-green-400/90',    border: 'border-green-400',    ring: 'focus:ring-green-400/50' },
      warning:     { bg: 'bg-yellow-400',    hover: 'hover:bg-yellow-400/90',   border: 'border-yellow-400',   ring: 'focus:ring-yellow-400/50' },
      caution:     { bg: 'bg-orange-400',    hover: 'hover:bg-orange-400/90',   border: 'border-orange-400',   ring: 'focus:ring-orange-400/50' },
      outline:     { bg: 'bg-accent',        hover: 'hover:bg-accent/90',       border: 'border-accent',       ring: 'focus:ring-accent/50' },
      secondary:   { bg: 'bg-secondary',     hover: 'hover:bg-secondary/80',    border: 'border-secondary',    ring: 'focus:ring-secondary/50' },
      neutral:     { bg: 'bg-gray-500',      hover: 'hover:bg-gray-500/90',     border: 'border-gray-500',     ring: 'focus:ring-gray-500/50' },
      edit:        { bg: 'bg-blue-600',      hover: 'hover:bg-blue-600/90',     border: 'border-blue-600',     ring: 'focus:ring-blue-600/50' },
      add:         { bg: 'bg-green-600',     hover: 'hover:bg-green-600/90',    border: 'border-green-600',    ring: 'focus:ring-green-600/50' },
      save:        { bg: 'bg-green-600',     hover: 'hover:bg-green-600/90',    border: 'border-green-600',    ring: 'focus:ring-green-600/50' },
      delete:      { bg: 'bg-red-600',       hover: 'hover:bg-red-600/90',      border: 'border-red-600',      ring: 'focus:ring-red-600/50' },
      cancel:      { bg: 'bg-gray-500',      hover: 'hover:bg-gray-500/90',     border: 'border-gray-500',     ring: 'focus:ring-gray-500/50' },
      magic:       { bg: 'bg-purple-600',    hover: 'hover:bg-purple-600/90',   border: 'border-purple-600',   ring: 'focus:ring-purple-600/50' },
    }
    const defaultVariantColor = variantColors.default

    // Normalize each entry into { label, value, icon, variant }:
    //
    // - If obj.label && obj.value exist, use them directly.
    // - Otherwise, assume exactly one key in the object; that key is `label`, its value is `value`.
    const normalizedOptions = computed(() => {
      // Case A: props.options is an Array
      if (Array.isArray(props.options)) {
        return props.options.map((opt) => {
          if (
            opt &&
            Object.prototype.hasOwnProperty.call(opt, 'label') &&
            Object.prototype.hasOwnProperty.call(opt, 'value')
          ) {
            return { 
              label: opt.label, 
              value: opt.value,
              icon: opt.icon || null,
              variant: opt.variant || null,
            }
          }
          // Otherwise, assume it's a single‐key object:
          const [firstKey] = Object.keys(opt || {})
          return { 
            label: firstKey, 
            value: opt[firstKey],
            icon: null,
            variant: null,
          }
        })
      }
    
      // Case B: props.options is a plain Object (e.g. { "Daily": "daily", "Weekly": "weekly" })
      return Object.entries(props.options).map(([key, val]) => ({
        label: val,
        value: key,
        icon: null,
        variant: null,
      })
    )
    })
    const selectedValue = computed(() => props.modelValue)
    
    function select(val) {
      if (props.disabled) return
      if (val !== selectedValue.value) {
        emit('update:modelValue', val)
      }
    }
    
    function getVariantColor(opt) {
      if (opt?.variant && variantColors[opt.variant]) {
        return variantColors[opt.variant]
      }
      return defaultVariantColor
    }

    function buttonClasses(idx, value) {
      const isSelected = value === selectedValue.value
      const opt = normalizedOptions.value[idx]
      const vc = getVariantColor(opt)
    
      let classes =
        `px-4 ${props.buttonClass} text-sm font-medium focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-2 ${vc.ring} `
    
      if (props.disabled) {
        if (isSelected) {
          // When disabled but selected, show selected styling with reduced opacity
          classes += `${vc.bg} text-white opacity-75 cursor-not-allowed `
        } else {
          // When disabled and not selected, show gray disabled styling
          classes += 'bg-gray-100 text-gray-400 cursor-not-allowed opacity-50 '
        }
      } else if (isSelected) {
        classes += `${vc.bg} text-white `
      } else {
        classes +=
          'bg-white text-gray-700 hover:bg-gray-50 hover:text-gray-900 '
      }
    
      if (idx === 0) {
        classes += `rounded-l-md border ${vc.border} `
      } else if (idx === normalizedOptions.value.length - 1) {
        classes += `rounded-r-md border ${vc.border} border-l-0 `
      } else {
        classes += `border ${vc.border} border-l-0 `
      }
    
      return classes
    }
    </script>
    <template>
      <div class="inline-flex rounded-md shadow-sm" role="group">
        <button
          v-if="dataType=='list'"
          v-for="(opt, idx) in normalizedOptions"
          :key="opt.value"
          type="button"
          :disabled="disabled"
          @click="select(opt.value)"
          :class="buttonClasses(idx, opt.value)"
        >
          <component v-if="opt.icon" :is="opt.icon" class="w-4 h-4 inline-block mr-1" />
          {{ opt.label }}
        </button>
        <button
          v-if="dataType=='object'"
          v-for="(opt, idx) in normalizedOptions"
          :key="opt[keyIndex]"
          type="button"
          :disabled="disabled"
          @click="select(opt[valueIndex])"
          :class="buttonClasses(idx, opt[valueIndex])"
        >
          <component v-if="opt.icon" :is="opt.icon" class="w-4 h-4 inline-block mr-1" />
          {{ opt.label }}
        </button>
      </div>
    </template>