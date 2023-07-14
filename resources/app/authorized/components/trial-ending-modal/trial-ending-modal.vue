<template>
    <div class="trialEndingComponent">
        <b-modal
            v-model="isModalShown"
            hide-header
            lazy
            centered
            no-close-on-backdrop
            no-close-on-esc
        >
            <b-container>
                <app-message class="w-100" type="error" :messages="apiErrors" @close="apiErrors = []"></app-message>
                <h3 class="text-center">
                    <span v-if="user.current_account.status === 'free_trial'">Subscribe to Otto<span v-if="daysRemainingOnAccount <=0"> TODAY</span>!</span>
                    <span v-else>Last chance to subscribe to Otto!</span>
                </h3>
                <div class="text-dark">
                    <div v-if="user.current_account.status === 'free_trial'">
                        <p>We hope you are enjoying your free trial of Otto.</p>
                        <p v-if="daysRemainingOnAccount > 0">Your free trial is set to expire in {{ daysRemainingOnAccount }} days.</p>
                        <p v-else>Your free trial expires TODAY.</p>
                        <p>To ensure your uninterrupted access to Otto and keep your bank balances and credit card charges automatically updated, click the button below and choose a subscription plan.</p>
                    </div>
                    <div v-else>
                        <p>Your free trial has expired.</p>
                        <p>To continue using Otto and keep your bank balances and credit card charges automatically updated, click the button below and choose a subscription plan.</p>
                    </div>
                </div>
            </b-container>
            <div slot='modal-footer' class="mx-auto">
                <span v-if="user">
                    <b-button variant="outline-secondary" @click="closeModal()" class="mx-2" v-if="user.current_account.status === 'free_trial'">
                        Not now
                    </b-button>
                    <b-button variant="outline-secondary" @click="downgradeToBasic()" class="mx-2" v-else :disabled="isDowngradingAccount">
                        <loading-spinner :showSpinner="isDowngradingAccount" custom-class="size-auto">
                            Use basic plan
                        </loading-spinner>
                    </b-button>
                    <b-button variant="primary" @click="openPaymentComponent" v-if="user.current_account.subscription_provider !== 'itunes'">
                        Subscribe
                    </b-button>
                    <a href="https://buy.itunes.apple.com/WebObjects/MZFinance.woa/wa/manageSubscriptions" class="btn btn-primary mx-2" @click.native="closeModal()" v-else>
                        Subscribe
                    </a>
                </span>
            </div>
        </b-modal>
        <purchase-component ref="paymentComponent" @paymentSubmittedSuccess="closeModal"/>
    </div>
</template>
<script src="./trial-ending-modal.controller.js"></script>
