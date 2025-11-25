<template>
    <div class="relative">
        <form method="GET" :action="formAction" class="flex-col" ref="filterForm">
            <div class="flex justify-between mb-2">
                <div>
                    <form-select
                        name="per_page"
                        :options="[
                            { 'value': 25, 'name': '25 results per page'},
                            { 'value': 50, 'name': '50 results per page'},
                            { 'value': 75, 'name': '75 results per page'},
                            { 'value': 100, 'name': '100 results per page'},
                            { 'value': 'all', 'name': 'Show all results '},
                        ]"
                        :empty-option="false"
                        :selected="perPageSelected"
                        @select-change="submitForm"
                    ></form-select>
                </div>
                <div>
                    <span>
                        <div class="relative" @click="showFilter = !showFilter">
                            <span
                                v-if="filtersApplied > 0"
                                class="filter-icon p-1 rounded-full absolute text-white text-xs h-5 w-5 flex justify-center items-center"
                                style="top: -4px; right: -4px;"
                            >
                                {{ filtersApplied }}
                            </span>
                            <Icon name="filter" class-name="border border-gray-200 rounded cursor-pointer h-10 w-10 bg-white p-2 text-black-400"></Icon>
                        </div>
                    </span>
                </div>
            </div>

            <div v-show="showFilter" class="shadow-2xl w-full absolute bg-white px-6 py-3 border border-gray-200 rounded mb-4">
                <span class="text-lg font-semibold text-gray-900 mb-2 block">Filter results</span>
                <div class="flex-col space-y-2">
                    <slot></slot>

                    <div class="flex justify-end mt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-white text-black">Filter</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {

    props: {
        formAction: String,
        filtersApplied: Number,
    },

    data() {
        return {
            showFilter: false,
        }
    },

    computed: {
        perPageSelected: function() {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const perPage = urlParams.get('per_page')

            if (perPage === 'all') return perPage

            return perPage ? parseInt(perPage) : 25
        },
    },

    methods: {
        submitForm() {
            this.$refs.filterForm.submit()
        }
    }

}
</script>
