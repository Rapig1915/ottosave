import SubscriptionModal from 'vue_root/components/subscription-modal/subscription-modal';
import InAppPurchase from './in-app-purchase/in-app-purchase';

export default {
    components: {
        SubscriptionModal,
        InAppPurchase
    },
    data: data,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        platform: window.appEnv.clientPlatform
    };
}

function getMethods(){
    return {
        displayPaymentComponent,
        handleSuccessfulPurchase,
    };

    function displayPaymentComponent(mode){
        const vm = this;
        if(vm.platform === 'ios'){
            vm.$refs.inAppPurchaseModal.openModal(mode);
        } else if(vm.platform === 'web'){
            vm.$refs.subscriptionModal.openModal(mode);
        }
    }
    function handleSuccessfulPurchase(payload){
        const vm = this;
        return vm.$store.dispatch('user/GET_USER').then(emitPaymentSuccess);
        function emitPaymentSuccess(){
            vm.$emit('paymentSubmittedSuccess', payload);
        }
    }
}
