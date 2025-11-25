<script setup>
import { computed } from 'vue'
import { cn } from '../../../lib/utils'

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'secondary', 'destructive', 'outline', 'success', 'warning'].includes(value)
    },
})

const variants = {
    default: 'border-transparent bg-primary text-primary-foreground hover:bg-primary/80',
    secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
    destructive: 'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
    outline: 'text-foreground',
    success: 'border-transparent bg-green-100 text-green-800 hover:bg-green-100/80',
    warning: 'border-transparent bg-yellow-100 text-yellow-800 hover:bg-yellow-100/80',
}

const classes = computed(() => cn(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
    variants[props.variant]
))
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
