import draggable from 'vuedraggable';
import editTransactionsModal from './edit/edit-transactions-modal.vue';
import AccountScheduleModal from 'vue_root/components/account-schedule-modal/account-schedule-modal';
import CalculatorPopover from 'vue_root/components/calculator-popover/calculator-popover';

export default {
    components: {
        draggable,
        editTransactionsModal,
        CalculatorPopover,
        AccountScheduleModal,
    },
    filters: {
        formatDate: function(date){
            return Vue.moment(date).format('MM/DD/YY');
        }
    },
    data: data,
    computed: getComputed(),
    watch: getWatchers(),
    created: created,
    beforeDestroy,
    methods: getMethods(),
};

function created(){
    const vm = this;
    vm.isInitializingView = true;
    if(!vm.$store.state.authorized.bankAccounts.bankAccounts.length){
        vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
    }
    const loadAccountsPromises = [
        vm.loadAssignableAccounts(true),
        vm.loadSavingsAccessCC(),
        vm.getCCPayoffAccount(true),
        vm.loadCreditCards()
    ];
    vm.setPendingAssignments();
    Promise.all(loadAccountsPromises).finally(resetLoadingState);
    window.addEventListener('resize', vm.toggleDragAndDropFeature);
    vm.toggleDragAndDropFeature();

    function resetLoadingState(){
        vm.isInitializingView = false;
        vm.isFinishedAssigningAllTransactions = !vm.pendingAssignments.length;
    }
}

function beforeDestroy(){
    const vm = this;
    if(vm.isBankAccountStoreOutOfSync){
        vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
    }
    window.removeEventListener('resize', vm.toggleDragAndDropFeature);

}

function data(){
    return {
        pendingAssignments: [],
        assignableAccounts: [],
        assignableAccountVisibility: [],
        savingsAccessCC: {},
        ccPayoffAccount: {},
        creditCards: [],
        apiErrors: [],
        isInitializingView: false,
        isBankAccountStoreOutOfSync: false,
        selectedTransactions: [],
        selectedAccount: { label: 'Choose an account', value: null },
        bulkAssignmentError: '',
        isBulkAssigningTransactions: false,
        isMobileScreenSize: false,
        isFinishedAssigningAllTransactions: true,
        pendingAccountsReload: null,
        pendingCCPayoffReload: null,
        isIOS: window.appEnv.clientPlatform === 'ios',
        selectedBankAccount: null,
    };
}

function getComputed(){
    return {
        fetchingTransactions(){
            return this.$store.state.authorized.transactions.isFetchingTransactions;
        },
        assignableTransactions(){
            const vm = this;
            return JSON.parse(JSON.stringify(vm.$store.state.authorized.transactions.unassignedTransactions));
        },
        sortedAssignableAccounts(){
            const vm = this;
            return [...vm.assignableAccounts, vm.ccPayoffAccount].map(addAttribute).filter(({ id }) => id).sort(accordingToModifiedStoreOrder);

            function addAttribute(account){
                const hasAccountSchedule = account.slug !== 'income_deposit' && account.slug !== 'cc_payoff';
                account.hasAccountSchedule = hasAccountSchedule;
                return account;
            }

            function accordingToModifiedStoreOrder(a, b){
                const aIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === a.id);
                const bIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === b.id);
                let position = 0;
                if(a.slug === 'cc_payoff' || b.slug === 'cc_payoff'){
                    position = a.slug === 'cc_payoff' ? 1 : -1;
                } else if(aIndex >= 0 && bIndex >= 0){
                    position = aIndex < bIndex ? -1 : 1;
                }
                return position;
            }
        },
        totalCreditCardBalance(){
            const vm = this;
            var balance = vm.creditCards.reduce((accumulator, account) => accumulator.plus(account.balance_current).toDecimalPlaces(2), new Decimal(0));
            return balance.toDecimalPlaces(2).toNumber();
        },
        assignedCCTransactions(){
            const vm = this;
            const assignmentTotal = vm.assignableAccounts.reduce((accumulator, account) => accumulator.plus(vm.calculateAssignedTotal(account.untransferred_assignments)).toDecimalPlaces(2), new Decimal(0));
            const partialPaymentTotal = vm.assignableAccounts.reduce((accumulator, account) => accumulator.plus(vm.calculatePartialPaymentTotal(account.untransferred_assignments)).toDecimalPlaces(2), new Decimal(0));
            return assignmentTotal.minus(partialPaymentTotal).toDecimalPlaces(2).toNumber();
        },
        dymCCBalance(){
            const vm = this;
            var difference = new Decimal(vm.totalCreditCardBalance).plus(vm.ccPayoffAvailableBalance).toDecimalPlaces(2).toNumber();
            return difference;
        },
        ccPayoffAvailableBalance(){
            const vm = this;
            return new Decimal(vm.ccPayoffAccount.balance_current || 0).plus(vm.ccPayoffAccount.allocation_balance_adjustment || 0).plus(vm.ccPayoffAccount.assignment_balance_adjustment).toDecimalPlaces(2).toNumber();
        },
        finicityRefreshStatus(){
            const vm = this;
            return vm.$store.state.authorized.finicityRefreshStatus;
        },
        creditCardsKeyedById(){
            const vm = this;
            return vm.creditCards.reduce(keyByBankAccountId, {});
            function keyByBankAccountId(accumulator, creditCard){
                accumulator[creditCard.id] = creditCard;
                return accumulator;
            }
        },
        pendingAssignmentsByDate(){
            const vm = this;
            return vm.pendingAssignments.sort(vm.sortByTransactionDate);
        },
        totalOfSelectedTransactions(){
            const vm = this;
            return vm.selectedTransactions.reduce(sumSelectedTransactions, new Decimal(0)).toDecimalPlaces(2).toNumber();
            function sumSelectedTransactions(accumulator, pendingAssignment){
                return accumulator.plus(pendingAssignment.transaction.amount).toDecimalPlaces(2);
            }
        },
        accountSelectOptions(){
            const vm = this;
            const nullOptions = [{ label: 'Choose an account', value: null }];
            const options = vm.sortedAssignableAccounts.map((bankAccount) => {
                return { label: bankAccount.name, value: bankAccount };
            });
            return nullOptions.concat(options);
        },
        isBulkAssignmentAllowed(){
            const vm = this;
            const hasSelectedAccount = vm.selectedAccount && !!vm.selectedAccount.value;
            const isProjectedBalanceNegative = hasSelectedAccount && vm.selectedAccount.value.slug !== 'cc_payoff' && vm.totalOfSelectedTransactions > vm.selectedAccount.value.balance_available;
            const isProjectedPayoffBalanceNegative = hasSelectedAccount && vm.selectedAccount.value.slug === 'cc_payoff' && Math.abs(vm.totalOfSelectedTransactions) > vm.selectedAccount.value.balance_current;
            const isProjectedAssignmentTotalNegative = hasSelectedAccount && new Decimal(vm.selectedAccount.value.assignment_balance_adjustment || 0).plus(vm.totalOfSelectedTransactions).toDecimalPlaces(2).toNumber() < 0;
            const isOnlyNegativeTransactionsSelected = vm.selectedTransactions.every(({ transaction }) => transaction.amount < 0);
            const isInvalidCcPayoffSelection = hasSelectedAccount && vm.selectedAccount.value.slug === 'cc_payoff' && !isOnlyNegativeTransactionsSelected;
            let errorMessage = '';
            if(isInvalidCcPayoffSelection){
                errorMessage = 'You can only assign negative amounts to this account.';
            } else if(isProjectedBalanceNegative || isProjectedPayoffBalanceNegative){
                errorMessage = 'There is not enough money in this account. Please deselect charges or choose a different account.';
            } else if(isProjectedAssignmentTotalNegative && vm.selectedAccount.value.slug !== 'cc_payoff'){
                errorMessage = 'Assignment would create a negative assignment total. Please deselect charges or choose a different account.';
            }
            if(errorMessage){
                vm.bulkAssignmentError = errorMessage;
                if(vm.$refs.bulkAssignmentError){
                    Vue.nextTick(() => {
                        vm.$refs.bulkAssignmentError.focus();
                    });
                }
            }
            return hasSelectedAccount && !errorMessage && !vm.isBulkAssigningTransactions;
        },
        isBulkAssignmentPanelDisplayed(){
            const vm = this;
            return vm.selectedTransactions.length || vm.isBulkAssigningTransactions;
        },
        allTransactionsSelected: {
            get(){
                const vm = this;
                return vm.selectedTransactions.length === vm.pendingAssignmentsByDate.length;
            },
            set(isChecked){
                const vm = this;
                vm.selectedTransactions = isChecked ? vm.pendingAssignmentsByDate.slice() : [];
            }
        },
        isDragAssignmentDisabled(){
            const vm = this;
            return vm.selectedTransactions.length || vm.isBulkAssigningTransactions || vm.isMobileScreenSize;
        }
    };
}

function getWatchers(){
    return {
        finicityRefreshStatus(newStatus, oldStatus){
            const vm = this;
            const refreshCompleted = oldStatus === 'pending' && newStatus !== 'pending';
            if(refreshCompleted){
                created.apply(vm);
                vm.loadAssignableTransactions();
            }
        },
        assignableTransactions(newTransactions){
            const vm = this;
            vm.setPendingAssignments();
        },
        isFinishedAssigningAllTransactions(hasCompletedAssignments){
            const vm = this;
            const user = vm.$store.getters.user;
            const isUserAccountOlderThanThreeMonths = Vue.moment().subtract(3, 'months').isAfter(user.current_account.created_at);
            if(hasCompletedAssignments && isUserAccountOlderThanThreeMonths){
                Vue.iosInAppReview.requestReview();
            }
        }
    };
}

function getMethods(){
    return {
        calculateAssignedTotal,
        calculatePartialPaymentTotal,
        calculateAssignmentBalanceAdjustment,
        loadAssignableTransactions,
        setPendingAssignments,
        loadAssignableAccounts,
        removeAssignment,
        toggle,
        assignTransaction,
        applyMoveStyles,
        loadSavingsAccessCC,
        loadCreditCards,
        getCCPayoffAccount,
        resetDropTransactionHere,
        editUnassignedTransactions,
        displayApiError,
        createReconciliationTransaction,
        sortByTransactionDate,
        bulkAssignTransactions,
        toggleDragAndDropFeature,
        openAccountSchedule,
    };

    function calculateAssignedTotal(assignments){
        const total = assignments.reduce((sum, assignment) => sum.plus(assignment.transaction.amount).toDecimalPlaces(2), new Decimal(0));
        return total.toNumber();
    }

    function calculatePartialPaymentTotal(assignments){
        const total = assignments.reduce((accumulator, assignment) => accumulator.plus(assignment.allocated_amount || 0).toDecimalPlaces(2), new Decimal(0));
        return total.toNumber();
    }

    function calculateAssignmentBalanceAdjustment(assignments){
        const vm = this;
        const assignmentBalanceAdjustment = new Decimal(vm.calculateAssignedTotal(assignments)).minus(vm.calculatePartialPaymentTotal(assignments)).toDecimalPlaces(2);
        return assignmentBalanceAdjustment.toNumber();
    }

    function loadAssignableTransactions(){
        const vm = this;
        return vm.$store.dispatch('authorized/transactions/FETCH_UNASSIGNED_TRANSACTIONS');
    }

    function setPendingAssignments(){
        const vm = this;
        vm.pendingAssignments = vm.assignableTransactions.map(formatTransactionAsAssignment);
    }

    function loadAssignableAccounts(isInitialLoad){
        const vm = this;
        if(!isInitialLoad){
            vm.isBankAccountStoreOutOfSync = true;
        }

        if(vm.pendingAccountsReload){
            vm.pendingAccountsReload.cancel('Cancelling pending accounts reload.');
        }
        const promise = Vue.appApi().authorized().bankAccount().getAssignableAccounts();
        vm.pendingAccountsReload = promise.cancelToken;
        return promise.then(setAssignableAccounts).catch(displayErrors);

        function setAssignableAccounts(response){
            var assignableBankAccounts = response.data.map(setDisplayProperties).sort(byAccountType);
            vm.assignableAccounts = assignableBankAccounts;
            vm.assignableAccounts.forEach(sortAssignments);
            return Promise.resolve();

            function setDisplayProperties(bankAccount){
                bankAccount.loading = false;
                bankAccount.apiError = '';
                return bankAccount;
            }

            function byAccountType(a, b){
                if(a.slug === 'monthly_bills'){
                    return -1;
                } else if(a.slug === 'everyday_checking'){
                    return b.slug === 'monthly_bills' ? 1 : -1;
                } else {
                    return 1;
                }
            }
            function sortAssignments(bankAccount){
                bankAccount.untransferred_assignments = bankAccount.untransferred_assignments.sort(vm.sortByTransactionDate);
            }
        }

        function displayErrors(response){
            if(response.appMessage){
                vm.apiErrors.push(response.appMessage);
            }
        }
    }

    function removeAssignment(assignment, assignableAccount){
        const vm = this;
        const currentAssignmentTotal = vm.calculateAssignedTotal(assignableAccount.untransferred_assignments);
        var assignmentTotalWillBeNegative = new Decimal(currentAssignmentTotal).minus(assignment.transaction.amount).toDecimalPlaces(2).toNumber() < 0;
        if(assignmentTotalWillBeNegative && assignableAccount.slug !== 'cc_payoff'){
            return vm.apiErrors.push('Assignment removal cancelled to prevent negative assignment total.');
        }
        vm.$set(assignableAccount, 'loading', true);
        const index = assignableAccount.untransferred_assignments.findIndex(({ id }) => id === assignment.id);
        assignableAccount.untransferred_assignments.splice(index, 1);

        return Vue.appApi().authorized().bankAccount(assignment.bank_account_id).assignment(assignment.id).deleteAssignment()
            .then(updateAccountBalance)
            .then(updatePendingAssignments)
            .catch(displayErrors)
            .finally(resetLoadingState);

        function updateAccountBalance(response){
            let promise = Promise.resolve();
            if(assignableAccount.slug !== 'cc_payoff'){
                const newAccountBalance = new Decimal(assignableAccount.balance_available).plus(assignment.transaction.amount).minus(assignment.allocated_amount).toDecimalPlaces(2).toNumber();
                assignableAccount.balance_available = newAccountBalance;
                assignableAccount.assignment_balance_adjustment = vm.calculateAssignmentBalanceAdjustment(assignableAccount.untransferred_assignments);
                vm.ccPayoffAccount.assignment_balance_adjustment = vm.assignedCCTransactions;
                vm.ccPayoffAccount.balance_available = vm.ccPayoffAvailableBalance;
                vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', assignableAccount);
                vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', vm.ccPayoffAccount);
            } else {
                promise = Promise.all([
                    vm.loadAssignableAccounts(),
                    vm.getCCPayoffAccount()
                ]);
            }
            return promise;
        }
        function updatePendingAssignments(){
            vm.$store.commit('authorized/transactions/ADD_UNASSIGNED_TRANSACTION', assignment.transaction);
        }
        function displayErrors(response){
            if(response.appMessage){
                vm.apiErrors.push(response.appMessage);
                assignableAccount.untransferred_assignments.splice(index, 0, assignment);
            }
        }
        function resetLoadingState(){
            assignableAccount.loading = false;
        }
    }

    function toggle(assignableAccount){
        const vm = this;

        vm.$set(vm.assignableAccountVisibility, assignableAccount.id, !vm.assignableAccountVisibility[assignableAccount.id]);
    }

    function assignTransaction(bankAccount, event){
        const vm = this;
        vm.selectedTransactions = [];
        const isAssignedToUnlinkedPayoffAccount = bankAccount.slug === 'cc_payoff' && !bankAccount.institution_account_id;
        const isAssignedToPayoffSubAccount = bankAccount.slug === 'cc_payoff' && bankAccount.parent_bank_account_id;
        var transaction_id = parseInt(event.item.getAttribute('data-id'));
        var pendingAssignmentIndex = bankAccount.untransferred_assignments.findIndex(assignment => assignment.transaction.id === transaction_id);
        var pendingAssignment = bankAccount.untransferred_assignments[pendingAssignmentIndex];
        var newAccountBalance = pendingAssignment && new Decimal(bankAccount.balance_available).minus(pendingAssignment.transaction.amount).toDecimalPlaces(2).toNumber();
        if(isAssignedToUnlinkedPayoffAccount){
            newAccountBalance = pendingAssignment && new Decimal(bankAccount.balance_current).plus(pendingAssignment.transaction.amount).toDecimalPlaces(2).toNumber();
        } else if(bankAccount.slug === 'cc_payoff'){
            newAccountBalance = bankAccount.balance_available;
        }
        const assignmentCancellationMessage = getAssignmentCancellationMessage();
        const shouldCancelAssignment = !!assignmentCancellationMessage;
        if(shouldCancelAssignment){
            bankAccount.untransferred_assignments.splice(pendingAssignmentIndex, 1);
            vm.pendingAssignments.push(pendingAssignment);
            bankAccount.apiError = assignmentCancellationMessage;
            Vue.nextTick(() => {
                vm.$refs[`assignable-account-error-${bankAccount.id}`][0].focus();
            });
            return false;
        } else {
            vm.$set(bankAccount, 'loading', true);
            return Vue.appApi().authorized().bankAccount(bankAccount.id).assignment().postAssignTransaction({ transaction_id })
                .then(responseToPostAssignTransaction)
                .catch(displayErrors);
        }

        function responseToPostAssignTransaction(response){
            let promise = Promise.resolve();

            if(newAccountBalance === +response.data.updated_balance && !isAssignedToPayoffSubAccount){
                if(isAssignedToUnlinkedPayoffAccount){
                    bankAccount.balance_current = newAccountBalance;
                    bankAccount.balance_available = new Decimal(bankAccount.balance_current)
                        .plus(bankAccount.allocation_balance_adjustment)
                        .plus(bankAccount.assignment_balance_adjustment)
                        .toDecimalPlaces(2).toNumber();
                    vm.ccPayoffAccount.balance_current = bankAccount.balance_current;
                } else if(bankAccount.slug !== 'cc_payoff'){
                    bankAccount.balance_available = newAccountBalance;
                    bankAccount.assignment_balance_adjustment = vm.calculateAssignmentBalanceAdjustment(bankAccount.untransferred_assignments);
                }
                bankAccount.untransferred_assignments.splice(pendingAssignmentIndex, 1, response.data.assignment);
                bankAccount.untransferred_assignments = bankAccount.untransferred_assignments.sort(vm.sortByTransactionDate);
                vm.$set(bankAccount, 'loading', false);
            } else {
                promise = Promise.all([
                    vm.loadAssignableAccounts(),
                    vm.getCCPayoffAccount()
                ]);
            }
            vm.$store.commit('authorized/transactions/REMOVE_UNASSIGNED_TRANSACTION', { id: transaction_id });
            vm.ccPayoffAccount.assignment_balance_adjustment = vm.assignedCCTransactions;
            vm.ccPayoffAccount.balance_available = vm.ccPayoffAvailableBalance;
            vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', bankAccount);
            vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', vm.ccPayoffAccount);
            vm.isFinishedAssigningAllTransactions = !vm.pendingAssignments.length;
            return promise;
        }

        function displayErrors(response){
            vm.apiErrors.push(response.appMessage);
            vm.loadAssignableAccounts();
            vm.getCCPayoffAccount();
            vm.loadAssignableTransactions();
        }

        function getAssignmentCancellationMessage(){
            let assignmentCancellationMessage = '';
            if(bankAccount.slug === 'cc_payoff'){
                const isAssignmentAmountPositive = pendingAssignment && pendingAssignment.transaction.amount > 0;
                if(isAssignmentAmountPositive){
                    assignmentCancellationMessage = 'Assignment cancelled because you can only assign negative amounts to this account.';
                }
            } else {
                const balanceWillBeNegative = pendingAssignment && newAccountBalance < 0;
                const assignmentTotalWillBeNegative = pendingAssignment && vm.calculateAssignedTotal(bankAccount.untransferred_assignments) < 0;
                if(assignmentTotalWillBeNegative){
                    assignmentCancellationMessage = 'Assignment cancelled to prevent negative assignment total. Please choose a different account.';
                } else if(balanceWillBeNegative){
                    assignmentCancellationMessage = 'There is not enough money in this account. Please choose a different account.';
                }
            }

            return assignmentCancellationMessage;
        }
    }

    function applyMoveStyles(event, originalEvent){
        const vm = this;

        vm.resetDropTransactionHere();
        if(event.to.id !== null){
            // Add a frame
            var dropAssignableAccountElement = document.getElementById(event.to.id + '-body');
            if(dropAssignableAccountElement){
                dropAssignableAccountElement.classList.add('assignable-account-border-' + dropAssignableAccountElement.dataset.color);
            }
        }

        return true;
    }

    function loadSavingsAccessCC(){
        const vm = this;

        return Vue.appApi().authorized().bankAccount().savingsAccessCreditCard().get().then(setSavingsAccessCC).catch(displayErrors);

        function setSavingsAccessCC(response){
            vm.savingsAccessCC = response.data;
        }
        function displayErrors(response){
            if(response && response.appMessage){
                vm.apiErrors.push(response.appMessage);
            }
        }
    }

    function loadCreditCards(){
        const vm = this;
        return Vue.appApi().authorized().bankAccount().loadWithInstitutionAccounts({ type: 'credit' }).then(setCreditCards).catch(vm.displayApiError);

        function setCreditCards(response){
            vm.creditCards = response.data;
        }
    }

    function getCCPayoffAccount(isInitialLoad){
        const vm = this;
        if(!isInitialLoad){
            vm.isBankAccountStoreOutOfSync = true;
        }
        if(vm.pendingCCPayoffReload){
            vm.pendingCCPayoffReload.cancel('Cancelling pending accounts reload.');
        }
        const promise = Vue.appApi().authorized().bankAccount().getCCPayoffAccountOverview();
        vm.pendingCCPayoffReload = promise.cancelToken;
        return promise.then(setCCPayoffAccount).catch(displayErrors);

        function setCCPayoffAccount(response){
            vm.ccPayoffAccount = response.data;
            vm.$set(vm.ccPayoffAccount, 'loading', false);
            vm.$set(vm.ccPayoffAccount, 'apiError', '');
        }
        function displayErrors(response){
            if(response && response.appMessage){
                vm.apiErrors.push(response.appMessage);
            }
        }
    }

    function resetDropTransactionHere(){
        const vm = this;
        vm.sortedAssignableAccounts.forEach(resetMoveStyles);

        function resetMoveStyles(assignableAccount){
            var assignableAccountElement = document.getElementById('assignableAccount-' + assignableAccount.id + '-body');
            if(assignableAccountElement !== null){
                assignableAccountElement.classList.remove('assignable-account-border-' + assignableAccountElement.dataset.color);
            }
            var assignableAccountElementParent = document.getElementById('assignableAccount-' + assignableAccount.id + '-header-parent');
            if(assignableAccountElementParent){
                assignableAccountElementParent.style.display = 'none';
            }
        }
    }

    function editUnassignedTransactions(){
        const vm = this;
        vm.$refs.editTransactionsModal.openModal();
    }

    function displayApiError(response){
        const vm = this;
        if(response && response.appMessage){
            vm.apiErrors.push(response.appMessage);
        }
    }

    function createReconciliationTransaction(){
        const vm = this;
        const reconciliationEntry = {
            bank_account_id: vm.savingsAccessCC.id,
            remote_transaction_date: Vue.moment().format('YYYY-MM-DD 00:00:00'),
            amount: (vm.dymCCBalance * -1),
            merchant: 'To reconcile'
        };
        return Vue.appApi().authorized().bankAccount(vm.savingsAccessCC.id).transaction().storeTransaction(reconciliationEntry).then(vm.loadAssignableTransactions).catch(vm.displayApiErrors);
    }

    function sortByTransactionDate(a, b){
        const firstDate = a.transaction ? a.transaction.remote_transaction_date : a.remote_transaction_date;
        const secondDate = b.transaction ? b.transaction.remote_transaction_date : b.remote_transaction_date;
        return Vue.moment(firstDate).isAfter(secondDate) ? -1 : 1;
    }

    function bulkAssignTransactions(){
        const vm = this;
        vm.isBulkAssigningTransactions = true;
        const bankAccount = vm.selectedAccount.value;
        const isAssignedToPayoffSubAccount = bankAccount.slug === 'cc_payoff' && bankAccount.parent_bank_account_id;
        var newAccountBalance = new Decimal(bankAccount.balance_available).minus(vm.totalOfSelectedTransactions).toDecimalPlaces(2).toNumber();
        if(bankAccount.slug === 'cc_payoff'){
            newAccountBalance = new Decimal(bankAccount.balance_current).plus(vm.totalOfSelectedTransactions).toDecimalPlaces(2).toNumber();
        }
        const transaction_ids = vm.selectedTransactions.map(({ transaction }) => transaction.id);
        transaction_ids.forEach((id) => vm.$store.commit('authorized/transactions/REMOVE_UNASSIGNED_TRANSACTION', { id }));
        vm.$set(bankAccount, 'loading', true);
        const selectedTransactions = vm.selectedTransactions;
        vm.selectedTransactions = [];

        return Vue.appApi().authorized().bankAccount(bankAccount.id).assignment().postAssignTransaction({ transaction_ids })
            .then(handleSuccessfulAssignments)
            .catch(handleAssignmentErrors)
            .finally(resetLoadingState);

        function handleSuccessfulAssignments(response){
            let promise = Promise.resolve();
            const updatedApiBalance = response.data[0].updated_balance;
            if(newAccountBalance === +updatedApiBalance && !isAssignedToPayoffSubAccount){
                const assignments = response.data.map(({ assignment }) => assignment);
                bankAccount.untransferred_assignments.push(...assignments);
                bankAccount.untransferred_assignments = bankAccount.untransferred_assignments.sort(vm.sortByTransactionDate);
                if(bankAccount.slug === 'cc_payoff'){
                    bankAccount.balance_current = newAccountBalance;
                } else {
                    bankAccount.balance_available = newAccountBalance;
                    bankAccount.assignment_balance_adjustment = vm.calculateAssignmentBalanceAdjustment(bankAccount.untransferred_assignments);
                }
            } else {
                promise = Promise.all([
                    vm.loadAssignableAccounts(),
                    vm.getCCPayoffAccount()
                ]);
            }
            vm.ccPayoffAccount.assignment_balance_adjustment = vm.assignedCCTransactions;
            vm.ccPayoffAccount.balance_available = vm.ccPayoffAvailableBalance;
            vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', bankAccount);
            vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_BALANCE_PROPERTIES', vm.ccPayoffAccount);
            vm.selectedAccount = { label: 'Choose an account', value: null };
            vm.isFinishedAssigningAllTransactions = !vm.pendingAssignments.length;
            return promise;
        }

        function handleAssignmentErrors(error){
            vm.selectedTransactions = selectedTransactions;
            vm.selectedTransactions.forEach(({ transaction }) => vm.$store.commit('authorized/transactions/ADD_UNASSIGNED_TRANSACTION', transaction));
            vm.bulkAssignmentError = error.appMessage || (error.data && error.data.message);
        }

        function resetLoadingState(){
            vm.$set(bankAccount, 'loading', false);
            vm.isBulkAssigningTransactions = false;
        }
    }

    function toggleDragAndDropFeature(){
        const vm = this;
        const mobileBreakpoint = 576;
        if(vm.isMobileScreenSize && window.innerWidth > mobileBreakpoint){
            vm.isMobileScreenSize = false;
        } else if(!vm.isMobileScreenSize && window.innerWidth <= mobileBreakpoint){
            vm.isMobileScreenSize = true;
        }
    }

    function openAccountSchedule(bankAccount){
        if(!bankAccount.hasAccountSchedule){
            return;
        }

        const vm = this;
        vm.selectedBankAccount = bankAccount;
        Vue.nextTick(() => {
            vm.$refs.accountScheduleModal.show();
        });
    }
}
function formatTransactionAsAssignment(transaction){
    return {
        allocated_amount: 0,
        transaction
    };
}
