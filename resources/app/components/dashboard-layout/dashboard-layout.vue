<template>
    <b-row class="dashboardLayoutComponent">
        <b-col cols="12" lg="3" class="bg-white px-0 sticky">
            <b-navbar toggleable="lg" class="p-0" :sticky="true">
                <b-row class="w-100 m-0 no-gutters">
                    <app-header logo-left-on-small-screen logo-size="sm" :link-location="{ name: 'dashboard'}"/>

                    <b-col cols="5" class="d-flex justify-content-end align-items-center">
                        <refresh-accounts-button
                            class="d-lg-none refresh-button"
                            :class="{'refresh-button--ios': useIosMenu}"
                            mobile
                            v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }"
                        />
                        <span class="position-relative mr-3 d-lg-none" v-if="!useIosMenu">
                            <b-navbar-toggle target="nav_collapse" @click="toggleSidebar"></b-navbar-toggle>
                            <b-badge v-if="totalNotificationCount" variant="danger" pill class="menuLinkBadge menuLinkBadge--mobile" :class="[`menuLinkBadge--length-${(totalNotificationCount.toString().length)}`]">
                                <div>
                                    {{ totalNotificationCount }}
                                </div>
                            </b-badge>
                        </span>
                    </b-col>

                    <b-col cols="12">
                        <div class="py-lg-4 sidebar-menu" :class="{'sidebar-menu--active': showMenu}" ref="sidebarMenu" tabindex="0" @blur="toggleSidebar">
                            <b-navbar-nav class="flex-column w-100 px-3">
                                <div class="pl-4 py-3" v-if="accountSwitchable">
                                    <v-select
                                        :options="accessibleAccounts"
                                        :clearable="false"
                                        :searchable="false"
                                        v-model="currentActiveAccount"
                                        class="menu__account-select account-select"
                                        @option:selected="onSwitchAccount"
                                    >
                                        <template v-slot:selected-option>
                                            <div class="account-select__select-option select-option">
                                                <div class="select-option__account-name" v-if="currentActiveAccount">
                                                    <i class="fas fa-user mr-2 text-black" />
                                                    <span>{{ currentActiveAccount.user.name }}</span>
                                                </div>
                                            </div>
                                        </template>

                                        <template v-slot:option="account">
                                            <div class="account-select__select-option select-option dropdown-option">
                                                <div class="select-option__account-name">
                                                    {{ account.user.name }}
                                                </div>
                                            </div>
                                        </template>
                                    </v-select>
                                </div>

                                <router-link
                                    class="pl-4 py-3 nav-item"
                                    tag="li"
                                    v-for="(routerLink, index) in sidebarLinks"
                                    :key="index"
                                    :to="{ name: routerLink.routeName }"
                                    @click.native="hideMenuAndAdminNav"
                                    :exact="routerLink.exact"
                                >
                                    <i :class="`${routerLink.icon}`"></i>
                                    <span class="ml-3 position-relative">
                                        {{ routerLink.description }}
                                        <b-badge v-if="routerLink.notificationCount" variant="danger" pill class="menuLinkBadge" :class="[`menuLinkBadge--length-${routerLink.notificationLength}`]">
                                            <div>
                                                {{ routerLink.notificationCount }}
                                            </div>
                                        </b-badge>
                                    </span>
                                </router-link>
                                <div class="pl-4 py-3 nav-item" v-dym-access="{ permission: 'permission', behavior: 'hide', valueToTest: 'access super-admin' }">
                                    <div title="Admin Section" @click="isAdminNavOpen = !isAdminNavOpen">
                                        <i class="fas fa-lock"></i>
                                        <span class="ml-3">Admin</span>
                                    </div>
                                    <b-collapse id="admin-nav-items" class="mt-2" v-model="isAdminNavOpen">
                                        <router-link
                                            class="pl-4 py-3 nav-item"
                                            tag="li"
                                            v-for="(adminLink, index) in adminLinks"
                                            :key="index"
                                            :to="{ name: adminLink.routeName }"
                                            @click.native="toggleSidebar"
                                        >
                                            <i :class="`${adminLink.icon}`"></i>
                                            <span class="ml-3">{{ adminLink.description }}</span>
                                        </router-link>
                                    </b-collapse>
                                </div>
                                <a class="pl-4 py-3 nav-item" title="Get Help" href="https://www.ottosave.com/help" target="_blank">
                                    <i class="icon-dym-help"></i>
                                    <span class="ml-3">Help</span>
                                </a>

                                <b-button variant="link" @click.prevent="logout" class="pl-4 py-3 nav-item text-left">
                                    <i class="icon-dym-sign-out"></i>
                                    <span class="ml-3">Sign Out</span>
                                </b-button>
                            </b-navbar-nav>
                        </div>
                    </b-col>
                </b-row>
            </b-navbar>
        </b-col>

        <div class="col dashboard-header-body-footer">
            <div class="row flex-column align-items-start">
                <div class="d-none container d-print-flex justify-content-end align-items-center">
                    <span class="mb-2 header-date">{{ new Date() | date }}</span>
                </div>
                <div class="d-none d-lg-block dashboard-header-content col-12 d-print-none">
                    <div class="container d-flex justify-content-end align-items-center">
                        <span class="mr-3 header-date">{{ new Date() | date }}</span>
                        <refresh-accounts-button class="d-inline-block" v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }"/>
                    </div>
                </div>
                <div class="col-12 dashboard-body p-0 pl-sm-3 pr-sm-3">
                    <transition name="transition-mobile-menu-overlay">
                        <div class="mobile-menu-overlay"
                            v-if="showMenu"
                            @click="toggleSidebar"
                        >
                        </div>
                    </transition>
                    <div class="container">
                        <div v-if="isFinicityRecoveryWarningDisplayed">
                            <b-alert v-model="isFinicityRecoveryWarningDisplayed" variant="danger" dismissible fade>
                                We are having trouble communicating with your financial institution. Click
                                <router-link
                                    :to="{ name: 'accounts', query: { settings: 'open', id: $store.getters['authorized/bankAccounts/bankAccountsWithRecoverableErrors'][0].id }}"
                                >
                                    here
                                </router-link>
                                to reestablish your connection.
                            </b-alert>
                        </div>
                        <div v-if="isFinicityErrorCodeMessageDisplayed">
                            <b-alert v-model="isFinicityErrorCodeMessageDisplayed" variant="danger" dismissible fade>
                                There appears to be a problem connecting to {{ finicityErroredInstitutionList }}.
                                <br>
                                We have been notified of the issue and we are working hard to fix it. Most connection issues will be resolved within 24 hours.
                                If the problem persists, please <a :href="`mailto:support@ottosave.com?subject=Institution Error Code ${finicityErrorCodes.join(', ')}`">contact our support team</a> referencing Error Code<span v-if="finicityErrorCodes.length > 1">s</span>:
                                <span v-for="(code, index) in finicityErrorCodes" :key="index">{{ code }}<span v-if="index < finicityErrorCodes.length - 1">, </span></span>.
                            </b-alert>
                        </div>

                        <transition name="fadeHeight">
                            <div class="alert alert-info" v-if="finicityRefreshStatus === 'pending'">
                                We are bringing in the most up-to-date information from your financial institutions. This may take a minute.
                            </div>
                        </transition>
                        <oauth-migration-component></oauth-migration-component>

                    </div>
                    <slot></slot>
                </div>
                <div class="ios-footer" v-if="useIosMenu">
                    <div class="ios-footer__menu" :class="{'ios-footer__menu--keyboard-active': isKeyboardActive}">
                        <router-link
                            class="my-3 ios-footer__link"
                            tag="div"
                            v-for="(routerLink, index) in iosFooterLinks"
                            :key="index"
                            :to="{ name: routerLink.routeName }"
                            :exact="routerLink.exact"
                        >
                            <i :class="`${routerLink.icon}`" class="ios-footer__link-icon"></i>
                            <div class="text-center">
                                {{ routerLink.description }}
                            </div>
                            <b-badge v-if="routerLink.notificationCount" variant="danger" pill class="menuLinkBadge" :class="[`menuLinkBadge--length-${routerLink.notificationLength}`]">
                                <div>
                                    {{ routerLink.notificationCount }}
                                </div>
                            </b-badge>
                        </router-link>

                        <div class="py-3 ios-footer__link" :class="{'router-link-active': showMenu}">
                            <i class="fas fa-bars ios-footer__link-icon" @click="toggleSidebar"></i>
                            <div class="text-center">
                                More
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 dashboard-footer-content mt-auto d-print-none bg-gray-e text-gray-3" v-if="!useIosMenu">
                    <span class="brand-copy">&copy; {{ Date.now() | moment("YYYY") }} Defend Your Money, LLC. All rights reserved.</span>
                </div>
            </div>
        </div>
    </b-row>
</template>
<style lang="scss" src="./_dashboard-layout.scss" scoped></style>
<script src="./dashboard-layout.controller.js"></script>
