import AccountSchedulesPanel from './components/account-schedules-panel/account-schedules-panel.vue';
import MoneyMoverModal from './components/money-mover-modal/money-mover-modal.vue';
import LinkAccountsModal from './components/link-accounts-modal/link-accounts-modal.vue';
import AccountBalancesPanel from './components/account-balances-panel/account-balances-panel.vue';
import CalculatorPopover from 'vue_root/components/calculator-popover/calculator-popover';
import formatAsDecimal from 'vue_root/mixins/formatAsDecimal.mixin.js';
import sortBankAccounts from 'vue_root/mixins/sortBankAccounts.mixin.js';
import TransactionMoverModal from './components/transaction-mover-modal/transaction-mover-modal.vue';

export default {
    components: {
        AccountSchedulesPanel,
        MoneyMoverModal,
        LinkAccountsModal,
        AccountBalancesPanel,
        CalculatorPopover,
        TransactionMoverModal
    },
    mixins: [formatAsDecimal, sortBankAccounts],
    created: created,
    data: data,
    filters: getFilters(),
    computed: getComputed(),
    watch: getWatchers(),
    methods: getMethods()
};

function created(){
    const vm = this;
    // reload user data to update most_recent_defense
    vm.isInitializingView = true;
    Promise.all([
        vm.$store.dispatch('user/GET_USER'),
        vm.getSpendingAccountOverview(),
        vm.getCreditCardOverviews(),
        vm.initializeBankAccounts()
    ]).finally(resetLoadingState);
    function resetLoadingState(){
        vm.isInitializingView = false;
    }
}

function data(){
    const vm = this;
    return {
        spending_account: {
            balance_available: 0,
            spent_after_most_recent_defense: 0,
            count_of_unassigned_transactions: 0
        },
        creditCards: [],
        errorMessages: [],
        loadingSpendingAccountOverview: false,
        isLoadingCreditCards: false,
        isUpdatingLinkedAccountBalances: false,
        isInitializingView: false,
        loadingCountOfUnassignedCreditCardTransactions: false,
        initializingBankAccounts: true,
        userAllocations: [],
        calculatedAllocations: [],
        finalAllocations: [],
        chart_event_handlers: vm.getChartEventHandlers(),
        chart_options: {
            donut: true,
            donutSolid: true,
            donutWidth: 115,
            ignoreEmptyValues: true,
            showLabel: true,
            startAngle: 105,
        },
        chart_data: {
            labels: [],
            series: [],
        },
        activeChartSliceId: null,
        savings_access_credit_card: {},
        income_account: {},
        payoff_account: {},
        allocation_accounts: [],
        isTotalUnallocatedOutdated: false,
        isNegativeBalanceCheckOutdated: false,
        isChartDrawn: false,
        isIOS: window.appEnv.clientPlatform === 'ios',
    };
}

function getFilters(){
    return {
        pluralize: function(count, word){
            if(count === 1){
                return count + ' ' + word;
            } else {
                return count + ' ' + word + 's';
            }
        }
    };
}

function getComputed(){
    let cachedTotalUnallocated = 0;
    return {
        available_account_balances,
        currentAccount,
        daysAfterLastDefense,
        daysUntilYouDefendYourMoney,
        filteredAccounts,
        getSpentWidth,
        getAvailableWidth,
        getTodayOffset,
        spendingAccountHasInstitutionId,
        spendingAccountBalanceAtDefense,
        userSpendingOnTrack,
        dailyAllowedSpending,
        daysBetweenDefenses,
        popoverTriggers,
        finicityRefreshStatus,
        defenseStartDate,
        balancePanelAccounts,
        ccTrackerAccounts,
        allocationAccounts,
        chartAccountList,
        totalUnallocated,
        getTotalCheckingAndSavings,
        untransferredAllocationTotal,
        allocations,
        internalAllocations,
        sortedUserAllocations,
        totalCreditCardBalance,
        dymCCBalance,
        ccPayoffAvailableBalance
    };

    function totalCreditCardBalance(){
        const vm = this;
        var balance = vm.creditCards.reduce((accumulator, account) => accumulator.plus(account.balance_current).toDecimalPlaces(2), new Decimal(0));
        return balance.toDecimalPlaces(2).toNumber();
    }

    function dymCCBalance(){
        const vm = this;
        var difference = new Decimal(vm.totalCreditCardBalance).plus(vm.ccPayoffAvailableBalance).toDecimalPlaces(2).toNumber();
        return difference;
    }

    function ccPayoffAvailableBalance(){
        const vm = this;
        return new Decimal(vm.payoff_account.balance_current || 0).plus(vm.payoff_account.allocation_balance_adjustment || 0).plus(vm.payoff_account.assignment_balance_adjustment || 0).toDecimalPlaces(2).toNumber();
    }

    function available_account_balances(){
        return this.$store.state.authorized.bankAccounts.bankAccounts;
    }

    function currentAccount(){
        return this.$store.state.guest.user.user.current_account;
    }

    function daysAfterLastDefense(){
        const vm = this;
        const now = Vue.moment.utc().endOf('day');
        const mostRecentDefenseAt = Vue.moment.utc(vm.defenseStartDate).startOf('day');
        const daysSinceDefense = now.diff(mostRecentDefenseAt, 'days');
        return daysSinceDefense;
    }

    function daysUntilYouDefendYourMoney(){
        const vm = this;
        const now = Vue.moment.utc().startOf('day');
        const nextDefenseDate = Vue.moment.utc(vm.defenseStartDate).add(vm.daysBetweenDefenses, 'days').startOf('day');
        const daysRemainingInDefense = nextDefenseDate.diff(now, 'days');
        return daysRemainingInDefense;
    }

    function filteredAccounts(){
        const vm = this;
        const hiddenPurposes = [
            'none',
            'primary_checking',
            'primary_savings',
            'unassigned'
        ];
        var accounts = vm.available_account_balances
            .filter(({ purpose }) => !hiddenPurposes.includes(purpose))
            .sort((a, b) => a.slug === 'income_deposit' ? -1 : 0);
        return accounts;
    }

    function getSpentWidth(){
        const vm = this;
        if(vm.spendingAccountBalanceAtDefense > 0){
            return 100 * (vm.spending_account.spent_after_most_recent_defense / vm.spendingAccountBalanceAtDefense) + '%';
        } else {
            return '100%';
        }
    }

    function getAvailableWidth(){
        const vm = this;
        return 100 - parseFloat(vm.getSpentWidth) + '%';
    }

    function getTodayOffset(){
        const vm = this;
        var boundedDaysAfterLastDefense = Math.min(Math.max(vm.daysAfterLastDefense, 1), vm.daysBetweenDefenses);
        var todayOffset = boundedDaysAfterLastDefense / vm.daysBetweenDefenses;
        return todayOffset;
    }

    function spendingAccountHasInstitutionId(){
        const vm = this;
        return !!vm.spending_account.institution_account_id;
    }

    function spendingAccountBalanceAtDefense(){
        const vm = this;
        let balanceAtDefense = new Decimal(vm.spending_account.balance_available || 0).plus(vm.spending_account.spent_after_most_recent_defense || 0).toDecimalPlaces(2).toNumber();
        if(vm.currentAccount.most_recent_defense){
            balanceAtDefense = vm.currentAccount.most_recent_defense.everyday_checking_starting_balance;
            const spendingAccountDefenseAllocation = vm.currentAccount.most_recent_defense.allocation.find(allocation => allocation.bank_account_id === vm.spending_account.id || allocation.transferred_from_id === vm.spending_account.id);
            if(spendingAccountDefenseAllocation){
                if(spendingAccountDefenseAllocation.transferred_from_id === vm.spending_account.id){
                    balanceAtDefense = new Decimal(balanceAtDefense).minus(spendingAccountDefenseAllocation.amount).toDecimalPlaces(2).toNumber();
                } else {
                    balanceAtDefense = new Decimal(balanceAtDefense).plus(spendingAccountDefenseAllocation.amount).toDecimalPlaces(2).toNumber();
                }
            }
        }
        return balanceAtDefense;
    }

    function userSpendingOnTrack(){
        const vm = this;
        const daysSinceDefenseIncludingToday = vm.daysAfterLastDefense + 1;
        const originalDailyAllowedSpending = new Decimal(vm.spendingAccountBalanceAtDefense || 0).dividedBy(vm.daysBetweenDefenses || 1).toDecimalPlaces(2).toNumber();
        const onTrackSpendingAmount = new Decimal(originalDailyAllowedSpending).times(daysSinceDefenseIncludingToday).toDecimalPlaces(2).toNumber();
        return (vm.spending_account.spent_after_most_recent_defense <= onTrackSpendingAmount);
    }

    function dailyAllowedSpending(){
        const vm = this;
        return new Decimal(vm.spending_account.balance_available || 0).dividedBy(vm.daysUntilYouDefendYourMoney || 1).toDecimalPlaces(2).toNumber();
    }

    function daysBetweenDefenses(){
        const vm = this;
        let defenseInterval = 30;
        if(vm.currentAccount.projected_defenses_per_month === 2){
            defenseInterval = 15;
        }
        return defenseInterval;
    }

    function popoverTriggers(){
        const clientPlatform = window.appEnv.clientPlatform || 'web';
        const triggers = clientPlatform === 'web' ? 'hover click blur' : 'click blur';
        return triggers;
    }

    function finicityRefreshStatus(){
        const vm = this;
        return vm.$store.state.authorized.finicityRefreshStatus;
    }

    function defenseStartDate(){
        const vm = this;
        const hasRecentDefense = !!vm.currentAccount.most_recent_defense;
        const mostRecentDefenseAt = hasRecentDefense ? Vue.moment.utc(vm.currentAccount.most_recent_defense.created_at).startOf('day') : Vue.moment.utc(vm.currentAccount.created_at).startOf('day');
        return mostRecentDefenseAt.format('YYYY-MM-DD');
    }

    function balancePanelAccounts(){
        const vm = this;
        return vm.filteredAccounts.filter(bankAccount => {
            return bankAccount.type !== 'credit';
        }).sort(vm.byModifiedStoreOrder);
    }

    function ccTrackerAccounts(){
        return this.filteredAccounts.filter(bankAccount => {
            return bankAccount.type === 'credit' || bankAccount.slug === 'cc_payoff';
        }).sort((a, b) => {
            if(a.type === 'credit' && b.type === 'credit'){
                return 0;
            } else {
                return a.type === 'credit' ? -1 : 1;
            }
        });
    }

    function allocationAccounts(){
        return this.filteredAccounts.filter(bankAccount => {
            return bankAccount.type !== 'credit' && bankAccount.slug !== 'cc_payoff' && bankAccount.slug !== 'income_deposit';
        });
    }

    function chartAccountList(){
        const vm = this;
        return [vm.income_account, vm.allocation_accounts].flat();
    }

    function totalUnallocated(){
        const vm = this;
        if(vm.isTotalUnallocatedOutdated){
            // isTotalUnallocatedOutdated is used to only update this property on blur of new allocation amounts
            const incomeBalance = new Decimal(vm.income_account.balance_available || 0);
            const formattedTotal = incomeBalance.minus(vm.untransferredAllocationTotal).toDecimalPlaces(2).toNumber();
            cachedTotalUnallocated = formattedTotal;
            vm.isTotalUnallocatedOutdated = false;
            return formattedTotal;
        } else {
            return vm.parseDecimal(cachedTotalUnallocated);
        }
    }
    function getTotalCheckingAndSavings(){
        const vm = this;
        const allocationAccounts = vm.allocation_accounts;
        const incomeBalance = new Decimal(vm.income_account.balance_available || 0);
        var totalBalance = allocationAccounts.reduce((accumulator, bankAccount) => {
            return accumulator.plus(bankAccount.balance_available).toDecimalPlaces(2);
        }, incomeBalance);

        if(vm.payoff_account){
            const payoffAcountBalance = Math.max(0, vm.payoff_account.balance_available);
            totalBalance = totalBalance.plus(payoffAcountBalance).toDecimalPlaces(2);
        }

        return totalBalance.toNumber();
    }
    function untransferredAllocationTotal(){
        const vm = this;
        const allocationsFromIncome = vm.allocations.filter(({ transferred_from_id }) => transferred_from_id === vm.income_account.id);
        const pendingTotal = allocationsFromIncome.reduce((accumulator, allocation) => {
            return allocation.transferred ? accumulator : accumulator.plus(allocation.amount).toDecimalPlaces(2);
        }, new Decimal(0));
        return pendingTotal.toNumber();
    }
    function allocations(){
        const vm = this;
        return !vm.transferView ? vm.sortedUserAllocations : vm.finalAllocations.filter(allocation => {
            return allocation.metadata.allocationType !== 'internal' &&
                (vm.parseNumber(allocation.amount) > 0 || allocation.metadata.isPlaceholderTransfer);
        });
    }
    function internalAllocations(){
        const vm = this;
        return vm.finalAllocations.filter(({ metadata }) => metadata.allocationType === 'internal');
    }
    function sortedUserAllocations(){
        const vm = this;
        return vm.userAllocations.sort(byBankAccountOrder);
        function byBankAccountOrder(a, b){
            const aIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === a.bank_account.id);
            const bIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === b.bank_account.id);
            let position = 0;
            if(aIndex >= 0 && bIndex >= 0){
                position = aIndex < bIndex ? -1 : 1;
            }
            return position;
        }
    }
}

function getWatchers(){
    return {
        finicityRefreshStatus(newStatus, oldStatus){
            const vm = this;
            const refreshCompleted = oldStatus === 'pending' && newStatus !== 'pending';
            if(refreshCompleted){
                vm.getSpendingAccountOverview();
                vm.getCreditCardOverviews();
            }
        }
    };
}

function getMethods(){
    return {
        getSpendingAccountOverview,
        updateBankAccountBalance,
        refreshBankAccountsOverview,
        getAvailableAccountBalances,
        getCreditCardOverviews,
        initializeBankAccounts,
        updateChart,
        resetAllocations,
        createAllocation,
        getChartEventHandlers,
        refreshAccountBalances,
    };

    function getSpendingAccountOverview(){
        const vm = this;
        vm.loadingSpendingAccountOverview = true;
        Vue.appApi().authorized().bankAccount().spendingAccount().getOverview()
            .then(onLoadSpendingAccountSuccess)
            .catch(onLoadSpendingAccountFailure);

        function onLoadSpendingAccountSuccess(response){
            if(response.data instanceof Object){
                vm.spending_account = response.data;
            }
            vm.loadingSpendingAccountOverview = false;
        }

        function onLoadSpendingAccountFailure(error){
            if(error.appMessage){
                vm.errorMessages.push(error.appMessage);
            }
            vm.loadingSpendingAccountOverview = false;
        }
    }

    function updateBankAccountBalance(updatedBankAccount){
        const vm = this;
        const bankAccount = vm.available_account_balances.find(bankAccount => bankAccount.id === updatedBankAccount.id);
        const isBalanceOverrideRemoved = !updatedBankAccount.is_balance_overridden && bankAccount.is_balance_overridden;
        if(isBalanceOverrideRemoved){
            vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS');
        }
        bankAccount.balance_available = updatedBankAccount.balance_available;
        bankAccount.balance_current = updatedBankAccount.balance_current;
        bankAccount.allocation_balance_adjustment = updatedBankAccount.allocation_balance_adjustment;
        bankAccount.assignment_balance_adjustment = updatedBankAccount.assignment_balance_adjustment;
        bankAccount.is_balance_overridden = updatedBankAccount.is_balance_overridden;
        if(bankAccount.slug === 'everyday_checking'){
            vm.getSpendingAccountOverview();
        }
    }

    function refreshBankAccountsOverview(){
        const vm = this;
        vm.getSpendingAccountOverview();
        vm.getCreditCardOverviews();
    }

    function getAvailableAccountBalances(){
        const vm = this;
        return vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS')
            .catch(onLoadBankAccountFailure);

        function onLoadBankAccountFailure(error){
            if(error.appMessage){
                vm.errorMessages.push(error.appMessage);
            }
        }
    }

    function getCreditCardOverviews(){
        const vm = this;
        vm.isLoadingCreditCards = true;
        return Vue.appApi().authorized().bankAccount().getCreditCardOverviews().then(setCreditCards).catch(displayError).finally(resetLoadingState);

        function setCreditCards(response){
            vm.creditCards = response.data;
        }

        function displayError(error){
            if(error.appMessage){
                vm.errorMessages.push(error.appMessage);
            }
        }

        function resetLoadingState(){
            vm.isLoadingCreditCards = false;
        }
    }

    function initializeBankAccounts(){
        const vm = this;
        vm.initializingBankAccounts = true;

        const fetchAssignableAccountsPromise = Vue.appApi().authorized().bankAccount().getAllocationAccounts().then(setAllocationAccounts);
        const fetchIncomeAccountPromise = Vue.appApi().authorized().bankAccount().getIncomeAccountOverview().then(setIncomeAccount);
        const fetchPayoffAccountPromise = Vue.appApi().authorized().bankAccount().getCCPayoffAccountOverview().then(setPayoffAccount);

        return Promise.all([
            fetchAssignableAccountsPromise,
            fetchIncomeAccountPromise,
            fetchPayoffAccountPromise,
        ]).then(initializeView).catch(displayError);

        function setAllocationAccounts(response){
            response.data.forEach(updateOrAddAllocationAccount);
            function updateOrAddAllocationAccount(bankAccount){
                const accountIndex = vm.allocation_accounts.findIndex(({ id }) => id === bankAccount.id);
                if(accountIndex >= 0){
                    Object.assign(vm.allocation_accounts[accountIndex], bankAccount);
                } else {
                    vm.allocation_accounts.push(bankAccount);
                }
            }
        }
        function setIncomeAccount(response){
            vm.income_account = response.data;
            vm.isTotalUnallocatedOutdated = true;
        }
        function setPayoffAccount(response){
            vm.payoff_account = response.data;
        }

        function displayError(response){
            if(response.appMessage){
                vm.errorMessages.push(response.appMessage);
            }
        }
        function initializeView(){
            vm.resetAllocations();
            vm.updateChart();
            vm.isTotalUnallocatedOutdated = true;
            vm.isNegativeBalanceCheckOutdated = true;
            vm.initializingBankAccounts = false;
            showLinkAccountModal();
        }

        function showLinkAccountModal(){
            const linkedAccounts = vm.available_account_balances.filter(bankAccount => {
                const bankIsLinked = bankAccount.institution_account_id !== null;
                return bankIsLinked;
            });
            const hasNoLinkedAccounts = linkedAccounts.length === 0;
            if(hasNoLinkedAccounts){
                vm.$refs.linkAccountsModal.openModal();
            }
        }
    }

    function resetAllocations(){
        const vm = this;
        vm.userAllocations = vm.allocation_accounts.map(createUserAllocation);
        function createUserAllocation(bankAccount){
            const userAllocation = vm.createAllocation(vm.income_account, bankAccount);
            userAllocation.incomeToPayoffAllocation = vm.createAllocation(vm.income_account, vm.payoff_account);
            userAllocation.incomeToPayoffAllocation.userAllocation = userAllocation;
            userAllocation.incomeToSavingsAllocation = vm.createAllocation(vm.income_account, bankAccount);
            userAllocation.incomeToSavingsAllocation.userAllocation = userAllocation;
            userAllocation.savingsToPayoffAllocation = vm.createAllocation(bankAccount, vm.payoff_account);
            userAllocation.savingsToPayoffAllocation.userAllocation = userAllocation;
            return userAllocation;
        }
    }
    function createAllocation(fromAccount, toAccount){
        const vm = this;
        fromAccount = cloneObject(fromAccount);
        toAccount = cloneObject(toAccount);
        const storedFromAccount = vm.$store.getters['authorized/bankAccounts/getBankAccountById'](fromAccount.id);
        const storedToAccount = vm.$store.getters['authorized/bankAccounts/getBankAccountById'](toAccount.id);
        if(storedFromAccount){
            fromAccount.institution_account = cloneObject(storedFromAccount.institution_account);
        }
        if(storedToAccount){
            toAccount.institution_account = cloneObject(storedToAccount.institution_account);
        }
        return {
            bank_account: toAccount,
            bank_account_id: toAccount.id,
            transferred: false,
            cleared_out: false,
            transferred_from_id: fromAccount.id,
            from_account: fromAccount,
            isTransferring: false,
            editable: true,
            amount: 0,
            allocatedAssignments: [],
            child_allocations: [],
            hasValidationError: false
        };
    }
    function getChartEventHandlers(){
        const vm = this;
        return [{
            event: 'draw',
            fn(svgObject){
                // keeps the popover working on chart redraws
                if(svgObject.type === 'slice'){
                    const targetId = svgObject.meta.target;
                    if(targetId){
                        svgObject.element._node.id = targetId;
                        vm.isChartDrawn = false;
                        Vue.nextTick(() => {
                            vm.isChartDrawn = true;
                        });
                    }
                }
            }
        }];
    }
    function updateChart(){
        const vm = this;
        const chart_data = {
            labels: [],
            series: [],
        };
        const accounts = vm.chartAccountList;
        const allocations = vm.userAllocations;
        accounts.forEach(addAccountToChartData);
        addPayoffAccountToChartData();
        vm.chart_data = chart_data;

        function addAccountToChartData(bankAccount){
            if(bankAccount !== null){
                var allocatedAmount = 0;
                var totalAfterDefense = 0;
                var className = `${bankAccount.color}-slice`;

                if(bankAccount.type === 'income'){
                    allocatedAmount = new Decimal(vm.untransferredAllocationTotal).toDecimalPlaces(2).toNumber();
                    totalAfterDefense = vm.totalUnallocated || 0;
                } else {
                    const allocation = allocations.find(allocation => allocation.bank_account_id === bankAccount.id);
                    allocation.bank_account = bankAccount; //ensure reference is maintained when accounts are updated from server
                    allocatedAmount = 0;
                    totalAfterDefense = vm.parseNumber(bankAccount.balance_available);
                    if((vm.parseNumber(allocation.amount) > 0 || bankAccount.untransferred_assignments.length) && !allocation.transferred){
                        allocatedAmount += vm.parseNumber(allocation.amount);
                        totalAfterDefense += vm.parseNumber(allocation.amount);
                    }
                }
                const percentOfTotal = totalAfterDefense / vm.getTotalCheckingAndSavings * 100;
                const label = percentOfTotal > 7 ? vm.formatAsDecimal(totalAfterDefense, 'currency') : ' ';
                const seriesItem = {
                    className,
                    value: Math.max(0, totalAfterDefense),
                    meta: {
                        id: bankAccount.id,
                        target: `ct-${bankAccount.id}`,
                        defendedBankAccount: JSON.parse(JSON.stringify(bankAccount))
                    }
                };
                seriesItem.meta.defendedBankAccount.balance_available = totalAfterDefense;
                seriesItem.meta.defendedBankAccount.allocatedAmount = allocatedAmount;
                seriesItem.meta.defendedBankAccount.allocation_balance_adjustment = new Decimal(bankAccount.allocation_balance_adjustment || 0).plus(allocatedAmount).toDecimalPlaces(2).toNumber();
                bankAccount.defendedCopy = seriesItem.meta.defendedBankAccount;

                chart_data.series.push(seriesItem);
                chart_data.labels.push(label);
            }
        }

        function addPayoffAccountToChartData(){
            chart_data.series.push({
                className: 'black-slice',
                value: Math.max(0, vm.payoff_account.balance_available),
                meta: {
                    id: 'payoff',
                    target: 'ct-payoff',
                    defendedBankAccount: JSON.parse(JSON.stringify(vm.payoff_account))
                }
            });

            const percentOfTotal = vm.payoff_account.balance_available / vm.getTotalCheckingAndSavings * 100;
            const label = percentOfTotal > 7 ? vm.formatAsDecimal(vm.payoff_account.balance_available, 'currency') : ' ';
            chart_data.labels.push(label);
        }
    }

    function refreshAccountBalances(affectedAccounts = []){
        const vm = this;
        vm.getAvailableAccountBalances();
        vm.initializeBankAccounts();

        vm.$refs.accountBalancesPanel.reloadTransactionHistory(affectedAccounts);
    }
}

function cloneObject(object){
    return JSON.parse(JSON.stringify(object));
}
