<script setup>
import { cn } from '../../../lib/utils'
import Card from './Card.vue'

const props = defineProps({
    title: String,
    value: [String, Number],
    description: String,
    icon: String,
    trend: String,
    class: String,
})
</script>

<template>
    <div :class="cn('bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6', props.class)">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-muted-foreground">{{ title }}</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-foreground mt-2">{{ value }}</p>
                <p v-if="description" class="text-xs text-gray-500 dark:text-muted-foreground mt-1">{{ description }}</p>
            </div>
            <div v-if="icon || $slots.icon" class="flex-shrink-0">
                <slot name="icon">
                    <div class="w-5 h-5 text-gray-400 dark:text-muted-foreground" v-html="icon"></div>
                </slot>
            </div>
        </div>
        <div v-if="trend || $slots.trend" class="mt-4 flex items-center text-sm">
            <slot name="trend">
                <span class="text-gray-600 dark:text-muted-foreground">{{ trend }}</span>
            </slot>
        </div>
    </div>
</template>
