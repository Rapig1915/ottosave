export default {
    data: data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        originalParentTransaction: null,
        parentTransaction: { split_transactions: [] },
        deletedTransactions: [],
        errorMessages: [],
        isCloseConfirmed: false,
        isLoadingParentTransaction: false,
        isSaving: false,
        splitTransactionFields: [
            { key: 'remote_transaction_date', label: 'Date', thClass: 'btable-th pl-1', tdClass: 'btable-td' },
            { key: 'amount', label: 'Amount', thClass: 'btable-th pl-1', tdClass: 'btable-td amount-input' },
            { key: 'merchant', label: 'Merchant/Description', thClass: 'btable-th pl-1', tdClass: 'btable-td' },
            { key: 'action', label: '', thClass: 'btable-th pl-1', tdClass: 'btable-td action' },
        ],
        parentTransactionFields: [
            { key: 'remote_transaction_date', label: 'Original Date', thClass: 'btable-th pl-1', tdClass: 'btable-td' },
            { key: 'amountRemaining', label: 'Original Amount', thClass: 'btable-th pl-1', tdClass: 'btable-td' },
            { key: 'merchant', label: 'Original Description', thClass: 'btable-th pl-1', tdClass: 'btable-td' },
            { key: 'spacer', label: '', thClass: 'btable-th pl-1', tdClass: 'btable-td px-5' },
        ]
    };
}

function getComputed(){
    return {
        isSaveButtonDisabled,
        dirtyTransactions
    };

    function isSaveButtonDisabled(){
        const vm = this;
        const hasInvalidTransactions = vm.parentTransaction.split_transactions.some(({ validationMessage }) => validationMessage);
        return vm.isSaving || hasInvalidTransactions;
    }

    function dirtyTransactions(){
        const vm = this;
        return vm.parentTransaction.split_transactions.filter(({ isDirty }) => isDirty);
    }
}

function getMethods(){
    return {
        openModal,
        intializeSplitTransactions,
        splitAgain,
        save,
        hideModal,
        validateSplitAmount,
        removeSplitTransaction,
        displayApiErrors,
        confirmUnsavedChanges,
        updateFirstSplitTransaction,
        cancelChanges
    };

    function openModal(parentTransactionId){
        const vm = this;
        vm.isLoadingParentTransaction = true;
        vm.deletedTransactions = [];
        vm.errorMessages = [];
        vm.isCloseConfirmed = false;
        vm.parentTransaction = { split_transactions: [] };
        vm.$refs.splitTransactionsModal.show();
        Vue.appApi().authorized().bankAccount().transaction(parentTransactionId).getParentTransaction().then(setParentTransaction).catch(vm.displayApiErrors).finally(resetLoadingState);

        function setParentTransaction(response){
            response.data.split_transactions.forEach(addDisplayProperties);
            response.data.split_transactions.sort(ensureFirstSplitIsUnassigned);
            vm.originalParentTransaction = JSON.parse(JSON.stringify(response.data));
            vm.intializeSplitTransactions();

            function addDisplayProperties(transaction){
                transaction.displayedAmount = transaction.amount;
                transaction.isDirty = false;
                transaction.validationMessage = '';
            }
            function ensureFirstSplitIsUnassigned(a, b){
                if(a.assignment){
                    return 1;
                } else {
                    return -1;
                }
            }
        }

        function resetLoadingState(){
            vm.isLoadingParentTransaction = false;
        }
    }

    function intializeSplitTransactions(){
        const vm = this;
        vm.parentTransaction = JSON.parse(JSON.stringify(vm.originalParentTransaction));
        if(!vm.parentTransaction.split_transactions.length){
            vm.splitAgain();
            vm.splitAgain();
        }
        vm.updateFirstSplitTransaction(false);
    }

    function splitAgain(){
        const vm = this;
        var splitTransaction = {
            amount: 0,
            bank_account_id: vm.parentTransaction.bank_account_id,
            is_assignable: true,
            merchant: vm.parentTransaction.merchant,
            remote_transaction_date: vm.parentTransaction.remote_transaction_date,
            parent_transaction_id: vm.parentTransaction.id,
            isDirty: true,
            displayedAmount: 0,
            validationMessage: ''
        };
        vm.parentTransaction.split_transactions.push(splitTransaction);
    }

    function save(){
        const vm = this;
        vm.isSaving = true;
        const splitTransactionsToSave = vm.parentTransaction.split_transactions.filter(matchNonZeroAmounts);
        if(splitTransactionsToSave.length > 1){
            const deleteSplitPromises = vm.deletedTransactions.map(deleteTransaction);
            const saveSplitPromises = splitTransactionsToSave.map(saveTransaction);
            Promise.all(deleteSplitPromises.concat(saveSplitPromises))
                .then(removeParentTransaction)
                .then(closeModal)
                .catch(vm.displayApiErrors)
                .finally(resetSavingState);
        } else {
            const savedSplitTransactions = vm.parentTransaction.split_transactions.filter(({ id }) => id);
            const deleteSplitPromises = vm.deletedTransactions.concat(savedSplitTransactions).map(deleteTransaction);
            Promise.all(deleteSplitPromises)
                .then(restoreParentTransaction)
                .then(closeModal)
                .catch(vm.displayApiErrors)
                .finally(resetSavingState);
        }

        function deleteTransaction(transaction){
            return Vue.appApi().authorized().bankAccount(transaction.bank_account_id).transaction(transaction.id).removeTransaction(transaction);
        }
        function matchNonZeroAmounts(transaction){
            return transaction.amount !== 0;
        }
        function saveTransaction(transaction){
            const payload = JSON.parse(JSON.stringify(transaction));
            return Vue.appApi().authorized().bankAccount(payload.bank_account_id).transaction(payload.id).storeTransaction(payload);
        }
        function removeParentTransaction(){
            if(vm.parentTransaction.is_assignable){
                vm.parentTransaction.is_assignable = false;
                return saveTransaction(vm.parentTransaction);
            }
        }
        function restoreParentTransaction(){
            vm.parentTransaction.is_assignable = true;
            return saveTransaction(vm.parentTransaction);
        }
        function closeModal(){
            vm.hideModal(true);
            vm.$emit('close');
        }
        function resetSavingState(){
            vm.isSaving = false;
        }
    }

    function hideModal(isCloseConfirmed){
        const vm = this;
        vm.$refs.confirmUnsavedChangesModal.hide();
        vm.isCloseConfirmed = isCloseConfirmed === true;
        vm.$refs.splitTransactionsModal.hide();
    }

    function validateSplitAmount(transaction){
        const vm = this;
        validateTransaction(transaction);
        if(!transaction.validationMessage){
            vm.parentTransaction.split_transactions.slice(1).forEach(validateTransaction);
        }

        function validateTransaction(transaction){
            let validationMessage = '';
            if(transaction.assignment){
                return;
            }
            const isParentAmountNegative = vm.parentTransaction.amount < 0;
            const isIncorrectSign = (transaction.displayedAmount > 0 && isParentAmountNegative) || (transaction.displayedAmount < 0 && !isParentAmountNegative);
            const remainingToSplit = new Decimal(vm.parentTransaction.split_transactions[0].amount).plus(transaction.amount).toDecimalPlaces(2).toNumber();
            if(isIncorrectSign){
                validationMessage = `Amount must be ${isParentAmountNegative ? 'negative' : 'positive'}.`;
            } else if(!isParentAmountNegative && transaction.displayedAmount >= remainingToSplit){
                validationMessage = `Please enter a value less than ${remainingToSplit}`;
            } else if(isParentAmountNegative && transaction.displayedAmount <= remainingToSplit){
                validationMessage = `Please enter a value greater than ${remainingToSplit}`;
            }
            if(!validationMessage){
                transaction.amount = transaction.displayedAmount;
                vm.updateFirstSplitTransaction();
            }
            transaction.validationMessage = validationMessage;
        }
    }

    function removeSplitTransaction(transaction, index){
        const vm = this;
        vm.parentTransaction.split_transactions.splice(index, 1);
        if(transaction.id){
            vm.deletedTransactions.push(transaction);
        }
        vm.updateFirstSplitTransaction();
    }

    function displayApiErrors(err){
        const vm = this;
        if(err.appMessage){
            vm.errorMessages.push(err.appMessage);
        }
    }

    function confirmUnsavedChanges(event){
        const vm = this;
        const hasUnsavedChanges = vm.deletedTransactions.length || vm.parentTransaction.split_transactions.some(({ isDirty }) => isDirty);
        if(!vm.isCloseConfirmed && hasUnsavedChanges){
            event.preventDefault();
            vm.$refs.confirmUnsavedChangesModal.show();
        }
    }

    function updateFirstSplitTransaction(updateIsDirty = true){
        const vm = this;
        const totalOfSplitsAfterFirst = vm.parentTransaction.split_transactions.slice(1).reduce((accumulator, transaction) => accumulator.plus(transaction.amount).toDecimalPlaces(2), new Decimal(0)).toNumber();
        const firstSplitTransaction = vm.parentTransaction.split_transactions[0];
        if(firstSplitTransaction){
            firstSplitTransaction.amount = new Decimal(vm.parentTransaction.amount || 0).minus(totalOfSplitsAfterFirst).toDecimalPlaces(2).toNumber();
            if(updateIsDirty){
                firstSplitTransaction.isDirty = firstSplitTransaction.displayedAmount !== firstSplitTransaction.amount;
            }
            firstSplitTransaction.displayedAmount = firstSplitTransaction.amount;
        }
    }

    function cancelChanges(){
        const vm = this;
        const hasPreviouslySavedSplits = vm.parentTransaction.split_transactions.some(({ id }) => id);
        if(hasPreviouslySavedSplits){
            vm.intializeSplitTransactions();
        } else {
            vm.hideModal(true);
        }
    }
}
