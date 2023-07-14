export default {
    data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        isStartingTrial: false,
        mode: 'trial'
    };
}

function getComputed(){
    return {
        user(){
            const vm = this;
            return vm.$store.getters.user;
        },
        isUpgradeModalShown: {
            get(){
                const vm = this;
                return vm.$store.state.authorized.displayUpgradeModal;
            },
            set(value){
                const vm = this;
                vm.$store.commit('authorized/TOGGLE_UPGRADE_MODAL', value);
                return value;
            }
        }
    };
}

function getMethods(){
    return {
        openPaymentComponent,
        navigateToTour,
        startTrial,
        setMode
    };

    function openPaymentComponent(){
        const vm = this;
        vm.$store.commit('authorized/TOGGLE_UPGRADE_MODAL', false);
        const freeTrialUsed = vm.$store.getters.freeTrialUsed;
        const subscriptionModalMode = freeTrialUsed ? 'purchase' : 'trial';
        vm.$refs.paymentComponent.displayPaymentComponent(subscriptionModalMode);
    }

    function navigateToTour(){
        const vm = this;
        vm.$store.commit('authorized/TOGGLE_UPGRADE_MODAL', false);
        vm.$root.$emit('dym::restart-tour');
    }

    function startTrial(){
        const vm = this;
        vm.isStartingTrial = true;
        vm.$store.dispatch('user/START_NEW_TRIAL').then(vm.navigateToTour).catch(displayError).finally(resetSpinner);
        function displayError(error){
            if(error.appMessage){
                vm.apiErrors = [error.appMessage];
            }
        }
        function resetSpinner(){
            vm.isStartingTrial = false;
        }
    }

    function setMode($event){
        const vm = this;
        vm.mode = vm.$store.getters.freeTrialUsed ? 'purchase' : 'trial';
        if(vm.mode === 'purchase'){
            $event.preventDefault();
            if(vm.user.current_account.subscription_provider !== 'itunes'){
                vm.openPaymentComponent();
            } else {
                window.open('https://buy.itunes.apple.com/WebObjects/MZFinance.woa/wa/manageSubscriptions');
            }
        }
    }
}
