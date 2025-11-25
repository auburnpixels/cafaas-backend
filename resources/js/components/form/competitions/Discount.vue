<template>
    <FormGroup
        title="Discount"
        help-text="
            Percentage or monetary discounts can be offered on either single ticket, bulk buy of tickets or the
            total checkout total.
        "
        explainer-video-label="Discount explainer video"
        :explainer-video-url="explainerVideoUrl"
    >
        <FormField
            label="Type"
            :input-col-span="1"
            label-help-text="
                <strong>Checkout total</strong><br />Discount will apply to the total of the checkout.<br /><br />
                <strong>Batch tickets</strong><br />Discount will apply to batch of tickets bought.<br /><br />
                <strong>Per ticket</strong><br />Discount will apply to each ticket bought.
            "
        >
            <FormSelect
                name="discount_type"
                :selected="discountType"
                :options="discountTypeOptions"
                @select-change="handleDiscountTypeChange"
                :error-message="errors['discount_type'] ? errors['discount_type'][0] : null"
            ></FormSelect>
        </FormField>
        <FormField
            label="Tickets"
            :input-col-span="2"
            label-help-text="
                The amount of tickets the discount applies for along with rule:<br /><br/>
                <strong>ticket(s) exactly</strong><br />
                The discount will only apply if you buy this exact amount of tickets.
                <br /><br />

                <strong>or more tickets with batch discount</strong><br />
                The discount will apply if you buy the set amount of tickets or more. The discount will also be calculated
                based on each 'batch' of tickets bought. For example, if you set the tickets to 3 and 6 tickets where bought,
                the discount would be applied twice.
                <br /><br />

                <strong>or more tickets without batch discount</strong><br />
                The discount will apply if you buy the set amount of tickets or more. The discount will be applied only once.
            "
        >
            <div class="flex">
                <FormInput
                    type="number"
                    name="discount_tickets"
                    :value="discountTickets"
                    :readonly="isPerTicketOption || isCheckoutTotalOption"
                    :show-prepend="true"
                    :show-error-message="false"
                    :error-message="errors['discount_tickets'] ? errors['discount_tickets'][0] : null"
                ></FormInput>
                <FormSelect
                    name="discount_ticket_type"
                    :selected="discountTicketType"
                    :options="discountTicketTypeOptions"
                    :readonly="isPerTicketOption || isCheckoutTotalOption"
                    @select-change="handleDiscountTicketTypeChange"
                    :show-error-message="false"
                    class-overrides="rounded-tl-none rounded-bl-none border-l-0"
                    :error-message="errors['discount_ticket_type'] ? errors['discount_ticket_type'][0] : null"
                ></FormSelect>
            </div>
        </FormField>
        <FormField
            label="Unit"
            :input-col-span="1"
            label-help-text="
                <strong>Fixed amount</strong><br />A monetary value that will be deducted.<br /><br />
                <strong>Percentage</strong><br />A percentage discount between 1 and 100.
            "
        >
            <FormSelect
                name="discount_unit"
                :selected="discountUnit"
                :show-error-message="false"
                :options="discountUnitOptions"
                @select-change="handleDiscountUnitChange"
                :error-message="errors['discount_type'] ? errors['discount_type'][0] : null"
            ></FormSelect>
        </FormField>
        <FormField
            :input-col-span="1"
            label="Amount"
            label-help-text="Monetary or percentage value depending the discount unit selected."
        >
            <FormInput
                step="any"
                type="number"
                ref="discount_amount"
                name="discount_amount"
                :value="discountAmount"
                :show-error-message="false"
                :error-message="errors['discount_amount'] ? errors['discount_amount'][0] : null"
                :show-append="selectedDiscountUnit === 'amount_off'"
                :show-prepend="selectedDiscountUnit === 'percent_off'"
            >
                <template #append>
                    <div class="input-append">
                        <span class="">£</span>
                    </div>
                </template>
                <template #prepend>
                    <div class="input-prepend">
                        <span>%</span>
                    </div>
                </template>
            </FormInput>
        </FormField>

        <button @click.prevent="resetDiscount" class="bg-gray-200 rounded text-sm px-2 py-1 cursor-pointer">Reset discount</button>

    </FormGroup>

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
        errors: {
            type: Array,
            default: []
        },
        discount: Array,
        discountTypeOptions: Array,
        discountUnitOptions: Array,
        selectedDiscountType: String,
        selectedDiscountUnit: String,
        discountTicketTypeOptions: Array,
        selectedDiscountTicketType: String,
        explainerVideoUrl: {
            type: String,
        },
        explainerVideoLabel: {
            type: String,
        },
    },

    data() {
        return {
            selectedDiscountType:
                (typeof this.old?.discount_type !== 'undefined') ? this.old?.discount_type :
                this.discount?.type || null,
            selectedDiscountUnit:
                (typeof this.old?.discount_unit !== 'undefined') ? this.old?.discount_unit :
                this.discount?.unit || null,
            selectedDiscountTicketType:
                (typeof this.old?.discount_ticket_type !== 'undefined') ? this.old?.discount_ticket_type :
                this.discount?.ticket_type || null,
        }
    },

    computed: {
        isEmptyTypeOption() {
            return (this.selectedDiscountType === '')
        },

        isPerTicketOption() {
            return (this.selectedDiscountType === 'per_ticket')
        },

        isBatchTicketsOption() {
            return (this.selectedDiscountType === 'batch_ticket')
        },

        isCheckoutTotalOption() {
            return (this.selectedDiscountType === 'checkout_total')
        },

        isAmountUnit() {
            return this.discountUnit && (this.discountUnit === 'amount_off')
        },

        discountType() {
            if (typeof this.old?.discount_type !== 'undefined') return this.old?.discount_type
            if (this.discount?.type) return this.discount?.type
        },

        discountTicketType() {
            if (this.isCheckoutTotalOption) return null
            if (this.isPerTicketOption) return 'equal'
            if (typeof this.old?.discount_ticket_type !== 'undefined') return this.old?.discount_ticket_type
            if (this.discount?.ticket_type) return this.discount?.ticket_type
        },

        discountUnit() {
            if (typeof this.old?.discount_unit !== 'undefined') return this.old?.discount_unit
            if (this.discount?.unit) return this.discount?.unit
        },

        discountAmount() {
            if (typeof this.old?.discount_amount !== 'undefined') {
                if (this.isAmountUnit) return this.moneyFormat(this.old?.discount_amount)
                return this.old?.discount_amount
            }

            if (this.discount?.amount) {
                if (this.isAmountUnit) return this.moneyFormat(this.discount?.amount)
                return this.discount?.amount
            }
        },

        discountTickets() {
            if (this.isCheckoutTotalOption) return null
            if (this.isPerTicketOption) return 1
            if (typeof this.old?.discount_tickets !== 'undefined') return this.old?.discount_tickets
            if (this.discount?.tickets) return this.discount?.tickets
        },
    },

    methods: {
        handleDiscountTypeChange(value) {
            this.selectedDiscountType = value
        },

        handleDiscountUnitChange(value) {
            this.selectedDiscountUnit = value
        },

        handleDiscountTicketTypeChange(value) {
            this.selectedDiscountTicketType = value
        },

        moneyFormat(value) {
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'GBP',
            });

            return formatter.format(value / 100).substring(1);
        },

        resetDiscount() {
            document.querySelector("[name='discount_type']").value = ''
            document.querySelector("[name='discount_unit']").value = ''
            document.querySelector("[name='discount_amount']").value = ''
            document.querySelector("[name='discount_tickets']").value = ''
            document.querySelector("[name='discount_ticket_type']").value = ''
        }
    }

}
</script>
