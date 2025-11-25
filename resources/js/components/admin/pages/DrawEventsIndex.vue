<script setup>
import {ref} from 'vue'
import AdminLayout from '../AdminLayout.vue'

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    },
    eventTypes: {
        type: Array,
        default: () => []
    },
    competitions: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 100,
            total: 0,
            from: 0,
            to: 0
        })
    },
})

const expandedEvent = ref(null)
const verificationResult = ref(null)

// Get URL parameters to pre-populate filters
const urlParams = new URLSearchParams(window.location.search)
const selectedEventType = ref(urlParams.get('event_type') || '')
const selectedCompetitionId = ref(urlParams.get('competition_id') || '')
const selectedActorType = ref(urlParams.get('actor_type') || '')
const selectedDateFrom = ref(urlParams.get('date_from') || '')
const selectedDateTo = ref(urlParams.get('date_to') || '')

const toggleEventDetails = (eventId) => {
    expandedEvent.value = expandedEvent.value === eventId ? null : eventId
}

const verifyIntegrity = async () => {
    verificationResult.value = {loading: true}

    try {
        const response = await fetch('/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })

        const data = await response.json()
        verificationResult.value = data.results
    } catch (error) {
        verificationResult.value = {error: error.message}
    }
}

const getEventBadgeClass = (eventType) => {
    if (eventType.includes('draw')) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    if (eventType.includes('raffle')) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    if (eventType.includes('entry')) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    if (eventType.includes('complaint')) return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    if (eventType.includes('system')) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

const truncateHash = (hash) => {
    if (!hash) return 'null'
    return hash.substring(0, 8) + '...' + hash.substring(hash.length - 8)
}

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text)
        // Visual feedback - you could add a toast here
        console.log('Copied to clipboard:', text)
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = text
        textArea.style.position = 'fixed'
        textArea.style.left = '-999999px'
        document.body.appendChild(textArea)
        textArea.select()
        try {
            document.execCommand('copy')
            console.log('Copied to clipboard (fallback):', text)
        } catch (error) {
            console.error('Failed to copy:', error)
        }
        document.body.removeChild(textArea)
    }
}

const buildPageUrl = (page) => {
    const url = new URL(window.location.href)
    url.searchParams.set('page', page)
    return url.toString()
}

const getPageNumbers = () => {
    const current = props.pagination.current_page
    const last = props.pagination.last_page
    const delta = 2 // Number of pages to show on each side of current page
    const pages = []
    
    // Always show first page
    pages.push(1)
    
    // Show pages around current page
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        // Add ellipsis if there's a gap
        if (pages[pages.length - 1] < i - 1) {
            pages.push('...')
        }
        pages.push(i)
    }
    
    // Show ellipsis before last page if needed
    if (last > 1 && pages[pages.length - 1] < last - 1) {
        pages.push('...')
    }
    
    // Always show last page
    if (last > 1) {
        pages.push(last)
    }
    
    return pages
}
</script>

<template>
    <AdminLayout title="Draw Event Log">
        <template #actions>
            <div class="flex items-center gap-2">
                <button
                    @click="verifyIntegrity"
                    type="button"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                >
                    Verify Integrity
                </button>
                <a
                    href="/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events/export/csv"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                >
                    Export CSV
                </a>
                <a
                    href="/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events/export/json"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                >
                    Export JSON
                </a>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Verification Result -->
            <div v-if="verificationResult && !verificationResult.loading"
                 :class="[
                     'rounded-lg border p-4',
                     verificationResult.is_valid
                         ? 'bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-900'
                         : 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-900'
                 ]"
            >
                <div v-if="verificationResult.is_valid" class="text-sm text-green-800 dark:text-green-300">
                    <strong class="font-semibold">✓ Verification Successful!</strong><br>
                    Total Events: {{ verificationResult.total_events }}<br>
                    Verified: {{ verificationResult.verified_events }}<br>
                    Failed: {{ verificationResult.failed_events }}
                </div>
                <div v-else class="text-sm text-red-800 dark:text-red-300">
                    <strong class="font-semibold">✗ Verification Failed!</strong><br>
                    Total Events: {{ verificationResult.total_events }}<br>
                    Verified: {{ verificationResult.verified_events }}<br>
                    Failed: {{ verificationResult.failed_events }}<br>
                    <em>Check the console for detailed errors.</em>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Event Type</label>
                        <select
                            v-model="selectedEventType"
                            name="event_type"
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">All Events</option>
                            <option v-for="type in eventTypes" :key="type" :value="type">
                                {{ type.replace(/[_\.]/g, ' ') }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Competition</label>
                        <select
                            v-model="selectedCompetitionId"
                            name="competition_id"
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">All Competitions</option>
                            <option v-for="comp in competitions" :key="comp.id" :value="comp.id">
                                #{{ comp.id }} - {{ comp.title ? comp.title.substring(0, 30) : 'Untitled' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Actor Type</label>
                        <select
                            v-model="selectedActorType"
                            name="actor_type"
                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">All Actors</option>
                            <option value="system">System</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">From Date</label>
                        <input
                            v-model="selectedDateFrom"
                            type="date"
                            name="date_from"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">To Date</label>
                        <input
                            v-model="selectedDateTo"
                            type="date"
                            name="date_to"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>

                    <div class="lg:col-span-5 flex gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            Apply Filters
                        </button>
                        <a
                            href="/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-10 px-4 py-2 border border-input bg-background hover:bg-accent"
                        >
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Events Table -->
            <div class="bg-white dark:bg-card rounded-lg border border-gray-200 dark:border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Event Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hash</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Previous Hash</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Competition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-card">
                        <template v-for="event in events" :key="`event-${event.id}`">
                            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-mono">
                                    {{ event.id ? String(event.id).substring(0, 12) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getEventBadgeClass(event.event_type)]">
                                            {{ event.event_type }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <code class="text-xs text-gray-600 dark:text-gray-400 font-mono">
                                            {{ truncateHash(event.event_hash) }}
                                        </code>
                                        <button
                                            @click="copyToClipboard(event.event_hash)"
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Copy hash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div v-if="event.previous_event_hash" class="flex items-center gap-2">
                                        <code class="text-xs text-gray-600 dark:text-gray-400 font-mono">
                                            {{ truncateHash(event.previous_event_hash) }}
                                        </code>
                                        <button
                                            @click="copyToClipboard(event.previous_event_hash)"
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Copy previous hash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-400 text-xs">null</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div v-if="event.competition" class="flex items-center gap-2">
                                        <a
                                            :href="`/aed23bc1-6900-47c2-908d-9dc8215690b0/draw-events?competition_id=${event.competition_id}`"
                                            class="text-primary hover:underline"
                                        >
                                            #{{ event.competition_id }}
                                        </a>
                                        <button
                                            @click="copyToClipboard(event.competition_id.toString())"
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Copy competition ID"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div v-if="event.actor_type">
                                        <span class="font-medium">{{ event.actor_type }}</span>
                                        <span v-if="event.actor_id" class="text-xs text-gray-500 ml-1">({{ event.actor_id }})</span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ new Date(event.created_at).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button
                                        @click.prevent="toggleEventDetails(event.id)"
                                        type="button"
                                        class="text-primary hover:underline focus:outline-none"
                                    >
                                        {{ expandedEvent === event.id ? 'Hide' : 'View' }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Expanded Details Row -->
                            <tr v-if="expandedEvent === event.id" :key="`event-${event.id}-details`" class="border-t border-gray-200 dark:border-gray-700">
                                <td colspan="8" class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                                    <div class="space-y-3 text-sm">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <strong class="text-gray-700 dark:text-gray-300">Full Event ID:</strong>
                                                <code class="block text-xs bg-white dark:bg-gray-900 px-2 py-1 rounded mt-1">{{ event.id }}</code>
                                            </div>
                                            <div>
                                                <strong class="text-gray-700 dark:text-gray-300">Sequence:</strong>
                                                <code class="block text-xs bg-white dark:bg-gray-900 px-2 py-1 rounded mt-1">{{ event.sequence || 'N/A' }}</code>
                                            </div>
                                        </div>
                                        <div>
                                            <strong class="text-gray-700 dark:text-gray-300">Event Hash (Full):</strong>
                                            <code class="block text-xs bg-white dark:bg-gray-900 px-2 py-1 rounded mt-1">{{ event.event_hash }}</code>
                                        </div>
                                        <div>
                                            <strong class="text-gray-700 dark:text-gray-300">Previous Hash (Full):</strong>
                                            <code class="block text-xs bg-white dark:bg-gray-900 px-2 py-1 rounded mt-1">{{ event.previous_event_hash || 'null (first event)' }}</code>
                                        </div>
                                        <div v-if="event.ip_address">
                                            <strong class="text-gray-700 dark:text-gray-300">IP Address:</strong>
                                            <span class="ml-2">{{ event.ip_address }}</span>
                                        </div>
                                        <div v-if="event.user_agent">
                                            <strong class="text-gray-700 dark:text-gray-300">User Agent:</strong>
                                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ event.user_agent }}</span>
                                        </div>
                                        <div>
                                            <strong class="text-gray-700 dark:text-gray-300">Event Payload:</strong>
                                            <pre class="bg-white dark:bg-gray-900 p-3 rounded border text-xs overflow-auto max-h-96 mt-1">{{ typeof event.event_payload === 'string' ? JSON.stringify(JSON.parse(event.event_payload), null, 2) : JSON.stringify(event.event_payload, null, 2) }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <div v-if="!events || events.length === 0" class="p-8 text-center text-gray-500">
                    No events found.
                </div>

                <!-- Pagination Controls -->
                <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-400">
                            Showing <span class="font-medium">{{ pagination.from }}</span> to <span class="font-medium">{{ pagination.to }}</span> of <span class="font-medium">{{ pagination.total }}</span> events
                        </div>
                        <nav class="flex items-center gap-1">
                            <!-- Previous Button -->
                            <a
                                v-if="pagination.current_page > 1"
                                :href="buildPageUrl(pagination.current_page - 1)"
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </a>
                            <span
                                v-else
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 border border-input bg-muted text-muted-foreground cursor-not-allowed"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </span>

                            <!-- Page Numbers -->
                            <template v-for="(page, index) in getPageNumbers()" :key="`page-${index}`">
                                <span v-if="page === '...'" class="px-2 text-gray-500">...</span>
                                <a
                                    v-else-if="page !== pagination.current_page"
                                    :href="buildPageUrl(page)"
                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 min-w-9 px-3 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    {{ page }}
                                </a>
                                <span
                                    v-else
                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 min-w-9 px-3 bg-primary text-primary-foreground"
                                >
                                    {{ page }}
                                </span>
                            </template>

                            <!-- Next Button -->
                            <a
                                v-if="pagination.current_page < pagination.last_page"
                                :href="buildPageUrl(pagination.current_page + 1)"
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 border border-input bg-background hover:bg-accent hover:text-accent-foreground"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <span
                                v-else
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 w-9 border border-input bg-muted text-muted-foreground cursor-not-allowed"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
