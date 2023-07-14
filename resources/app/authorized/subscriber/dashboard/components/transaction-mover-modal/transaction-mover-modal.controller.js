import sortBankAccounts from 'vue_root/mixins/sortBankAccounts.mixin.js';

export default {
    components: {},
    mixins: [sortBankAccounts],
    filters: {
        formatDate: function(date){
            return Vue.moment(date).format('MM/DD');
        }
    },
    props: {},
    data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        transaction: null,
        fromAccount: null,
        toAccount: null,
        validationError: '',
        isMoving: false,
        apiErrors: [],
        oldDescription: null,
        isDeleting: false,
        isSavingDescription: false,
    };
}

function getComputed(){
    return {
        bankAccounts(){
            const hiddenPurposes = [
                'none',
                'primary_checking',
                'primary_savings',
                'unassigned',
                'credit'
            ];
            return Vue.dymUtilities.cloneObject(this.$store.state.authorized.bankAccounts.bankAccounts).filter(({ purpose }) => !hiddenPurposes.includes(purpose));
        },
        toAccountSelectOptions(){
            const vm = this;
            const nullOptions = [{ label: 'Choose an account', value: null, disabled: true }];
            const options = vm.bankAccounts.sort(vm.byModifiedStoreOrder).map((bankAccount) => {
                const fromParentAccountId = vm.fromAccount ? vm.fromAccount.parent_bank_account_id : null;
                const isDisabled = !bankAccount.parent_bank_account_id || bankAccount.parent_bank_account_id !== fromParentAccountId || bankAccount.id === vm.fromAccount.id;
                return { label: bankAccount.name, value: bankAccount, disabled: isDisabled };
            });
            return nullOptions.concat(options);
        },
        selectedToAccountOption: {
            get(){
                const vm = this;
                return vm.toAccountSelectOptions.find(({ value }) => value === vm.toAccount);
            },
            set(option){
                const vm = this;
                vm.toAccount = option.value;
                vm.validateMove();
            }
        },
        isMoveButtonDisabled(){
            const vm = this;
            const isDisabled = !vm.fromAccount || !vm.toAccount || !!vm.validationError;
            return isDisabled;
        },
        isDescriptionUpdated(){
            return this.oldDescription && this.oldDescription !== this.transaction.merchant;
        },
    };
}

function getMethods(){
    return {
        validateMove,
        move,
        openModal,
        initializeViewModel,
        preventCloseWhileLoading,
        updateDescription,
        revertDescriptionAndClose,
        deleteTransaction,
        showDeleteTransactionConfirmModal,
        onConfirmDeleteTransaction,
        waitOnFetchBankAccountsAndClose,
    };

    function validateMove(){
        const vm = this;
        vm.validationError = '';
        if(!vm.transaction.merchant){
            vm.validationError = 'Please enter transaction description.';
        } else if(vm.transaction.amount < 0 && vm.fromAccount.balance_available < -vm.transaction.amount){
            vm.validationError = `There is only $ ${vm.fromAccount.balance_available.toFixed(2)} available in your ${vm.fromAccount.name}.`;
        } else if(vm.toAccount && vm.transaction.amount > 0 && vm.toAccount.balance_available < vm.transaction.amount){
            vm.validationError = `There is only $ ${vm.toAccount.balance_available.toFixed(2)} available in your ${vm.toAccount.name}.`;
        }
    }

    function move(){
        const vm = this;
        vm.isMoving = true;
        vm.apiErrors = [];

        return Vue.appApi().authorized().bankAccount(vm.fromAccount.parent_bank_account_id).transaction(vm.transaction.id)
            .moveTransaction({ from: vm.fromAccount.id, to: vm.toAccount.id, merchant: vm.transaction.merchant })
            .then(handleSuccess)
            .catch(displayError);
        function handleSuccess(){
            vm.$emit('transaction-move-success', [vm.fromAccount, vm.toAccount]);
            vm.waitOnFetchBankAccountsAndClose();
        }
        function displayError(error){
            vm.isMoving = false;
            if(error.appMessage){
                vm.apiErrors.push(error.appMessage);
            }
        }
    }

    function openModal(transaction, bankAccount){
        const vm = this;
        vm.transaction = transaction;
        vm.fromAccount = bankAccount;
        vm.$refs.transactionMoverModal.show();
        vm.validateMove();
    }

    function initializeViewModel(isShowing = true){
        const vm = this;
        vm.apiErrors = [];
        vm.validationError = '';
        vm.toAccount = null;
        vm.isMoving = false;
        vm.isSavingDescription = false;
        vm.isDeleting = false;

        if(isShowing){
            if(vm.transaction && vm.oldDescription){
                vm.transaction.merchant = vm.oldDescription;
            } else {
                vm.oldDescription = vm.transaction.merchant;
            }
        } else {
            vm.oldDescription = null;
        }
    }

    function preventCloseWhileLoading(event){
        const vm = this;
        if(vm.isMoving){
            event.preventDefault();
        }

        if(vm.isDescriptionUpdated){
            event.preventDefault();
            vm.$refs.updateDescriptionConfirmModal.show();
        }
    }

    function showDeleteTransactionConfirmModal(){
        this.$refs.deleteTransactionConfirmModal.show();
    }

    function onConfirmDeleteTransaction(confirm){
        this.$refs.deleteTransactionConfirmModal.hide();

        if(confirm){
            this.deleteTransaction();
        }
    }

    function updateDescription(){
        const vm = this;
        vm.isSavingDescription = true;

        return Vue.appApi().authorized().bankAccount(vm.transaction.bank_account_id).transaction(vm.transaction.id)
            .storeTransaction(vm.transaction)
            .then(handleSuccess)
            .catch(displayError);
        function handleSuccess(){
            vm.$emit('transaction-move-success', [vm.fromAccount]);
            vm.$refs.updateDescriptionConfirmModal.hide();
            vm.waitOnFetchBankAccountsAndClose();
        }
        function displayError(error){
            vm.isSavingDescription = false;
            if(error.appMessage){
                vm.apiErrors.push(error.appMessage);
            }
        }
    }

    function revertDescriptionAndClose(){
        const vm = this;
        if(vm.transaction && vm.oldDescription){
            vm.transaction.merchant = vm.oldDescription;
        }

        vm.$refs.updateDescriptionConfirmModal.hide();
        vm.$refs.transactionMoverModal.hide();
    }

    function deleteTransaction(){
        const vm = this;
        vm.isDeleting = true;

        return Vue.appApi().authorized().bankAccount(vm.transaction.bank_account_id).transaction(vm.transaction.id)
            .removeTransaction()
            .then(handleSuccess)
            .catch(displayError);
        function handleSuccess(){
            vm.$emit('transaction-move-success', [vm.fromAccount]);
            vm.waitOnFetchBankAccountsAndClose();
        }
        function displayError(error){
            vm.isDeleting = false;
            if(error.appMessage){
                vm.apiErrors.push(error.appMessage);
            }
        }
    }

    function waitOnFetchBankAccountsAndClose(){
        const vm = this;
        setTimeout(() => handleWaitAndClose(0), 500);

        function handleWaitAndClose(numTried = 0){
            const isFetchingBankAccounts = vm.$store.state.authorized.bankAccounts.isFetchingBankAccounts;
            const shouldWaitAgain = isFetchingBankAccounts && numTried < 10;
            if(shouldWaitAgain){
                setTimeout(() => handleWaitAndClose(numTried + 1), 1000);
            } else {
                vm.initializeViewModel(false);
                vm.$refs.transactionMoverModal.hide();
            }
        }
    }
}
