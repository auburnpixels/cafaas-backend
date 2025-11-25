<template>
    <div class="mt-5">
        <div class="block">
            <div class="border-b detail_page_tab_border">
                <nav class="-mb-px flex space-x-4 lg:space-x-6 overflow-x-auto" aria-label="Tabs">
                    <a :class="[(activeTab === 'summary') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('summary')">
                        Summary
                    </a>
                    <a v-if="showCompetitionDetails" :class="[(activeTab === 'prize') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('prize')">
                        {{ isAccessRaffle ? 'Prize and shipping' : 'Prizes(s)'}}
                    </a>
                    <a v-if="showCompetitionDetails" :class="[(activeTab === 'details') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('details')">
                        Details
                    </a>
                    <a v-if="showCompetitionDetails" :class="[(activeTab === 'host') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('host')">
                        Host
                    </a>
                    <a v-if="showPartneredCharities" :class="[(activeTab === 'partnered-charities') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('partnered-charities')">
                        Charity
                    </a>
                    <a v-if="showCompetitionDetails && showShare" :class="[(activeTab === 'share') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('share')">
                        Share
                    </a>
                    <a v-if="showCompetitionDetails && showGuarantee" :class="[(activeTab === 'guarantee') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('guarantee')">
                        Guarantee
                    </a>
                    <a v-if="showCompetitionDetails" :class="[(activeTab === 'entrants') ? 'detail-page-tab-active' : 'border-transparent detail-page-tab-inactive detail-page-tab-inactive', 'whitespace-nowrap py-2 px-1 border-b-2 cursor-pointer']" @click="setTab('entrants')">
                        Entrants
                    </a>
                </nav>
            </div>

            <div class="pt-5 body-text">
                <div v-if="activeTab === 'summary'" id="competition-description">
                    <slot name="summary"></slot>
                </div>
                <div v-if="activeTab === 'prize' && showCompetitionDetails">
                    <slot name="prize"></slot>
                </div>
                <div v-if="activeTab === 'details' && showCompetitionDetails">
                    <slot name="details"></slot>
                </div>
                <div v-if="activeTab === 'host' && showCompetitionDetails">
                    <slot name="host"></slot>
                </div>
                <div v-if="activeTab === 'guarantee' && showCompetitionDetails && showGuarantee">
                    <slot name="guarantee"></slot>
                </div>
                <div v-if="activeTab === 'entrants' && showCompetitionDetails">
                    <slot name="entrants"></slot>
                </div>
                <div v-if="activeTab === 'partnered-charities' && showPartneredCharities">
                    <slot name="partnered-charities"></slot>
                </div>
                <div v-if="activeTab === 'share' && showShare">
                    <slot name="share"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script>


export default {

    data() {
        return {
            activeTab: 'summary',
        }
    },

    mounted() {
        if (this.requestedTab) this.setTab(this.requestedTab)
    },

    props: {
        isAccessRaffle: {
            type: Boolean,
            default: false
        },
        showCompetitionDetails: {
            type: Boolean,
            default: true
        },
        showPartneredCharities: {
            type: Boolean,
            default: false
        },
        showGuarantee: {
            type: Boolean,
            default: true
        },
        showShare: {
            type: Boolean,
            default: true
        },
        requestedTab: {
            type: String,
            default: null,
        }
    },

    methods: {
        setTab: function(tab) {
            this.activeTab = tab
        }
    }

}
</script>
