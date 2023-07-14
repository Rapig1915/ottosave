import SplitTransactionModal from './split-transaction-modal/split-transaction-modal';
import PastTransactions from './past-transactions/past-transactions';
import UnsavedChangesModal from 'vue_root/components/unsaved-changes-modal/unsaved-changes-modal';
import ConfirmDeleteModal from 'vue_root/components/confirm-delete-modal/confirm-delete-modal';

export default {
    components: {
        SplitTransactionModal,
        PastTransactions,
        UnsavedChangesModal,
        ConfirmDeleteModal
    },
    props: {
        bankAccountId: {
            type: Number,
            required: true
        },
        creditCardAccounts: {
            type: Array,
            required: true
        }
    },
    data: data,
    computed: getComputed(),
    methods: getMethods(),
};

function data(){
    return {
        unchangedCopyOfUnassignedTransactions: [],
        workingCopyOfUnassignedTransactions: [],
        apiErrors: [],
        isCloseConfirmed: false,
        isBulkSaveInProgress: false,
        isLoadingTransactions: false,
        isBulkDeletingTransactions: false,
    };
}

function getComputed(){
    return {
        unassignedTransactions,
        dirtyTransactions,
        creditCardsKeyedById,
        linkedCreditCards,
    };
    function unassignedTransactions(){
        const vm = this;
        return JSON.parse(JSON.stringify(vm.$store.state.authorized.transactions.unassignedTransactions));
    }
    function dirtyTransactions(){
        const vm = this;
        return vm.workingCopyOfUnassignedTransactions.filter(({ isDirty, isSaving }) => isDirty && !isSaving);
    }
    function creditCardsKeyedById(){
        const vm = this;
        return vm.creditCardAccounts.reduce(keyByBankAccountId, {});
        function keyByBankAccountId(accumulator, creditCard){
            accumulator[creditCard.id] = creditCard;
            return accumulator;
        }
    }
    function linkedCreditCards(){
        const vm = this;
        return vm.creditCardAccounts.filter(bankAccount => !!bankAccount.institution_account);
    }
}

function getMethods(){
    return {
        openModal,
        getUnassignedTransactions,
        save,
        storeTransaction,
        removeTransaction,
        add,
        handleRemoteTransactionDateChanged,
        confirmUnsavedChanges,
        closeWithoutSaving,
        bulkSaveAndClose,
        splitTransaction,
        refreshTransactions,
        deleteUnassignedTransactions,
        sortByTransactionDate
    };

    function openModal(){
        const vm = this;
        vm.isCloseConfirmed = false;
        vm.newTransaction = { isDirty: false, isSaving: false };
        vm.workingCopyOfUnassignedTransactions = [];
        vm.getUnassignedTransactions();
        vm.$refs.editTransactionsModal.show();
    }

    function getUnassignedTransactions(){
        const vm = this;
        vm.isLoadingTransactions = true;

        return setWorkingCopyOfTransactions()
            .then(fetchParentTransactions)
            .then(createWorkingCopyOfTransactions)
            .catch(displayErrors)
            .finally(resetLoadingState);

        function setWorkingCopyOfTransactions(){
            vm.unchangedCopyOfUnassignedTransactions = vm.unassignedTransactions.filter(({ parent_transaction_id }) => !parent_transaction_id);
            const parentTransactionIds = vm.unassignedTransactions.filter(({ parent_transaction_id }) => parent_transaction_id).map(({ parent_transaction_id }) => parent_transaction_id);
            const uniqueParentIds = [...new Set(parentTransactionIds)];
            return Promise.resolve(uniqueParentIds);
        }

        function fetchParentTransactions(parentTransactionIds){
            const getParentPromises = parentTransactionIds.map(getParentTransaction);
            return Promise.all(getParentPromises);

            function getParentTransaction(parentTransactionId){
                return Vue.appApi().authorized().bankAccount().transaction(parentTransactionId).getParentTransaction().then(addParentToUnchangedCopy);

                function addParentToUnchangedCopy(response){
                    vm.unchangedCopyOfUnassignedTransactions.push(response.data);
                }
            }
        }

        function createWorkingCopyOfTransactions(){
            vm.workingCopyOfUnassignedTransactions = JSON.parse(JSON.stringify(vm.unchangedCopyOfUnassignedTransactions)).sort(vm.sortByTransactionDate);
            vm.workingCopyOfUnassignedTransactions.forEach(addDisplayProperties);
            function addDisplayProperties(transaction){
                vm.$set(transaction, 'isDirty', false);
                vm.$set(transaction, 'isSaving', false);
            }
        }

        function displayErrors(response){
            if(response.appMessage){
                vm.apiErrors.push(response.appMessage);
            } else if(typeof response === 'string'){
                vm.apiErrors.push(response);
            } else if(response.message){
                vm.apiErrors.push(response.message);
            }
        }

        function resetLoadingState(){
            vm.isLoadingTransactions = false;
        }
    }

    function save(transaction){
        const vm = this;
        vm.storeTransaction(transaction).then(updateParent).catch(displayApiErrors);

        function updateParent(){
            vm.$emit('transactions-updated');
            vm.workingCopyOfUnassignedTransactions = vm.workingCopyOfUnassignedTransactions.sort(vm.sortByTransactionDate);
        }
        function displayApiErrors(response){
            transaction.isSaving = false;
            vm.apiErrors.push(response.appMessage || response.data.message);
        }
    }

    function storeTransaction(transaction){
        const vm = this;
        transaction.isSaving = true;
        var payload = JSON.parse(JSON.stringify(transaction));
        payload.remote_transaction_date = Vue.moment(transaction.remote_transaction_date).format('YYYY-MM-DD 00:00:00');

        return Vue.appApi().authorized().bankAccount(payload.bank_account_id).transaction(payload.id).storeTransaction(payload).then(updateLocalTransaction);

        function updateLocalTransaction(response){
            transaction.isDirty = false;
            transaction.isSaving = false;
            if(!transaction.id){
                transaction.id = response.data.id;
                vm.unchangedCopyOfUnassignedTransactions.push(response.data);
            } else {
                var unchangedTransaction = vm.unchangedCopyOfUnassignedTransactions.find(({ id }) => id === response.data.id);
                unchangedTransaction.merchant = response.data.merchant;
                unchangedTransaction.amount = response.data.amount;
                unchangedTransaction.remote_transaction_date = response.data.remote_transaction_date;
            }
        }
    }

    function removeTransaction(transaction, index){
        const vm = this;
        vm.$refs.confirmDeleteModal.openModal().then((isConfirmed) => {
            if(isConfirmed){
                transaction.isSaving = true;
                const transactionIsUnsaved = !transaction.id;
                if(transactionIsUnsaved){
                    vm.workingCopyOfUnassignedTransactions.splice(index, 1);
                } else {
                    return Vue.appApi().authorized().bankAccount(transaction.bank_account_id).transaction(transaction.id)
                        .removeTransaction(transaction)
                        .then(updateParent).catch(displayApiErrors);
                }
            }
        }).catch(() => {});

        function updateParent(){
            const index = vm.workingCopyOfUnassignedTransactions.findIndex(searchTransaction => transaction.id === searchTransaction.id);
            vm.workingCopyOfUnassignedTransactions.splice(index, 1);
            vm.$emit('transactions-updated');
        }

        function displayApiErrors(response){
            transaction.isSaving = false;
            vm.apiErrors.push(response.appMessage || response.data.message);
        }
    }

    function add(){
        const vm = this;
        const newTransaction = {
            isDirty: true,
            isSaving: false,
            bank_account_id: vm.bankAccountId,
            remote_transaction_date: Vue.moment().format('MM/DD/YYYY')
        };
        vm.workingCopyOfUnassignedTransactions.push(newTransaction);
    }

    function handleRemoteTransactionDateChanged(transaction){
        transaction.isDirty = true;
    }

    function confirmUnsavedChanges(event){
        const vm = this;
        const hasUnsavedChanges = vm.dirtyTransactions.length;
        if(!vm.isCloseConfirmed && hasUnsavedChanges){
            event.preventDefault();
            vm.$refs.confirmUnsavedChangesModal.show();
        }
    }

    function closeWithoutSaving(){
        const vm = this;
        vm.isCloseConfirmed = true;
        vm.$refs.confirmUnsavedChangesModal.hide();
        vm.$refs.editTransactionsModal.hide();
    }

    function bulkSaveAndClose(){
        const vm = this;
        vm.isBulkSaveInProgress = true;
        const saveTransactionPromises = vm.dirtyTransactions.map(vm.storeTransaction);
        return Promise.all(saveTransactionPromises).then(closeModals).catch(displayApiErrors).finally(resetState);

        function closeModals(){
            vm.isCloseConfirmed = true;
            vm.$refs.editTransactionsModal.hide();
        }
        function displayApiErrors(error){
            if(error.appMessage){
                vm.apiErrors = [error.appMessage];
            }
        }
        function resetState(){
            vm.isBulkSaveInProgress = false;
            vm.$refs.confirmUnsavedChangesModal.hide();
            vm.$emit('transactions-updated');
        }
    }
    function splitTransaction(transaction){
        const vm = this;
        const parentTransactionId = transaction.id;
        vm.$refs.splitTransactionModal.openModal(parentTransactionId);
    }
    function refreshTransactions(){
        const vm = this;
        vm.$emit('transactions-updated');
        vm.$refs.editTransactionsModal.hide();
    }

    function deleteUnassignedTransactions(){
        const vm = this;
        return confirmDelete().then(makeDeleteRequest);

        function confirmDelete(){
            const message = 'Delete all unassigned credit card charges?';
            const options = {
                okVariant: 'danger',
                cancelVariant: 'light',
                okTitle: 'Yes, delete',
                hideHeader: true,
                bodyClass: 'text-center font-weight-semibold h2 mb-0 mt-3',
                footerClass: 'd-flex justify-content-center flex-row-reverse border-0',
                centered: true
            };
            return vm.$bvModal.msgBoxConfirm(message, options);
        }
        function makeDeleteRequest(isConfirmed){
            if(isConfirmed){
                vm.isBulkDeletingTransactions = true;
                return Vue.appApi().authorized().bankAccount().assignment().deleteUnassignedTransactions()
                    .then(updateTransactions)
                    .then(hideModal)
                    .catch(displayApiErrors)
                    .finally(resetState);
            }

            function updateTransactions(){
                return vm.$store.dispatch('authorized/transactions/FETCH_UNASSIGNED_TRANSACTIONS').then(displayErrorIfTransactionsRemain);

                function displayErrorIfTransactionsRemain(){
                    if(vm.unassignedTransactions.length){
                        vm.$emit('error', 'Unable to delete transactions where split transactions have already been assigned.');
                    }
                }
            }
            function hideModal(){
                vm.$refs.editTransactionsModal.hide();
            }
            function displayApiErrors(error){
                if(error.appMessage){
                    vm.apiErrors = [error.appMessage];
                }
            }
            function resetState(){
                vm.isBulkDeletingTransactions = false;
                vm.$emit('transactions-updated');
            }
        }
    }

    function sortByTransactionDate(a, b){
        const firstDate = a.transaction ? a.transaction.remote_transaction_date : a.remote_transaction_date;
        const secondDate = b.transaction ? b.transaction.remote_transaction_date : b.remote_transaction_date;
        return Vue.moment(firstDate).isAfter(secondDate) ? -1 : 1;
    }
}
