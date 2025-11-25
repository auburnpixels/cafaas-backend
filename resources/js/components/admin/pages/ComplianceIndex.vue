<script setup>
import {ref} from 'vue'
import AdminLayout from '../AdminLayout.vue'
import Card from '../ui/Card.vue'
import Stat from '../ui/Stat.vue'
import Table from '../ui/Table.vue'
import TableHeader from '../ui/TableHeader.vue'
import TableBody from '../ui/TableBody.vue'
import TableRow from '../ui/TableRow.vue'
import TableHead from '../ui/TableHead.vue'
import TableCell from '../ui/TableCell.vue'
import Badge from '../ui/Badge.vue'
import Button from '../ui/Button.vue'

const props = defineProps({
    metrics: Object,
    raffles: Array,
})

const getComplianceVariant = (score) => {
    if (score >= 80) return 'success'
    if (score >= 60) return 'warning'
    return 'destructive'
}

const getStatusVariant = (status) => {
    const variants = {
        active: 'success',
        ended: 'default',
        review: 'warning',
    }
    return variants[status] || 'outline'
}

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text)
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
</script>

<template>
    <AdminLayout title="Compliance Dashboard">
        <template #actions>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" :as="'a'" href="/aed23bc1-6900-47c2-908d-9dc8215690b0/compliance/export">
                    Export CSV
                </Button>
                <Button variant="outline" size="sm" :as="'a'" href="/aed23bc1-6900-47c2-908d-9dc8215690b0/compliance/export-json">
                    Export JSON
                </Button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Stat
                    title="Raffles Hosted"
                    :value="metrics?.total_raffles || 0"
                    description="Total active and completed raffles"
                />

                <Stat
                    title="With Free Entry Route"
                    :value="`${metrics?.free_entry_percentage || 0}%`"
                    :description="`${metrics?.raffles_with_free_entry || 0} of ${metrics?.total_raffles || 0}`"
                />

                <Stat
                    title="With Audit Logs"
                    :value="`${metrics?.audit_logs_percentage || 0}%`"
                    :description="`${metrics?.raffles_with_audit_logs || 0} of ${metrics?.total_raffles || 0}`"
                />

                <Stat
                    title="Active Complaints"
                    :value="metrics?.active_complaints || 0"
                    description="Complaints pending resolution"
                />

                <Stat
                    title="Postal Entries Received"
                    :value="metrics?.total_postal_entries_received || 0"
                    description="Total free postal entries across platform"
                />

                <Stat
                    title="Avg Postal per Raffle"
                    :value="(metrics?.avg_postal_entries_per_raffle || 0).toFixed(2)"
                    description="Average free entries per competition"
                />
            </div>

            <!-- Raffles Details Table -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Raffle Details</h2>
                </div>

                <Card>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Raffle ID</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-center">Total Entries</TableHead>
                                <TableHead class="text-center">Postal Entries</TableHead>
                                <TableHead class="text-center">Free Entry %</TableHead>
                                <TableHead class="text-center">Audit</TableHead>
                                <TableHead class="text-center">Complaints</TableHead>
                                <TableHead class="text-center">Compliance Score</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="raffle in raffles" :key="raffle.id" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <code class="text-xs font-mono whitespace-nowrap">{{ raffle.uuid.substring(0, 12) }}</code>
                                        <button
                                            @click="copyToClipboard(raffle.uuid)"
                                            type="button"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="Copy raffle UUID"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="max-w-md">
                                        <a
                                            :href="`/raffles/${raffle.slug}`"
                                            target="_blank"
                                            class="text-primary underline"
                                        >
                                            {{ raffle.title }}
                                        </a>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="max-w-md">
                                        <div class="mt-1">
                                            <Badge :variant="getStatusVariant(raffle.status)" class="text-xs">
                                                {{ raffle.status.replace(/_/g, ' ') }}
                                            </Badge>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span class="font-semibold">{{ raffle.entries_total.toLocaleString() }}</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="raffle.postal_entries_count > 0" class="text-primary font-semibold">
                                        {{ raffle.postal_entries_count.toLocaleString() }}
                                    </span>
                                    <span v-else class="text-muted-foreground">0</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="raffle.free_entry_pct > 0" class="text-green-600 font-semibold">
                                        {{ raffle.free_entry_pct }}%
                                    </span>
                                    <span v-else class="text-muted-foreground">0%</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <a
                                        v-if="raffle.has_audit"
                                        :href="`/draw-audit/${raffle.uuid}`"
                                        target="_blank"
                                        class="text-green-600 hover:underline font-semibold"
                                    >
                                        ✓ View
                                    </a>
                                    <span v-else class="text-muted-foreground">✗</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="raffle.active_complaints_count > 0" class="text-destructive font-semibold">
                                        {{ raffle.active_complaints_count }}
                                    </span>
                                    <span v-else class="text-green-600 font-semibold">✓ 0</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="getComplianceVariant(raffle.compliance_score)" class="font-bold">
                                        {{ raffle.compliance_score }}%
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="!raffles || raffles.length === 0" class="p-8 text-center text-muted-foreground">
                        No raffles found.
                    </div>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
