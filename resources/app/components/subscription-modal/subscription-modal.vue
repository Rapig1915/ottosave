<template>
    <b-modal ref="subscriptionModal" hide-footer hide-header @hide="cleanupBraintree" class="subscriptionModalComponent" @shown="setModalActive">
        <app-header class="mb-4"/>
        <b-form-group label="Choose a subscription plan:" v-if="mode !== 'update'" class="mb-0">
            <a :href="`https://www.braintreegateway.com/merchants/${braintreeMerchantId}/verified`" target="_blank" class="float-right">
                <img src="https://s3.amazonaws.com/braintree-badges/braintree-badge-light.png" width="164px" height="44px" border="0"/>
            </a>
            <b-form-group name="Subscription Type">
                <b-form-radio v-model="subscriptionType" v-for="availableSubscriptionType in subscriptionTypesForPurchase" :value="availableSubscriptionType.value" :key="availableSubscriptionType.value">{{ availableSubscriptionType.text }}</b-form-radio>
            </b-form-group>
            <div v-if="activeDiscountPercent">
                <div>
                    Applied Discount: {{ activeDiscountPercent }}%
                </div>
                <div>
                    Your Price: {{ priceLessDiscount | currency }} for the next {{ activeDiscountDuration }}, then {{ selectedSubscriptionPrice | currency }} thereafter
                </div>
            </div>
        </b-form-group>
        <b-row v-if="mode === 'update' && braintreeMerchantId">
            <a :href="`https://www.braintreegateway.com/merchants/${braintreeMerchantId}/verified`" target="_blank" class="ml-auto mr-3">
                <img src="https://s3.amazonaws.com/braintree-badges/braintree-badge-light.png" width="164px" height="44px" border="0"/>
            </a>
        </b-row>
        <div id="drop-in-container" v-if="isModalActive">
            <!-- Populated by braintree dropin package -->
        </div>
        <b-button @click="submitPaymentInfo()" :disabled="!braintreeInstance || processingPayment" variant="primary" block>
            <loading-spinner :show-spinner="!braintreeInstance || processingPayment" custom-class="size-auto">
                <span v-if="mode === 'purchase'">Subscribe</span>
                <span v-else-if="mode === 'update'">Update Payment</span>
            </loading-spinner>
        </b-button>
        <app-message class="w-100" :type="'error'" :messages="apiErrors" @close="apiErrors = []"></app-message>
    </b-modal>
</template>

<script src="./subscription-modal.controller.js"></script>
<style lang="scss" src="./_subscription-modal.scss" scoped></style>
