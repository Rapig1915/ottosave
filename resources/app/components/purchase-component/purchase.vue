<template>
    <div class="purchaseComponent">
        <SubscriptionModal v-if="platform === 'web'" ref="subscriptionModal" @paymentSubmittedSuccess="handleSuccessfulPurchase"/>
        <InAppPurchase v-if="platform === 'ios'" ref="inAppPurchaseModal" @paymentSubmittedSuccess="handleSuccessfulPurchase"/>
    </div>
</template>

<script src="./purchase.controller.js"></script>
