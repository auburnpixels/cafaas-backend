<template>
    <div>
        <label v-if="label" class="label mb-2">{{ label }}</label>

        <div class="flex">
            <slot name="append" v-if="showAppend"></slot>
            <input
                :id="id"
                :min="min"
                :ref="ref"
                :type="type"
                :name="name"
                :step="step"
                :value="value"
                :readonly="readonly"
                :disabled="disabled"
                :required="required"
                :placeholder="placeholder"
                :class="inputBrandClasses"
                :maxlength="maxlength"
                autocomplete="off"
            />
            <slot name="prepend" v-if="showPrepend"></slot>
        </div>

        <span v-if="errorMessage && showErrorMessage" class="block mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>

</template>

<script>
export default {

    props: {
        id: String,
        step: {
            type: String,
            default: '1'
        },
        min: Number,
        name: String,
        type: String,
        value: String,
        label: String,
        readonly: Boolean,
        placeholder: String,
        errorMessage: String,
        styling: {
            type: String,
            default: 'neutral'
        },
        brandClass: {
            type: String,
            default: ''
        },
        required: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        maxlength: {
            type: Number,
        },
        showAppend: {
            type: Boolean,
            default: false
        },
        showPrepend: {
            type: Boolean,
            default: false
        },
        showErrorMessage: {
            type: Boolean,
            default: true
        },
        ref: {
            type: String,
        }
    },

    computed: {
        inputBrandClasses: function() {
            let classes = 'input text-sm md:text-base '

            if (this.errorMessage) classes += 'input-error '
            if (this.readonly) classes += 'input-readonly '
            if (this.styling === 'brand') classes += (' ' + this.brandClass)

            if (this.showAppend) classes += ' rounded-tl-none rounded-bl-none '
            if (this.showPrepend) classes += ' rounded-tr-none rounded-br-none '

            return classes
        },
    }

}
</script>

<style>
input[readonly],
input[disabled] {
    opacity: 0.5;
    background-color: rgb(221, 221, 221);
    cursor: not-allowed;
}
</style>
