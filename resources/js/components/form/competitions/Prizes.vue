<template>
    <form-group
        title="Prizes"
        help-text="
            On our platform, certain things are not permitted as prizes, and if your raffle includes prizes that do not comply with our terms, we reserve the right to withdraw it immediately.
            You may read more about our forbidden items <a target='_blank' class='text-pakapou-primary underline' href='{{ route('prohibited-items.index') }}'>here</a>.
        "
        :transparent-background="true"
    >
        <div class="space-y-4">
            <div v-for="(prize, key) in locationPrizes" class="space-y-4 bg-white w-full p-5 md:p-10 rounded-xl mt-5" :class="{'pt-4' : key > 0}">
                <div v-if="!disabled">
                    <button v-if="locationPrizes.length > 1" class="text-raffaly-primary flex items-center" @click.prevent="removePrize(key)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span class="ml-1">Remove</span>
                    </button>
                </div>

                <input type="hidden" :name="'prizes[' + key + '][id]'" :value="prize.id" />

                <form-field
                    label="Name"
                    :border="false"
                    :input-col-span="2"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="Enter the name of the winning prize."
                    :required-for-publishing="isRequiredForPublishing(key)"
                >
                    <form-input
                        type="text"
                        maxlength="255"
                        :class="{ 'input-error' : errors['prizes.' + key + '.name']?.[0] }"
                        :value="prize?.name"
                        :readonly="isDisabled(prize)"
                        :name="'prizes[' + key + '][name]'"
                        :error-message="errors['prizes.' + key + '.name']?.[0] ?? null"
                    ></form-input>
                </form-field>

                <form-field
                    :input-col-span="2"
                    label="Description"
                    :required="false"
                    orientation="vertical"
                    :border="false"
                    :required-for-publishing="isRequiredForPublishing(key)"
                    label-help-text-type="string"
                    label-help-text="Write a description of the winning prize."
                >
                    <form-textarea
                        placeholder=""
                        :readonly="isDisabled(prize)"
                        :value="prize?.description"
                        :name="'prizes[' + key + '][description]'"
                        :error-message="errors['prizes.' + key + '.description']?.[0] ?? null"
                    ></form-textarea>
                </form-field>

                <form-field
                    :input-col-span="2"
                    label="Image"
                    :required-for-publishing="false"
                    orientation="vertical"
                    label-help-text="This image will be featured in the prize details and the main gallery."
                    label-help-text-type="string"
                >
                    <form-image-input
                        :max-images="1"
                        :name="'prizes[' + key + '][images]'"
                        :value="prize?.imagesInput"
                        :images="prize?.imagesInput"
                    ></form-image-input>
                </form-field>

                <form-field
                    :input-col-span="2"
                    label="Delivery"
                    :required="false"
                    orientation="vertical"
                    :border="false"
                    :required-for-publishing="isRequiredForPublishing(key)"
                    label-help-text-type="string"
                    label-help-text="Choose the delivery method in which you will deliver the prize."
                >
                    <form-select
                        :readonly="isDisabled(prize)"
                        :selected="prize?.delivery"
                        :options="deliveryOptions"
                        :name="'prizes[' + key + '][delivery]'"
                        :error-message="errors['prizes.' + key + '.delivery']?.[0] ?? null"
                    ></form-select>
                </form-field>

                <form-field
                    :border="false"
                    :input-col-span="2"
                    orientation="vertical"
                    label="Collection town"
                    label-help-text-type="string"
                    label-help-text="If the prize is collection only, enter the town if can be collected from."
                >
                    <form-input
                        type="text"
                        :readonly="isDisabled(prize)"
                        :value="prize?.collection"
                        :name="'prizes[' + key + '][collection]'"
                    ></form-input>
                </form-field>
            </div>
        </div>

        <button @click.prevent="addAnotherPrize" class="w-full py-2 text-raffaly-primary border-raffaly-primary border rounded-xl">Add</button>

    </form-group>

</template>

<script>
import FormSearchableSelect from "../FormSearchableSelect";
import FormSelect from "../FormSelect";
import FormGroup from "../FormGroup";

export default {
    components: {
        FormGroup,
        FormSelect,
        FormSearchableSelect,
    },

    props: {
        old: Array,
        disabled: {
            type: Boolean,
            default: false
        },
        errors: {
            type: Array,
            default: []
        },
        deliveryOptions: {
            type: Array,
            default: []
        },
        prizes: {
            type: Array,
            default: [{
                name: null,
                description: null,
                delivery: null,
                collection: null
            }]
        },
        requiredForPublishing: {
            type: Boolean,
            default: false
        },
        showSubmitButton: {
            type: Boolean,
            default: false
        },
        submitButtonText: {
            type: String,
            default: 'Submit'
        },
    },

    data() {
        return {
            locationPrizes: this.prizes
        }
    },

    computed: {

    },

    methods: {
        isRequiredForPublishing(index) {
            return this.requiredForPublishing
        },

        addAnotherPrize() {
            this.locationPrizes.push({
                name: null,
                description: null,
                delivery: null,
                collection: null
            })
        },

        removePrize(key) {
            this.locationPrizes.splice(key, 1);
        },

        isDisabled(prize) {
            return (this.disabled && prize?.id) ? true : false
        }
    }

}
</script>

<style>

textarea[readonly], select[readonly] {
    opacity: 0.5;
    background-color: rgb(221, 221, 221);
    cursor: not-allowed;
}

</style>
