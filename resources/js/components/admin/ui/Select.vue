<script setup>
import { computed } from 'vue'
import { cn } from '../../../lib/utils'

const props = defineProps({
    modelValue: [String, Number],
    options: Array,
    class: String,
    disabled: Boolean,
})

const emit = defineEmits(['update:modelValue'])

const classes = computed(() => cn(
    'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    props.class
))
</script>

<template>
    <select 
        :value="modelValue"
        @change="emit('update:modelValue', $event.target.value)"
        :class="classes"
        :disabled="disabled"
    >
        <slot />
    </select>
</template>
