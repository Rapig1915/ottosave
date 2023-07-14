import braintreeWebDropIn from 'braintree-web-drop-in';

export default {
    data: data,
    computed: getComputed(),
    created,
    beforeDestroy,
    methods: getMethods()
};

function data(){
    return {
        subscriptionType: 'monthly_4_99',
        subscriptionPlan: 'plus',
        braintreeClientToken: null,
        braintreeInstance: null,
        processingPayment: false,
        mode: 'purchase',
        apiErrors: [],
        subscriptionTypesForPurchase: [],
        braintreeMerchantId: null,
        isModalActive: false
    };
}

function getComputed(){
    return {
        user(){
            const vm = this;
            return vm.$store.state.guest.user.user;
        },
        activeDiscountPercent(){
            const vm = this;
            let discountPercentage = 0;
            if(vm.user.current_account.active_discount_coupon){
                discountPercentage = vm.user.current_account.active_discount_coupon.amount;
            }
            return discountPercentage;
        },
        activeDiscountDuration(){
            const vm = this;
            let discountDuration = 0;
            if(vm.user.current_account.active_discount_coupon){
                discountDuration = vm.user.current_account.active_discount_coupon.remaining_months > 1 ? (vm.user.current_account.active_discount_coupon.remaining_months + ' months') : 'month';
            }
            return discountDuration;
        },
        selectedSubscriptionPrice(){
            const vm = this;
            let price = 0;
            const selectedType = vm.subscriptionTypesForPurchase.find(({ value }) => value === vm.subscriptionType);
            if(selectedType){
                price = selectedType.price;
            }
            return price;
        },
        priceLessDiscount(){
            const vm = this;
            const discountAmount = new Decimal(vm.selectedSubscriptionPrice).times((vm.activeDiscountPercent / 100)).toDecimalPlaces(2).toNumber();
            return new Decimal(vm.selectedSubscriptionPrice).minus(discountAmount).toDecimalPlaces(2).toNumber();
        }
    };
}

function created(){
    const vm = this;
    vm.getSubscriptionTypes();
}

function beforeDestroy(){
    const vm = this;
    vm.cleanupBraintree();
}

function getMethods(){
    return {
        openModal,
        getClientToken,
        showBraintreeDropinForm,
        submitPaymentInfo,
        cleanupBraintree,
        getSubscriptionTypes,
        setModalActive
    };

    function openModal(mode = 'purchase'){
        const vm = this;
        vm.mode = mode;
        vm.showBraintreeDropinForm();
        vm.$refs.subscriptionModal.show();
    }

    function getClientToken(){
        const vm = this;
        return Vue.appApi().guest().braintree().getClientAuthToken().then(setClientToken).catch(showError);

        function setClientToken(response){
            vm.braintreeClientToken = response.data.token;
            vm.braintreeMerchantId = response.data.merchant_id;
        }
        function showError(response){
            vm.apiErrors.push(response.appMessage || response.data.message);
        }
    }

    function showBraintreeDropinForm(){
        const vm = this;
        if(!vm.braintreeClientToken){
            return vm.getClientToken().then(vm.showBraintreeDropinForm);
        }
        braintreeWebDropIn.create(
            {
                authorization: vm.braintreeClientToken,
                container: '#drop-in-container',
                vaultManager: true,
                card: {}
            },
            function(createErr, instance){
                if(createErr){
                    vm.apiErrors.push(`Oops, we're having trouble communicating with our payment processor. Please try again later.`);
                } else {
                    vm.braintreeInstance = instance;
                }
            }
        );
    }

    function submitPaymentInfo(){
        const vm = this;
        vm.processingPayment = true;
        vm.apiErrors = [];
        vm.braintreeInstance.requestPaymentMethod().then(storePayment).catch(showError);

        function storePayment(payload){
            vm.apiErrors = [];
            const subscriptionDetails = {
                paymentNonce: payload.nonce,
                subscriptionType: vm.subscriptionType,
                subscriptionPlan: vm.subscriptionPlan
            };
            let processPaymentPromise = null;
            if(vm.mode === 'purchase'){
                processPaymentPromise = vm.$store.dispatch('user/PURCHASE_SUBSCRIPTION', subscriptionDetails);
            } else if(vm.mode === 'update'){
                processPaymentPromise = Vue.appApi().authorized().account().updateSubscriptionPayment({ paymentNonce: payload.nonce });
            } else {
                processPaymentPromise = new Promise((resolve, reject) => reject('Unknown subscription modal mode.'));
            }

            processPaymentPromise.then(submitPaymentSuccess).catch(submitPaymentError);

            function submitPaymentSuccess(){
                vm.processingPayment = false;
                vm.$emit('paymentSubmittedSuccess', vm.mode);
                vm.$refs.subscriptionModal.hide();
            }
            function submitPaymentError(response){
                vm.processingPayment = false;
                vm.apiErrors.push(response.appMessage || response.data.message);
            }
        }
        function showError(requestPaymentMethodErr){
            vm.processingPayment = false;
            if(requestPaymentMethodErr){
                vm.apiErrors.push(requestPaymentMethodErr.message);
            }
        }
    }

    function cleanupBraintree(){
        const vm = this;
        if(vm.braintreeInstance instanceof Object){
            vm.braintreeInstance.teardown().then(() => {
                vm.braintreeInstance = null;
            });
        }
        vm.braintreeClientToken = null;
        vm.isModalActive = false;
    }

    function getSubscriptionTypes(){
        const vm = this;
        Vue.appApi().authorized().account().getSubscriptionTypes().then(setSubscriptionTypes).catch(displayError);
        function setSubscriptionTypes(response){
            const availableSubscriptionTypes = Object.values(response.data).filter(({ cleared_for_sale }) => cleared_for_sale);
            vm.subscriptionTypesForPurchase = availableSubscriptionTypes.map(formatSubscriptionTypeOptions).sort(sortByPrice);
            const currentSubscriptionType = vm.subscriptionTypesForPurchase.find(({ value }) => value === vm.user.current_account.subscription_type);
            vm.subscriptionType = currentSubscriptionType ? currentSubscriptionType.value : vm.subscriptionTypesForPurchase[0].value;

            function formatSubscriptionTypeOptions(subscriptionType){
                const subscriptionTypeOption = {
                    value: subscriptionType.slug,
                    text: `$${subscriptionType.price} - ${subscriptionType.name}`,
                    price: subscriptionType.price
                };
                return subscriptionTypeOption;
            }
            function sortByPrice(a, b){
                return a.price - b.price;
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.apiErrors.push(response.appMessage);
            }
        }
    }

    function setModalActive(){
        const vm = this;
        vm.isModalActive = true;
    }
}
