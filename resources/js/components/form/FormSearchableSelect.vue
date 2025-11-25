<template>
    <div>
        <div class="flex justify-between items-center">
            <label v-if="label" class="label">{{ label }} <span v-if="required" class="text-red-600">*</span></label>
            <span v-if="requiredForPublishing" class="text-xs items-center uppercase text-gray-500">Required for publishing</span>
        </div>

        <div v-if="labelHelpText && (labelHelpTextType === 'string')" class="text-gray-700 text-sm lg:text-base">
            <p>{{ labelHelpText }}</p>
        </div>

        <v-select
            :name="name"
            label="name"
            class="w-full"
            :value="selected"
            :options="options"
            :multiple="multiple"
            v-model="selectedValue"
            :class="{ 'mt-2': label, 'input-error': errorMessage }"
            :placeholder="placeholder"
            :reduce="name => name.value"
            :disabled="disabled"
        ></v-select>

        <span v-if="errorMessage" class="block mt-2 text-red-500 text-sm">{{ errorMessage }}</span>

        <input type="hidden" :name="name" v-model="selectedValue" />
    </div>
</template>

<script>
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css';

export default {

    props: {
        id: String,
        name: String,
        type: String,
        label: String,
        options: Array,
        required: Boolean,
        placeholder: String,
        errorMessage: String,
        labelHelpText: {
            type: String,
        },
        labelHelpTextType: {
            type: String,
            default: 'icon'
        },
        multiple: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        selected: [String, Array, Number],
        requiredForPublishing: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            selectedValue: this.multiple ?
                (this.selected) ? this.selected?.split(',').map(Number) : null :
                this.selected ? parseInt(this.selected) : null,
        }
    },

    components: {
        vSelect,
    },

}
</script>

<style>

:root {
    --vs-colors--lightest: rgba(60, 60, 60, 0.26);
    --vs-colors--light: rgba(60, 60, 60, 0.5);
    --vs-colors--dark: #333;
    --vs-colors--darkest: rgba(0, 0, 0, 0.15);

    /* Search Input */
    --vs-search-input-color: inherit;
    --vs-search-input-placeholder-color: inherit;

    /* Font */
    --vs-font-size: 1rem;
    --vs-line-height: 1.4;

    /* Disabled State */
    --vs-state-disabled-bg: rgb(221, 221, 221);
    --vs-state-disabled-color: var(--vs-colors--light);
    --vs-state-disabled-controls-color: var(--vs-colors--light);
    --vs-state-disabled-cursor: not-allowed;

    /* Borders */
    --vs-border-color: rgb(209 213 219);
    --vs-border-width: 1px;
    --vs-border-style: solid;
    --vs-border-radius: 0.375rem;

    /* Actions: house the component controls */
    --vs-actions-padding: 4px 6px 0 3px;

    /* Component Controls: Clear, Open Indicator */
    --vs-controls-color: var(--vs-colors--light);
    --vs-controls-size: 1;
    --vs-controls--deselect-text-shadow: 0 1px 0 #fff;

    /* Selected */
    --vs-selected-bg: #f0f0f0;
    --vs-selected-color: var(--vs-colors--dark);
    --vs-selected-border-color: var(--vs-border-color);
    --vs-selected-border-style: var(--vs-border-style);
    --vs-selected-border-width: var(--vs-border-width);

    /* Dropdown */
    --vs-dropdown-bg: #fff;
    --vs-dropdown-color: inherit;
    --vs-dropdown-z-index: 1000;
    --vs-dropdown-min-width: 160px;
    --vs-dropdown-max-height: 350px;
    --vs-dropdown-box-shadow: 0px 3px 6px 0px var(--vs-colors--darkest);

    /* Options */
    --vs-dropdown-option-bg: #000;
    --vs-dropdown-option-color: var(--vs-dropdown-color);
    --vs-dropdown-option-padding: 3px 20px;

    /* Active State */
    --vs-dropdown-option--active-bg: #F5F5F5;
    --vs-dropdown-option--active-color: #000;

    /* Deselect State */
    --vs-dropdown-option--deselect-bg: #F5F5F5;
    --vs-dropdown-option--deselect-color: #fff;

    /* Transitions */
    --vs-transition-timing-function: cubic-bezier(1, -0.115, 0.975, 0.855);
    --vs-transition-duration: 150ms;
}

.vs--disabled {
    opacity: 0.5;
    background-color: rgb(221, 221, 221);
    cursor: not-allowed;
}
</style>
