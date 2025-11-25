<template>
    <div class="mb-5">
        <label class="text-base font-medium detail-page-question-label sm:mt-px sm:pt-2 mb-2">
            Voucher code
        </label>
        <div class="flex gap-2">
            <input
                v-model="voucherCode"
                type="text"
                placeholder="Enter voucher code"
                class="block detail-page-question-select-background detail-page-question-select detail-page-question-select-border flex-1 rounded-md focus:ring-0"
                :disabled="voucherApplied"
            />
            <button
                v-if="!voucherApplied"
                type="button"
                @click="validateVoucher"
                :disabled="isValidating || !voucherCode || voucherCode.trim().length === 0"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm font-medium details-page-submit details-page-submit-background disabled:opacity-50"
            >
                {{ isValidating ? 'Validating...' : 'Apply' }}
            </button>
            <button
                v-if="voucherApplied"
                type="button"
                @click="removeVoucher"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
            >
                Remove
            </button>
        </div>
        <input type="hidden" name="voucher_code" :value="voucherApplied ? voucherCode : ''">
        <p v-if="voucherMessage" :class="voucherApplied ? 'text-green-600' : 'text-red-600'" class="text-sm mt-2">
            {{ voucherMessage }}
        </p>
        <p v-if="voucherApplied && discount" class="text-sm mt-1 text-green-600">
            You will save {{ discount.discount_amount_formatted }}
        </p>
    </div>
</template>

<script>
export default {
    name: 'VoucherInput',
    props: {
        competitionId: {
            type: Number,
            required: true
        },
        userEmail: {
            type: String,
            default: ''
        },
        initialCode: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            voucherCode: this.initialCode,
            voucherApplied: false,
            voucherMessage: '',
            isValidating: false,
            discount: null
        }
    },
    methods: {
        async validateVoucher() {
            this.isValidating = true;
            this.voucherMessage = '';

            const ticketCount = document.querySelector('select[name=tickets]').value;
            const email = document.querySelector('input[name=email]')?.value || this.userEmail;

            if (!email) {
                this.voucherMessage = 'Email is required to validate voucher';
                this.isValidating = false;
                return;
            }

            // Get CSRF token from meta tag or Laravel's global token
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content
                || document.querySelector('meta[name="csrf-token"]')?.content
                || '';

            if (!csrfToken) {
                console.error('CSRF token not found');
                this.voucherMessage = 'Security token missing. Please refresh the page.';
                this.isValidating = false;
                return;
            }

            try {
                const response = await fetch('/api/vouchers/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        code: this.voucherCode,
                        competition_id: this.competitionId,
                        email: email,
                        ticket_count: parseInt(ticketCount)
                    })
                });

                const data = await response.json();

                console.log('Response:', response.status, data);

                if (data.success) {
                    this.voucherApplied = true;
                    this.voucherMessage = data.message;
                    this.discount = data.voucher;
                } else {
                    this.voucherMessage = data.message || 'Invalid voucher code';
                }
            } catch (error) {
                console.error('Voucher validation error:', error);
                this.voucherMessage = 'Error validating voucher. Please try again.';
            } finally {
                this.isValidating = false;
            }
        },
        removeVoucher() {
            this.voucherCode = '';
            this.voucherApplied = false;
            this.voucherMessage = '';
            this.discount = null;
        }
    }
}
</script>

