import PasswordModal from './password-modal/password-modal';
import CancelModal from './cancel-modal/cancel-modal';
import DeactivateButton from './deactivate-button/deactivate-button';
import CanUserMixin from 'vue_root/mixins/can-user.mixin.js';

export default {
    components: {
        PasswordModal,
        CancelModal,
        DeactivateButton
    },
    mixins: [
        CanUserMixin,
    ],
    created: created,
    computed: getComputed(),
    data: data,
    methods: getMethods(),
};

function created(){
    const vm = this;
    if(!vm.canUpdateAccountSettings){
        vm.$router.push({ name: 'dashboard' });
        return;
    }

    vm.user = JSON.parse(JSON.stringify(vm.$store.state.guest.user.user));
    vm.getSubscriptionTypes();
    getNotificationPreferences();

    function getNotificationPreferences(){
        vm.loadingNotificationPreferences = true;
        Vue.appApi().authorized().account().notificationPreferences().getNotificationPreferences().then(setNotificationPreferences).catch(displayError);
        function setNotificationPreferences(response){
            vm.loadingNotificationPreferences = false;
            vm.notificationPreferences = response.data;
        }
        function displayError(response){
            vm.loadingNotificationPreferences = false;
            if(response.appMessage){
                vm.updateNotificationPreferencesErrors = [response.appMessage];
            }
        }
    }
}

function getComputed(){
    return {
        daysRemainingOnAccount,
        currentAccount,
        isCurrentPlusSubscriberWithPaymentOnFile,
        isDeactivationButtonShown,
        activeDiscountPercent,
        activeDiscountDuration,
        selectedSubscriptionPrice,
        subscriptionPriceLessDiscount,

        canUpdateAccountSettings,
        canInviteToAccount,
    };

    function daysRemainingOnAccount(){
        const vm = this;
        const expireDate = Vue.moment(vm.currentAccount.expire_date).endOf('day');
        const today = Vue.moment().endOf('day');
        const duration = Vue.moment.duration(expireDate.diff(today));
        return Math.floor(duration.asDays());
    }

    function currentAccount(){
        const vm = this;
        return vm.$store.state.guest.user.user.current_account;
    }

    function isCurrentPlusSubscriberWithPaymentOnFile(){
        const vm = this;
        const isPlusSubscriber = vm.currentAccount.subscription_plan === 'plus';
        const isCurrentSubscriber = ['active', 'free_trial', 'pending_renewal'].includes(vm.currentAccount.status);
        const hasPaymentOnFile = vm.currentAccount.braintree_customer_id;
        return isPlusSubscriber && isCurrentSubscriber && hasPaymentOnFile;
    }

    function isDeactivationButtonShown(){
        const vm = this;
        const statusesEligibleForDeactivation = ['grace', 'trial_grace', 'expired'];
        const isBasicUser = vm.currentAccount.subscription_plan === 'basic';
        const isItunesSubscriber = vm.currentAccount.subscription_provider === 'itunes';
        const isAccountStatusEligibleForDeactivation = statusesEligibleForDeactivation.includes(vm.currentAccount.status);
        return !isItunesSubscriber && (isBasicUser || isAccountStatusEligibleForDeactivation);
    }
    function activeDiscountPercent(){
        const vm = this;
        let discountPercentage = 0;
        if(vm.user.current_account.active_discount_coupon){
            discountPercentage = vm.user.current_account.active_discount_coupon.amount;
        }
        return discountPercentage;
    }
    function activeDiscountDuration(){
        const vm = this;
        let discountDuration = 0;
        if(vm.user.current_account.active_discount_coupon){
            discountDuration = vm.user.current_account.active_discount_coupon.remaining_months > 1 ? (vm.user.current_account.active_discount_coupon.remaining_months + ' months') : 'month';
        }
        return discountDuration;
    }
    function selectedSubscriptionPrice(){
        const vm = this;
        let price = 0;
        const selectedType = vm.subscriptionTypes.find(({ value }) => value === vm.user.current_account.subscription_type);
        if(selectedType){
            price = selectedType.price;
        }
        return price;
    }
    function subscriptionPriceLessDiscount(){
        const vm = this;
        const discountAmount = new Decimal(vm.selectedSubscriptionPrice).times((vm.activeDiscountPercent / 100)).toDecimalPlaces(2).toNumber();
        return new Decimal(vm.selectedSubscriptionPrice).minus(discountAmount).toDecimalPlaces(2).toNumber();
    }

    function canUpdateAccountSettings(){
        return this.canUser('update account-settings');
    }
    function canInviteToAccount(){
        return this.canUser('invite account-users');
    }
}

function data(){
    return {
        storeUserErrors: [],
        storeUserSuccess: [],
        changeEmailErrors: [],
        changeEmailSuccess: [],
        changePasswordErrors: [],
        changePasswordSuccess: [],
        updateSubscriptionErrors: [],
        updateSubscriptionSuccess: [],
        updateNotificationPreferencesErrors: [],
        updateNotificationPreferencesSuccess: [],
        validationErrors: {},
        user: {},
        storingUser: false,
        updatingSubscription: false,
        changingPassword: false,
        changingEmail: false,
        updatingNotificationPreferences: false,
        subscriptionTypes: [],
        notificationOptions: [
            'weekly',
            'daily',
            'never'
        ],
        notificationPreferences: {},
        loadingNotificationPreferences: false,
        loggingOut: false,
        couponCode: '',
        isRedeemingCoupon: false
    };
}

function getMethods(){
    return {
        confirmPassword,
        changeEmail,
        changePassword,
        storeUser,
        confirmCancel,
        cancelSubscription,
        upgradeSubscription,
        updatePaymentMethod,
        getSubscriptionTypes,
        saveSubscriptionInterval,
        paymentSubmitted,
        updateNotificationPreferences,
        logout,
        redeemCoupon,
    };

    function confirmPassword(){
        const vm = this;
        vm.validationErrors = {};
        vm.$refs.passwordModal.openModal();
    }

    function changeEmail(currentPassword){
        const vm = this;
        vm.validationErrors = {};
        vm.changeEmailErrors = [];
        vm.changingEmail = true;
        var payload = JSON.parse(JSON.stringify(vm.user));
        payload.current_password = currentPassword;
        Vue.appApi().authorized().user()
            .changeEmail(payload)
            .then(handleEmailChanged)
            .catch(displayEmailErrors);

        function handleEmailChanged(response){
            vm.changeEmailSuccess.push('A verification email has been sent, please check your inbox to confirm your new address.');
            vm.$store.dispatch('user/GET_USER')
                .then(turnSpinnerOff)
                .catch(displayEmailErrors);
            function turnSpinnerOff(){
                vm.changingEmail = false;
            }
        }

        function displayEmailErrors(response){
            if(response.status === 403){
                vm.changeEmailErrors.push(response.data.message);
            } else {
                vm.changeEmailErrors.push(response.appMessage);
            }
            if(response.validation_errors){
                vm.validationErrors = response.validation_errors;
            }
            vm.changingEmail = false;
        }
    }

    function changePassword(){
        const vm = this;
        vm.validationErrors = {};
        vm.changePasswordErrors = [];
        vm.changingPassword = true;
        Vue.appApi().authorized().user()
            .changePassword(vm.user)
            .then(handlePasswordChanged)
            .catch(displayChangePasswordErrors);

        function handlePasswordChanged(response){
            vm.changePasswordSuccess.push('Password changed.');
            vm.user.current_password = '';
            vm.user.password = '';
            vm.user.password_confirmation = '';
            vm.changingPassword = false;
        }
        function displayChangePasswordErrors(response){
            if(response.status === 403){
                vm.changePasswordErrors.push(response.data.message);
            } else {
                vm.changePasswordErrors.push(response.appMessage);
            }
            if(response.validation_errors){
                vm.validationErrors = response.validation_errors;
            }
            vm.changingPassword = false;
        }
    }

    function storeUser(){
        const vm = this;
        vm.validationErrors = {};
        vm.storeUserErrors = [];
        vm.storeUserSuccess = [];
        vm.storingUser = true;
        Vue.appApi().authorized().user()
            .store(vm.user)
            .then(handleStoreUserSuccess)
            .catch(displayStoreUserErrors);

        function handleStoreUserSuccess(response){
            vm.storeUserSuccess.push('Name Changed.');
            vm.$store.dispatch('user/GET_USER')
                .then(turnSpinnerOff)
                .catch(displayStoreUserErrors);

            function turnSpinnerOff(){
                vm.storingUser = false;
            }
        }

        function displayStoreUserErrors(response){
            vm.storeUserErrors.push(response.appMessage);
            if(response.validation_errors){
                vm.validationErrors = response.validation_errors;
            }
            vm.storingUser = false;
        }
    }

    function confirmCancel(){
        const vm = this;
        vm.$refs.cancelModal.openModal();
    }

    function cancelSubscription(){
        const vm = this;
        vm.updateSubscriptionErrors = [];
        vm.updateSubscriptionSuccess = [];
        vm.updatingSubscription = true;
        vm.$store.dispatch('user/CANCEL_SUBSCRIPTION').then(handleCancelSubscriptionSuccess).catch(displayCancelSubscriptionErrors);

        function handleCancelSubscriptionSuccess(response){

            return vm.$store.dispatch('user/GET_USER').then(setUserData).catch(displayCancelSubscriptionErrors);

            function setUserData(){
                vm.user = JSON.parse(JSON.stringify(vm.$store.state.guest.user.user));
                if(vm.$route.name !== 'settings'){
                    vm.$router.replace({ name: 'settings' });
                }
                vm.updatingSubscription = false;
                vm.updateSubscriptionSuccess.push('Subscription was cancelled.');
            }
        }

        function displayCancelSubscriptionErrors(response){
            if(response.appMessage){
                vm.updateSubscriptionErrors.push(response.appMessage);
            }
            vm.updatingSubscription = false;
        }
    }

    function upgradeSubscription(){
        const vm = this;
        vm.updateSubscriptionSuccess = [];
        if(vm.currentAccount.subscription_plan === 'plus'){
            vm.$refs.paymentComponent.displayPaymentComponent('purchase');
        } else {
            vm.$store.commit('authorized/TOGGLE_UPGRADE_MODAL', true);
        }
    }

    function updatePaymentMethod(){
        const vm = this;
        vm.updateSubscriptionSuccess = [];
        vm.$refs.paymentComponent.displayPaymentComponent('update');
    }

    function getSubscriptionTypes(){
        const vm = this;
        return Vue.appApi().authorized().account().getSubscriptionTypes().then(setSubscriptionTypes).catch(displayError);
        function setSubscriptionTypes(response){
            const availableSubscriptionTypes = Object.values(response.data).filter(getCurrentAndAvailableSubscriptionTypes);
            vm.subscriptionTypes = availableSubscriptionTypes.map(formatSubscriptionTypeOptions);

            function getCurrentAndAvailableSubscriptionTypes(subscriptionType){
                const isAvailableForPurchase = subscriptionType.cleared_for_sale;
                const isCurrentPlan = subscriptionType.slug === vm.currentAccount.subscription_type;
                return isAvailableForPurchase || isCurrentPlan;
            }
            function formatSubscriptionTypeOptions(subscriptionType){
                const subscriptionTypeOption = {
                    value: subscriptionType.slug,
                    text: `${subscriptionType.name} - $${subscriptionType.price}`,
                    price: subscriptionType.price
                };
                return subscriptionTypeOption;
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.updateSubscriptionErrors.push(response.appMessage);
            }
        }
    }

    function saveSubscriptionInterval(){
        const vm = this;
        vm.updateSubscriptionSuccess = [];
        vm.updatingSubscription = true;
        Vue.appApi().authorized().account().updateSubscriptionInterval(vm.user.current_account).then(refreshData).catch(displayError).finally(clearSpinner);
        function refreshData(response){
            return vm.$store.dispatch('user/GET_USER').then(vm.getSubscriptionTypes).then(displaySuccess);
            function displaySuccess(){
                vm.user.current_account = vm.currentAccount;
                vm.updateSubscriptionSuccess.push('Success! You will be billed according to the new plan at your next renewal date.');
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.updateSubscriptionErrors.push(response.appMessage);
            }
        }
        function clearSpinner(){
            vm.updatingSubscription = false;
        }
    }

    function paymentSubmitted(){
        const vm = this;
        if(vm.$route.name !== 'settings'){
            vm.$router.replace({ name: 'settings' });
        } else {
            vm.updatingSubscription = true;
            vm.$store.dispatch('user/GET_USER').then(setUserData).catch(displayError);
        }
        function setUserData(){
            vm.updatingSubscription = false;
            vm.user = JSON.parse(JSON.stringify(vm.$store.state.guest.user.user));
            vm.updateSubscriptionSuccess.push('Subscription Updated!');
        }
        function displayError(response){
            vm.updatingSubscription = false;
            if(response.appMessage){
                vm.updateSubscriptionErrors.push(response.appMessage);
            }
        }
    }

    function updateNotificationPreferences(){
        const vm = this;
        vm.updatingNotificationPreferences = true;
        vm.updateNotificationPreferencesErrors = [];
        vm.updateNotificationPreferencesSuccess = [];

        Vue.appApi().authorized().account().notificationPreferences().storeNotificationPreferences(vm.notificationPreferences).then(handleSuccess).catch(displayError);

        function handleSuccess(response){
            vm.notificationPreferences = response.data;
            vm.updatingNotificationPreferences = false;
            vm.updateNotificationPreferencesSuccess = ['Notification Preferences Updated!'];
        }
        function displayError(response){
            vm.updatingNotificationPreferences = false;
            if(response.appMessage){
                vm.updateNotificationPreferencesErrors = [response.appMessage];
            }
        }
    }

    function logout(){
        const vm = this;
        vm.loggingOut = true;
        vm.$store.dispatch('user/LOGOUT').then(logoutSuccess).catch(logoutError);
        function logoutSuccess(){
            const clientPlatform = window.appEnv.clientPlatform || 'web';
            if(clientPlatform === 'web'){
                vm.$router.go();
            } else {
                vm.$router.replace({ name: 'login' });
            }
        }
        function logoutError(){
            vm.$store.dispatch('user/LOGOUT_FRONTEND').then(logoutSuccess);
        }
    }

    function redeemCoupon(){
        const vm = this;
        vm.isRedeemingCoupon = true;
        vm.updateSubscriptionErrors = [];
        vm.updateSubscriptionSuccess = [];
        return Vue.appApi().authorized().account().coupons().redeemCoupon({ code: vm.couponCode }).then(displaySuccess).catch(displayRedemptionError).finally(resetLoadingState);

        function displaySuccess(response){
            vm.$store.dispatch('user/GET_USER');
            const redeemedCoupon = response.data;
            let successMessage = '';
            if(redeemedCoupon.reward_type === 'free_month'){
                const interval = redeemedCoupon.amount > 1 ? 'months' : 'month';
                successMessage = `Coupon for ${redeemedCoupon.amount} free ${interval} applied successfully.`;
            } else if(redeemedCoupon.reward_type === 'discount_percentage'){
                const interval = redeemedCoupon.reward_duration_in_months > 1 ? 'months' : 'month';
                successMessage = `Coupon for ${redeemedCoupon.amount}% discount successfully applied for next ${redeemedCoupon.reward_duration_in_months} ${interval}.`;
            }
            vm.couponCode = '';
            if(successMessage){
                vm.updateSubscriptionSuccess.push(successMessage);
                setTimeout(autoClearSuccessMessage, 2000);
            }
            function autoClearSuccessMessage(){
                const successMessageIndex = vm.updateSubscriptionSuccess.indexOf(successMessage);
                if(successMessageIndex >= 0){
                    vm.updateSubscriptionSuccess.splice(successMessageIndex, 1);
                }
            }
        }

        function displayRedemptionError(response){
            if(response.appMessage){
                vm.updateSubscriptionErrors.push(response.data.message);
            }
        }

        function resetLoadingState(){
            vm.isRedeemingCoupon = false;
        }
    }
}
