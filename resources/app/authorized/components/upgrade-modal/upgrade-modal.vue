<template>
    <div class="upgradeComponent">
        <b-modal
            v-model="isUpgradeModalShown"
            hide-header
            hide-footer
            lazy
            centered
            no-close-on-esc
            @show="setMode"
        >
            <div class="px-4 pt-4">
                <app-message class="w-100" type="error" :messages="apiErrors" @close="apiErrors = []"></app-message>
                <h3>Start your 30-day free trial of Otto today!</h3>
                <p>Otto links to your financial institutions and automatically updates your bank balances and credit card charges for you.</p>
                <p>No payment information required.</p>
                <b-row class="justify-content-center">
                    <b-button @click="startTrial()" :disabled="isStartingTrial">
                        <loading-spinner :show-spinner="isStartingTrial" custom-class="size-auto" :class="{ 'px-5': isStartingTrial }">
                            <span>Start Free Trial <i class="fas fa-arrow-right"></i></span>
                        </loading-spinner>
                    </b-button>
                </b-row>
            </div>
        </b-modal>
        <purchase-component ref="paymentComponent" @paymentSubmittedSuccess="navigateToTour"/>
    </div>
</template>
<script src="./upgrade-modal.controller.js"></script>
