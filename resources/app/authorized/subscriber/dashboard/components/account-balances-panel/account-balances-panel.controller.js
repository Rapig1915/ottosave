import CalculatorPopover from 'vue_root/components/calculator-popover/calculator-popover';
import BankConectionErrorIcon from 'vue_root/components/bank-connection-error-icon/bank-connection-error-icon.vue';
import AccountScheduleModal from 'vue_root/components/account-schedule-modal/account-schedule-modal';
import _ from 'lodash';

export default {
    components: {
        CalculatorPopover,
        BankConectionErrorIcon,
        AccountScheduleModal
    },
    filters: {
        formatDate: function(date){
            return Vue.moment(date).format('MM/DD');
        }
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
    return {
        selectedBankAccount: null,
        bankAccountTransactionVisibility: [],
        bankAccountTransactionLoading: [],
        bankAccountTransactions: [],
        scrollingBankAccount: null,
        debouncedScrollHandler: null
    };
}

function getComputed(){
    return {
        filteredAccounts(){
            const vm = this;
            return [...vm.bankAccounts].filter(account => account && account.id).map(addAttribute);

            function addAttribute(account){
                const hasAccountSchedule = account.slug !== 'income_deposit' && account.slug !== 'cc_payoff';
                account.hasAccountSchedule = hasAccountSchedule;
                return account;
            }
        },
        popoverTriggers(){
            const clientPlatform = window.appEnv.clientPlatform || 'web';
            const triggers = clientPlatform === 'web' ? 'click blur' : 'click blur';
            return triggers;
        },
        total(){
            const vm = this;
            var totalBalance = vm.bankAccounts.reduce((accumulator, bankAccount) => accumulator.plus(bankAccount.balance_available), new Decimal(0)).toDecimalPlaces(2);
            if(vm.ccPayoffAccount){
                const payoffAcountBalance = Math.max(0, vm.ccPayoffAccount.balance_available);
                totalBalance = totalBalance.plus(payoffAcountBalance).toDecimalPlaces(2);
            }
            return totalBalance.toNumber();
        },
        firstSavingsAccountId(){
            const firstSavingsAccount = this.filteredAccounts.find(bankAccount => {
                return bankAccount.type === 'savings' && !bankAccount.is_required;
            });
            return firstSavingsAccount ? firstSavingsAccount.id : 0;
        },
    };
}

function created(){
    const vm = this;
    const isMobileSizedViewport = window.innerWidth <= 576;
    if(isMobileSizedViewport){
        vm.debouncedScrollHandler = _.debounce(vm.refreshStickyAccount, 200);
        document.addEventListener('scroll', vm.debouncedScrollHandler);
    }

    vm.filteredAccounts.map(vm.loadRecentBankTransactionsHistory);
}

function getMethods(){
    return {
        emitError,
        editBalances,
        updateBankAccountBalance,
        refreshBankAccounts,
        getLimitUsagePercent,
        getCreditUtilizationColor,
        openAccountSchedule,
        toggleBankAccountTransactions,
        loadRecentBankTransactionsHistory,
        refreshStickyAccount,
        reloadTransactionHistory,
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

    function toggleBankAccountTransactions(bankAccount){
        const vm = this;
        const shouldLoadTransactions = !vm.bankAccountTransactionVisibility[bankAccount.id];
        vm.$set(vm.bankAccountTransactionVisibility, bankAccount.id, !vm.bankAccountTransactionVisibility[bankAccount.id]);

        if(shouldLoadTransactions){
            vm.loadRecentBankTransactionsHistory(bankAccount);
        }

        setTimeout(() => vm.refreshStickyAccount(), 300);
    }

    function reloadTransactionHistory(affectedAccounts){
        const vm = this;
        const accountsToReload = (affectedAccounts && affectedAccounts.length) ? affectedAccounts : vm.filteredAccounts;

        accountsToReload.map(vm.loadRecentBankTransactionsHistory);
    }

    function loadRecentBankTransactionsHistory(bankAccount){
        const vm = this;

        vm.$set(vm.bankAccountTransactionLoading, bankAccount.id, true);
        Vue.appApi().authorized().bankAccount(bankAccount.id).loadRecentBankTransactionsHistory()
            .then(setBankAccounTransactions)
            .catch(vm.emitError)
            .finally(resetLoadingState);

        function setBankAccounTransactions(response){
            vm.bankAccountTransactions[bankAccount.id] = response.data;
        }

        function resetLoadingState(){
            vm.$set(vm.bankAccountTransactionLoading, bankAccount.id, false);
        }
    }

    function refreshStickyAccount(){
        const vm = this;
        let newScrollingBankAccount = null;
        vm.filteredAccounts.some(checkBankAccountIsSticky);
        vm.scrollingBankAccount = newScrollingBankAccount;

        function checkBankAccountIsSticky(bankAccount){
            const bankAccountTitleBoxId = `bank-account-row-content-${bankAccount.id}`;
            const bankAccountTitleBox = document.getElementById(bankAccountTitleBoxId);
            if(bankAccountTitleBox){
                const bankAccountTransactionListBoxId = `collapse-transaction-list-${bankAccount.id}`;
                const bankAccountTransactionListBox = document.getElementById(bankAccountTransactionListBoxId);

                const shouldStick = bankAccountTransactionListBox && isInViewport(bankAccountTransactionListBox) && !isInViewport(bankAccountTitleBox);
                if(shouldStick){
                    newScrollingBankAccount = bankAccount;
                }
            }

            return !!newScrollingBankAccount;
        }

        function isInViewport(element){
            const rect = element.getBoundingClientRect();
            return (
                rect.bottom - rect.top > 0 &&
                (
                    (rect.top >= 85 && rect.top < (window.innerHeight || document.documentElement.clientHeight)) ||
                    (rect.bottom >= 135 && rect.bottom < (window.innerHeight || document.documentElement.clientHeight))
                )
            );
        }
    }
}
