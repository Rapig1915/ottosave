export default {
    components: {},
    props: {
        bankAccounts: {
            type: Array,
            required: true
        },
        creditCards: {
            type: Array,
            required: true
        },
        startDate: {
            type: String,
            required: true
        },
        endDate: {
            type: String,
            required: true
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods(),
    watch: getWatchHandlers(),
};

function data(){
    return {
        selectedMonth: null,
        assignmentListCollapseStates: {},
        loadingAssignments: true,
        assignments: []
    };
}

function getComputed(){
    return {
        monthOptions(){
            const vm = this;
            const options = [];
            const startDate = Vue.moment(vm.startDate);
            const endDate = Vue.moment(vm.endDate);
            do {
                const _endDate = endDate.format('YYYY-MM-DD');
                endDate.subtract(1, 'month');
                const _startDate = endDate.format('YYYY-MM-DD');

                options.push({
                    label: endDate.format('MMMM YYYY'),
                    value: endDate.format(),
                    startDate: _startDate,
                    endDate: _endDate
                });
            } while(endDate.isAfter(startDate));
            return options;
        },
        assignmentsByBankAccount(){
            const vm = this;
            const accumulator = vm.bankAccounts.reduce((acc, bankAccount) => {
                acc[bankAccount.id] = [];
                return acc;
            }, {});
            vm.assignments.reduce((acc, assignment) => {
                if(acc[assignment.bank_account_id]){
                    acc[assignment.bank_account_id].push(assignment);
                }
                return acc;
            }, accumulator);
            return accumulator;
        },
        totalsByBankAccount(){
            const vm = this;
            const totals = vm.bankAccounts.reduce((accumulator, bankAccount) => {
                accumulator[bankAccount.id] = (vm.assignmentsByBankAccount[bankAccount.id] || []).reduce(sumTransactions, new Decimal(0)).toDecimalPlaces(2).toNumber();
                return accumulator;
                function sumTransactions(sum, assignment){
                    return sum.plus(assignment.transaction.amount);
                }
            }, {});
            return totals;
        },
        totalOfAssignments(){
            const vm = this;
            return Object.values(vm.totalsByBankAccount).reduce(sumTotals, new Decimal(0)).toDecimalPlaces(2).toNumber();

            function sumTotals(sum, total){
                return sum.plus(total);
            }
        },
        creditCardsKeyedById(){
            const vm = this;
            return vm.creditCards.reduce((accumulator, creditCard) => {
                accumulator[creditCard.id] = creditCard;
                return accumulator;
            }, {});
        }
    };
}

function created(){
    const vm = this;
    vm.selectedMonth = vm.monthOptions[0];
}

function getMethods(){
    return {
        fetchAssignments,
    };

    function fetchAssignments(startDate, endDate){
        const vm = this;
        const query = {
            start_date: startDate,
            end_date: endDate
        };

        vm.loadingAssignments = true;

        return Vue.appApi().authorized().bankAccount().assignment().indexAssignmentsByTransactionDate(query)
            .then(setAssignments)
            .catch(handleLoadingError)
            .finally(resetLoadingState);

        function setAssignments({ data }){
            vm.assignments = data.sort(sortByTransactionDate);
            function sortByTransactionDate(a, b){
                const firstDate = a.transaction ? a.transaction.remote_transaction_date : a.remote_transaction_date;
                const secondDate = b.transaction ? b.transaction.remote_transaction_date : b.remote_transaction_date;
                return Vue.moment(firstDate).isBefore(secondDate) ? -1 : 1;
            }
        }

        function handleLoadingError(){
            vm.assignments = [];
        }

        function resetLoadingState(){
            vm.loadingAssignments = false;
        }
    }
}

function getWatchHandlers(){
    return {
        'selectedMonth': function(newMonth){
            const vm = this;
            vm.fetchAssignments(newMonth.startDate, newMonth.endDate);
        }
    };
}
