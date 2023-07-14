import sortBankAccounts from 'vue_root/mixins/sortBankAccounts.mixin.js';

export default {
    components: {},
    mixins: [sortBankAccounts],
    props: {},
    data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        fromAccount: null,
        toAccount: null,
        amount: null,
        amountValidationError: '',
        isMovingMoney: false,
        apiErrors: []
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
        fromAccountSelectOptions(){
            const vm = this;
            const nullOptions = [{ label: 'Choose an account', value: null }];
            const options = vm.bankAccounts.sort(vm.byModifiedStoreOrder).map((bankAccount) => {
                return { label: bankAccount.name, value: bankAccount };
            });
            return nullOptions.concat(options);
        },
        toAccountSelectOptions(){
            const vm = this;
            const nullOptions = [{ label: 'Choose an account', value: null, disabled: true }];
            const options = vm.bankAccounts.sort(vm.byModifiedStoreOrder).map((bankAccount) => {
                const fromParentAccountId = vm.fromAccount ? vm.fromAccount.parent_bank_account_id : null;
                const isDisabled = !bankAccount.parent_bank_account_id || bankAccount.parent_bank_account_id !== fromParentAccountId || bankAccount === vm.fromAccount;
                return { label: bankAccount.name, value: bankAccount, disabled: isDisabled };
            });
            return nullOptions.concat(options);
        },
        selectedFromAccountOption: {
            get(){
                const vm = this;
                return vm.fromAccountSelectOptions.find(({ value }) => value === vm.fromAccount) || null;
            },
            set(option){
                const vm = this;
                vm.fromAccount = option.value;
                const invalidToAccountSelected = vm.fromAccount && vm.toAccount && vm.fromAccount.parent_bank_account_id && vm.fromAccount.parent_bank_account_id !== vm.toAccount.parent_bank_account_id;
                if(invalidToAccountSelected){
                    vm.toAccount = null;
                }
                vm.validateAmount();
            }
        },
        selectedToAccountOption: {
            get(){
                const vm = this;
                return vm.toAccountSelectOptions.find(({ value }) => value === vm.toAccount);
            },
            set(option){
                const vm = this;
                vm.toAccount = option.value;
                vm.validateAmount();
            }
        },
        isMoveButtonDisabled(){
            const vm = this;
            const isDisabled = !vm.fromAccount || !vm.toAccount || !vm.amount || !!vm.amountValidationError;
            return isDisabled;
        }
    };
}

function getMethods(){
    return {
        validateAmount,
        moveMoney,
        openModal,
        initializeViewModel,
        preventCloseWhileLoading
    };

    function validateAmount(){
        const vm = this;
        vm.amountValidationError = '';
        if(vm.amount < 0){
            vm.amount = 0;
            vm.amountValidationError = 'Please enter a positive amount.';
        } else if(vm.fromAccount && vm.amount > vm.fromAccount.balance_available){
            vm.amount = vm.fromAccount.balance_available;
            vm.amountValidationError = `There is only $ ${vm.fromAccount.balance_available.toFixed(2)} available in your ${vm.fromAccount.name}.`;
        }
    }

    function moveMoney(){
        const vm = this;
        vm.isMovingMoney = true;
        vm.apiErrors = [];
        const fromAccountPayload = Vue.dymUtilities.cloneObject(vm.fromAccount);
        fromAccountPayload.balance_current = new Decimal(fromAccountPayload.balance_current).minus(vm.amount).toDecimalPlaces(2).toNumber();
        const toAccountPayload = Vue.dymUtilities.cloneObject(vm.toAccount);
        toAccountPayload.balance_current = new Decimal(toAccountPayload.balance_current).plus(vm.amount).toDecimalPlaces(2).toNumber();
        const updatePromises = [
            vm.$store.dispatch('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', fromAccountPayload),
            vm.$store.dispatch('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', toAccountPayload)
        ];
        return Promise.all(updatePromises)
            .then(handleSuccess)
            .catch(displayError);
        function handleSuccess(){
            closeModal();
            vm.$emit('money-move-success');
        }
        function closeModal(){
            vm.isMovingMoney = false;
            vm.$refs.moneyMoverModal.hide();
        }
        function displayError(error){
            vm.isMovingMoney = false;
            if(error.appMessage){
                vm.apiErrors.push(error.appMessage);
            }
        }
    }

    function openModal(){
        const vm = this;
        vm.$refs.moneyMoverModal.show();
    }

    function initializeViewModel(){
        const vm = this;
        vm.apiErrors = [];
        vm.amountValidationError = '';
        vm.fromAccount = null;
        vm.toAccount = null;
        vm.isMovingMoney = false;
        vm.amount = 0;
    }

    function preventCloseWhileLoading(event){
        const vm = this;
        if(vm.isMovingMoney){
            event.preventDefault();
        }
    }
}
