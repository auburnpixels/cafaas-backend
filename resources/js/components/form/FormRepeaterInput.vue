<template>
    <div>
        <div v-for="n in amount" class="flex items-center">
            <input
                type="text"
                :name="name + '[]'"
                :class="{ 'border-red-300 text-red-900': errorMessage }"
                autocomplete="off"
                class="mb-3 block w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300 rounded-md"
            />

<!--            <div class="ml-4">-->
<!--                <input type="radio" name="correct" :value="(n - 1)" />-->
<!--            </div>-->
        </div>

        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>
</template>

<script>
export default {

    props: {
        value: Array,
        name: String,
        amount: Number,
        errorMessage: String
    },

}
</script>
