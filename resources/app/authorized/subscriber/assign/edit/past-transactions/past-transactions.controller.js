export default {
    components: {},
    props: {
        creditCardAccounts: {
            type: Array,
            required: true
        }
    },
    data,
    computed: getComputed(),
    watch: getWatchers(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        isLoadingCharges: false,
        selectedInstitutionAccountId: null,
        startDate: '',
        endDate: '',
        isComponentDisplayed: false
    };
}

function getComputed(){
    return {
        creditCardSelectOptions,
        isUpdatingAccounts,
        selectedCCAccount,
        disabledStartDates,
        disabledEndDates,
        hasInstitutionAccount
    };
    function creditCardSelectOptions(){
        const vm = this;
        return vm.creditCardAccounts.map(formatAsSelectOption);
        function formatAsSelectOption(bankAccount){
            return { text: bankAccount.name, value: bankAccount.institution_account.id };
        }
    }
    function isUpdatingAccounts(){
        const vm = this;
        return vm.$store.state.authorized.finicityRefreshStatus === 'pending';
    }
    function selectedCCAccount(){
        const vm = this;
        return vm.creditCardAccounts.find(({ institution_account }) => +institution_account.id === +vm.selectedInstitutionAccountId);
    }
    function disabledStartDates(){
        const vm = this;
        const disabledDates = {};
        if(vm.selectedCCAccount){
            disabledDates.to = new Date(Vue.moment(vm.selectedCCAccount.institution_account.created_at).subtract(6, 'months').format());
            disabledDates.from = new Date();
        }
        return disabledDates;
    }
    function disabledEndDates(){
        const vm = this;
        const disabledDates = {};
        if(vm.selectedCCAccount){
            disabledDates.to = new Date(Vue.moment(vm.selectedCCAccount.institution_account.created_at).subtract(6, 'months').format());
            disabledDates.from = new Date();
        }
        return disabledDates;
    }
    function hasInstitutionAccount(){
        const vm = this;
        var hasInstitutionAccount = false;
        vm.creditCardAccounts.map(findInstitutionAccounts);
        return hasInstitutionAccount;

        function findInstitutionAccounts(bankAccount){
            if(typeof bankAccount.institution_account === 'object'){
                hasInstitutionAccount = true;
            }
        }
    }
}

function created(){}

function getWatchers(){
    return {
        isUpdatingAccounts
    };

    function isUpdatingAccounts(newValue, oldValue){
        const vm = this;
        const finishedUpdatingAccounts = !newValue && oldValue;
        if(finishedUpdatingAccounts){
            vm.$emit('transactions-downloaded');
        }
    }
}

function getMethods(){
    return {
        displayErrorMessage,
        hide,
        show,
        downloadPastCharges,
    };

    function displayErrorMessage(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data && error.data.errors;
        if(isValidationError){
            vm.errorMessages = Object.values(error.data.errors).flat();
        } else if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function hide(){
        const vm = this;
        vm.isComponentDisplayed = false;
    }

    function show(){
        const vm = this;
        if(vm.hasInstitutionAccount){
            vm.selectedInstitutionAccountId = vm.creditCardSelectOptions[0].value;
        }
        vm.isComponentDisplayed = true;
    }

    function downloadPastCharges(){
        const vm = this;
        const payload = {
            start_date: vm.startDate,
            end_date: vm.endDate
        };
        vm.errorMessages = [];
        vm.isLoadingCharges = true;
        return Vue.appApi().authorized().institution().institutionAccounts(vm.selectedInstitutionAccountId).downloadPastTransactions(payload).then(monitorRefreshStatus).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function monitorRefreshStatus(response){
            return vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS');
        }

        function resetLoadingState(){
            vm.isLoadingCharges = false;
        }
    }
}
