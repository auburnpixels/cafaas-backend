<script setup>
import {ref, computed} from 'vue'
import {Menu, X, Home, FileText, AlertCircle, Activity, BarChart3, Moon, Sun} from 'lucide-vue-next'
import {useDarkMode} from '../../composables/useDarkMode'

const props = defineProps({
    title: {
        type: String,
        default: 'Admin Panel'
    },
    breadcrumbs: Array,
})

const sidebarOpen = ref(true)
const currentPath = ref(window.location.pathname)

const navigation = [
    {name: 'Raffles', href: '/aed23bc1-6900-47c2-908d-9dc8215690b0/raffles', icon: Home, pattern: '/raffles'},
    {name: 'Complaints', href: '/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints', icon: AlertCircle, pattern: '/complaints'},
    {name: 'Draw Events', href: '/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events', icon: Activity, pattern: '/draw-events'},
    {name: 'Compliance', href: '/aed23bc1-6900-47c2-908d-9dc8215690b0/compliance', icon: BarChart3, pattern: '/compliance'},
]

const isActive = (pattern) => {
    return currentPath.value.includes(pattern)
}

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

// Dark mode (automatically initializes on mount via composable)
const {isDark, toggle: toggleDarkMode} = useDarkMode()
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-background">
        <!-- Top Navigation -->
        <header class="bg-white dark:bg-card border-b border-gray-200 dark:border-border">
            <div class="mx-auto max-w-7xl">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Left side - Logo/Brand -->
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gray-900 dark:bg-primary flex items-center justify-center">
                                <span class="text-white dark:text-primary-foreground font-bold text-sm">A</span>
                            </div>
                            <span class="text-lg font-semibold text-gray-900 dark:text-foreground hidden sm:inline">Admin Panel</span>
                        </div>

                        <!-- Main Navigation -->
                        <nav class="hidden md:flex items-center gap-1">
                            <a
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                    isActive(item.pattern)
                                        ? 'text-gray-900 dark:text-foreground bg-gray-100 dark:bg-accent'
                                        : 'text-gray-600 dark:text-muted-foreground hover:text-gray-900 dark:hover:text-foreground hover:bg-gray-50 dark:hover:bg-accent/50'
                                ]"
                            >
                                {{ item.name }}
                            </a>
                        </nav>
                    </div>

                    <!-- Right side - Search & Tools -->
                    <div class="flex items-center gap-3">
                        <!-- Mobile menu button -->
                        <button
                            type="button"
                            class="md:hidden p-2 text-gray-600 dark:text-muted-foreground"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <Menu class="h-5 w-5"/>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <div class="bg-white dark:bg-card border-b border-gray-200 dark:border-border">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-foreground">{{ title }}</h1>
                    <div class="flex items-center gap-3">
                        <slot name="actions"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            <slot/>
        </main>

        <!-- Mobile Navigation Drawer -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-50 md:hidden"
            @click="sidebarOpen = false"
        >
            <div class="absolute inset-0 bg-black/50"/>
            <div class="absolute right-0 top-0 bottom-0 w-64 bg-white dark:bg-card p-4" @click.stop>
                <nav class="flex flex-col gap-2">
                    <a
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-md',
                            isActive(item.pattern)
                                ? 'text-gray-900 dark:text-foreground bg-gray-100 dark:bg-accent'
                                : 'text-gray-600 dark:text-muted-foreground'
                        ]"
                    >
                        {{ item.name }}
                    </a>
                </nav>
            </div>
        </div>
    </div>
</template>
