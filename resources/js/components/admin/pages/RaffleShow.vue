<script setup>
import { ref } from 'vue'
import AdminLayout from '../AdminLayout.vue'
import Card from '../ui/Card.vue'
import Badge from '../ui/Badge.vue'
import Button from '../ui/Button.vue'
import Alert from '../ui/Alert.vue'

const props = defineProps({
    competition: Object,
    host: Object,
})

const showRejectConfirm = ref(false)
const showApproveConfirm = ref(false)

const handleReject = async () => {
    if (confirm('Are you sure you want to reject this raffle?')) {
        document.getElementById('reject-form').submit()
    }
}

const handleApprove = async () => {
    if (confirm(`Approve this raffle with ${props.competition?.tickets?.length || 0} tickets?`)) {
        document.getElementById('approve-form').submit()
    }
}
</script>

<template>
    <AdminLayout :title="`Review: ${competition?.title}`">
        <template #actions>
            <form id="reject-form" :action="`/aed23bc1-6900-47c2-908d-9dc8215690b0/raffles/${competition?.uuid}/reject`" method="POST" class="inline">
                <input type="hidden" name="_token" :value="document.querySelector('meta[name=csrf-token]').content">
                <Button variant="destructive" size="sm" type="button" @click="handleReject">
                    Reject
                </Button>
            </form>

            <form id="approve-form" :action="`/aed23bc1-6900-47c2-908d-9dc8215690b0/raffles/${competition?.uuid}/approve`" method="POST" class="inline">
                <input type="hidden" name="_token" :value="document.querySelector('meta[name=csrf-token]').content">
                <Button size="sm" type="button" @click="handleApprove">
                    Approve ({{ competition?.tickets?.length || 0 }})
                </Button>
            </form>
        </template>

        <div class="space-y-6 max-w-6xl">
            <!-- Competition Images -->
            <Card class="p-6">
                <h2 class="text-xl font-semibold mb-4">Images</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-if="competition?.promotional_image" class="aspect-square overflow-hidden rounded-lg border">
                        <img 
                            :src="competition.promotional_image" 
                            :alt="competition.title"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <div 
                        v-for="(image, index) in competition?.imageGallery" 
                        :key="index"
                        class="aspect-square overflow-hidden rounded-lg border"
                    >
                        <img 
                            :src="image.location" 
                            :alt="`Gallery image ${index + 1}`"
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>
            </Card>

            <!-- Competition Details -->
            <Card class="p-6">
                <h2 class="text-xl font-semibold mb-4">Competition Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Title</label>
                        <p class="font-medium">{{ competition?.title }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
                        <Badge variant="outline">{{ competition?.type || 'traditional' }}</Badge>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Ticket Price</label>
                        <p class="font-medium">£{{ (competition?.price / 100).toFixed(2) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Total Tickets</label>
                        <p class="font-medium">{{ competition?.tickets_available }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Draw Date</label>
                        <p class="font-medium">{{ competition?.draw_at ? new Date(competition.draw_at).toLocaleString() : 'Not set' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
                        <Badge variant="warning">{{ competition?.status }}</Badge>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Summary</label>
                        <p class="text-sm">{{ competition?.summary }}</p>
                    </div>
                </div>
            </Card>

            <!-- Host Information -->
            <Card class="p-6">
                <h2 class="text-xl font-semibold mb-4">Host Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
                        <p class="font-medium">{{ host?.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Username</label>
                        <p class="font-medium">@{{ host?.username }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Email</label>
                        <a :href="`mailto:${host?.email}`" class="text-primary hover:underline">{{ host?.email }}</a>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Phone</label>
                        <p>{{ host?.phone || 'Not provided' }}</p>
                    </div>

                    <div v-if="host?.biography" class="col-span-2">
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Biography</label>
                        <p class="text-sm">{{ host.biography }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Total Competitions</label>
                        <p>{{ host?.competitions_count || 0 }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Member Since</label>
                        <p>{{ host?.created_at ? new Date(host.created_at).toLocaleDateString() : 'Unknown' }}</p>
                    </div>
                </div>
            </Card>

            <!-- Prizes -->
            <Card class="p-6">
                <h2 class="text-xl font-semibold mb-4">Prizes</h2>
                <div class="space-y-4">
                    <div 
                        v-for="(prize, index) in competition?.prizes" 
                        :key="prize.id"
                        class="border rounded-lg p-4"
                    >
                        <div class="flex items-start gap-4">
                            <div v-if="prize.images && prize.images.length > 0" class="flex-shrink-0">
                                <img 
                                    :src="prize.images[0].location" 
                                    :alt="prize.name"
                                    class="w-24 h-24 object-cover rounded"
                                />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg">{{ prize.name }}</h3>
                                <p class="text-sm text-muted-foreground mt-1">{{ prize.description }}</p>
                                <div class="mt-2 text-sm">
                                    <span class="font-medium">Delivery:</span> {{ prize.delivery_description || 'Not specified' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Question -->
            <Card v-if="competition?.question" class="p-6">
                <h2 class="text-xl font-semibold mb-4">Entry Question</h2>
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-2">Question</label>
                    <p class="font-medium mb-4">{{ competition.question.question }}</p>
                    
                    <label class="block text-sm font-medium text-muted-foreground mb-2">Answer Options</label>
                    <ul class="list-disc list-inside space-y-1">
                        <li 
                            v-for="option in competition.question.answersOptions" 
                            :key="option.value"
                            class="text-sm"
                            :class="option.value === competition.question.correct_answer ? 'font-semibold text-green-600' : ''"
                        >
                            {{ option.name }}
                            <span v-if="option.value === competition.question.correct_answer" class="text-xs">(Correct Answer)</span>
                        </li>
                    </ul>
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
