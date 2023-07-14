export default {
    data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        user: { current_account: {}},
        isModalShown: false,
        isDowngradingAccount: false,
        apiErrors: []
    };
}

function getComputed(){
    return {
        daysRemainingOnAccount(){
            const vm = this;
            if(vm.user && vm.user.current_account){
                const expireDate = Vue.moment(vm.user.current_account.expire_date).endOf('day');
                const today = Vue.moment().endOf('day');
                const duration = Vue.moment.duration(expireDate.diff(today));
                return Math.floor(duration.asDays());
            }
        },
    };
}

function getMethods(){
    return {
        openPaymentComponent,
        openModal,
        closeModal,
        downgradeToBasic,
        displayApiError
    };

    function openPaymentComponent(){
        const vm = this;
        if(vm.user.current_account.status === 'free_trial'){
            vm.closeModal();
        }
        vm.$refs.paymentComponent.displayPaymentComponent('purchase');
    }

    function openModal(user){
        const vm = this;
        vm.user = user;
        const isTrialEnding = user.current_account.status === 'free_trial' && vm.daysRemainingOnAccount <= 7;
        const isTrialExpired = user.current_account.status === 'trial_grace';
        if(isTrialExpired){
            vm.isModalShown = true;
        } else if(isTrialEnding){
            Vue.clientStorage.getItem(`${user.current_account.id}_trial_ending_shown_at`).then(showModalOnceADay);
        }

        function showModalOnceADay(lastOpened){
            let wasModalShownToday = false;
            if(lastOpened){
                wasModalShownToday = Vue.moment(lastOpened).isSame(Vue.moment(), 'd');
            }
            if(!wasModalShownToday){
                vm.isModalShown = true;
                Vue.clientStorage.setItem(`${user.current_account.id}_trial_ending_shown_at`, Vue.moment().format());
            }
        }
    }

    function closeModal(){
        const vm = this;
        vm.isModalShown = false;
    }

    function downgradeToBasic(){
        const vm = this;
        vm.isDowngradingAccount = true;
        vm.$store.dispatch('user/START_BASIC_PLAN').then(vm.closeModal).catch(vm.displayApiError).finally(resetLoadingState);

        function resetLoadingState(){
            vm.isDowngradingAccount = false;
        }
    }

    function displayApiError(err){
        const vm = this;
        vm.apiErrors.push(err.appMessage || err.data.message);
    }
}
