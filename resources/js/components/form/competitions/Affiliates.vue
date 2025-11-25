<template>
    <form-group
        title="Competition affiliates"
        help-text="
            Enter the user ID and commission for specific Raffaly users you want to use as affiliates. Users can find their user ID in their account details.
        "
        :transparent-background="true"
    >
        <div class="space-y-4">
            <div v-for="(affiliate, key) in data" class="space-y-4 bg-white w-full p-5 md:p-10 rounded-xl mt-5" :class="{'pt-4' : key > 0}">
                <div v-if="!disabled">
                    <button class="text-raffaly-primary flex items-center" @click.prevent="removeAffiliate(key)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span class="ml-1">Remove</span>
                    </button>
                </div>

                <input type="hidden" :name="'competition_affiliates[' + key + '][id]'" :value="affiliate.id" />

                <form-field
                    :input-col-span="1"
                    label="User ID"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="
                        The User ID of the Raffaly user you want to set as an affiliate. The affiliate can find their User ID in their account details.
                    "
                >
                    <form-input
                        id="affiliate_user_id"
                        type="text"
                        :disabled="disabled"
                        :value="affiliate?.uuid"
                        :name="'competition_affiliates[' + key + '][affiliate_user_id]'"
                        :error-message="errors['competition_affiliates.' + key + '.affiliate_user_id']?.[0] ?? null"
                    >
                    </form-input>
                </form-field>

                <form-field
                    :input-col-span="1"
                    label="Commission"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="The percentage of total ticket sales you want to give to the affiliate."
                >
                    <form-input
                        id="affiliate_commission"
                        type="number"
                        min="0"
                        max="100"
                        :show-prepend="true"
                        :disabled="disabled"
                        :value="affiliate?.commission"
                        :name="'competition_affiliates[' + key + '][affiliate_commission]'"
                        :error-message="errors['competition_affiliates.' + key + '.affiliate_commission']?.[0] ?? null"
                    >
                        <template #prepend>
                            <div class="input-prepend">
                                <span class="">%</span>
                            </div>
                        </template>
                    </form-input>
                </form-field>

                <form-field
                    :input-col-span="1"
                    label="Affiliate link"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="The link that this competition affiliate need to use."
                >
                    <a :href="affiliate.link" class="text-raffaly-primary underline">{{ affiliate.link }}</a>
                </form-field>
            </div>
        </div>

        <button v-if="!disabled" @click.prevent="addAnotherAffiliate" class="w-full py-2 text-raffaly-primary border-raffaly-primary border rounded-xl">Add</button>

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
        competitionAffiliates: {
            type: Array,
            default: [],
        },
        affiliates: {
            type: Array,
            default: [{
                affiliate_user_id: null,
                affiliate_commission: null,
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
            data: this.competitionAffiliates
        }
    },

    computed: {

    },

    methods: {
        isRequiredForPublishing(index) {
            return this.requiredForPublishing
        },

        addAnotherAffiliate() {
            this.data.push({
                affiliate_user_id: null,
                affiliate_commission: null,
            })
        },

        removeAffiliate(key) {
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
