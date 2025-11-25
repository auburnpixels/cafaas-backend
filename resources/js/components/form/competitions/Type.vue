<template>
    <FormGroup title="Type">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div
                @click="setType('traditional')"
                class="border p-4 rounded-xl flex flex-col items-center "
                :class="{
                    'border-2 border-raffaly-primary': (type === 'traditional'),
                    'opacity-50': disabled,
                    'cursor-pointer': !disabled,
                }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-raffaly-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                </svg>
                <h3 class="font-extrabold text-base mt-2">Traditional</h3>
                <p class="text-sm text-gray-700 text-center">A raffle as you know it, having one or multiple prizes, setting a ticket limit and offering tickets at a fixed price.</p>
                <a :href="traditionalRafflesRoute" class="text-sm text-raffaly-primary underline mt-1">Read more</a>
            </div>

            <div
                @click="setType('access')"
                class="border p-4 rounded-xl flex flex-col items-center"
                :class="{
                    'border-2 border-raffaly-primary': (type === 'access'),
                    'opacity-50': disabled,
                    'cursor-pointer': !disabled,
                }">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-raffaly-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <h3 class="font-extrabold text-base mt-2">Access</h3>
                <p class="text-sm text-gray-700 text-center">When has one or more of the same item in high demand and wants to ensure that everyone has a fair chance of owning.</p>
                <a :href="accessRafflesRoute" class="text-sm text-raffaly-primary underline mt-1">Read more</a>
            </div>
        </div>

        <input type="hidden" name="type" :value="type" />
    </FormGroup>

</template>

<script>
import FormGroup from "../FormGroup";

export default {
    components: {
        FormGroup
    },

    props: {
        selected: null,
        accessRafflesRoute: null,
        traditionalRafflesRoute: null,
        disabled: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['setType'],

    mounted() {
        this.$emit('setType', this.type)
    },

    data() {
        return {
            type: _.cloneDeep(this.selected)
        }
    },

    computed: {
    },

    methods: {
        setType(type) {
            if (!this.disabled) this.type = type
            this.$emit('setType', type)
        }
    }

}
</script>
