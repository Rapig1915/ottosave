export default {
    components: {},
    props: {
        popoverTriggers: {
            type: String,
            default: 'click'
        },
        bankAccount: {
            type: Object,
            default: function(){
                return {};
            }
        },
        id: {
            type: String,
            default: ''
        },
        showAssignmentAdjustment: {
            type: [String, Boolean],
            default: 'when-applicable',
            validator: function(value){
                const validValues = [true, false, 'when-applicable'];
                return validValues.includes(value);
            }
        },
        targetRef: {
            type: String,
            required: false
        },
        offset: {
            type: String,
            default: '0,0'
        },
        hideIcon: {
            type: Boolean,
            default: false
        },
        balanceLabel: {
            type: String,
            default: ''
        },
        availableBalanceLabel: {
            type: String,
            default: ''
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods(),
};

function data(){
    return {
        errorMessages: [],
        validationErrors: {},
    };
}

function getComputed(){
    return {
        isAssignmentAdjustmentVisible(){
            const vm = this;
            const isAssignableAccount = !['income', 'credit'].includes(vm.bankAccount.type);
            const hasAdjustmentToDisplay = vm.bankAccount.assignment_balance_adjustment > 0;
            return isAssignableAccount && (hasAdjustmentToDisplay || vm.showAssignmentAdjustment === true);
        },
        popoverTarget(){
            const vm = this;
            return vm.targetRef || `calculator-popover-target-${vm.bankAccount.id}-${vm.id}`;
        },
        isBankAccountParentAccount(){
            const vm = this;
            return vm.bankAccount.sub_accounts && vm.bankAccount.sub_accounts.length;
        },
        showBankAccountIcon(){
            const vm = this;
            return !vm.isBankAccountParentAccount;
        },
        institutionName(){
            const vm = this;
            let name = '';
            if(vm.isBankAccountParentAccount){
                name = vm.bankAccount.institution_account ? vm.bankAccount.institution_account.institution.name : '';
            }
            return name;
        },
        bankAccountName(){
            const vm = this;
            let name = '';
            if(vm.isBankAccountParentAccount){
                name = vm.bankAccount.institution_account ? `${vm.bankAccount.institution_account.name} x-${vm.bankAccount.institution_account.mask}` : 'Unlinked';
            } else {
                name = vm.bankAccount.name;
            }
            return name;
        },
        displayedBalanceLabel(){
            const vm = this;
            let label = 'Bank balance';
            if(vm.bankAccount.parent_bank_account_id){
                label = 'Balance';
            } else if(vm.bankAccount.type === 'credit'){
                label = 'Current balance';
            }
            return vm.balanceLabel || label;
        },
        assignmentLabel(){
            const vm = this;
            let label = 'Assigned charges';
            if(vm.isBankAccountParentAccount){
                label = 'Total assigned charges';
            } else if(vm.bankAccount.slug === 'cc_payoff'){
                label = 'Assigned for payoff';
            }
            return label;
        },
        allocationLabel(){
            const vm = this;
            let label = 'Organized income';
            if(vm.isBankAccountParentAccount){
                label = 'Total organized income';
            }
            return label;
        },
        displayedAvailableBalanceLabel(){
            const vm = this;
            let label = 'Available balance';
            if(vm.bankAccount.slug === 'cc_payoff'){
                label = 'Total';
            }
            return vm.availableBalanceLabel || label;
        }
    };
}

function created(){}

function getMethods(){
    return {
        displayErrorMessage,
        recreateToolpop,
        getLimitUsagePercent,
        onPopoverShown,
    };

    function displayErrorMessage(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data.errors;
        if(isValidationError){
            vm.validationErrors = error.data.errors;
        } else {
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function recreateToolpop(){
        const vm = this;
        vm.$refs.popoverElement.createToolpop({ target: vm.popoverTarget });
    }

    function getLimitUsagePercent(){
        const vm = this;
        const bankAccount = vm.bankAccount;
        let limitUsage = '0';
        const creditLimit = Math.abs(bankAccount.balance_limit);
        const creditUsage = Math.abs(bankAccount.balance_available);
        if(creditLimit){
            const usagePercent = Math.floor((creditUsage / creditLimit) * 100);
            limitUsage = usagePercent;
        }
        return limitUsage + '%';
    }

    function onPopoverShown(event){
        event.target.focus();

        const isIOS = window.appEnv.clientPlatform === 'ios';
        if(isIOS){
            this.$emit('popover-shown');
        }
    }
}
