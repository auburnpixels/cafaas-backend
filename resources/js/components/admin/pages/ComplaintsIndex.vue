<script setup>
import {ref} from 'vue'
import AdminLayout from '../AdminLayout.vue'

const props = defineProps({
    initialComplaints: Array,
    statuses: Object,
    categories: Object,
})

const complaints = ref(props.initialComplaints || [])

// Get URL parameters to pre-populate filters
const urlParams = new URLSearchParams(window.location.search)
const selectedStatus = ref(urlParams.get('status') || '')
const selectedCategory = ref(urlParams.get('category') || '')

const getBadgeClass = (status) => {
    return status === 'pending'
        ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
        : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
}

const getCategoryBadgeClass = (category) => {
    const classes = {
        fairness: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        payment: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        entry_issue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        prize_issue: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        technical: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    }
    return classes[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}

const formatCategoryName = (category) => {
    return category.replace(/_/g, ' ').split(' ').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}
</script>

<template>
    <AdminLayout title="Complaints & Reports">
        <template #actions>
            <a
                href="/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints/export"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
            >
                Export
            </a>
        </template>

        <div class="space-y-6">
            <!-- Filters -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <select
                            v-model="selectedStatus"
                            name="status"
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Category</label>
                        <select
                            v-model="selectedCategory"
                            name="category"
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">All Categories</option>
                            <option value="fairness">Fairness</option>
                            <option value="payment">Payment</option>
                            <option value="entry_issue">Entry Issue</option>
                            <option value="prize_issue">Prize Issue</option>
                            <option value="technical">Technical</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="flex-1 inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            Apply Filters
                        </button>
                        <a
                            href="/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 py-2 border border-input bg-background hover:bg-accent"
                        >
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Complaints table -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Competition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-card divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="complaint in complaints" :key="complaint.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 text-sm">
                            <td class="px-6 py-4 whitespace-nowrap font-mono">
                                #{{ complaint.id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a
                                    v-if="complaint.competition"
                                    :href="`/competitions/${complaint.competition.slug}`"
                                    target="_blank"
                                    class="text-primary hover:underline max-w-[200px] truncate block"
                                >
                                    {{ complaint.competition.title }}
                                </a>
                                <span v-else class="text-gray-400">N/A</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-[200px]">
                                    <div class="font-medium text-sm">{{ complaint.reporterName }}</div>
                                    <div class="text-sm text-gray-500">{{ complaint.reporterEmail }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getCategoryBadgeClass(complaint.category)]">
                                        {{ formatCategoryName(complaint.category) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getBadgeClass(complaint.status)]">
                                        {{ complaint.status.charAt(0).toUpperCase() + complaint.status.slice(1) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {{ new Date(complaint.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a
                                    :href="`/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints/${complaint.id}`"
                                    class="text-primary hover:underline"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!complaints || complaints.length === 0" class="p-8 text-center text-gray-500">
                    No complaints found.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
