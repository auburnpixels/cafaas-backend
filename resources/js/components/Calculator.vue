<template>
    <div class="bg-[#E1DCEA] rounded-xl p-10">
        <div v-if="showSubheading || showHeading" class="mb-5">
            <span v-if="showSubheading" class="text-raffaly-primary font-extrabold whitespace-nowrap">{{ subheading }}</span>
            <h2 v-if="showHeading" class="text-2xl tracking-tight font-extrabold lg:text-2xl text-raffaly-primary">
                {{ heading }}
            </h2>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center w-full justify-between">
            <div :class="classes">
                If you sold <input type="text" v-model="tickets" :class="inputClasses">
                tickets at <div :class="input2Classes">£<input type="text" v-model="price" :class="input3Classes"></div>
                you would make <span :class="totalClasses">£{{ total }}</span>
            </div>

            <div v-if="showAction" class="flex items-start mt-4 lg:mt-0">
                <a :href="hostRaffleUrl" class="lg:ml-4 px-3 py-2 md:px-6 md:py-3 text-base sm:text-lg border border-white bg-white text-raffaly-primary rounded-xl whitespace-nowrap">Host a raffle</a>
            </div>
        </div>
    </div>
</template>

<script>
export default {

    props: {
        hostRaffleUrl: {
            type: String,
            default: null,
        },
        direction: {
            type: String,
            default: 'horizontal'
        },
        subheading: {
            type: String,
            default: 'FUNDRAISING'
        },
        showSubheading: {
            type: Boolean,
            default: true
        },
        heading: {
            type: String,
            default: 'How much could you make?'
        },
        showHeading: {
            type: Boolean,
            default: true
        },
        showAction: {
            type: Boolean,
            default: true,
        }
    },

    data() {
        return {
            tickets: 500,
            price: 1.99,
        }
    },


    computed: {
        classes() {
            if (this.direction === 'vertical') return 'text-raffaly-primary text-2xl flex flex-col items-start'
            if (this.direction === 'horizontal') return 'text-raffaly-primary text-2xl flex flex-col items-start lg:flex-row lg:items-center w-full lg:whitespace-nowrap'
        },

        inputClasses() {
            if (this.direction === 'vertical') return 'mt-2 mb-2 pl-4 border border-white bg-transparent text-2xl rounded-xl focus:border-white focus:ring-0 w-full'
            if (this.direction === 'horizontal') return 'mt-2 mb-2 lg:mt-0 lg:mb-0 lg:ml-4 mr-4 pl-4 border border-white bg-transparent text-2xl rounded-xl focus:border-white focus:ring-0 w-full lg:max-w-[150px]'
        },

        input2Classes() {
            if (this.direction === 'vertical') return 'mt-2 mb-2 pl-4 border border-white rounded-xl w-full'
            if (this.direction === 'horizontal') return 'mt-2 mb-2 lg:mt-0 lg:mb-0 lg:ml-4 mr-4 pl-4 border border-white rounded-xl w-full lg:max-w-[200px]'
        },

        input3Classes() {
            if (this.direction === 'vertical') return 'mr-4 bg-transparent text-2xl border-transparent rounded-xl focus:border-transparent focus:ring-0 max-w-[150px]'
            if (this.direction === 'horizontal') return 'mr-4 bg-transparent text-2xl border-transparent rounded-xl focus:border-transparent focus:ring-0 max-w-[150px]'
        },

        totalClasses() {
            if (this.direction === 'vertical') return 'mt-2 mb-2 font-extrabold border-b border-raffaly-primary border-dotted text-2xl'
            if (this.direction === 'horizontal') return 'mt-2 mb-2 lg:mt-0 lg:mb-0 lg:ml-2 font-extrabold border-b border-raffaly-primary border-dotted text-2xl'
        },


        total() {
            return ((this.tickets * this.price) * 0.90).toFixed(2)
        }
    }


}
</script>
