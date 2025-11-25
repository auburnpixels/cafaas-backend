<script setup>
import { ref } from 'vue'
import AdminLayout from '../AdminLayout.vue'

const props = defineProps({
    competitions: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: () => ({})
    }
})

const getStatusBadgeClass = (status) => {
    const classes = {
        review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        ended: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    }
    return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}
</script>

<template>
    <AdminLayout title="Raffles for Review">
        <div class="space-y-6">
            <!-- Raffles Table -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Host</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tickets</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-card divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="raffle in competitions" :key="raffle.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">
                                    #{{ raffle.id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-md">
                                        <div class="font-medium text-sm">{{ raffle.title }}</div>
                                        <div v-if="raffle.summary" class="text-sm text-gray-500 mt-1 truncate">
                                            {{ raffle.summary.substring(0, 60) }}...
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-sm">{{ raffle.user?.name }}</div>
                                        <div class="text-sm text-gray-500">@{{ raffle.user?.username }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ raffle.type || 'traditional' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadgeClass(raffle.status)]">
                                        {{ raffle.status.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ raffle.tickets_available || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ new Date(raffle.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a 
                                        :href="`/aed23bc1-6900-47c2-908d-9dc8215690b0/raffles/${raffle.uuid}`"
                                        class="text-primary hover:underline"
                                    >
                                        Review
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!competitions || competitions.length === 0" class="p-8 text-center text-gray-500">
                    No raffles pending review.
                </div>
            </div>

            <!-- Pagination Info -->
            <div v-if="pagination && pagination.total" class="text-sm text-gray-500 text-center">
                Showing {{ competitions.length }} of {{ pagination.total }} raffles
            </div>
        </div>
    </AdminLayout>
</template>
