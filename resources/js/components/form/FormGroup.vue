<template>
    <div
        :class="{
            'flex flex-col lg:flex-row': (isHorizontal)
        }"
    >
        <div
            v-if="title || helpText"
            :class="{
                'w-full lg:w-1/3 mb-5 lg:mb-0 lg:mr-10': (isHorizontal)
            }"
        >
            <div class="flex justify-between align-top pb border-raffaly-primary">
                <div>
                    <h3
                        class="text-lg md:text-xl leading-6 font-medium"
                        :class="{ 'page-header-text': (styling === 'brand'), 'text-gray-00': (styling === 'neutral')}"
                    >{{ title }}</h3>

                    <div v-if="explainerVideoUrl && explainerVideoLabel && showExplainerVideoLink">
                        <text-link
                            target="_blank"
                            :external-link="true"
                            class="text-xs"
                            :href="explainerVideoUrl"
                        >
                            {{ explainerVideoLabel }}
                        </text-link>
                    </div>
                </div>

                <button
                    v-if="showSubmitButton"
                    :value="submitButtonText"
                    class="flex items-center"
                    :class="submitBrandClasses"
                    :disabled="disabledSubmitButton"
                    @click="isSaving = true"
                >
                    <svg v-if="isSaving" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="mr-2 text-white h-4 w-4"><path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" fill="currentColor"><animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/></path></svg>
                    <span v-if="isSaving">Saving</span>
                    <span v-if="!isSaving">{{ submitButtonText}}</span>
                </button>

            </div>
            <p
                v-if="helpText"
                class="mt-2 text-sm md:text-base"
                :class="{ 'text-gray-700': (styling === 'neutral'), 'text': (styling === 'brand') }"
                v-html="helpText"
            ></p>
        </div>

        <div
            class="space-y-4"
            :class="{
                'bg-white w-full lg:w-2/3 p-5 md:p-10 rounded-xl': (isHorizontal && !transparentBackground),
                'bg-white w-full p-5 md:p-10 rounded-xl mt-5': (!isHorizontal && !transparentBackground),
                'bg-transparent': (transparentBackground)
            }"
        >
            <slot></slot>
        </div>
    </div>
</template>

<script>
import TextLink from "../TextLink";
export default {
    components: {
        TextLink
    },

    props: {
        title: String,
        helpText: String,
        orientation: {
            type: String,
            default: 'vertical',
        },
        styling: {
            type: String,
            default: 'neutral'
        },
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
        showSubmitButton: {
            type: Boolean,
            default: false
        },
        submitButtonBrandClass: {
            type: String,
            default: ''
        },
        submitButtonText: {
            type: String,
            default: 'Submit'
        },
        disabledSubmitButton: {
            type: Boolean,
            default: false
        },
        transparentBackground: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            isSaving: false
        }
    },

    computed: {
        isHorizontal() {
            return (this.orientation === 'horizontal')
        },

        submitBrandClasses: function() {
            let classes = 'cursor-pointer items-center text-sm font-medium text-raffaly-primary underline '

            if (this.styling === 'neutral') classes += 'admin-button '
            if (this.styling === 'brand') classes += this.submitButtonBrandClass
            if (this.isSaving) classes += 'opacity-50'

            return classes
        },
    }
}
</script>
