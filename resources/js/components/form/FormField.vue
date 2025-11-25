<template>
    <div :class="divBrandClasses">
        <div class="flex flex-col">
            <div class="flex justify-center mb-2 lg:mb-0 flex-col">
                <div class="flex justify-between items-center">
                    <div>
                        <label :class="labelClasses" v-if="label">
                            {{ label }} <span v-if="required" class="text-red-600">*</span>
                        </label>

                        <Popper v-if="labelHelpText && (labelHelpTextType === 'icon')" :arrow="true" :hover="true">
                            <icon name="question-mark-circle" :class-name="tooltipClasses"></icon>

                            <template #content>
                                <div class="text-sm" v-html="labelHelpText"></div>
                            </template>
                        </Popper>
                    </div>
                    <span v-if="requiredForPublishing" class="text-xs items-center uppercase text-gray-500">Required for publishing</span>
                </div>

                <div v-if="labelHelpText && (labelHelpTextType === 'string')" class="text-gray-700 text-sm lg:text-base">
                    <p v-html="labelHelpText"></p>
                </div>
            </div>

            <div v-if="explainerVideoUrl && explainerVideoLabel && showExplainerVideoLink">
                <text-link
                    target="_blank"
                    :external-link="true"
                    class="text-xs mt-1"
                    :href="explainerVideoUrl"
                >
                    {{ explainerVideoLabel }}
                </text-link>
            </div>

            <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage }}</span>

        </div>
        <div :class="{'sm:col-span-2': (inputColSpan === 2), 'sm:col-span-3': (inputColSpan === 3) }" class="mt-1 sm:mt-0">
            <slot></slot>
        </div>
    </div>
</template>

<script>

import Popper from "vue3-popper";

export default {
    components: {
        Popper
    },

    props: {
        label: String,
        labelHelpText: {
            type: String,
        },
        labelHelpTextType: {
            type: String,
            default: 'icon'
        },
        required: {
            type: Boolean,
            default: false
        },
        border: {
            type: Boolean,
            default: true
        },
        inputColSpan: {
            type: Number,
            default: 2
        },
        styling: {
            type: String,
            default: 'neutral'
        },
        brandClass: {
            type: String,
            default: ''
        },
        divBrandClass: {
            type: String,
            default: ''
        },
        orientation: {
            type: String,
            default: 'horizontal'
        },
        errorMessage: String,
        explainerVideoUrl: {
            type: String,
        },
        explainerVideoLabel: {
            type: String,
        },
        showExplainerVideoLink: {
            type: Boolean,
            default: true
        },
        requiredForPublishing: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        divBrandClasses: function() {
            let classes = 'sm:grid '

            if (this.styling === 'brand') classes += (this.divBrandClass) + ' sm:gap-2'

            if (this.orientation === 'horizontal') classes += ' sm:grid-cols-3 sm:gap-8'
            if (this.orientation === 'vertical') classes += ' sm:grid-cols-1 sm:gap-2'

            return classes
        },

        labelClasses: function() {
            let classes = 'label'
            if (this.styling === 'brand') classes += (' ' + this.brandClass)
            if (this.orientation === 'vertical') classes += ' font-bold'

            return classes
        },

        labelHelpTextClasses: function() {
            let classes = 'label-help-text'
            if (this.styling === 'brand') classes += (' ' + this.brandClass)
            return classes
        },

        tooltipClasses: function() {
            let classes = 'h-5 w-5 ml-2 cursor-pointer '

            if (this.styling === 'brand') classes += 'page-header-text'
            if (this.styling === 'neutral') classes += 'text-gray-700'

            return classes
        }
    }

}
</script>

<style>
.popper {
    max-width: 400px;
    border: 1px solid rgb(209, 213, 219) !important;
}

.popper #arrow::before {
    border-top: 1px solid rgb(209, 213, 219) !important;
    border-left: 1px solid rgb(209, 213, 219) !important;
}

:root {
    --popper-theme-background-color: #FFF;
    --popper-theme-background-color-hover: #FFF;
    --popper-theme-text-color: #000;
    --popper-theme-border-width: 1px;
    --popper-theme-border-style: solid;
    --popper-theme-border-radius: 6px;
    --popper-theme-padding: 20px;
    --popper-theme-box-shadow: 0 6px 30px -6px rgba(0, 0, 0, 0.25);
}
</style>
