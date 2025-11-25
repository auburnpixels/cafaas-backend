<template>
    <div>
        <!-- Guest/Account Tabs (only for non-authenticated users on step 0) -->
        <div v-if="showTabs && currentStep === 0" class="flex justify-center mb-5">
            <div class="w-full margin-auto">
                <div class="rounded-[12px] p-[4px] grid grid-cols-2 border payment-method">
                    <button @click="showGuestForm = true" class="p-[6px] rounded-[8px] border-[transparent] border-[1px] border-solid inline-block focus:outline-none bg-[transparent] transform relative text-[black] font-eina-semibold text-[16px] tracking-[0px] payment-method-button" type="button" style="">
                        <span class="relative z-[10] text-sm">Checkout as guest</span>
                        <div v-if="showGuestForm" class="border absolute top-[0px] right-[0px] bottom-[0px] left-[0px] bg-[white] rounded-[8px] payment-method-button-active"></div>
                    </button>
                    <a :href="loginUrl" class="p-[6px] flex items-center justify-center border-[transparent] border-[1px] border-solid inline-block focus:outline-none bg-[transparent] transform relative text-[black] font-eina-semibold text-[16px] tracking-[0px] payment-method-button text-center" style="">
                        <span class="relative z-[10] text-sm">Pay with account</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Only show form if not tabs, or if tabs and guest form is selected -->
        <div v-if="!showTabs || showGuestForm">
            <!-- Step Indicator -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="flex items-center"
                        :class="{ 'flex-1': index < steps.length - 1 }"
                    >
                        <div class="flex flex-col items-center">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-colors"
                                :class="currentStep > index ? 'bg-green-500 text-white' : currentStep === index ? 'bg-raffaly-primary text-white bg-entry-step' : 'bg-gray-200 text-gray-600 bg-entry-inactive-step'"
                            >
                                <span v-if="currentStep > index">✓</span>
                                <span v-else>{{ index + 1 }}</span>
                            </div>
                            <span
                                class="text-xs mt-1 text-center bg-entry-step-text"
                                :class="currentStep >= index ? 'text-gray-900 font-medium' : 'text-gray-500'"
                            >
                            {{ step }}
                        </span>
                        </div>
                        <div
                            v-if="index < steps.length - 1"
                            class="flex-1 h-1 mx-2"
                            :class="currentStep > index ? 'bg-green-500' : 'bg-gray-200 bg-panel-line'"
                        ></div>
                    </div>
                </div>
            </div>

            <form
                :action="actionUrl"
                method="post"
                class="relative detail-page-card-border payment-modal"
                ref="entryForm"
                @submit.prevent="handleSubmit"
            >
                <input type="hidden" name="_token" :value="csrfToken">
                <input v-if="ticketAssignmentUuid" type="hidden" name="ticket_assignment_uuid" :value="ticketAssignmentUuid">

                <!-- Step 1: Name and Email (Only for guests) -->
                <div v-show="adjustedStep === 0" class="pb-5">
                    <div v-if="!isAuthenticated" class="mb-2 grid grid-cols-1 gap-2">
                        <div>
                            <div class="flex items-center">
                                <label for="name" class="label authentication-page-label">Name</label>
                                <span class="ml-1 text-red-700">*</span>
                            </div>
                            <div class="mt-1">
                                <input
                                    id="name"
                                    ref="nameInput"
                                    v-model="formData.name"
                                    name="name"
                                    type="text"
                                    :required="!isAuthenticated"
                                    autofocus
                                    class="input block detail-page-question-select-background detail-page-question-select detail-page-question-select-border w-full rounded-md focus:ring-0"
                                >
                                <span v-if="errors.name" class="mt-2 block text-red-500 text-sm">{{ errors.name }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center">
                                <label for="surname" class="label authentication-page-label">Surname</label>
                                <span class="ml-1 text-red-700">*</span>
                            </div>
                            <div class="mt-1">
                                <input
                                    id="surname"
                                    v-model="formData.surname"
                                    name="surname"
                                    type="text"
                                    :required="!isAuthenticated"
                                    class="input block detail-page-question-select-background detail-page-question-select detail-page-question-select-border w-full rounded-md focus:ring-0"
                                >
                                <span v-if="errors.surname" class="mt-2 block text-red-500 text-sm">{{ errors.surname }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!isAuthenticated" class="mb-5">
                        <div class="flex flex-col">
                            <div class="flex items-center">
                                <label for="email" class="label authentication-page-label">Email address</label>
                                <span class="ml-1 text-red-700">*</span>
                            </div>
                        </div>
                        <div class="mt-1">
                            <input
                                id="email"
                                v-model="formData.email"
                                name="email"
                                type="email"
                                :required="!isAuthenticated"
                                autocomplete="email"
                                class="input block detail-page-question-select-background detail-page-question-select detail-page-question-select-border w-full rounded-md focus:ring-0"
                            >
                            <span v-if="errors.email" class="mt-2 block text-red-500 text-sm">{{ errors.email }}</span>
                            <span class="bg-entry-step-text text-xs mt-1 block">We'll only use this to send your ticket confirmation.</span>
                        </div>
                    </div>

                    <!-- Consent Checkbox - Only on Step 1 -->
                    <div class="pb-5 mb-5 border-b payment-method border-t pt-5">
                        <div class="gap-2 flex items-center">
                            <input
                                id="consent"
                                v-model="formData.consent"
                                type="checkbox"
                                name="consent"
                                class="h-4 w-4 border-gray-300 rounded focus:ring-0 focus:border-gray-300"
                            />
                            <label for="consent" class="text-sm">
                                Yes, I consent to sharing my name and email with the host for marketing purposes.
                            </label>
                        </div>
                    </div>

                    <div v-if="isAuthenticated" class="text-center py-8">
                        <p class="text-gray-600">You're logged in and ready to enter!</p>
                    </div>
                </div>

                <!-- Step 2: Entry Question -->
                <div v-show="adjustedStep === 1" class="pb-5">
                    <div class="sm:mt-px sm:pt-2 mb-2">
                        <div class="mb-5">
                            <p class="text-xs text-opacity-50 detail-page-question-label text">
                                This question is required for fair entry in compliance with UK gambling regulations.
                                <button
                                    type="button"
                                    @click="showMoreInfo = !showMoreInfo"
                                    class="text-raffaly-primary underline ml-1 hover:no-underline"
                                >
                                    {{ showMoreInfo ? 'Less info' : 'More info' }}
                                </button>
                            </p>
                            <p v-if="showMoreInfo" class="text-xs text-opacity-50 detail-page-question-label form-help mt-2">
                                Tickets are supplied regardless of the entry question answer picked by the entrant,
                                and participation in the raffle is not contingent upon providing the correct answer.
                                However, only tickets with the correct answer to the entry question will be admitted into
                                the final draw in accordance with the guidelines established by the Gambling Commission in respect to the Gambling Act 2005.
                            </p>
                        </div>
                        <label class="block text-base font-medium detail-page-question-label">{{ questionText }}</label>
                    </div>

                    <div class="flex flex-col space-y-2">
                        <label
                            v-for="answer in answers"
                            :key="answer.value"
                            class="input block detail-page-question-select-background py-2 px-4 cursor-pointer border detail-page-question-select detail-page-question-select-border w-full rounded-md focus:ring-0"
                        >
                            <input
                                type="radio"
                                v-model="formData.answer"
                                name="answer"
                                :value="answer.value"
                                class="mr-2"
                            >
                            {{ answer.name }}
                        </label>
                    </div>

                    <span v-if="errors.answer" class="text-red-600 mt-2 block">{{ errors.answer }}</span>
                </div>

                <!-- Step 3: Tickets and Voucher -->
                <div v-show="adjustedStep === 2" class="flex flex-col gap-4">
                    <div>
                        <odds-widget :raffle-id="competitionId" :user-tickets="parseInt(formData.tickets) || 0"></odds-widget>
                    </div>

                    <div>
                        <label class="text-base font-medium detail-page-question-label sm:mt-px sm:pt-2 mb-2 flex flex-col sm:flex-row justify-between">
                            How many raffle tickets would you like to purchase?
                            <span class="text text-sm">Maximum of {{ maxTicketsText }} allowed</span>
                        </label>
                        <select
                            v-model="formData.tickets"
                            name="tickets"
                            class="block detail-page-question-select-background detail-page-question-select detail-page-question-select-border w-full rounded-md focus:ring-0"
                        >
                            <option v-for="i in maxTickets" :key="i" :value="i">{{ i }}</option>
                        </select>
                        <p class="text-sm bg-entry-step-text mt-2">
                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Tickets will be emailed instantly after checkout.
                        </p>
                        <p v-if="formData.tickets >= maxTickets" class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded px-3 py-2 mt-2">
                            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            You've reached the maximum tickets per user for this competition. Learn more about 
                            <a href="/responsible-play" target="_blank" class="underline font-medium">Responsible Play</a>.
                        </p>
                    </div>

                    <div v-if="md5VouchersEnabled">
                        <button
                            type="button"
                            @click="showVoucher = !showVoucher"
                            class="text-sm text-raffaly-primary underline hover:no-underline"
                        >
                            {{ showVoucher ? 'Hide voucher' : 'Have a voucher?' }}
                        </button>

                        <div v-if="showVoucher" class="mt-3">
                            <voucher-input
                                :competition-id="competitionId"
                                :user-email="userEmail"
                                :initial-code="voucherCodeFromUrl"
                            ></voucher-input>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between items-center pt-4">
                    <div>
                        <button
                            v-if="currentStep > 0"
                            type="button"
                            @click="previousStep"
                            class="px-4 py-2 border border-highlight highlight rounded-md"
                        >
                            ← Previous
                        </button>
                        <a
                            v-else
                            :href="termsUrl"
                            class="underline text-link text-sm"
                            target="_blank"
                        >
                            Free entry available
                        </a>
                    </div>

                    <div v-if="currentStep < steps.length - 1">
                        <button
                            type="button"
                            @click="nextStep"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm font-medium details-page-submit details-page-submit-background"
                        >
                            Next →
                        </button>
                    </div>

                    <div v-else>
                        <button
                            v-if="!submittingEntry"
                            type="submit"
                            class="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm font-medium details-page-submit details-page-submit-background"
                        >
                            Enter
                        </button>
                        <button
                            v-else
                            disabled
                            class="opacity-50 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm font-medium details-page-submit details-page-submit-background"
                        >
                            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="mr-2 text-white h-4 w-4">
                                <path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" fill="currentColor">
                                    <animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/>
                                </path>
                            </svg>
                            Entering
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: 'EntryFormStepper',
    props: {
        actionUrl: {
            type: String,
            required: true
        },
        competitionId: {
            type: Number,
            required: true
        },
        isAuthenticated: {
            type: Boolean,
            default: false
        },
        userEmail: {
            type: String,
            default: ''
        },
        questionText: {
            type: String,
            required: true
        },
        answers: {
            type: Array,
            required: true
        },
        maxTickets: {
            type: Number,
            required: true
        },
        maxTicketsText: {
            type: String,
            required: true
        },
        md5VouchersEnabled: {
            type: Boolean,
            default: false
        },
        ticketAssignmentUuid: {
            type: String,
            default: ''
        },
        termsUrl: {
            type: String,
            required: true
        },
        oldValues: {
            type: Object,
            default: () => ({})
        },
        serverErrors: {
            type: Object,
            default: () => ({})
        },
        showTabs: {
            type: Boolean,
            default: false
        }
    },
    data() {
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const voucherCode = urlParams.get('voucher_code') || urlParams.get('voucher') || '';

        return {
            currentStep: 0,
            submittingEntry: false,
            showGuestForm: true,
            showMoreInfo: false,
            showVoucher: !!voucherCode, // Auto-expand voucher if code in URL
            loginUrl: '/login',
            voucherCodeFromUrl: voucherCode,
            formData: {
                name: this.oldValues.name || urlParams.get('name') || '',
                surname: this.oldValues.surname || urlParams.get('surname') || '',
                email: this.oldValues.email || urlParams.get('email') || '',
                answer: this.oldValues.answer || '',
                tickets: this.oldValues.tickets || 1,
                consent: false
            },
            errors: {...this.serverErrors},
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    },
    computed: {
        steps() {
            if (this.isAuthenticated) {
                return ['Entry question', 'Tickets & voucher']
            }
            return ['Your details', 'Entry question', 'Tickets & voucher']
        },
        adjustedStep() {
            // If authenticated, we skip step 0, so we need to adjust the step index for rendering
            return this.isAuthenticated ? this.currentStep + 1 : this.currentStep
        }
    },
    methods: {
        nextStep() {
            if (this.validateStep()) {
                this.currentStep++
            }
        },
        previousStep() {
            this.currentStep--
            // Clear errors when going back
            this.errors = {}
        },
        validateStep() {
            this.errors = {}

            // For guests: Step 0 = details, Step 1 = question, Step 2 = tickets
            // For authenticated: Step 0 = question, Step 1 = tickets
            const actualStep = this.adjustedStep

            if (actualStep === 0 && !this.isAuthenticated) {
                if (!this.formData.name) {
                    this.errors.name = 'Name is required'
                    return false
                }
                if (!this.formData.surname) {
                    this.errors.surname = 'Surname is required'
                    return false
                }
                if (!this.formData.email) {
                    this.errors.email = 'Email is required'
                    return false
                }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                if (!emailRegex.test(this.formData.email)) {
                    this.errors.email = 'Please enter a valid email address'
                    return false
                }
            }

            if (actualStep === 1) {
                if (!this.formData.answer) {
                    this.errors.answer = 'Please select an answer'
                    return false
                }
            }

            return true
        },
        handleSubmit() {
            if (this.validateStep()) {
                this.submittingEntry = true
                this.$refs.entryForm.submit()
            }
        }
    },
    mounted() {
        // If there are server errors, jump to the appropriate step
        if (Object.keys(this.serverErrors).length > 0) {
            if (this.serverErrors.name || this.serverErrors.surname || this.serverErrors.email) {
                // Guest details errors - only relevant for non-authenticated users
                this.currentStep = 0
            } else if (this.serverErrors.answer) {
                // Entry question errors
                this.currentStep = this.isAuthenticated ? 0 : 1
            } else {
                // Tickets/voucher errors
                this.currentStep = this.isAuthenticated ? 1 : 2
            }
        }
    }
}
</script>

<style scoped>
/* Add any custom styles here if needed */
</style>

