<template>
    <div :class="{ 'bg-green-50': (type === 'success'), 'bg-red-50': (type === 'error') }" class="rounded p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg v-if="type === 'error'" class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-if="type ==='success'" class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p :class="{ 'text-green-800': (type === 'success'), 'text-red-800': (type === 'error') }" class="text-sm font-medium text-green-800">{{ message }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {

    props: {
        type: {
            type: String,
            default: 'success'
        },
        message: {
            type: String
        }
    },

    data() {
        return {}
    },


}
</script>
