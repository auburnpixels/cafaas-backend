<template>
    <div>
        <input
            :id="id"
            :name="name"
            type="checkbox"
            :placeholder="placeholder"
            :checked="isChecked"
            class="block w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300 rounded-md"
            :class="{ 'border-red-300 text-red-900': errorMessage }"
        />
        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage}}</span>
    </div>

</template>

<script>
export default {

    props: {
        id: String,
        name: String,
        checked: Number,
        placeholder: String,
        errorMessage: String
    },

    computed: {
        isChecked: function() {
            if (this.checked === 1) return true
            return false
        }
    }

}
</script>
