<template>
    <nav :class="{ 'bg-transparent z-10 absolute w-full': isHomepage, 'header-background page-header-border border-b': !isHomepage }">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-3 lg:py-8">
            <div class="flex justify-between h-16 flex justify-between items-center">
                <a href="/">
                    <img v-if="logoUrl" :src="logoUrl" class="max-h-16 max-w-[150px] md:max-w-[200px]" :alt="appName" />
                    <span v-else class="body-text">{{ appName }}</span>
                </a>
                <div class="flex">
                    <div class="hidden lg:flex md:space-x-8">
                        <a
                            :href="competitionsRoute"
                            :class="{ 'header-links-active': isActiveRoute('competitions.index') }"
                            class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium"
                        >
                            Competitions
                        </a>
                    </div>
                    <div class="hidden md:ml-6 lg:flex md:space-x-8">
                        <a
                            :href="resultsRoute"
                            :class="{ 'header-links-active': isActiveRoute('results.index') }"
                            class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium"
                        >
                            Results
                        </a>
                    </div>
                    <div class="hidden md:ml-6 lg:flex md:space-x-8 space-x-0">
                        <a
                            :href="winnersRoute"
                            :class="{ 'header-links-active': isActiveRoute('winners.index') }"
                            class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium"
                        >
                            Winners
                        </a>
                    </div>
                    <div class="hidden md:ml-6 lg:flex md:space-x-8 space-x-0">
                        <a
                            :href="howItWorksRoute"
                            :class="{ 'header-links-active': isActiveRoute('how-it-works.index') }"
                            class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium"
                        >
                            How it works
                        </a>
                    </div>
                    <div class="hidden md:ml-6 lg:flex md:space-x-8 space-x-0">
                        <a
                            :href="charitiesRoute"
                            :class="{ 'header-links-active': isActiveRoute('charities.index') }"
                            class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium"
                        >
                            Charity partners
                        </a>
                    </div>
                </div>
                <div class="hidden lg:flex md:items-center">
                    <div class="relative">
                        <div v-if="!authenticatedAsUser && !authenticatedAsAdmin">
                            <a :href="loginRoute"  class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium">Sign in</a>
                            <span class="body-text">|</span>
                            <a :href="registerRoute"  class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium">Create an account</a>
                        </div>

                        <div v-else>
                            <div v-if="authenticatedAsAdmin">
                                <a :href="dashboardRoute" class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium">Admin</a>
                            </div>
                            <div v-else>
                                <a :href="accountRoute" class="header-links border-transparent flex-1 whitespace-nowrap py-4 px-1 border-b-2 text-base font-medium">My account</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="-mr-2 flex items-center lg:hidden">
                    <button @click="toggleMenu" type="button" class="inline-flex items-center justify-center p-2 rounded-md header-links" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div :class="mobileMenuClasses">
            <div class="pt-2 pb-3 space-y-1">
                <a :href="competitionsRoute" :class="{ 'mobile-header-links-active': isActiveRoute('competitions.index'), 'border-transparent mobile-header-links': !isActiveRoute('competitions.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Competitions</a>
                <a :href="resultsRoute" :class="{ 'mobile-mobile-header-links-active': isActiveRoute('results.index'), 'border-transparent mobile-header-links': !isActiveRoute('results.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Results</a>
                <a :href="winnersRoute" :class="{ 'mobile-header-links-active': isActiveRoute('winners.index'), 'border-transparent mobile-header-links': !isActiveRoute('winners.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Winners</a>
                <a :href="howItWorksRoute" :class="{ 'mobile-header-links-active': isActiveRoute('how-it-works.index'), 'border-transparent mobile-header-links': !isActiveRoute('how-it-works.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">How it works</a>
                <a :href="charitiesRoute" :class="{ 'mobile-header-links-active': isActiveRoute('charities.index'), 'border-transparent mobile-header-links': !isActiveRoute('charities.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Charity partners</a>
                <span class="border-t page-header-border block"></span>
                <div v-if="!authenticatedAsUser && !authenticatedAsAdmin">
                    <a :href="loginRoute" class="border-transparent mobile-header-links block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Login</a>
                    <a :href="registerRoute" class="border-transparent mobile-header-links block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Create an account</a>
                </div>
                <div v-if="authenticatedAsUser">
                    <a :href="activeEntriesRoute" :class="{ 'mobile-header-links-active': isActiveRoute('account.tickets.live'), 'border-transparent mobile-header-links': !isActiveRoute('account.tickets.live') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Active entries</a>
                    <a :href="pastEntriesRoute" :class="{ 'mobile-header-links-active': isActiveRoute('account.past-entries'), 'border-transparent mobile-header-links': !isActiveRoute('account.past-entries') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Past Entries</a>
                    <a :href="detailsRoute" :class="{ 'mobile-header-links-active': isActiveRoute('account.details'), 'border-transparent mobile-header-links': !isActiveRoute('account.details') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Details</a>
                    <a :href="shippingAddressRoute" :class="{ 'mobile-header-links-active': isActiveRoute('account.shipping-address'), 'border-transparent mobile-header-links': !isActiveRoute('account.shipping-address') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Shipping address</a>
                    <span class="border-t page-header-border block"></span>
                    <form method="post" :action="logoutRoute">
                        <input type="hidden" name="_token" :value="csrf" />
                        <button type="submit" class="border-transparent mobile-header-links block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                            <span class="truncate">Logout</span>
                        </button>
                    </form>
                </div>
                <div v-if="authenticatedAsAdmin">
                    <a :href="accountRoute" :class="{ 'mobile-links-active': isActiveRoute('admin.dashboard.index'), 'border-transparent mobile-header-links': !isActiveRoute('admin.dashboard.index') }" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Admin</a>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
export default {

    props: {
        csrf: String,
        logoUrl: String,
        appName: String,
        loginRoute: String,
        isHomepage: Boolean,
        logoutRoute: String,
        resultsRoute: String,
        winnersRoute: String,
        accountRoute: String,
        detailsRoute: String,
        registerRoute: String,
        dashboardRoute: String,
        charitiesRoute: String,
        howItWorksRoute: String,
        currentRouteName: String,
        pastEntriesRoute: String,
        competitionsRoute: String,
        activeEntriesRoute: String,
        shippingAddressRoute: String,
        authenticatedAsUser: Boolean,
        authenticatedAsAdmin: Boolean,
    },

    data() {
        return {
            showMobileMenu: false,
        }
    },

    computed: {
        mobileMenuClasses: function() {
            return (!this.showMobileMenu) ? 'hidden' : 'navbar-background block lg:hidden';
        }
    },

    methods: {
        toggleMenu() {
            this.showMobileMenu = !this.showMobileMenu
        },

        isActiveRoute(route) {
            return this.currentRouteName === route
        }
    }

}
</script>
