require('./bootstrap');

import { createApp } from 'vue'
import VueMask from 'v-mask'
import VueClickAway from "vue3-click-away";

const app = createApp({
    data() {
        return {
            showUserMenu: false,
            showMobileMenu: false,
            showProfileMenu: false,
            showRafflesMenu: false,
            showAccountMenu: false,
            showProductMenu: false,
            showPlatformMenu: false,
            showMyRafflesMenu: false,
            showMyTicketsMenu: false,
            showEntering: true,
            showHosting: false,
            showFaqEntering: true,
            showFaqHosting: false,
            showEditProfileModal: false,
            selectedPrizeTab: null,
            showPaymentWithAccount: false,
            showPaymentAsGuest: true,
            showWinningTab: true,
            showLosingTab: false,
            validPhoneNumber: false,
            showNotificationMessage: false,
            competitionType: null,
            submitingEntry: false,
            paying: false,
            payingPayPal: false,
            accessLinkShippingPrice: '-',
            accessLinkTotalPrice: '-',
            showEnterRaffleModal: false,
            showPublishModal: false,
            publishModalAction: null,
            terms: null,
            showComplaintModal: false,
            complaintCategory: '',
            complaintMessage: ''
        }
    },

    mounted() {
        // Check if there's a complaint success message and reopen the modal
        const complaintSuccess = document.querySelector('#complaint-success-indicator');
        if (complaintSuccess) {
            this.showComplaintModal = true;
        }
    },

    methods: {
        openPublishModal(action) {
            this.showPublishModal = true
            this.publishModalAction = action
        },

        openRaffleModal() {
            this.showEnterRaffleModal = true
        },

        calculateShippingAndTotal(e) {
            this.accessLinkShippingPrice = '£' + (JSON.parse(window.shippingCosts)[e.target.value] / 100).toFixed(2)
            this.accessLinkTotalPrice =  '£' + ((parseInt(JSON.parse(window.shippingCosts)[e.target.value]) + parseInt(window.purchasePrice)) / 100).toFixed(2)
        },

        setType(type) {
            this.competitionType = type
        },

        triggerShowRafflesMenu(e) {
            this.showRafflesMenu = !this.showRafflesMenu
        },

        toggleRafflesMenuMethod() {
            this.closeDropdowns('raffles')
            this.showRafflesMenu = !this.showRafflesMenu
        },

        showRafflesMenuMethod() {
            this.showRafflesMenu = true
        },

        hideRafflesMenuMethod() {
            this.showRafflesMenu = false
        },

        togglePlatformMenuMethod() {
            this.closeDropdowns('platform')
            this.showPlatformMenu = !this.showPlatformMenu
        },

        showPlatformMenuMethod() {
            this.showPlatformMenu = true
        },

        hidePlatformMenuMethod() {
            this.showPlatformMenu = false
        },

        toggleAccountMenuMethod() {
            this.closeDropdowns('account')
            this.showAccountMenu = !this.showAccountMenu
        },

        showAccountMenuMethod() {
            this.showAccountMenu = true
        },

        hideAccountMenuMethod() {
            this.showAccountMenu = false
        },

        toggleMyRafflesMenuMethod()  {
            this.closeDropdowns('my-raffles')
            this.showMyRafflesMenu = !this.showMyRafflesMenu
        },

        showMyRafflesMenuMethod() {
            this.showMyRafflesMenu = true
        },

        hideMyRafflesMenuMethod() {
            this.showMyRafflesMenu = false
        },

        toggleMyTicketsMenuMethod() {
            this.closeDropdowns('my-tickets')
            this.showMyTicketsMenu = !this.showMyTicketsMenu
        },

        showMyTicketsMenuMethod() {
            this.showMyTicketsMenu = true
        },

        hideMyTicketsMenuMethod() {
            this.showMyTicketsMenu = false
        },

        showProductMenuMethod() {
            this.showProductMenu = true
        },

        hideProductMenuMethod() {
            this.showProductMenu = false
        },

        triggerShowProductMenu(e) {
            this.closeDropdowns('product')
            this.showProductMenu = !this.showProductMenu
        },

        clickAway(target) {
            if ((target === 'rafflesMenu') && (this.showRafflesMenu === true)) {
                this.showRafflesMenu = false
            }
        },

        showHomepageTab(tab) {
            if (tab === 'entering') {
                this.showEntering = true
                this.showHosting = false
            }

            if (tab === 'hosting') {
                this.showHosting = true
                this.showEntering = false
            }
        },

        showFaqTab(tab) {
            if (tab === 'entering') {
                this.showFaqEntering = true
                this.showFaqHosting = false
            }

            if (tab === 'hosting') {
                this.showFaqHosting = true
                this.showFaqEntering = false
            }
        },


        showGuestTab(tab) {
            if (tab === 'winning') {
                this.showWinningTab = true
                this.showLosingTab= false
            }

            if (tab === 'losing') {
                this.showLosingTab = true
                this.showWinningTab = false
            }
        },

        showPaymentTab(tab) {
            if (tab === 'account') {
                this.showPaymentWithAccount = true
                this.showPaymentAsGuest = false
            }

            if (tab === 'guest') {
                this.showPaymentAsGuest = true
                this.showPaymentWithAccount = false
            }
        },

        setSelectedPrizeTab(prizeId) {
            if (this.selectedPrizeTab === prizeId) {
                this.selectedPrizeTab = null
            } else {
                this.selectedPrizeTab = prizeId
            }
        },

        closeDropdowns(except) {
            if (except !== 'product') this.showProductMenu = false
            if (except !== 'platform') this.showPlatformMenu = false
            if (except !== 'raffles') this.showRafflesMenu = false
            if (except !== 'account') this.showAccountMenu = false
            if (except !== 'my-raffles') this.showMyRafflesMenu = false
            if (except !== 'my-tickets') this.showMyTicketsMenu = false
        },
    }
})

// app.use(VueMask)
app.use(VueClickAway)

app.component('Card', require('./components/Card.vue').default)
app.component('Faqs', require('./components/Faqs.vue').default);
app.component('Tabs', require('./components/Tabs.vue').default);
app.component('Alert', require('./components/Alert.vue').default);
app.component('Navbar', require('./components/Navbar.vue').default);
app.component('TextLink', require('./components/TextLink.vue').default);
app.component('Countdown', require('./components/Countdown.vue').default);
app.component('Calculator', require('./components/Calculator.vue').default);
app.component('PageHeader', require('./components/PageHeader.vue').default);
app.component('ImageGallery', require('./components/ImageGallery.vue').default);
app.component('OddsWidget', require('./components/OddsWidget.vue').default);

app.component('Icon', require('./components/icons/Icon.vue').default);
app.component('UserAccountNavIcon', require('./components/icons/UserAccountNavIcon.vue').default);

app.component('Type', require('./components/form/competitions/Type.vue').default);
app.component('Prizes', require('./components/form/competitions/Prizes.vue').default);
app.component('Charities', require('./components/form/competitions/Charities.vue').default);
app.component('Affiliates', require('./components/form/competitions/Affiliates.vue').default);
app.component('Discount', require('./components/form/competitions/Discount.vue').default);
app.component('AssociatedCompetitions', require('./components/form/competitions/AssociatedCompetitions.vue').default);

app.component('FormFilter', require('./components/filtering/Filter.vue').default);

app.component('FormGroup', require('./components/form/FormGroup.vue').default);
app.component('FormField', require('./components/form/FormField.vue').default);
app.component('FormInput', require('./components/form/FormInput.vue').default);
app.component('FormLayout', require('./components/form/FormLayout.vue').default);
app.component('FormSelect', require('./components/form/FormSelect.vue').default);
app.component('FormTextarea', require('./components/form/FormTextarea.vue').default);
app.component('FormCheckbox', require('./components/form/FormCheckbox.vue').default);
app.component('FormImageInput', require('./components/form/FormImageInput.vue').default);
app.component('FormColourInput', require('./components/form/FormColourInput.vue').default);
app.component('FormToggleField', require('./components/form/FormToggleField.vue').default);
app.component('FormRepeaterInput', require('./components/form/FormRepeaterInput.vue').default);
app.component('FormRichTextField', require('./components/form/FormRichTextField.vue').default);
app.component('FormDateTimeInput', require('./components/form/FormDateTimeInput.vue').default);
app.component('FormTelephoneInput', require('./components/form/FormTelephoneInput.vue').default);
app.component('FormSearchableSelect', require('./components/form/FormSearchableSelect.vue').default);

app.component('VoucherInput', require('./components/VoucherInput.vue').default);
app.component('EntryFormStepper', require('./components/EntryFormStepper.vue').default);

window.onload = function() {
    app.mount('#app')
}

