import BalanceChart from './balance-chart.js';
import SassVariablesMixin from 'vue_root/mixins/sass-variables.mixin.js';

export default {
    components: {
        BalanceChart
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
        chartjsData(){
            const vm = this;
            const balances = vm.selectedAccount.value === 'all' ? vm.allMonthlyBalances : vm.monthlyBankBalances[vm.selectedAccount.value.id];
            const data = {
                labels: vm.chartLabels,
                datasets: [{
                    backgroundColor: vm.getAccountColor(vm.selectedAccount.value),
                    data: balances
                }]
            };
            return data;
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
        monthlyBankBalances(){
            const vm = this;
            return vm.bankAccounts.reduce((accumulator, bankAccount) => {
                accumulator[bankAccount.id] = vm.calculateMonthlyBankBalances(bankAccount);
                return accumulator;
            }, {});
        },
        allMonthlyBalances(){
            const vm = this;
            return vm.displayedMonths.map((dateString, index) => {
                return Object.values(vm.monthlyBankBalances).reduce((sum, balanceArray) => {
                    return sum.plus(balanceArray[index]);
                }, new Decimal(0)).toDecimalPlaces(2).toNumber();
            });
        }
    };
}

function created(){
    const vm = this;
    vm.selectedAccount = vm.accountOptions[0];
}

function getMethods(){
    return {
        calculateMonthlyBankBalances,
        getDefensesForMonth,
    };

    function calculateMonthlyBankBalances(bankAccount){
        const vm = this;
        const monthlyBalances = vm.displayedMonths.map(dateString => {
            return calculateAverageBalance(dateString, bankAccount);
        });
        return monthlyBalances;

        function calculateAverageBalance(dateString, bankAccount){
            const defenses = vm.getDefensesForMonth(dateString);
            const balances = defenses.map(defense => getBalanceForDefense(defense));
            const averageBalance = balances.length
                ? balances.reduce((sum, balance) => sum.plus(balance), new Decimal(0)).dividedBy(balances.length).toDecimalPlaces(2).toNumber()
                : 0;
            return averageBalance;

            function getBalanceForDefense(defense){
                let balance = 0;
                const hasBalanceSnapshot = defense.balance_snapshots && Object.keys(defense.balance_snapshots).includes(`${bankAccount.id}`);
                if(hasBalanceSnapshot){
                    const startingBalance = defense.balance_snapshots[`${bankAccount.id}`] || 0;
                    balance = (defense.allocations || []).reduce((sum, allocation) => {
                        if(allocation.from_account_id === bankAccount.id){
                            return sum.minus(allocation.amount);
                        } else if(allocation.bank_account_id === bankAccount.id){
                            return sum.plus(allocation.amount);
                        } else {
                            return sum;
                        }
                    }, new Decimal(startingBalance)).toDecimalPlaces(2).toNumber();
                }

                return balance;
            }
        }
    }

    function getDefensesForMonth(dateString){
        const vm = this;
        return vm.defenses.filter(defense => {
            return Vue.moment(dateString).isSame(defense.created_at, 'month');
        });
    }
}
