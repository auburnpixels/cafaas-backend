<script setup>
import { ref } from 'vue'
import AdminLayout from '../AdminLayout.vue'

const props = defineProps({
    complaint: Object,
})

const status = ref(props.complaint?.status || 'pending')
const adminNotes = ref(props.complaint?.admin_notes || '')

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
    <AdminLayout :title="`Complaint #${complaint?.id}`">
        <template #actions>
            <a 
                href="/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
            >
                Back to Complaints
            </a>
        </template>

        <div class="space-y-6 max-w-4xl">
            <!-- Complaint Details -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Complaint Details</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <div>
                            <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getBadgeClass(complaint.status)]">
                                {{ complaint.status.charAt(0).toUpperCase() + complaint.status.slice(1) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <div>
                            <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getCategoryBadgeClass(complaint.category)]">
                                {{ formatCategoryName(complaint.category) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Submitted</label>
                        <p class="text-sm">{{ new Date(complaint.created_at).toLocaleString() }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Last Updated</label>
                        <p class="text-sm">{{ new Date(complaint.updated_at).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <!-- Competition Information -->
            <div v-if="complaint.competition" class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Competition Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <a 
                            :href="`/competitions/${complaint.competition.slug}`" 
                            target="_blank"
                            class="text-primary hover:underline"
                        >
                            {{ complaint.competition.title }}
                        </a>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Host</label>
                        <a 
                            :href="`/profile/${complaint.competition.user.username}`" 
                            target="_blank"
                            class="text-primary hover:underline"
                        >
                            {{ complaint.competition.user.name }}
                        </a>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <p class="text-sm">{{ complaint.competition.status.replace(/_/g, ' ') }}</p>
                    </div>
                </div>
            </div>

            <!-- Reporter Information -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Reporter Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <p class="text-sm">{{ complaint.reporterName }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <a :href="`mailto:${complaint.reporterEmail}`" class="text-primary hover:underline text-sm">
                            {{ complaint.reporterEmail }}
                        </a>
                    </div>

                    <div v-if="complaint.user">
                        <label class="block text-sm font-medium mb-1">User Account</label>
                        <a 
                            :href="`/profile/${complaint.user.username}`" 
                            target="_blank"
                            class="text-primary hover:underline text-sm"
                        >
                            View Profile
                        </a>
                    </div>
                    <div v-else>
                        <label class="block text-sm font-medium mb-1">User Account</label>
                        <p class="text-sm text-gray-500">Not registered</p>
                    </div>
                </div>
            </div>

            <!-- Complaint Message -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Complaint Message</h2>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-md border border-gray-200 dark:border-gray-700">
                    <p class="text-sm whitespace-pre-wrap">{{ complaint.message }}</p>
                </div>
            </div>

            <!-- Admin Response Form -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Admin Response</h2>
                
                <form 
                    :action="`/aed23bc1-6900-47c2-908d-9dc8215690b0/complaints/${complaint.id}`" 
                    method="POST" 
                    class="space-y-4"
                >
                    <input type="hidden" name="_token" :value="document.querySelector('meta[name=\'csrf-token\']').content">
                    <input type="hidden" name="_method" value="PATCH">

                    <div>
                        <label for="status" class="block text-sm font-medium mb-2">
                            Status <span class="text-red-600">*</span>
                        </label>
                        <select 
                            v-model="status" 
                            name="status" 
                            id="status" 
                            required
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>

                    <div>
                        <label for="admin_notes" class="block text-sm font-medium mb-2">Admin Notes</label>
                        <textarea 
                            v-model="adminNotes" 
                            name="admin_notes" 
                            id="admin_notes"
                            rows="6"
                            placeholder="Add internal notes about this complaint (not visible to the reporter)..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            These notes are for internal use only and will not be shared with the reporter.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            Update Complaint
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Admin Notes -->
            <div v-if="complaint.admin_notes" class="bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-900 p-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-900 dark:text-blue-300">Current Admin Notes</h2>
                <div class="bg-white dark:bg-gray-900 p-4 rounded-md border border-blue-200 dark:border-blue-800">
                    <p class="text-sm whitespace-pre-wrap">{{ complaint.admin_notes }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
