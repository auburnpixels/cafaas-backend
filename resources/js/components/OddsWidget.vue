<template>
    <div class="odds-widget">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h3 class="font-medium text">
                    Your chance of winning with {{ userTickets }} ticket{{ userTickets > 1 ? 's' : '' }} - <span class="highlight">{{ Math.min(userTickets, totalEntriesWithUser) }} in {{ displayOdds }}</span>
                </h3>
            </div>
            <button
                type="button"
                class="ml-4 text focus:outline-none"
                @click="showTooltip = !showTooltip"
                @blur="showTooltip = false"
                aria-label="Learn more about odds"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <transition>
            <div v-if="showTooltip" class="mt-3 p-3 body-bg rounded-md text-sm text">
                <p v-if="userTickets > 0">
                    With <span class="highlight">{{ userTickets }} ticket{{ userTickets > 1 ? 's' : '' }}</span>, you'll have <span class="highlight">{{ userTickets }} {{ userTickets > 1 ? 'entries' : 'entry' }}</span> out of <span class="highlight">{{ totalEntriesWithUser }} total entries</span>.
                    All entries — paid or free — have the same chance of winning. The odds update in real-time as more people enter the raffle.
                </p>
                <p v-else>
                    Your odds are calculated based on the number of tickets you select and the total entries.
                    The more tickets you buy, the better your chances of winning!
                </p>
            </div>
        </transition>

        <div v-if="hasError" class="mt-2 text-xs text-red-500">
            Unable to fetch latest odds
        </div>
    </div>
</template>

<script>
export default {
    props: {
        raffleId: {
            type: [Number, String],
            required: true
        },
        userTickets: {
            type: Number,
            default: 0
        }
    },

    data() {
        return {
            totalEntries: 0,
            isLoading: false,
            hasError: false,
            showTooltip: false,
            pollingInterval: null
        }
    },

    computed: {
        totalEntriesWithUser() {
            // Total entries if user's tickets are added
            return Math.max(this.totalEntries + this.userTickets, 1)
        },

        displayOdds() {
            // Display total entries with user's potential tickets
            return this.totalEntriesWithUser
        },

        accessibleText() {
            if (this.userTickets > 0) {
                return `With ${this.userTickets} ticket${this.userTickets > 1 ? 's' : ''}, your chance of winning is ${this.userTickets} in ${this.totalEntriesWithUser}. This means you have ${this.userTickets} ${this.userTickets > 1 ? 'entries' : 'entry'} out of ${this.totalEntriesWithUser} total entries.`
            }
            return `Current odds are 1 in ${this.displayOdds} based on ${this.totalEntries} existing entries.`
        }
    },

    mounted() {
        // Fetch initial stats
        this.fetchStats()

        // Set up polling every 4 seconds
        this.pollingInterval = setInterval(() => {
            this.fetchStats()
        }, 4000)
    },

    beforeUnmount() {
        // Clean up polling interval
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval)
        }
    },

    methods: {
        async fetchStats() {
            try {
                this.isLoading = true
                this.hasError = false

                const response = await fetch(`/api/raffles/${this.raffleId}/stats`)

                if (!response.ok) {
                    throw new Error('Failed to fetch stats')
                }

                const data = await response.json()
                this.totalEntries = data.totalEntries || 0
            } catch (error) {
                console.error('Error fetching raffle stats:', error)
                this.hasError = true
            } finally {
                this.isLoading = false
            }
        }
    }
}
</script>

