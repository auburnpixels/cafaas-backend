<template>
    <FormGroup
        title="Ticket bundles"
        help-text="
            Incentivise and reward entrants with free tickets if conditions are met.
            <br /><br />
            <div>
                <strong>Match tickets: </strong>: Receive the same amount of free tickets as they have bought. For example buy 4 and receive 4 free tickets.<br />
                <strong>Exact tickets:</strong> When they by amount in 'Tickets bought', receive the amount 'Free tickets'.<br />
                <strong>More than tickets:</strong>When the amount of tickets they bought is same, or more than, value in 'Tickets bought', receive the amount 'Free tickets'.<br />
                <strong>Batch tickets:</strong> Receive same amount of free tickets for each multiple of tickets in 'Tickets bought'. For example, for every 3 tickets bought, receive 2 free tickets. An example, 6 tickets bought results in 4 free tickets.
            </div>
        "
        :transparent-background="true"
    >
        <div class="space-y-4">
            <div v-for="(localTicketBundle, key) in localTicketBundles" class="space-y-4 bg-white w-full p-5 md:p-10 rounded-xl mt-5" :class="{'pt-4' : key > 0}">

                <input type="hidden" :disabled="disabled" :name="'free_ticket_offer[' + key + '][id]'" :value="localTicketBundle.id" />

                <div v-if="!disabled">
                    <button class="text-raffaly-primary flex items-center" @click.prevent="removeTicketBundle(key)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span class="ml-1">Remove</span>
                    </button>
                </div>

                <input type="hidden" :name="'free_ticket_offer[' + key + '][id]'" :value="localTicketBundle.id" />

                <FormField
                    :input-col-span="1"
                    label="Type"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="Choose the type of free ticket incentive."
                >
                    <FormSelect
                        :disabled="isDisabled(localTicketBundle)"
                        :options="freeTicketOptions"
                        :name="'free_ticket_offer[' + key + '][type]'"
                        @select-change="handleFreeTicketTypeChange(localTicketBundle, $event)"
                        :selected="selectedType(localTicketBundle, key)"
                        :error-message="errors['free_ticket_offer.' + key + '.type'] ? errors['free_ticket_offer.' + key + '.type'][0] : null"
                    ></FormSelect>
                </FormField>

                <FormField
                    :input-col-span="1"
                    label="Tickets bought"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="The ticket amount that has to be bought to qualify for free ticket incentive."
                >
                    <FormInput
                        min="1"
                        :disabled="isTicketsBoughtDisabled(localTicketBundle)"
                        type="number"
                        :readonly="isMatchTicketsOption(localTicketBundles)"
                        :name="'free_ticket_offer[' + key + '][tickets_bought]'"
                        :error-message="errors['free_ticket_offer.' + key + '.tickets_bought'] ? errors['free_ticket_offer.' + key + '.tickets_bought'][0] : null"
                        :value="ticketsBought(localTicketBundle, key)"
                    ></FormInput>
                </FormField>

                <FormField
                    :input-col-span="1"
                    label="Free tickets"
                    orientation="vertical"
                    label-help-text-type="string"
                    label-help-text="The free ticket amount the entrant will receive, if they quality for the free ticket offer."
                >
                    <FormInput
                        min="1"
                        type="number"
                        :disabled="isDisabled(localTicketBundle)"
                        :readonly="isMatchTicketsOption(localTicketBundle)"
                        :name="'free_ticket_offer[' + key + '][free_tickets]'"
                        :error-message="errors['free_ticket_offer.' + key + '.free_tickets'] ? errors['free_ticket_offer.' + key + '.free_tickets'][0] : null"
                        :value="freeTickets(localTicketBundle, key)"
                    ></FormInput>
                </FormField>

                <form-field
                    :input-col-span="1"
                    orientation="vertical"
                    label="Ending"
                    label-help-text-type="string"
                    label-help-text="Choose when you want the ticket bundle offer to end."
                >
                    <form-date-time-input
                        :disabled="isDisabled(localTicketBundle)"
                        :value="endingAt(localTicketBundle, key)"
                        :name="'free_ticket_offer[' + key + '][ending_at]'"
                        :error-message="errors['free_ticket_offer.' + key + '.ending_at'] ? errors['free_ticket_offer.' + key + '.ending_at'][0] : null"
                    ></form-date-time-input>
                </form-field>

                <FormField
                    v-if="!sameCompetition"
                    :input-col-span="2"
                    label="Competition"
                    label-help-text="The competition the free tickets apply to for the offer."
                >
                    <FormSearchableSelect
                        :disabled="disabled"
                        :options="competitions"
                        :selected="associatedCompetitionId"
                        name="free_ticket_offer[associated_competition_id]"
                        :error-message="errors['free_ticket_offer.associated_competition_id'] ? errors['free_ticket_offer.associated_competition_id'][0] : null"
                    ></FormSearchableSelect>
                </FormField>
            </div>

            <button @click.prevent="addTicketBundle" class="w-full py-2 mt-5 border-raffaly-primary border text-raffaly-primary rounded-xl">Add</button>
        </div>
    </FormGroup>

</template>

<script>
import FormSearchableSelect from "../FormSearchableSelect";
import FormSelect from "../FormSelect";
import FormGroup from "../FormGroup";
import moment from 'moment-timezone';

export default {
    components: {
        FormGroup,
        FormSelect,
        FormSearchableSelect,
    },

    props: {
        old: Array,
        errors: {
            type: Array,
            default: []
        },
        competitions: Array,
        freeTicketOffer: Array,
        freeCompetitions: Array,
        freeTicketOptions: Array,
        sameCompetition: {
            type: Boolean,
            default: false
        },
        disabled: {
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
        timezone: {
            type: String,
        }
    },

    data() {
        return {
            localTicketBundles: this.freeCompetitions,
        }
    },

    computed: {
        associatedCompetitionId() {
            if (this.isEmptyTypeOption) return null
            if (this.old?.free_ticket_offer?.associated_competition_id) return this.old?.free_ticket_offer?.associated_competition_id
            if (this.freeTicketOffer?.associated_competition_id) return this.freeTicketOffer?.associated_competition_id
        },
    },

    methods: {
        handleFreeTicketTypeChange(ticketBundle, value) {
            ticketBundle.freeTicketType = value
        },

        addTicketBundle() {
            this.localTicketBundles.push({
                type: null,
                tickets_bought: null,
                tickets_received: null,
            })
        },

        isTicketsBoughtDisabled(ticketBundle) {
            return this.disabled && ticketBundle?.id || ['match_tickets'].includes(ticketBundle.freeTicketType)
        },

        isDisabled(ticketBundle) {
            return this.disabled && ticketBundle?.id
        },

        removeTicketBundle(key) {
            this.localTicketBundles.splice(key, 1);
        },

        isEmptyTypeOption(ticketBundle, key) {
            if (
                this.old?.free_ticket_offer &&
                this.old?.free_ticket_offer[key] &&
                this.old?.free_ticket_offer[key].type &&
                (this.old?.free_ticket_offer[key].type === '')
            ) {
                return true
            }

            return (ticketBundle.freeTicketType === '')
        },

        isMatchTicketsOption(ticketBundle) {
            return ticketBundle.freeTicketType && (ticketBundle.freeTicketType === 'match_tickets')
        },

        ticketsBought(ticketBundle, key) {
            if (this.isMatchTicketsOption(ticketBundle) || this.isEmptyTypeOption(ticketBundle, key)) return null

            if (this.old?.free_ticket_offer && this.old?.free_ticket_offer[key] && this.old?.free_ticket_offer[key].tickets_bought) {
                return this.old?.free_ticket_offer[key].tickets_bought
            }

            return ticketBundle?.tickets_bought
        },

        endingAt(ticketBundle, key) {
            if (this.isMatchTicketsOption(ticketBundle) || this.isEmptyTypeOption(ticketBundle, key)) return null

            if (this.old?.free_ticket_offer && this.old?.free_ticket_offer[key] && this.old?.free_ticket_offer[key].ending_at) {
                return this.old?.free_ticket_offer[key].ending_at
            }

            if (ticketBundle?.ending_at) {
                return moment(ticketBundle?.ending_at).tz(this.timezone).format('YYYY-MM-DD HH:mm')
            }

            return null
        },

        freeTickets(ticketBundle, key) {
            if (this.isMatchTicketsOption(ticketBundle) || this.isEmptyTypeOption(ticketBundle, key)) return null

            if (this.old?.free_ticket_offer && this.old?.free_ticket_offer[key] && this.old?.free_ticket_offer[key].free_tickets) {
                return this.old?.free_ticket_offer[key].free_tickets
            }

            return ticketBundle?.free_tickets
        },

        selectedType(ticketBundle, key) {
            if (this.old?.free_ticket_offer && this.old?.free_ticket_offer[key] && this.old?.free_ticket_offer[key]?.type) {
                return this.old?.free_ticket_offer[key]?.type
            }

            if (ticketBundle?.type) return ticketBundle.type

            return null
        }
    }

}
</script>
