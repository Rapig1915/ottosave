import BalanceBar from './components/balance-bar/balance-bar.vue';
import TransferWarningModal from './components/transfer-warning-modal/transfer-warning-modal.vue';
import LeavingOrganizeModal from './components/leaving-organize-modal/leaving-organize-modal.vue';
import PartialTransferModal from './components/partial-transfer-modal/partial-transfer-modal.vue';
import BankAccountLink from './components/bank-account-link/bank-account-link.vue';
import formatAsDecimal from 'vue_root/mixins/formatAsDecimal.mixin.js';
import AccountScheduleModal from 'vue_root/components/account-schedule-modal/account-schedule-modal';
import CalculatorPopover from 'vue_root/components/calculator-popover/calculator-popover';
import sortBankAccounts from 'vue_root/mixins/sortBankAccounts.mixin.js';

export default {
    components: {
        CalculatorPopover,
        BalanceBar,
        TransferWarningModal,
        LeavingOrganizeModal,
        PartialTransferModal,
        BankAccountLink,
        AccountScheduleModal
    },
    mixins: [formatAsDecimal, sortBankAccounts],
    data: data,
    computed: computed(),
    watch: getWatchers(),
    created: created,
    methods: getMethods(),
    beforeRouteLeave: confirmNavigation,
    beforeRouteUpdate,
    destroyed
};

function data(){
    const vm = this;
    return {
        initializingBankAccounts: true,
        apiErrors: [],
        userAllocations: [],
        calculatedAllocations: [],
        finalAllocations: [],
        calculatingAllocations: false,
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
        currentDefense: {
            account: {}
        },
        savings_access_credit_card: {},
        income_account: {},
        payoff_account: {},
        creditCards: [],
        allocation_accounts: [],
        transferView: false,
        showSuccessMessage: false,
        scheduleLinkPrefix: '#open-',
        chart_event_handlers: vm.getChartEventHandlers(),
        isTotalUnallocatedOutdated: false,
        isNegativeBalanceCheckOutdated: false,
        defenseCreationPromise: false,
        defenseId: null,
        accountScheduleTotals: {},
        allocationErrorMessage: '',
        isCompletingInternalTransfers: false,
        isChartDrawn: false
    };
}

function computed(){
    let cachedTotalUnallocated = 0;
    return {
        allocations(){
            const vm = this;
            return !vm.transferView ? vm.sortedUserAllocations : vm.finalAllocations.filter(allocation => {
                return allocation.metadata.allocationType !== 'internal' &&
                    (vm.parseNumber(allocation.amount) > 0 || allocation.metadata.isPlaceholderTransfer);
            });
        },
        internalAllocations(){
            const vm = this;
            return vm.finalAllocations.filter(({ metadata }) => metadata.allocationType === 'internal');
        },
        sortedUserAllocations(){
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
        },
        chartAccountList(){
            const vm = this;
            return [vm.income_account, vm.allocation_accounts].flat();
        },
        allBankAccounts(){
            const vm = this;
            const creditCards = {
                color: 'primary',
                balance_current: vm.creditCards.reduce((accumulator, creditCard) => accumulator.plus(creditCard.balance_current), new Decimal(0)).toDecimalPlaces(2).toNumber(),
                type: 'credit',
                name: 'Credit card balances',
                icon: 'credit-card',
                originalAccounts: vm.creditCards
            };
            return [vm.income_account, vm.allocation_accounts, vm.payoff_account, creditCards].flat().filter(account => account).sort(vm.byModifiedStoreOrder);
        },
        incomeLeftToAllocateDisplay(){
            // the value on the left side of the page
            const vm = this;
            return vm.formatAsDecimal(Math.max(vm.totalUnallocated, 0));
        },
        totalUnallocated(){
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
        },
        getTotalCheckingAndSavings(){
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
        },
        untransferredAllocationTotal(){
            const vm = this;
            const allocationsFromIncome = vm.allocations.filter(({ transferred_from_id }) => transferred_from_id === vm.income_account.id);
            const pendingTotal = allocationsFromIncome.reduce((accumulator, allocation) => {
                return allocation.transferred ? accumulator : accumulator.plus(allocation.amount).toDecimalPlaces(2);
            }, new Decimal(0));
            return pendingTotal.toNumber();
        },
        showCCPayoffPrompt(){
            const vm = this;
            return !vm.transferView && vm.creditCards.some(creditCard => creditCard.count_of_unassigned_transactions > 0);
        },
        hasNegativeBalance(){
            const vm = this;
            if(vm.isNegativeBalanceCheckOutdated){
                vm.isNegativeBalanceCheckOutdated = false;
            }
            return vm.userAllocations.some(checkForNegativeBalance);
            function checkForNegativeBalance(allocation){
                return allocation.bank_account.defendedCopy.balance_available < 0;
            }
        },
        calculatorPopoverTriggers(){
            const clientPlatform = window.appEnv.clientPlatform || 'web';
            const triggers = clientPlatform === 'web' ? 'click blur' : 'click blur';
            return triggers;
        },
        someAllocationsTransferred(){
            const vm = this;
            return vm.calculatedAllocations.filter(({ amount }) => amount > 0).some(testAllocationTransferred);
        },
        allAllocationsTransferred(){
            const vm = this;
            return vm.calculatedAllocations.filter(({ amount }) => amount > 0).every(testAllocationTransferred);
        },
        areTransfersPartiallyFinished(){
            const vm = this;
            return vm.someAllocationsTransferred && !vm.allAllocationsTransferred;
        }
    };
    function testAllocationTransferred(allocation){
        return allocation.transferred;
    }
}

function getWatchers(){
    return {
        $route
    };
    function $route(newRoute, oldRoute){
        const vm = this;
        if(newRoute.name === 'transfer'){
            vm.beginTransfer();
        } else if(vm.areTransfersPartiallyFinished){
            vm.$refs.partialTransferModal.displayModal().then(resetOrganizeView).catch(navigationCancelled);
        } else if(vm.allAllocationsTransferred){
            resetOrganizeView();
        } else {
            vm.transferView = false;
            vm.isChartDrawn = false;
        }

        function resetOrganizeView(){
            vm.initializeBankAccounts();
            vm.transferView = false;
            vm.isChartDrawn = false;
        }
        function navigationCancelled(){
            vm.$router.replace({ name: oldRoute.name });
        }
    }
}

function created(){
    const vm = this;
    let initStorePromise = Promise.resolve();
    if(!vm.$store.state.authorized.bankAccounts.bankAccounts.length){
        initStorePromise = vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
    }
    initStorePromise.then(vm.initializeBankAccounts).then(setupView);

    function setupView(){
        vm.transferView = vm.$route.name === 'transfer';
        if(vm.$route.name === 'transfer' && !vm.hasNegativeBalance){
            vm.beginTransfer();
        } else if(vm.transferView){
            vm.handleInvalidTransferNavigation();
        }
    }
}
function getMethods(){
    return {
        initializeBankAccounts,
        navigateToTransferView,
        resetAllocations,
        createAllocation,
        beginTransfer,
        transferAllocation,
        printTransferList,
        allocateAmount,
        validateNewBalance,
        cachePreviousValue,
        updateChart,
        openWindow,
        getChartEventHandlers,
        handleInvalidTransferNavigation,
        updateAccountScheduleTotals,
        makeAllInternalTransfers
    };

    function initializeBankAccounts(){
        const vm = this;
        vm.initializingBankAccounts = true;

        const fetchAssignableAccountsPromise = Vue.appApi().authorized().bankAccount().getAllocationAccounts().then(setAllocationAccounts);
        const fetchIncomeAccountPromise = Vue.appApi().authorized().bankAccount().getIncomeAccountOverview().then(setIncomeAccount);
        const fetchPayoffAccountPromise = Vue.appApi().authorized().bankAccount().getCCPayoffAccountOverview().then(setPayoffAccount);
        const fetchCreditCards = Vue.appApi().authorized().bankAccount().loadWithInstitutionAccounts({ type: 'credit' }).then(setCreditCards);

        return Promise.all([
            fetchAssignableAccountsPromise,
            fetchIncomeAccountPromise,
            fetchPayoffAccountPromise,
            fetchCreditCards
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

        function setCreditCards(response){
            vm.creditCards = response.data;
        }

        function displayError(response){
            if(response.appMessage){
                vm.apiErrors.push(response.appMessage);
            }
        }
        function initializeView(){
            vm.resetAllocations();
            vm.updateChart();
            vm.isTotalUnallocatedOutdated = true;
            vm.isNegativeBalanceCheckOutdated = true;
            vm.initializingBankAccounts = false;
        }
    }

    function navigateToTransferView(){
        const vm = this;
        vm.$router.push({ name: 'transfer' });
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

    function beginTransfer(){
        const vm = this;
        vm.calculatingAllocations = true;
        var calculateAllocationsPromise = new Promise(calculateAllocations);
        transitionToTransferView();

        function calculateAllocations(resolve, reject){
            try {
                vm.userAllocations.forEach(calculateAllocationAmounts);
                vm.calculatedAllocations = getCalculatedAllocations();
                vm.finalAllocations = getFinalAllocations();
                resolve();
            } catch(e){
                reject(e);
            }
            function calculateAllocationAmounts(userAllocation){
                if(!userAllocation.editable){
                    return;
                }
                const assignmentTotal = userAllocation.bank_account.untransferred_assignments.reduce(calculateAssignmentTotal, 0);
                if(assignmentTotal === 0){
                    userAllocation.incomeToSavingsAllocation.amount = userAllocation.amount;
                    userAllocation.incomeToPayoffAllocation.amount = 0;
                    userAllocation.savingsToPayoffAllocation.amount = 0;
                } else if(assignmentTotal < userAllocation.amount){
                    const amountIncomeToPayoff = assignmentTotal;
                    const amountIncomeToSavings = new Decimal(userAllocation.amount).minus(amountIncomeToPayoff).toDecimalPlaces(2).toNumber();
                    userAllocation.incomeToPayoffAllocation.amount = amountIncomeToPayoff;
                    userAllocation.incomeToSavingsAllocation.amount = amountIncomeToSavings;
                    userAllocation.savingsToPayoffAllocation.amount = 0;
                } else {
                    const amountFromIncomeToPayoff = userAllocation.amount;
                    const amountFromSavingsToPayoff = new Decimal(assignmentTotal).minus(amountFromIncomeToPayoff).toDecimalPlaces(2).toNumber();
                    userAllocation.incomeToPayoffAllocation.amount = amountFromIncomeToPayoff;
                    userAllocation.savingsToPayoffAllocation.amount = amountFromSavingsToPayoff;
                    userAllocation.incomeToSavingsAllocation.amount = 0;
                }
                userAllocation.assignmentTotal = assignmentTotal;
                function calculateAssignmentTotal(accumulator, assignment){
                    return new Decimal(assignment.transaction.amount || 0).minus(assignment.allocated_amount).plus(accumulator).toDecimalPlaces(2).toNumber();
                }
            }
            function getCalculatedAllocations(){
                const incomeToPayoffAllocation = vm.createAllocation(vm.income_account, vm.payoff_account);
                const untransferredIncomeToPayoffAllocations = vm.userAllocations.map(userAllocation => userAllocation.incomeToPayoffAllocation).filter(({ transferred }) => !transferred);
                incomeToPayoffAllocation.amount = untransferredIncomeToPayoffAllocations.reduce((accumulator, allocation) => accumulator.plus(allocation.amount).toDecimalPlaces(2), new Decimal(0)).toNumber();
                const savingsAllocations = vm.userAllocations.map(allocation => [allocation.incomeToSavingsAllocation, allocation.savingsToPayoffAllocation]).flat();
                const calculatedAllocations = [savingsAllocations, incomeToPayoffAllocation].flat();
                calculatedAllocations.forEach(setCalculatedMetadata);
                return calculatedAllocations;

                function setCalculatedMetadata(calculatedAllocation){
                    const isIDAtoCCPayoff = calculatedAllocation.from_account.slug === 'income_deposit' && calculatedAllocation.bank_account.slug === 'cc_payoff';
                    const isIDAtoSavings = calculatedAllocation.from_account.slug === 'income_deposit' && !isIDAtoCCPayoff;
                    const isSavingstoCCPayoff = calculatedAllocation.from_account.slug !== 'income_deposit' && calculatedAllocation.bank_account.slug === 'cc_payoff';
                    const metadata = {
                        allocationType: ''
                    };
                    if(isIDAtoCCPayoff){
                        metadata.allocationType = 'ida_to_cc_payoff';
                        metadata.assignmentTotal = vm.userAllocations.reduce(totalAllAssignments, 0).toDecimalPlaces(2).toNumber();
                        metadata.amountOfOtherTransfers = new Decimal(metadata.assignmentTotal).minus(calculatedAllocation.amount).toDecimalPlaces(2).toNumber();
                    } else if(isIDAtoSavings){
                        metadata.allocationType = 'ida_to_savings';
                        metadata.assignmentTotal = calculatedAllocation.userAllocation.assignmentTotal;
                        metadata.allocatedAmount = calculatedAllocation.userAllocation.amount;
                        metadata.isPlaceholderTransfer = calculatedAllocation.userAllocation.assignmentTotal > 0 && vm.parseNumber(calculatedAllocation.userAllocation.assignmentTotal) === vm.parseNumber(calculatedAllocation.userAllocation.amount);
                    } else if(isSavingstoCCPayoff){
                        metadata.allocationType = 'savings_to_cc_payoff';
                        metadata.assignmentTotal = calculatedAllocation.userAllocation.assignmentTotal;
                        metadata.allocatedAmount = calculatedAllocation.userAllocation.amount;
                    }
                    calculatedAllocation.metadata = metadata;

                    function totalAllAssignments(accumulator, userAllocation){
                        return new Decimal(userAllocation.assignmentTotal || 0).plus(accumulator);
                    }
                }
            }
            function getFinalAllocations(){
                const primaryCheckingAccount = vm.$store.state.authorized.bankAccounts.bankAccounts.find(({ slug }) => slug === 'primary_checking');
                const primarySavingsAccount = vm.$store.state.authorized.bankAccounts.bankAccounts.find(({ slug }) => slug === 'primary_savings');

                if(!primaryCheckingAccount && !primarySavingsAccount){
                    return vm.calculatedAllocations;
                }
                const finalAllocations = [];
                vm.calculatedAllocations.forEach(createFinalAllocation);
                return finalAllocations;

                function createFinalAllocation(allocation){
                    if(!(vm.parseNumber(allocation.amount) > 0) && !allocation.metadata.isPlaceholderTransfer){
                        return;
                    }
                    const hasNoParentAccounts = !allocation.bank_account.parent_bank_account_id && !allocation.from_account.parent_bank_account_id;
                    if(hasNoParentAccounts){
                        finalAllocations.push(allocation);
                    }
                    const toAccount = allocation.bank_account.parent_bank_account_id ? vm.$store.getters['authorized/bankAccounts/getBankAccountById'](allocation.bank_account.parent_bank_account_id) : allocation.bank_account;
                    const fromAccount = allocation.from_account.parent_bank_account_id ? vm.$store.getters['authorized/bankAccounts/getBankAccountById'](allocation.from_account.parent_bank_account_id) : allocation.from_account;
                    let finalAllocation = finalAllocations.find(({ transferred_from_id, bank_account_id }) => {
                        return (transferred_from_id === fromAccount.id && bank_account_id === toAccount.id) ||
                            (transferred_from_id === toAccount.id && bank_account_id === fromAccount.id);
                    });
                    if(!finalAllocation){
                        finalAllocation = vm.createAllocation(fromAccount, toAccount);
                        finalAllocation.metadata = {
                            assignmentTotal: 0,
                            allocatedAmount: 0,
                            isFromParentAccount: !!allocation.from_account.parent_bank_account_id,
                            isToParentAccount: !!allocation.bank_account.parent_bank_account_id
                        };
                        finalAllocation.amount = allocation.amount;
                        finalAllocations.push(finalAllocation);
                    } else {
                        let updatedAmount = new Decimal(finalAllocation.amount);
                        if(+finalAllocation.bank_account_id === +toAccount.id){
                            updatedAmount = updatedAmount.plus(allocation.amount);
                        } else {
                            updatedAmount = updatedAmount.minus(allocation.amount);
                        }
                        finalAllocation.amount = updatedAmount.toDecimalPlaces(2).toNumber();
                    }
                    if(finalAllocation.amount < 0){
                        const tempFromAccount = finalAllocation.from_account;
                        finalAllocation.from_account = finalAllocation.bank_account;
                        finalAllocation.bank_account = tempFromAccount;
                        finalAllocation.bank_account_id = finalAllocation.bank_account.id;
                        finalAllocation.transferred_from_id = finalAllocation.from_account.id;
                        finalAllocation.amount = Math.abs(finalAllocation.amount);
                    }
                    updateAllocationType();
                    preventDoubleTransfer();
                    finalAllocation.child_allocations.push(allocation);
                    finalAllocation.allocatedAssignments = finalAllocation.allocatedAssignments.concat(allocation.allocatedAssignments);
                    if(allocation.userAllocation){
                        finalAllocation.metadata.assignmentTotal = new Decimal(finalAllocation.metadata.assignmentTotal).plus(allocation.userAllocation.assignmentTotal || 0).toDecimalPlaces(2).toNumber();
                        finalAllocation.metadata.allocatedAmount = new Decimal(finalAllocation.metadata.allocatedAmount).plus(allocation.userAllocation.amount || 0).toDecimalPlaces(2).toNumber();
                    }
                    function updateAllocationType(){
                        const isInternalAllocation = toAccount.id === fromAccount.id || finalAllocation.amount === 0;
                        const isFromIDA = fromAccount.slug === 'income_deposit' || (fromAccount.id === vm.income_account.parent_bank_account_id);
                        const isToCCPayoff = toAccount.slug === 'cc_payoff' || (toAccount.id === vm.payoff_account.parent_bank_account_id);
                        const isIDAtoCCPayoff = !isInternalAllocation && isFromIDA && isToCCPayoff;
                        const isIDAtoSavings = !isInternalAllocation && isFromIDA && !isToCCPayoff;
                        const isSavingstoCCPayoff = !isInternalAllocation && !isFromIDA && isToCCPayoff;
                        if(isInternalAllocation){
                            finalAllocation.metadata.allocationType = 'internal';
                        } else if(isIDAtoCCPayoff){
                            finalAllocation.metadata.allocationType = 'ida_to_cc_payoff';
                        } else if(isIDAtoSavings){
                            finalAllocation.metadata.allocationType = 'ida_to_savings';
                        } else if(isSavingstoCCPayoff){
                            finalAllocation.metadata.allocationType = 'savings_to_cc_payoff';
                        }
                    }
                    function preventDoubleTransfer(){
                        if(!allocation.from_account.parent_bank_account_id){
                            allocation.transferred_from_id = null;
                        } else if(!allocation.bank_account.parent_bank_account_id){
                            allocation.bank_account_id = null;
                        }
                    }
                }
            }
        }

        function transitionToTransferView(){
            calculateAllocationsPromise.then(updateView).catch(displayError);
            function updateView(){
                vm.calculatingAllocations = false;
                vm.transferView = true;
                window.scrollTo(0, 0);
                vm.updateChart();
            }
            function displayError(){
                vm.calculatingAllocations = false;
                vm.apiErrors = ['Oops, something went wrong while calculating your allocations, please try again.'];
            }
        }
    }

    function transferAllocation(allocation, requestConfirmation){
        const vm = this;
        allocation.isTransferring = true;
        allocation.transferred = true;
        let confirmTransferPromise = Promise.resolve();
        if(!vm.$store.getters.isInDemoMode && requestConfirmation){
            confirmTransferPromise = vm.$refs.transferWarningModal.displayModal();
        }
        return confirmTransferPromise.then(ensureDefenseExists).then(completeTransfer).catch(resetTransferStatus);
        function ensureDefenseExists(){
            if(!vm.defenseCreationPromise){
                vm.defenseCreationPromise = Vue.appApi().authorized().defense().createDefense().then(setDefenseId);
            }
            return vm.defenseCreationPromise;
            function setDefenseId(response){
                vm.defenseId = response.data.id;
            }
        }
        function completeTransfer(){
            setAllocatedAssignments(allocation);
            allocation.child_allocations.forEach(setAllocatedAssignments);

            // remove circular references from payload
            const userAllocation = allocation.userAllocation;
            const childAllocations = allocation.child_allocations;
            delete allocation.userAllocation;
            delete allocation.child_allocations;
            var payload = JSON.parse(JSON.stringify(allocation));
            delete payload.bank_account;
            delete payload.from_account;
            allocation.userAllocation = userAllocation;
            allocation.child_allocations = childAllocations;
            payload.child_allocations = childAllocations.map(childAllocation => {
                childAllocation.transferred = true;
                const userAllocation = childAllocation.userAllocation;
                delete childAllocation.userAllocation;
                const childAllocationPayload = JSON.parse(JSON.stringify(childAllocation));
                delete childAllocationPayload.bank_account;
                delete childAllocationPayload.from_account;
                childAllocation.userAllocation = userAllocation;
                return childAllocationPayload;
            });

            return Vue.appApi().authorized().defense(vm.defenseId).transferFunds(payload)
                .then(refreshBankAccountBalances)
                .then(completeTransferSuccess)
                .then(updateBarGraphs)
                .then(displaySuccessBanner)
                .catch(completeTransferError);

            function refreshBankAccountBalances(response){
                response.data.forEach(storedAllocation => {
                    if(storedAllocation.bank_account){
                        setBankAccount(storedAllocation.bank_account);
                    }
                    if(storedAllocation.transferred_from_bank_account){
                        setBankAccount(storedAllocation.transferred_from_bank_account);
                    }
                });
                return response;

                function setBankAccount(bankAccount){
                    vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', bankAccount);
                    if(bankAccount.slug === 'income_deposit'){
                        Object.assign(vm.income_account, bankAccount);
                    } else if(bankAccount.slug === 'cc_payoff'){
                        Object.assign(vm.payoff_account, bankAccount);
                    } else {
                        const allocationAccountIndex = vm.allocation_accounts.findIndex(({ id }) => id === bankAccount.id);
                        if(allocationAccountIndex >= 0){
                            Object.assign(vm.allocation_accounts[allocationAccountIndex], bankAccount);
                        }
                    }
                }
            }

            function completeTransferSuccess(response){
                const allSavedAllocations = [allocation, ...allocation.child_allocations];
                allSavedAllocations.forEach(allocation => {
                    allocation.transferred = true;
                    const allocatedFromIncomeToPayoff = allocation.from_account.slug === 'income_deposit' && allocation.bank_account.slug === 'cc_payoff';
                    if(allocatedFromIncomeToPayoff){
                        vm.userAllocations.forEach(userAllocation => {
                            if(userAllocation.incomeToPayoffAllocation.amount > 0){
                                userAllocation.editable = false;
                                userAllocation.incomeToPayoffAllocation.transferred = true;
                                userAllocation.amount = Math.max(new Decimal(userAllocation.amount).minus(userAllocation.incomeToPayoffAllocation.amount).toDecimalPlaces(2).toNumber(), 0);
                            }
                            setUserAllocationTransferStatus(userAllocation);
                        });
                    } else if(allocation.userAllocation){
                        allocation.userAllocation.editable = false;
                        allocation.userAllocation.amount = Math.max(new Decimal(allocation.userAllocation.amount).minus(allocation.amount).toDecimalPlaces(2).toNumber(), 0);
                        setUserAllocationTransferStatus(allocation.userAllocation);
                    }
                });

                const isLastTransferForAccountIn = getRemainingAllocationsForAccount(allocation.bank_account_id).length === 0;
                const isLastTransferForAccountOut = getRemainingAllocationsForAccount(allocation.transferred_from_id).length === 0;
                const shouldTransferAllRemainingInteralTransfers = vm.allocations.every(({ transferred }) => transferred) && !vm.internalAllocations.every(({ transferred }) => transferred);
                const makeInternalTransferPromises = [];
                if(isLastTransferForAccountIn){
                    const internalTransfersForAccountIn = vm.internalAllocations.filter(({ transferred, isTransferring, bank_account_id }) => !transferred && !isTransferring && bank_account_id === allocation.bank_account_id);
                    makeInternalTransferPromises.push(...internalTransfersForAccountIn.map(vm.transferAllocation));
                }
                if(isLastTransferForAccountOut){
                    const internalTransfersForAccountOut = vm.internalAllocations.filter(({ transferred, isTransferring, bank_account_id }) => !transferred && !isTransferring && bank_account_id === allocation.transferred_from_id);
                    makeInternalTransferPromises.push(...internalTransfersForAccountOut.map(vm.transferAllocation));
                }
                if(shouldTransferAllRemainingInteralTransfers){
                    const remainingInternalTransfers = vm.internalAllocations.filter(({ transferred, isTransferring }) => (!transferred && !isTransferring));
                    makeInternalTransferPromises.push(...remainingInternalTransfers.map(vm.transferAllocation));
                }

                vm.updateChart();
                return Promise.all(makeInternalTransferPromises).then(() => response);
                function setUserAllocationTransferStatus(userAllocation){
                    userAllocation.transferred = (userAllocation.incomeToPayoffAllocation.amount <= 0 || userAllocation.incomeToPayoffAllocation.transferred) &&
                    (userAllocation.incomeToSavingsAllocation.amount <= 0 || userAllocation.incomeToSavingsAllocation.transferred) &&
                    (userAllocation.savingsToPayoffAllocation.amount <= 0 || userAllocation.savingsToPayoffAllocation.transferred);
                }
                function getRemainingAllocationsForAccount(bankAccountId){
                    return vm.allocations.filter(({ bank_account_id, transferred, transferred_from_id }) => !transferred && (bank_account_id === bankAccountId || transferred_from_id === bankAccountId));
                }
            }

            function updateBarGraphs(response){
                response.data.forEach(allocation => {
                    if(vm.$refs[`balance-bar-${allocation.transferred_from_id}`]){
                        vm.$refs[`balance-bar-${allocation.transferred_from_id}`][0].updateBarGraph();
                    }
                    if(vm.$refs[`balance-bar-${allocation.bank_account_id}`]){
                        vm.$refs[`balance-bar-${allocation.bank_account_id}`][0].updateBarGraph();
                    }
                });
            }

            function displaySuccessBanner(){
                const allSavedAllocations = [allocation, ...allocation.child_allocations];
                allSavedAllocations.forEach(allocation => (allocation.isTransferring = false));
                const isEveryAllocationTransferred = [...vm.allocations, ...vm.internalAllocations].filter(allocation => vm.parseNumber(allocation.amount) > 0).every(allocation => allocation.transferred);
                if(isEveryAllocationTransferred){
                    vm.showSuccessMessage = true;
                }
            }

            function completeTransferError(response){
                [allocation, ...allocation.child_allocations].forEach(allocation => {
                    allocation.isTransferring = false;
                    allocation.transferred = false;
                });
                if(response.appMessage){
                    vm.apiErrors.push(response.appMessage);
                }
            }

            function setAllocatedAssignments(allocation){

                const allocationCoversAssignments = allocation.bank_account.slug === 'cc_payoff';
                if(allocationCoversAssignments){
                    if(allocation.from_account.slug === 'income_deposit'){
                        const incomeDepositAllocations = vm.userAllocations.map(({ incomeToPayoffAllocation }) => incomeToPayoffAllocation).filter(({ transferred }) => !transferred);
                        allocation.allocatedAssignments = incomeDepositAllocations.map(allocation => getAllocatedAssignments(allocation.amount, allocation.userAllocation.bank_account_id)).flat();
                    } else if(allocation.userAllocation){
                        allocation.allocatedAssignments = getAllocatedAssignments(allocation.amount, allocation.userAllocation.bank_account_id);
                    }
                }
                function getAllocatedAssignments(allocatedAmount, bankAccountId){
                    const bankAccount = vm.allocation_accounts.find(({ id }) => id === bankAccountId);
                    const assignments = bankAccount ? bankAccount.untransferred_assignments : [];
                    const allocatedAssignments = assignments.sort(getNegativeAndPartialAssignmentsFirst).filter(distributeAllocationToAssignments);
                    return JSON.parse(JSON.stringify(allocatedAssignments));
                    function getNegativeAndPartialAssignmentsFirst(a, b){
                        if(a.transaction.amount < 0){
                            return b.transaction.amount < 0 ? 0 : -1;
                        } else if(a.allocated_amount){
                            return b.allocated_amount ? 0 : -1;
                        } else {
                            return a.transaction.amount - b.transaction.amount;
                        }
                    }
                    function distributeAllocationToAssignments(assignment){
                        if(assignment.transferred || allocatedAmount <= 0){
                            return false;
                        } else {
                            const assignmentAmount = new Decimal(assignment.transaction.amount).minus(assignment.allocated_amount).toDecimalPlaces(2).toNumber();
                            if(assignmentAmount <= allocatedAmount){
                                assignment.allocated_amount = +assignment.transaction.amount;
                            } else {
                                assignment.allocated_amount = new Decimal(assignment.allocated_amount).plus(allocatedAmount).toDecimalPlaces(2).toNumber();
                            }
                            allocatedAmount = new Decimal(allocatedAmount).minus(assignmentAmount).toDecimalPlaces(2).toNumber();
                            return true;
                        }
                    }
                }
            }
        }

        function resetTransferStatus(){
            allocation.isTransferring = false;
            allocation.transferred = false;
        }
    }

    function printTransferList(){
        window.print();
    }

    function allocateAmount(bankAccountId, amount){
        const vm = this;
        vm.$refs[`ref-${bankAccountId}`][0].setInputValue([bankAccountId, amount]);
    }

    function validateNewBalance(allocation){
        const vm = this;
        vm.allocationErrorMessage = '';
        vm.allocations.forEach(allocation => {
            allocation.hasValidationError = false;
        });
        var amount = allocation.amount;
        const isAmountChanged = allocation.amount !== allocation.previousAmount;
        const availableFunds = new Decimal(vm.totalUnallocated).plus(allocation.previousAmount).minus(amount).toDecimalPlaces(2).toNumber();
        const amountExceedsAvailableFunds = availableFunds < 0;
        if(isAmountChanged && amountExceedsAvailableFunds){
            const newAmount = new Decimal(amount).minus(Math.abs(availableFunds)).toDecimalPlaces(2).toNumber();
            allocation.amount = Math.max(newAmount, 0);
            vm.allocationErrorMessage = `There was only $ ${allocation.amount.toFixed(2)} remaining to be organized.`;
            allocation.hasValidationError = true;
        } else {
            allocation.amount = amount;
        }
        if(isAmountChanged){
            vm.isTotalUnallocatedOutdated = true;
        }
        Vue.nextTick(vm.updateChart);
    }

    function cachePreviousValue(allocation){
        allocation.previousAmount = allocation.amount || 0;
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
        vm.isTotalUnallocatedOutdated = true;
        vm.isNegativeBalanceCheckOutdated = true;

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

    function openWindow(url){
        window.open(url, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=900');
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

    function handleInvalidTransferNavigation(){
        const vm = this;
        vm.transferView = false;
        vm.$router.replace({ name: 'organize' });
        vm.$bvToast.show('example-toast');
    }

    function updateAccountScheduleTotals(bankAccount, totalAmount){
        const vm = this;
        totalAmount = totalAmount > 0 ? (+totalAmount).toFixed(2) : totalAmount;
        vm.$set(vm.accountScheduleTotals, bankAccount.id, totalAmount);
    }

    function makeAllInternalTransfers(){
        const vm = this;
        vm.isCompletingInternalTransfers = true;
        const bankAccountIdsToTransfer = vm.internalAllocations.map(({ bank_account_id }) => bank_account_id);
        return new Promise(sequentiallyTransferInternalAllocations).catch(displayError).finally(resetLoadingState);

        function sequentiallyTransferInternalAllocations(resolve, reject){
            const bankAccountId = bankAccountIdsToTransfer.pop();
            if(bankAccountId){
                transferInternalAllocation().then(makeNextTransfer).catch(reject);
            } else {
                resolve();
            }

            function transferInternalAllocation(){
                const internalAllocationToTransfer = vm.internalAllocations.find(allocation => (!allocation.isTransferring && !allocation.transferred && allocation.bank_account_id === bankAccountId));
                return internalAllocationToTransfer ? vm.transferAllocation(internalAllocationToTransfer) : Promise.resolve();
            }

            function makeNextTransfer(){
                return sequentiallyTransferInternalAllocations(resolve, reject);
            }
        }

        function displayError(err){
            if(err.appMessage){
                vm.apiErrors.push(err.appMessage);
            } else {
                vm.apiErrors.push('Oops, failed to complete internal transfers.');
            }
        }

        function resetLoadingState(){
            vm.isCompletingInternalTransfers = false;
        }
    }
}

function confirmNavigation(to, from, next){
    const vm = this;
    const leavingTransferView = from.name === 'transfer';
    const isLeavingDefensePage = !['transfer', 'organize'].includes(to.name);
    const hasUserMadeAllocations = vm.userAllocations.some(allocation => allocation.amount > 0);

    if(leavingTransferView && vm.areTransfersPartiallyFinished){
        vm.$refs.partialTransferModal.displayModal().then(vm.resetAllocations).then(next).catch(navigationCancelled);
    } else if(hasUserMadeAllocations && isLeavingDefensePage){
        vm.$refs.leavingOrganizeModal.displayModal().then(next).catch(navigationCancelled);
    } else {
        next();
    }

    function navigationCancelled(){}
}

function beforeRouteUpdate(newRoute, oldRoute, next){
    const vm = this;
    if(newRoute.name !== 'transfer' || !vm.hasNegativeBalance){
        next();
    } else if(newRoute.name === 'transfer' && vm.hasNegativeBalance){
        vm.handleInvalidTransferNavigation();
    }
}

function destroyed(){
    const vm = this;
    if(vm.someAllocationsTransferred){
        vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
    }
}

function cloneObject(object){
    return JSON.parse(JSON.stringify(object));
}
