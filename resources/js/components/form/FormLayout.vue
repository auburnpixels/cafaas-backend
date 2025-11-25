<template>
    <div
        class="overflow-hidden rounded"
        :class="{ 'rounded py-10 pt-10': (styling === 'brand'),  'bg-white border border-gray-200': (styling === 'neutral'), 'p-5 md:p-10': !noPadding}"
    >
        <form
            :class="formBrandClasses"
            :action="action"
            :method="method"
            enctype="multipart/form-data"
        >
            <div class="space-y-8">
                <slot></slot>

                <div class="flex justify-end sm:pt-5">
                    <input type="hidden" name="_token" :value="csrf" />
                    <input v-if="secondaryMethod" type="hidden" name="_method" :value="secondaryMethod">

                    <button
                        v-if="showSaveButton"
                        :value="submitButtonText"
                        class="flex items-center"
                        :class="submitBrandClasses"
                        :disabled="disabledSubmitButton"
                        @click="isSaving = true"
                    >
                        <svg v-if="isSaving" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="mr-2 text-white h-4 w-4"><path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" fill="currentColor"><animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/></path></svg>
                        <template v-if="isSaving">Saving</template>
                        <template v-if="!isSaving">{{ submitButtonText}}</template>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {

    props: {
        csrf: String,
        method: String,
        action: String,
        secondaryMethod: String,
        submitButtonText: {
            type: String,
            default: 'Submit'
        },
        disabledSubmitButton: {
            type: Boolean,
            default: false
        },
        noPadding: {
            type: Boolean,
            default: false
        },
        styling: {
            type: String,
            default: 'neutral'
        },
        formBrandClass: {
            type: String,
            default: ''
        },
        submitButtonBrandClass: {
            type: String,
            default: ''
        },
        showSaveButton: {
            type: Boolean,
            default: false,
        }
    },

    data() {
        return {
            isSaving: false
        }
    },

    computed: {
        formBrandClasses: function() {
            let classes = 'space-y-8 divide-y '

            if (this.styling === 'brand') classes += this.formBrandClass

            return classes
        },

        submitBrandClasses: function() {
            let classes = 'cursor-pointer items-center px-6 py-3 text-base font-medium rounded-xl text-white bg-raffaly-primary '

            if (this.styling === 'neutral') classes += 'admin-button '
            if (this.styling === 'brand') classes += this.submitButtonBrandClass
            if (this.isSaving) classes += 'opacity-50'

            return classes
        },
    },


    methods: {
        submitForm() {
            this.isSaving = true
        }
    }

}
</script>
