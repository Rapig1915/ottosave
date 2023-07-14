import CalculatorPopover from 'vue_root/components/calculator-popover/calculator-popover';
import BankConectionErrorIcon from 'vue_root/components/bank-connection-error-icon/bank-connection-error-icon.vue';

export default {
    components: {
        CalculatorPopover,
        BankConectionErrorIcon,
    },
    props: {
        bankAccounts: {
            type: Array,
            required: true
        },
        hideEditButton: {
            type: Boolean,
            default: false
        },
        showFirstSavingsInfoPopover: {
            type: Boolean,
            default: false
        },
        useCustomEditAction: {
            type: Boolean,
            default: false
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {};
}

function getComputed(){
    return {
        filteredAccounts(){
            return this.bankAccounts;
        },
        popoverTriggers(){
            const clientPlatform = window.appEnv.clientPlatform || 'web';
            const triggers = clientPlatform === 'web' ? 'click blur' : 'click blur';
            return triggers;
        },
        total(){
            return this.bankAccounts.reduce((accumulator, bankAccount) => accumulator.plus(bankAccount.balance_available), new Decimal(0)).toDecimalPlaces(2).toNumber();
        },
        firstSavingsAccountId(){
            const firstSavingsAccount = this.filteredAccounts.find(bankAccount => {
                return bankAccount.type === 'savings' && !bankAccount.is_required;
            });
            return firstSavingsAccount ? firstSavingsAccount.id : 0;
        }
    };
}

function created(){}

function getMethods(){
    return {
        emitError,
        editBalances,
        updateBankAccountBalance,
        refreshBankAccounts,
        getLimitUsagePercent,
        getCreditUtilizationColor,
    };

    function emitError(error){
        const vm = this;
        vm.$emit('error', error);
    }

    function editBalances(){
        const vm = this;
        vm.$refs.editBalancesModal.openModal();
    }

    function updateBankAccountBalance(updatedBankAccount){
        const vm = this;
        vm.$emit('bank-account-updated', updatedBankAccount);
    }

    function refreshBankAccounts(){
        const vm = this;
        vm.$emit('refresh-requested');
    }

    function getLimitUsagePercent(bankAccount){
        let limitUsage = '0';
        const creditLimit = Math.abs(bankAccount.balance_limit);
        const creditUsage = Math.abs(bankAccount.balance_available);
        if(creditLimit){
            const usagePercent = Math.floor((creditUsage / creditLimit) * 100);
            limitUsage = usagePercent;
        }
        return limitUsage + '%';
    }

    function getCreditUtilizationColor(bankAccount){
        const vm = this;
        let usageColor = 'gray';
        if(bankAccount.balance_limit){
            const limitUsage = parseInt(vm.getLimitUsagePercent(bankAccount));
            if(limitUsage >= 0 && limitUsage <= 10){
                usageColor = 'green';
            } else if(limitUsage > 10 && limitUsage <= 30){
                usageColor = 'yellow';
            } else if(limitUsage > 30 && limitUsage <= 50){
                usageColor = 'orange';
            } else {
                usageColor = 'red';
            }
        }
        return usageColor;
    }
}
