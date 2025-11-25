<template>
    <form-group
        title="Charities"
        help-text="
            Donate a portion of your ticket sales to a charity or cause you choose to support, and once the winners have received their prizes, a payment will be paid directly to them on your behalf. If the charity or cause does not appear in the list, please email us at contact@raffaly.com and we will add it to our database. Please include a link to the charities website. As soon as the charity or cause has been added, you will be notified via the email associated with your account, at which point you will be able to select it from the list.
        "
        :transparent-background="true"
    >
        <div class="space-y-4">
            <div v-for="(charity, key) in data" class="space-y-4 bg-white w-full p-5 md:p-10 rounded-xl mt-5" :class="{'pt-4' : key > 0}">
                <div v-if="!disabled">
                    <button v-if="charities.length > 1" class="text-raffaly-primary flex items-center" @click.prevent="removeCharity(key)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span class="ml-1">Remove</span>
                    </button>
                </div>

                <input type="hidden" :name="'charities[' + key + '][id]'" :value="charity.id" />

                <form-searchable-select
                    label="Charity or cause"
                    :multiple="false"
                    :disabled="disabled"
                    :options="charities"
                    :selected="charity?.charity_id"
                    :name="'charities[' + key + '][charity_id]'"
                    :error-message="errors['charities.' + key + '.charity_id']?.[0] ?? null"
                ></form-searchable-select>

                <form-field
                    :input-col-span="1"
                    label="Donation"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="The total percentage of ticket sales you want to donate to charity."
                >
                    <form-input
                        id="charity_donation"
                        type="number"
                        min="0"
                        max="100"
                        :show-prepend="true"
                        :readonly="disabled"
                        :value="charity?.donation"
                        :name="'charities[' + key + '][charity_donation]'"
                        :error-message="errors['charities.' + key + '.charity_donation']?.[0] ?? null"
                    >
                        <template #prepend>
                            <div class="input-prepend">
                                <span class="">%</span>
                            </div>
                        </template>
                    </form-input>
                </form-field>
            </div>
        </div>

        <button v-if="!disabled" @click.prevent="addAnotherCharity" class="w-full py-2 text-raffaly-primary border-raffaly-primary border rounded-xl">Add</button>

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
            default: [],
        },
        competitionCharities: {
            type: Array,
            default: [],
        },
        charities: {
            type: Array,
            default: [{
                charity_id: null,
                charity_donation: null,
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
            data: this.competitionCharities
        }
    },

    computed: {

    },

    methods: {
        isRequiredForPublishing(index) {
            return this.requiredForPublishing
        },

        addAnotherCharity() {
            this.data.push({
                charity_id: null,
                charity_donation: null,
            })
        },

        removeCharity(key) {
            this.data.splice(key, 1);
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
