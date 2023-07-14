import DefenseChart from './defense-chart.js';
import SassVariablesMixin from 'vue_root/mixins/sass-variables.mixin.js';

export default {
    components: {
        DefenseChart
    },
    mixins: [
        SassVariablesMixin
    ],
    props: {
        bankAccounts: {
            type: Array,
            required: true
        },
        defenses: {
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
    methods: getMethods()
};

function data(){
    return {
        selectedAccount: null,
        assignments: [],
        loadingAssignments: false,
    };
}

function getComputed(){
    return {
        accountOptions(){
            const vm = this;
            const options = [{ label: 'All accounts', value: 'all' }];
            vm.bankAccounts.forEach((bankAccount) => {
                options.push({ label: bankAccount.name, value: JSON.parse(JSON.stringify(bankAccount)) });
            });
            return options;
        },
        displayedMonths(){
            const vm = this;
            const startDate = Vue.moment(vm.startDate);
            const months = [];
            do {
                months.push(startDate.format('YYYY-MM-DD'));
                startDate.add(1, 'month');
            } while(startDate.isBefore(vm.endDate));
            return months;
        },
        chartLabels(){
            const vm = this;
            return vm.displayedMonths.map(createChartLabel);

            function createChartLabel(dateString){
                const date = Vue.moment(dateString);
                const isJanuary = date.format('M') === '1';
                if(isJanuary){
                    return date.format('YYYY');
                } else {
                    return date.format('MMM');
                }
            }
        },
        chartjsData(){
            const vm = this;
            const organizedAmounts = vm.selectedAccount.value === 'all' ? vm.allOrganizedAmounts : vm.organizedAmountsByBankAccount[vm.selectedAccount.value.id];
            const assignmentAmounts = vm.selectedAccount.value === 'all' ? vm.allAssignedAmounts : vm.assignedAmountsByBankAccount[vm.selectedAccount.value.id];
            const datasetDefaults = {
                backgroundColor: '#FFF',
                borderColor: vm.getAccountColor(vm.selectedAccount.value),
                pointStyle: 'line',
                pointRadius: 0,
                hitRadius: 5,
                hoverRadius: 0,
                borderWidth: 4,
                lineTension: 0,
                label: '',
                fill: false,
                data: []
            };
            const data = {
                labels: vm.chartLabels,
                datasets: [
                    Object.assign({}, datasetDefaults, {
                        borderDash: [7, 7],
                        label: 'Organized Income',
                        data: organizedAmounts
                    }),
                    Object.assign({}, datasetDefaults, {
                        label: 'Assigned Charges',
                        data: assignmentAmounts
                    }),
                ]
            };
            return data;
        },
        organizedAmountsByBankAccount(){
            const vm = this;
            return vm.bankAccounts.reduce((accumulator, bankAccount) => {
                accumulator[bankAccount.id] = vm.displayedMonths.map(getOrganizedTotalForMonth);
                return accumulator;

                function getOrganizedTotalForMonth(dateString){
                    const defenses = vm.getDefensesForMonth(dateString);
                    return defenses.reduce((sum, defense) => {
                        const assignmentsInDefense = defense.allocations.reduce((accumulator, allocation) => {
                            accumulator.push(...allocation.assignments);
                            return accumulator;
                        }, []);
                        const paidOffAssignments = assignmentsInDefense.filter(function matchTransferredForBankAccount(assignment){
                            return assignment.bank_account_id === bankAccount.id && assignment.transferred === true;
                        }).filter(function matchUniqueAssignments(assignment, index, array){
                            return array.findIndex(({ id }) => id === assignment.id) === index;
                        });
                        let organizedAmount = new Decimal(0);
                        const transferIntoAccount = defense.allocations.find(allocation => allocation.bank_account_id === bankAccount.id);
                        const transferFromAccount = defense.allocations.find(allocation => allocation.transferred_from_id === bankAccount.id);
                        const assignmentTotal = paidOffAssignments.reduce((accumulator, assignment) => accumulator.plus(assignment.allocated_amount), new Decimal(0));
                        if(transferIntoAccount){
                            organizedAmount = assignmentTotal.plus(transferIntoAccount.amount);
                        } else {
                            const transferOutAmount = transferFromAccount ? transferFromAccount.amount : 0;
                            organizedAmount = assignmentTotal.minus(transferOutAmount);
                        }
                        return sum.plus(organizedAmount);
                    }, new Decimal(0)).toDecimalPlaces(2).toNumber();
                }
            }, {});
        },
        allOrganizedAmounts(){
            const vm = this;
            return vm.displayedMonths.map((dateString, index) => {
                return Object.values(vm.organizedAmountsByBankAccount).reduce((sum, organizedAmountArray) => {
                    return sum.plus(organizedAmountArray[index]);
                }, new Decimal(0)).toDecimalPlaces(2).toNumber();
            });
        },
        assignedAmountsByBankAccount(){
            const vm = this;
            return vm.bankAccounts.reduce(getMonthlyAssignmentTotals, {});
            function getMonthlyAssignmentTotals(accumulator, bankAccount){
                const monthlyAssignments = vm.displayedMonths.map(dateString => {
                    const assignments = vm.getBankAccountAssignmentsForMonth(bankAccount, dateString);
                    return assignments.reduce((sum, assignment) => {
                        return sum.plus(assignment.transaction.amount);
                    }, new Decimal(0)).toDecimalPlaces(2).toNumber();
                });
                accumulator[bankAccount.id] = monthlyAssignments;
                return accumulator;
            }
        },
        allAssignedAmounts(){
            const vm = this;
            return vm.displayedMonths.map((dateString, index) => {
                return Object.values(vm.assignedAmountsByBankAccount).reduce((sum, assignmentAmountsArray) => {
                    return sum.plus(assignmentAmountsArray[index] || 0);
                }, new Decimal(0)).toDecimalPlaces(2).toNumber();
            });
        }
    };
}

function created(){
    const vm = this;
    vm.selectedAccount = vm.accountOptions[0];
    vm.fetchAssignments();
}

function getMethods(){
    return {
        getDefensesForMonth,
        getBankAccountAssignmentsForMonth,
        fetchAssignments,
    };

    function getDefensesForMonth(dateString){
        const vm = this;
        return vm.defenses.filter(defense => {
            return Vue.moment(dateString).isSame(defense.created_at, 'month');
        });
    }

    function getBankAccountAssignmentsForMonth(bankAccount, dateString){
        const vm = this;
        const monthMoment = Vue.moment(dateString);
        return vm.assignments.filter((assignment) => {
            const isFromMonth = monthMoment.isSame(assignment.transaction.remote_transaction_date, 'month');
            const isForBankAccount = assignment.bank_account_id === bankAccount.id;
            return isFromMonth && isForBankAccount;
        });
    }

    function fetchAssignments(){
        const vm = this;
        const query = {
            start_date: vm.startDate,
            end_date: vm.endDate,
            mini: true,
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
