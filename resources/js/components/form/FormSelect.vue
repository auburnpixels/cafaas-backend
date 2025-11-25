<template>
    <select
        :id="id"
        :name="name"
        @change="$emit('selectChange', $event.target.value)"
        :class="classes"
        :disabled="disabled"
    >
        <option v-if="emptyOption"></option>
        <option
            v-for="option in options"
            :value="option.value"
            :selected="isSelectedOption(option)"
        >
            {{ option.name }}
        </option>
     </select>
    <span v-if="errorMessage && showErrorMessage" class="block mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
</template>

<script>
export default {

    props: {
        id: String,
        name: String,
        type: String,
        options: Array,
        selected: Number,
        readonly: Boolean,
        errorMessage: String,
        emptyOption: {
            type: Boolean,
            default: true
        },
        classOverrides: {
            type: String,
            default: ''
        },
        showErrorMessage: {
            type: Boolean,
            default: true
        },
        disabled: {
            type: Boolean,
            default: false,
        }
    },

    methods: {
        isSelectedOption: function(option) {
            return !!(this.selected && (option.value == this.selected));
        }
    },

    computed: {
        classes: function() {
            let classes = 'block w-full shadow-sm border-gray-300 focus:border-gray-300 rounded-md text-sm '

            if (this.errorMessage) classes += ' input-error '
            if (this.readonly) classes += 'pointer-events-none opacity-50 cursor-not-allowed '
            if (this.classOverrides) classes += this.classOverrides

            return classes
        },
    }

}
</script>

<style>
input[readonly],
select[disabled] {
    opacity: 0.5;
    background-color: rgb(221, 221, 221);
    cursor: not-allowed;
}
</style>
