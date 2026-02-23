<template>
    <button
    type="button"
    :class="[
        'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none',
        state ? 'bg-green-500' : 'bg-gray-200'
    ]"
    role="switch"
    :aria-checked="state?.toString()"
    @click="toggle"
    >
    <span class="sr-only">{{ label }}</span>
    <span
        :class="[
        'inline-block h-5 w-5 rounded-full bg-white shadow transform transition ease-in-out duration-200',
        state ? 'translate-x-5' : 'translate-x-0'
        ]"
    ></span>
    </button>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue'
  
  const props = defineProps({
    modelValue: {
      type: Boolean,
      default: false
    },
    label: {
      type: String,
      default: ''
    }
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  // internal state that mirrors prop but can update locally
  const state = ref(props.modelValue)
  
  // sync prop changes to internal state
  watch(() => props.modelValue, val => {
    state.value = val
  })
  
  function toggle() {
    state.value = !state.value
    emit('update:modelValue', state.value)
}
  </script>
  