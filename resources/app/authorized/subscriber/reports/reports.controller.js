import AssignmentsPanel from './components/assignments-panel/assignments-panel';
import BalancesPanel from './components/balances-panel/balances-panel';
import DefensePanel from './components/defense-panel/defense-panel';

export default {
    components: {
        AssignmentsPanel,
        BalancesPanel,
        DefensePanel
    },
    props: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        validationErrors: {},
        isInitializingView: false,
        startDate: null,
        endDate: null,
        defenses: [],
        assignableAccounts: [],
        creditCards: []
    };
}

function getComputed(){
    return {};
}

function created(){
    const vm = this;
    vm.isInitializingView = true;
    vm.startDate = Vue.moment().startOf('month').subtract(11, 'months').format('YYYY-MM-DD');
    vm.endDate = Vue.moment().startOf('month').add(1, 'month').format('YYYY-MM-DD');

    return Promise.all([
        vm.fetchDefenses(),
        vm.fetchAssignableAccounts(),
        vm.fetchCreditCards()
    ]).catch(vm.displayErrorMessage)
        .finally(resetLoadingState);

    function resetLoadingState(){
        vm.isInitializingView = false;
    }
}

function getMethods(){
    return {
        displayErrorMessage,
        fetchDefenses,
        fetchAssignableAccounts,
        fetchCreditCards
    };

    function displayErrorMessage(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data.errors;
        if(isValidationError){
            vm.validationErrors = error.data.errors;
        } else {
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function fetchDefenses(){
        const vm = this;
        const query = {
            start_date: vm.startDate,
            end_date: vm.endDate
        };
        return Vue.appApi().authorized().defense().indexDefenses(query).then(setDefenses);
        function setDefenses({ data }){
            vm.defenses = data;
        }
    }

    function fetchAssignableAccounts(){
        const vm = this;
        return Vue.appApi().authorized().bankAccount().getAssignableAccounts().then(setAssignableAccounts);
        function setAssignableAccounts({ data }){
            vm.assignableAccounts = data.sort(byAccountType);
            function byAccountType(a, b){
                if(a.slug === 'monthly_bills'){
                    return -1;
                } else if(a.slug === 'everyday_checking'){
                    return b.slug === 'monthly_bills' ? 1 : -1;
                } else {
                    return 1;
                }
            }
        }
    }

    function fetchCreditCards(){
        const vm = this;
        return Vue.appApi().authorized().bankAccount().loadWithInstitutionAccounts({ type: 'credit' }).then(setCreditCards);
        function setCreditCards({ data }){
            vm.creditCards = data;
        }
    }
}
