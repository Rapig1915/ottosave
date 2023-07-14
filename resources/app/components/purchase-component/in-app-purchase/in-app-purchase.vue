<template>
    <div class="inAppPurchaseComponent">
        <b-modal ref="inAppPurchaseModal"
            v-model="isModalShown"
            hide-header
            centered
            no-close-on-backdrop
        >
            <b-container fluid>
                <app-header />
                <loading-spinner :showSpinner="showSpinner">
                    <h3 class="text-center">Choose a subscription plan:</h3>
                    <b-form-group>
                        <b-form-radio-group name="Subscription Type" v-model="selectedProductId">
                            <b-form-radio v-for="product in products" :key="product.productId" :value="product.productId">
                                {{ product.title }} - {{ product.price | currency }}
                            </b-form-radio>
                        </b-form-radio-group>
                    </b-form-group>
                </loading-spinner>
                <app-message class="w-100" type="error" :messages="apiErrors" @close="apiErrors = []"></app-message>
                <b-row class="justify-content-end">
                    <b-button @click="isModalShown = false" :disabled="showSpinner" variant="secondary" class="mx-3">
                        Cancel
                    </b-button>
                    <b-button :disabled="showSpinner || !selectedProductId" @click="subscribeToProduct" variant="primary">
                        <span v-if="selectedProduct.dymProduct.free_trial_period">Start Free Trial</span>
                        <span v-else>Purchase</span>
                    </b-button>
                </b-row>
            </b-container>
            <template slot="modal-footer">
                <b-container fluid>
                    <b-row>
                        <p class="text-center smallest-text">
                            <span v-if="selectedProduct.dymProduct && selectedProduct.dymProduct.free_trial_period">
                                After the {{ selectedProduct.dymProduct.free_trial_period }} free-trial, this subscription automatically renews for {{ selectedProduct.priceAsDecimal | currency }}<span v-if="selectedProduct.dymProduct.billing_interval"> every {{ selectedProduct.dymProduct.billing_interval }}</span> unless it is cancelled at least 24 hours before the end of the trial period.
                                Payment will be charged to your Apple ID account and will be charged for renewal within 24 hours before the end of the trial period.
                            </span>
                            <span v-else>
                                Payment will be charged to your Apple ID account at the confirmation of purchase.
                                Subscription automatically renews unless it is cancelled at least 24 hours prior to the end of the current period.
                                Your account will be charged {{ selectedProduct.priceAsDecimal | currency }} for renewal within 24 hours before the end of the current period.
                            </span>
                            Subscriptions can be managed and auto-renewal turned off by going to your Account Settings after purchase.
                        </p>
                        <p class="text-center smallest-text">
                            For additional information, view our <a href="https://www.defendyourmoney.com/privacy-policy">Privacy Policy</a> and <a href="https://www.defendyourmoney.com/terms-of-service">Terms of Service</a>.
                        </p>
                    </b-row>
                </b-container>
            </template>
        </b-modal>
    </div>
</template>

<script src="./in-app-purchase.controller.js"></script>

<style lang="scss" src="./_in-app-purchase.scss" scoped></style>
