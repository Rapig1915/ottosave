import store from 'vue_root/app.store';

export default {
    methods: {
        verifySubscriptionPlan,
        verifySubscriptionStatus
    }
};

function verifySubscriptionPlan(planType){
    const userAccount = store.state.guest.user.user.current_account;
    const isSubscriptionCurrent = userAccount && userAccount.status !== 'expired';
    const hasRequiredPlan = userAccount && userAccount.subscription_plan === planType;
    const hasValidSubscription = isSubscriptionCurrent && hasRequiredPlan;

    return hasValidSubscription;
}

function verifySubscriptionStatus(status){
    const userAccount = store.state.guest.user.user.current_account;
    const hasValidSubscription = userAccount && userAccount.status === status;
    return hasValidSubscription;
}
