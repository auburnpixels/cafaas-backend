<template>
    <div class="flex flex-col">
        <Switch v-model="toggled" class="border border-gray-300" :class="[toggled ? 'bg-green-400' : 'user-profile-details-input-background', 'mt-2 md:mt-0 relative inline-flex flex-shrink-0 h-6 w-11 rounded-full cursor-pointer transition-colors ease-in-out duration-200']">
            <span class="sr-only">Use setting</span>
            <span aria-hidden="true" class="border border-gray-300" :class="[toggled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']" />
        </Switch>

        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage }}</span>

        <input type="hidden" :name="name" :value="toggled ? 1 : 0" />
    </div>

</template>

<script>
import { ref } from 'vue'
import { Switch } from '@headlessui/vue'

export default {
    props: {
        name: String,
        toggled: Number,
        errorMessage: String,
    },

    components: {
        Switch,
    },

}
</script>
