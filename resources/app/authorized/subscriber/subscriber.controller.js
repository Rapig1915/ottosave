import VerificationRequiredWarning from 'vue_root/components/verification-required-warning/verification-required.vue';

export default {
    components: {
        VerificationRequiredWarning
    },
    props: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        isConfirmingDemoExit: false,
        isStartingTrial: false,
        trialErrors: []
    };
}

function getComputed(){
    return {
        isInDemoMode(){
            const vm = this;
            return vm.$store.getters.isInDemoMode;
        },
        isMiniLayoutMode(){
            const vm = this;
            return vm.$route.name === 'accept-invite';
        },
        hasMultipleAccountAccess(){
            const vm = this;
            const accessibleAccounts = vm.$store.state.guest.user.accessible_accounts || [];
            return accessibleAccounts.length > 1;
        },
        currentAccountName(){
            const vm = this;
            const currentAccountId = vm.$store.getters['user/currentAccountId'];
            const accessibleAccounts = vm.$store.state.guest.user.accessible_accounts || [];

            const currentAccount = accessibleAccounts.find(account => account.account_id === currentAccountId);
            const isCoachAccount = currentAccount && currentAccount.roles.includes('coach');
            if(isCoachAccount){
                return currentAccount.user.name;
            }

            return '';
        }
    };
}

function created(){}

function getMethods(){
    return {
        exitDemoMode
    };

    function exitDemoMode(){
        const vm = this;
        vm.isStartingTrial = true;
        return vm.$store.dispatch('user/START_NEW_TRIAL')
            .then(refreshLocalData)
            .catch(displayError)
            .finally(resetLoadingState);
        function refreshLocalData(){
            vm.$router.go();
        }
        function displayError(err){
            vm.trialErrors = [err.appMessage || err.message];
        }
        function resetLoadingState(){
            vm.isStartingTrial = false;
        }
    }
}
