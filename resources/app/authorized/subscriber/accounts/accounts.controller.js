import FinicityConnect from 'vue_root/components/finicity-connect-service/finicity-connect.vue';
import subscriptionVerificationMixin from 'vue_root/mixins/subscriptionVerification.mixin';
import DeleteInstitutionModal from './delete-institution-modal/delete-institution-modal.vue';
import ConfirmDeleteModal from './confirm-delete-modal/confirm-delete-modal.vue';
import ConfirmPurposeChangeModal from './confirm-purpose-change-modal/confirm-purpose-change-modal.vue';
import LinkSuccessModal from './components/link-success-modal/link-success-modal.vue';
import BankConectionErrorIcon from 'vue_root/components/bank-connection-error-icon/bank-connection-error-icon.vue';
import AccountScheduleModal from 'vue_root/components/account-schedule-modal/account-schedule-modal';
import CanUserMixin from 'vue_root/mixins/can-user.mixin.js';
import sortBankAccounts from 'vue_root/mixins/sortBankAccounts.mixin.js';

export default {
    components: {
        FinicityConnect,
        DeleteInstitutionModal,
        ConfirmDeleteModal,
        ConfirmPurposeChangeModal,
        LinkSuccessModal,
        BankConectionErrorIcon,
        AccountScheduleModal
    },
    mixins: [subscriptionVerificationMixin, CanUserMixin, sortBankAccounts],
    data: data,
    computed: getComputed(),
    watch: getWatchers(),
    created: created,
    beforeRouteLeave: confirmNavigation,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        bankAccounts: [],
        addedBankAccounts: [],
        clientPlatform: window.appEnv.clientPlatform || 'web',
        isFinicityActive: false,
        wasFixAttempted: false,
        bankAccountToRepair: null,
        bankAccountToEdit: null,
        isEditingBalances: false,
        isBulkSaving: false,
        isClearingTransfers: false,
        isClearTransfersConfirmed: false,
        accountScheduleAccount: null,
        accountPurposesWithoutScheduleItems: ['income', 'cc_payoff', 'credit', 'none'],
        downloadTransaction: {
            bankAccount: null,
            startDate: null,
            endDate: null,
            sendingRequest: false,
            errors: []
        }
    };
}

function getComputed(){
    return {
        hasAccountInRecoverableState,
        finicityRefreshStatus,
        accountList,
        accountPurposeOptions,
        dirtyBankAccounts,
        loadingBankAccounts,
        hasPendingTransfers,

        canManageFinicityAccounts,
    };

    function hasAccountInRecoverableState(){
        const vm = this;
        return vm.bankAccounts.some(({ institution_account }) => institution_account && institution_account.api_status === 'recoverable');
    }

    function finicityRefreshStatus(){
        const vm = this;
        return vm.$store.state.authorized.finicityRefreshStatus;
    }

    function accountList(){
        const vm = this;
        return vm.bankAccounts
            .filter(({ appears_in_account_list }) => appears_in_account_list)
            .concat(vm.addedBankAccounts);
    }

    function accountPurposeOptions(){
        const vm = this;
        const accountListWithSubAccounts = vm.accountList.reduce((acc, bankAccount) => {
            acc.push(bankAccount);
            acc.push(...(bankAccount.sub_accounts || []));
            return acc;
        }, []);
        const primaryCheckingOptionDisabled = vm.accountList.some(({ purpose }) => purpose === 'primary_checking');
        const primarySavingsOptionDisabled = vm.accountList.some(({ purpose }) => purpose === 'primary_savings');
        const incomeOptionDisabled = accountListWithSubAccounts.some(({ purpose }) => purpose === 'income');
        const billsOptionDisabled = accountListWithSubAccounts.some(({ purpose }) => purpose === 'bills');
        const spendingOptionDisabled = accountListWithSubAccounts.some(({ purpose }) => purpose === 'spending');
        const payoffOptionDisabled = accountListWithSubAccounts.some(({ purpose }) => purpose === 'cc_payoff');
        const options = [
            { text: '', value: '', disabled: true, defaultName: '' },
            { text: 'Primary Checking', value: 'primary_checking', disabled: primaryCheckingOptionDisabled, defaultName: '' },
            { text: 'Primary Savings', value: 'primary_savings', disabled: primarySavingsOptionDisabled, defaultName: '' },
            { text: 'Collecting income', value: 'income', disabled: incomeOptionDisabled, defaultName: 'Income Account' },
            { text: 'Paying bills', value: 'bills', disabled: billsOptionDisabled, defaultName: 'Bills Account' },
            { text: 'Necessary spending', value: 'spending', disabled: spendingOptionDisabled, defaultName: 'Spending Account' },
            { text: 'Spending or saving', value: 'savings', disabled: false, defaultName: '' },
            { text: 'Credit card payments', value: 'cc_payoff', disabled: payoffOptionDisabled, defaultName: 'Credit Card Payoff Account' },
            { text: 'Credit Card', value: 'credit', disabled: false, defaultName: 'Credit Card' },
            { text: 'None (hidden on app)', value: 'none', disabled: false, defaultName: '' },
        ];
        const isIncomeOptionVisible = vm.accountList.find(({ purpose }) => purpose === 'income') || (primaryCheckingOptionDisabled && !incomeOptionDisabled);
        if(!isIncomeOptionVisible){
            const incomeOptionIndex = options.findIndex(({ value }) => value === 'income');
            options.splice(incomeOptionIndex, 1);
        }
        const isBillsOptionVisible = vm.accountList.find(({ purpose }) => purpose === 'bills') || (primaryCheckingOptionDisabled && !billsOptionDisabled);
        if(!isBillsOptionVisible){
            const billsOptionIndex = options.findIndex(({ value }) => value === 'bills');
            options.splice(billsOptionIndex, 1);
        }
        const isSpendingOptionVisible = vm.accountList.find(({ purpose }) => purpose === 'spending') || (primaryCheckingOptionDisabled && !spendingOptionDisabled);
        if(!isSpendingOptionVisible){
            const spendingOptionIndex = options.findIndex(({ value }) => value === 'spending');
            options.splice(spendingOptionIndex, 1);
        }
        const isPayoffOptionVisible = vm.accountList.find(({ purpose }) => purpose === 'cc_payoff') || (primaryCheckingOptionDisabled && !payoffOptionDisabled);
        if(!isPayoffOptionVisible){
            const payoffOptionIndex = options.findIndex(({ value }) => value === 'cc_payoff');
            options.splice(payoffOptionIndex, 1);
        }
        return options;
    }

    function dirtyBankAccounts(){
        const vm = this;
        return vm.accountList.filter(({ isDirty }) => isDirty);
    }

    function loadingBankAccounts(){
        const vm = this;
        return vm.$store.state.authorized.bankAccounts.isFetchingBankAccounts;
    }

    function hasPendingTransfers(){
        const vm = this;
        return vm.bankAccounts.some(({ allocation_balance_adjustment }) => !!allocation_balance_adjustment);
    }

    function canManageFinicityAccounts(){
        const vm = this;
        return vm.canUser('manage finicity-accounts') && !vm.$store.getters['user/isLoading'];
    }
}

function getWatchers(){
    return {
        finicityRefreshStatus(newStatus, oldStatus){
            const vm = this;
            const refreshCompleted = oldStatus === 'pending' && newStatus !== 'pending';
            if(refreshCompleted){
                created.apply(vm);
                vm.wasFixAttempted = false;
            }
        },
        loadingBankAccounts(newStatus, oldStatus){
            const vm = this;
            if(oldStatus && !newStatus){
                vm.refreshBankAccountsList();
            }
        },
        '$route'(newRoute){
            const vm = this;
            vm.openFinicityConnectFix(newRoute);
        }
    };
}

function created(){
    const vm = this;
    addCryptoLib();
    vm.initializeView().then(openFinicityConnectFix);

    function addCryptoLib(){
        const scriptUrlCore = 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/core.js';
        const scriptUrlLib = 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/md5.js';
        const isCryptoLibLoaded = checkIsCryptoLibLoaded();

        if(!isCryptoLibLoaded){
            addScript(scriptUrlCore, () => addScript(scriptUrlLib));
        }

        function checkIsCryptoLibLoaded(){
            for(var i = 0; i < document.scripts.length; i++){
                if(document.scripts[i].src === scriptUrlLib){
                    return true;
                }
            }
            return false;
        }

        function addScript(src, callback){
            var s = document.createElement('script');
            s.setAttribute('src', src);
            if(callback){
                s.onload = callback;
            }
            document.head.appendChild(s);
        }
    }

    function openFinicityConnectFix(){
        vm.openFinicityConnectFix(vm.$route);
    }
}

function confirmNavigation(to, from, next){
    const vm = this;
    const hasUnsavedChanges = vm.dirtyBankAccounts.length;

    if(hasUnsavedChanges){
        const modalOptions = {
            okVariant: 'muted-success',
            okTitle: 'Save all',
            cancelTitle: 'Leave page',
            bodyClass: 'text-center pt-5 pb-4 font-weight-semibold text-dark',
            footerClass: 'pb-5 justify-content-center border-0 flex-row-reverse',
            hideHeaderClose: false,
            cancelVariant: 'light',
            centered: true,
            noCloseOnBackdrop: true,
            noCloseOnEsc: true,
        };
        vm.$bvModal.msgBoxConfirm('You have unsaved changes.', modalOptions)
            .then(handleConfirmation)
            .then(next);
    } else {
        next();
    }

    function handleConfirmation(saveAll){
        if(saveAll){
            return vm.saveAllChanges();
        }
    }
}

function getMethods(){
    return {
        refreshBankAccountsList,
        refreshSubAccounts,
        sortBankAccounts,
        setBankAccountDisplayProperties,
        confirmDelete,
        deleteBankAccount,
        openFinicityConnect,
        completeFinicityConnect,
        openFinicityConnectFix,
        completeFinicityConnectFix,
        displayError,
        initializeView,
        updateBankAccount,
        saveAllChanges,
        updateBankAccountPurpose,
        addUnlinkedAccount,
        removeAccountsForInstitution,
        openEditAccountModal,
        openDownloadTransactionsModal,
        downloadTransactionWithDateRange,
        saveEditAccountInfo,
        removeSubAccount,
        addSubAccount,
        cancelSubAccountEdits,
        editSubAccountBalances,
        clearPendingTransfers,
        openAccountSchedule,
        updateSubAccountBalances,
        overrideInstitutionBalance
    };

    function refreshBankAccountsList(reset = false){
        const vm = this;
        const localCopyOfBankAccounts = JSON.parse(JSON.stringify(vm.$store.state.authorized.bankAccounts.bankAccounts));
        localCopyOfBankAccounts.forEach(vm.setBankAccountDisplayProperties);
        if(reset){
            vm.bankAccounts = localCopyOfBankAccounts;
            vm.addedBankAccounts = [];
        } else {
            localCopyOfBankAccounts.forEach(updateDisplayedAccountList);
        }
        vm.sortBankAccounts();
        vm.bankAccounts.forEach(vm.refreshSubAccounts);
        return Promise.resolve();
        function updateDisplayedAccountList(bankAccount){
            const workingCopy = vm.bankAccounts.find(({ id }) => id === +bankAccount.id);
            if(!workingCopy){
                vm.bankAccounts.push(bankAccount);
            } else if(!workingCopy.isDirty){
                Object.assign(workingCopy, bankAccount);
            }
        }
    }

    function refreshSubAccounts(bankAccount){
        const vm = this;
        const unalteredBankAccount = vm.$store.getters['authorized/bankAccounts/getBankAccountById'](bankAccount.id);
        if(unalteredBankAccount){
            Object.assign(bankAccount, unalteredBankAccount);
        }
        bankAccount.isDirty = false;
        const subAccounts = bankAccount.sub_accounts.map(getLoadedSubAccount).filter(subAccount => subAccount).sort(vm.byModifiedStoreOrder);
        vm.$set(bankAccount, 'sub_accounts', subAccounts);
        function getLoadedSubAccount(subAccount){
            let unalteredSubAccount = vm.$store.getters['authorized/bankAccounts/getBankAccountById'](subAccount.id);
            if(unalteredSubAccount){
                unalteredSubAccount = JSON.parse(JSON.stringify(unalteredSubAccount));
                vm.setBankAccountDisplayProperties(unalteredSubAccount);
            }
            return unalteredSubAccount || null;
        }
    }
    function sortBankAccounts(){
        const vm = this;
        vm.bankAccounts = vm.bankAccounts.sort((a, b) => {
            const accountPurposeOrder = [
                'unassigned',
                'primary_checking',
                'income',
                'bills',
                'spending',
                'primary_savings',
                'savings',
                'cc_payoff',
                'credit',
                'none'
            ];
            const checkedPurposes = [];
            let isHigher = false;
            accountPurposeOrder.forEach(purpose => {
                isHigher = isHigher || a.purpose === purpose && !checkedPurposes.includes(b.purpose);
                if(!isHigher){
                    checkedPurposes.push(purpose);
                }
            });
            if(a.purpose === b.purpose){
                isHigher = a.created_at < b.created_at;
            }
            return isHigher ? -1 : 0;
        });
    }

    function setBankAccountDisplayProperties(bankAccount, defaults = {}){
        const vm = this;
        vm.$set(bankAccount, 'isLoading', (defaults.isLoading || false));
        vm.$set(bankAccount, 'isSaving', (defaults.isSaving || false));
        vm.$set(bankAccount, 'isDeleting', (defaults.isDeleting || false));
        vm.$set(bankAccount, 'isDirty', (defaults.isDirty || false));
        vm.$set(bankAccount, 'balanceError', (defaults.balanceError || ''));
        vm.$set(bankAccount, 'isEditingSubAccountBalances', (defaults.isEditingSubAccountBalances || false));
        vm.$set(bankAccount, 'canCurrentUserManage', !bankAccount.institution_account || vm.canManageFinicityAccounts);
    }

    function confirmDelete(bankAccount){
        const vm = this;
        if(!bankAccount.id){
            const index = vm.bankAccounts.indexOf(bankAccount);
            vm.bankAccounts.splice(index, 1);
        } else {
            vm.$refs.confirmDeleteModal.openModal(bankAccount);
        }
    }

    function deleteBankAccount(bankAccount){
        const vm = this;
        bankAccount.isDeleting = true;
        const deletePromise = vm.$store.dispatch('authorized/bankAccounts/DELETE_BANK_ACCOUNT', bankAccount.id);

        return deletePromise.then(removeFromAccontList).catch(handleDeleteError).finally(resetLoadingState);

        function removeFromAccontList(){
            const accountIndex = vm.bankAccounts.findIndex(({ id }) => id === bankAccount.id);
            if(accountIndex >= 0){
                vm.bankAccounts.splice(accountIndex, 1);
            }
            if(bankAccount.parent_bank_account_id){
                const parentBankAccount = vm.bankAccounts.find(({ id }) => id === bankAccount.parent_bank_account_id);
                if(parentBankAccount){
                    const subAccountIndex = parentBankAccount.sub_accounts.findIndex(({ id }) => id === bankAccount.id);
                    if(subAccountIndex >= 0){
                        parentBankAccount.sub_accounts.splice(subAccountIndex, 1);
                        const mainSubAccount = parentBankAccount.sub_accounts.find(sub_account => sub_account.sub_account_order === 0);
                        mainSubAccount.balance_current = new Decimal(mainSubAccount.balance_current || 0)
                            .plus(bankAccount.balance_current || 0)
                            .toDecimalPlaces(2)
                            .toNumber();
                    }
                }
            }
        }

        function handleDeleteError(error){
            if(error.data && error.data.slug === 'finicity_oauth_delete'){
                vm.$refs.deleteInstitutionModal.openModal(bankAccount.institution_account.institution);
            } else if(error.appMessage){
                vm.errorMessages.push(error.appMessage);
            }
        }
        function resetLoadingState(){
            bankAccount.isDeleting = false;
        }
    }

    function displayError(errorMessage){
        const vm = this;
        vm.errorMessages = [errorMessage];
        vm.isFinicityActive = false;
    }

    function openFinicityConnect(){
        const vm = this;
        vm.isFinicityActive = true;
        vm.$refs.finicityService.openFinicityConnect();
    }

    function completeFinicityConnect(bankAccounts){
        const vm = this;
        vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS')
            .then(vm.refreshBankAccountsList)
            .finally(() => {
                vm.isFinicityActive = false;
            });
        vm.$refs.linkSuccessModalRef.openModal();
    }

    function openFinicityConnectFix(route){
        const vm = this;
        if(route.query.settings === 'open'){
            const bankAccount = vm.bankAccounts.find(({ id }) => id === +route.query.id);
            if(bankAccount){
                vm.isFinicityActive = true;
                vm.wasFixAttempted = true;
                vm.bankAccountToRepair = bankAccount;
                vm.$refs.finicityService.openFinicityConnectFix(bankAccount.institution_account.institution.id);
            }
            const query = Object.assign({}, route.query);
            delete query.settings;
            delete query.id;
            vm.$router.replace({ query });
        } else if(route.query.openConnectModal === true){
            const query = Object.assign({}, route.query);
            delete query.openConnectModal;
            vm.$router.replace({ query });

            vm.openFinicityConnect();
        }
    }

    function completeFinicityConnectFix(){
        const vm = this;
        const institutionId = vm.bankAccountToRepair.institution_account.institution.id;
        vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS', institutionId).then(vm.refreshBankAccountsList).catch(vm.displayError).finally(resetSpinner);
        const successMessage = `${vm.bankAccountToRepair.institution_account.institution.name} is connected again! We will now refresh your account to bring in your latest balances and credit card charges. This may take a minute or two.`;
        vm.$bvToast.toast(successMessage, {
            title: 'Link Repaired',
            variant: 'success',
        });
        function resetSpinner(){
            vm.isFinicityActive = false;
            vm.bankAccountToRepair = null;
        }
    }

    function initializeView(){
        const vm = this;
        return vm.refreshBankAccountsList();
    }

    function updateBankAccount(bankAccount){
        const vm = this;
        if(bankAccount.id){
            const localCopy = vm.bankAccounts.find(({ id }) => id === bankAccount.id);
            Object.assign(localCopy, bankAccount);
            bankAccount = localCopy;
        }
        const subAccounts = JSON.parse(JSON.stringify(bankAccount.sub_accounts || []));
        bankAccount.isSaving = true;
        vm.errorMessages = [];
        vm.validationErrors = {};
        return vm.$store.dispatch('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', bankAccount)
            .then(updateBankAccountSuccess)
            .catch(updateBankAccountFailure)
            .finally(resetLoadingState);

        function updateBankAccountSuccess(response){
            const bankAccountId = +response.data.id;
            const saveSubAccountPromises = subAccounts.map(saveSubAccount);
            return Promise.all(saveSubAccountPromises).then(updateLocalBankAccount);

            function saveSubAccount(subAccount, index){
                subAccount.parent_bank_account_id = bankAccountId;
                const isMainSubAccount = subAccount.sub_account_order === 0;
                subAccount.sub_account_order = isMainSubAccount ? subAccount.sub_account_order : Math.max(index, 1);
                const payload = JSON.parse(JSON.stringify(subAccount));
                return vm.$store.dispatch('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', payload).then(updateSubAccount);
                function updateSubAccount(response){
                    vm.setBankAccountDisplayProperties(subAccount);
                    Object.assign(subAccount, JSON.parse(JSON.stringify(response.data)));
                }
            }
            function updateLocalBankAccount(){
                if(!bankAccount.id){
                    const addedBankAccountIndex = vm.addedBankAccounts.indexOf(bankAccount);
                    if(addedBankAccountIndex >= 0){
                        vm.addedBankAccounts.splice(bankAccountIndex, 1);
                    }
                    const bankAccountIndex = vm.bankAccounts.findIndex(({ id }) => id === bankAccountId);
                    if(bankAccountIndex < 0){
                        vm.bankAccounts.push(response.data);
                    }
                    vm.sortBankAccounts();
                }
                const localCopy = vm.bankAccounts.find(({ id }) => id === response.data.id);
                Object.assign(localCopy, response.data);
                vm.setBankAccountDisplayProperties(localCopy);
                vm.refreshSubAccounts(localCopy);
            }
        }

        function updateBankAccountFailure(error){
            const errorMessage = error.appMessage || (error.data && error.data.message);
            if(errorMessage){
                vm.errorMessages.push(errorMessage);
            }
        }

        function resetLoadingState(){
            bankAccount.isSaving = false;
        }
    }

    function saveAllChanges(){
        const vm = this;
        vm.isBulkSaving = true;
        const savePromises = vm.dirtyBankAccounts.filter(({ isSaving }) => !isSaving).map(vm.updateBankAccount);
        return Promise.all(savePromises).then(() => {
            vm.bankAccounts.splice(0);
            vm.addedBankAccounts.splice(0);
            vm.$nextTick(() => {
                vm.refreshBankAccountsList(true);
            });
        }).finally(resetLoadingState);
        function resetLoadingState(){
            vm.isBulkSaving = false;
        }
    }

    function updateBankAccountPurpose(bankAccount, purpose){
        const vm = this;
        const originalPurpose = bankAccount.purpose;
        confirmPurposeChange().then(changePurpose).catch(cancelChange);

        function confirmPurposeChange(){
            const purposesRequiringConfirmation = ['primary_checking', 'primary_savings'];
            if(purposesRequiringConfirmation.includes(originalPurpose)){
                return promptForConfirmation();
            } else {
                return Promise.resolve();
            }
            function promptForConfirmation(){
                return vm.$refs.confirmPurposeChangeModal.openModal(originalPurpose);
            }
        }

        function changePurpose(){
            bankAccount.purpose = purpose;
            const option = vm.accountPurposeOptions.find(({ value }) => value === purpose);
            if(option){
                bankAccount.name = option.defaultName;
            }
            bankAccount.icon = purpose === 'credit' ? 'credit-card' : 'square';
            if(['primary_checking', 'primary_savings'].includes(purpose)){
                populateSubAccounts();
            } else {
                bankAccount.sub_accounts = [];
                setBankAccountColor(bankAccount);
            }
            bankAccount.isDirty = true;

            function setBankAccountColor(bankAccount){
                const colorsByPurpose = {
                    savings: [
                        'violet',
                        'orange',
                        'cyan',
                        'yellow',
                        'purple',
                    ],
                    credit: [
                        'gold',
                        'silver',
                        'bronze',
                    ],
                    income: ['gray'],
                    bills: ['pink'],
                    spending: ['green'],
                    cc_payoff: ['gray-alt'],
                    none: [''],
                };
                const allBankAccounts = vm.bankAccounts.reduce((acc, bankAccount) => {
                    acc.push(bankAccount);
                    if(bankAccount.sub_accounts){
                        acc.push(...bankAccount.sub_accounts);
                    }
                    return acc;
                }, []).filter((bankAccount, index, array) => {
                    const bankAccountIndex = array.findIndex(({ id }) => id === bankAccount.id);
                    return !bankAccount.id || bankAccountIndex === index;
                });

                const accountsForPurpose = allBankAccounts.filter(({ purpose }) => purpose === bankAccount.purpose);
                let colorChoices = JSON.parse(JSON.stringify(colorsByPurpose[bankAccount.purpose]));
                accountsForPurpose.forEach(account => {
                    const colorIndex = colorChoices.indexOf(account.color);
                    if(colorIndex >= 0){
                        colorChoices.splice(colorIndex, 1);
                    }
                    if(colorChoices.length === 0){
                        if(purpose === 'credit'){
                            const savingsChoices = JSON.parse(JSON.stringify(colorsByPurpose.savings));
                            const creditChoices = JSON.parse(JSON.stringify(colorsByPurpose.credit));
                            colorChoices = savingsChoices.concat(creditChoices);
                        } else {
                            colorChoices = JSON.parse(JSON.stringify(colorsByPurpose[bankAccount.purpose]));
                        }
                    }
                });
                bankAccount.color = colorChoices.shift();
            }

            function populateSubAccounts(){
                let subAccounts = [];
                if(purpose === 'primary_checking'){
                    const defaultSubAccounts = [
                        new BankAccount({
                            name: 'Bills Account',
                            purpose: 'bills',
                            slug: 'monthly_bills',
                            appears_in_account_list: false,
                            color: 'pink',
                            balance_current: 0
                        }),
                        new BankAccount({
                            name: 'Spending Account',
                            purpose: 'spending',
                            slug: 'everyday_checking',
                            appears_in_account_list: false,
                            color: 'green',
                        }),
                        new BankAccount({
                            name: 'Income Account',
                            purpose: 'income',
                            slug: 'income_deposit',
                            appears_in_account_list: false,
                            color: 'gray',
                            balance_current: bankAccount.balance_current || 0
                        }),
                        new BankAccount({
                            name: 'Credit Card Payoff Account',
                            purpose: 'cc_payoff',
                            slug: 'cc_payoff',
                            appears_in_account_list: false,
                            color: 'gray-alt',
                        }),
                    ];
                    subAccounts = defaultSubAccounts.filter(({ purpose }) => {
                        const accountWithPurposeExists = vm.bankAccounts.find(bankAccount => {
                            return bankAccount.appears_in_account_list && bankAccount.purpose === purpose;
                        });
                        return !accountWithPurposeExists;
                    });
                } else if(purpose === 'primary_savings'){
                    subAccounts = [
                        new BankAccount({
                            name: '',
                            purpose: 'savings',
                            balance_current: bankAccount.balance_current || 0
                        }),
                        new BankAccount({
                            purpose: 'savings'
                        }),
                    ];
                }
                bankAccount.sub_accounts = subAccounts;
                subAccounts.forEach(setBankAccountColor);
                subAccounts.forEach(subAccount => vm.setBankAccountDisplayProperties(subAccount, { isDirty: true }));
                subAccounts.forEach((subAccount, index) => {
                    vm.$set(subAccount, 'sub_account_order', index);
                });
            }
        }
        function cancelChange(){
            vm.$set(bankAccount, 'isRerenderingPurposeDropdown', true);
            Vue.nextTick(() => {
                bankAccount.isRerenderingPurposeDropdown = false;
                bankAccount.purpose = originalPurpose;
            });
        }
    }

    function addUnlinkedAccount(){
        const vm = this;
        const unlinkedAccount = new BankAccount({ appears_in_account_list: true });
        vm.setBankAccountDisplayProperties(unlinkedAccount);
        unlinkedAccount.isDirty = true;
        vm.addedBankAccounts.push(unlinkedAccount);
    }

    function removeAccountsForInstitution(institutionId){
        const vm = this;
        const accountsRelatedToInstitution = vm.bankAccounts.filter(({ institution_account }) => institution_account && institution_account.institution_id === institutionId);
        const removalPromises = accountsRelatedToInstitution.map(removeAccount);
        return Promise.all(removalPromises);

        function removeAccount(bankAccount){
            bankAccount.institution_account_id = null;
            vm.$store.commit('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', Vue.dymUtilities.cloneObject(bankAccount));
            return vm.deleteBankAccount(bankAccount);
        }
    }

    function openEditAccountModal(bankAccount){
        const vm = this;
        vm.bankAccountToEdit = JSON.parse(JSON.stringify(bankAccount));
        vm.bankAccountToEdit.balance_limit_override = vm.bankAccountToEdit.balance_limit;
        vm.$refs.editAccountInfoModal.show();
    }

    function openDownloadTransactionsModal(bankAccount){
        const vm = this;
        vm.downloadTransaction.bankAccount = JSON.parse(JSON.stringify(bankAccount));
        vm.downloadTransaction.errors = [];
        vm.downloadTransaction.startDate = null;
        vm.downloadTransaction.endDate = null;
        vm.$refs.downloadAccountTransactionsModal.show();
    }

    function downloadTransactionWithDateRange(){
        const vm = this;
        const canSendRequest = !vm.downloadTransaction.sendingRequest &&
            vm.downloadTransaction.bankAccount && vm.downloadTransaction.startDate && vm.downloadTransaction.endDate &&
            Vue.moment(vm.downloadTransaction.startDate).isBefore(Vue.moment(vm.downloadTransaction.endDate));
        if(!canSendRequest){
            return;
        }

        if(vm.downloadTransaction.bankAccount && vm.downloadTransaction.bankAccount.institution_account){
            const possibleEarliestDate = Vue.moment(vm.downloadTransaction.bankAccount.institution_account.linked_at).subtract(3, 'months');
            if(possibleEarliestDate.isAfter(Vue.moment(vm.downloadTransaction.startDate))){
                vm.downloadTransaction.errors.push(`You can only download transactions from ${possibleEarliestDate.format('MM/DD/YYYY')}`);
                return;
            }
        }

        const payload = {
            start_date: Vue.moment(vm.downloadTransaction.startDate).format('YYYY-MM-DD'),
            end_date: Vue.moment(vm.downloadTransaction.endDate).format('YYYY-MM-DD'),
        };
        vm.downloadTransaction.sendingRequest = true;
        vm.downloadTransaction.errors = [];
        Vue.appApi().authorized().bankAccount(vm.downloadTransaction.bankAccount.id).requestTransactionDownload(payload)
            .then(executeOnSuccess)
            .catch(displayError)
            .finally(resetLoadingState);

        function executeOnSuccess({ data }){
            const downloadUrl = formatDownloadUrl(data.token);
            if(downloadUrl){
                clearDownloadTransactionInfo();
                vm.$refs.downloadAccountTransactionsModal.hide();
                window.open(downloadUrl, '_blank');
            }

            function formatDownloadUrl(token){
                const baseURI = `${window.appEnv.baseURL}/download-account-transactions`;
                return `${baseURI}/${token}`;
            }
        }

        function displayError(error){
            if(error && error.appMessage){
                vm.downloadTransaction.errors.push(error.appMessage);
            }
        }

        function clearDownloadTransactionInfo(){
            vm.downloadTransaction.bankAccount = null;
            vm.downloadTransaction.startDate = null;
            vm.downloadTransaction.endDate = null;
            vm.downloadTransaction.errors = [];
        }

        function resetLoadingState(){
            vm.downloadTransaction.sendingRequest = false;
        }
    }

    function saveEditAccountInfo(){
        const vm = this;
        vm.bankAccountToEdit.isSaving = true;
        const bank_account_id = vm.bankAccountToEdit.id;
        const bankPayload = vm.bankAccountToEdit;
        const institutionPayload = vm.bankAccountToEdit.institution_account;
        return saveBankAccountToEdit(bankPayload);

        function saveBankAccountToEdit(bankPayload){
            return vm.$store.dispatch('authorized/bankAccounts/UPDATE_BANK_ACCOUNT', bankPayload)
                .then(saveInstitutionAccount)
                .catch(handleError);
            function saveInstitutionAccount(bankResponse){
                if(bankPayload.institution_account){
                    return vm.$store.dispatch('authorized/bankAccounts/UPDATE_INSTITUTION_ACCOUNT', { bank_account_id, institutionPayload })
                        .then(updateLocalInstitution);
                } else {
                    updateLocalBankAccountEditing(bankResponse);
                }

                function updateLocalInstitution(institutionResponse){
                    bankResponse.data.institution_account = institutionResponse.data;
                    updateLocalBankAccountEditing(bankResponse);
                }
                function updateLocalBankAccountEditing(bankResponse){
                    const bankAccount = vm.bankAccounts.find(({ id }) => id === +bank_account_id);
                    Object.assign(bankAccount, bankResponse.data);
                    handleFinal();
                }
            }
            function handleError(error){
                vm.errorMessages.push(error.appMessage || error.data.message);
            }
            function handleFinal(){
                vm.bankAccountToEdit = null;
                vm.$refs.editAccountInfoModal.hide();
            }
        }
    }

    function removeSubAccount(subAccount, bankAccount){
        const vm = this;
        if(subAccount.id){
            vm.$refs.confirmDeleteModal.openModal(subAccount);
        } else {
            const subAccountIndex = bankAccount.sub_accounts.findIndex(sub_account => sub_account === subAccount);
            if(subAccountIndex >= 0){
                bankAccount.sub_accounts.splice(subAccountIndex, 1);
            }
        }
    }

    function addSubAccount(bankAccount){
        const vm = this;
        const subAccount = new BankAccount({
            parent_bank_account_id: bankAccount.id,
            sub_account_order: bankAccount.sub_accounts.length,
            purpose: 'savings'
        });
        vm.updateBankAccountPurpose(subAccount, 'savings');
        subAccount.name = '';
        vm.setBankAccountDisplayProperties(subAccount, { isDirty: true });
        bankAccount.isDirty = true;
        bankAccount.sub_accounts.push(subAccount);
    }

    function cancelSubAccountEdits(bankAccount){
        const vm = this;
        vm.refreshSubAccounts(bankAccount);
        bankAccount.isEditingSubAccountBalances = false;
    }

    function editSubAccountBalances(bankAccount){
        const vm = this;
        if(bankAccount.slug === 'primary_checking'){
            displayConfirmEditModal().then(beginEdit);
        } else {
            beginEdit(true);
        }

        function displayConfirmEditModal(){
            const options = {
                title: 'Are you sure you want to edit these balances?',
                titleTag: 'h3',
                footerClass: 'd-flex justify-content-center',
                okTitle: 'Continue',
                okVariant: 'gray-e',
                cancelVariant: 'purple',
                centered: true
            };
            const message = 'The balances associated with your Primary Checking are calculated by the app to make sure you always have enough money to pay your bills and cover your credit card charges. Please edit these balances with caution.';
            return vm.$bvModal.msgBoxConfirm(message, options);
        }

        function beginEdit(confirmed){
            if(confirmed){
                bankAccount.isEditingSubAccountBalances = true;
            }
        }
    }

    function clearPendingTransfers(){
        const vm = this;
        vm.isClearingTransfers = true;
        Vue.appApi().authorized().bankAccount(0).clearPendingTransfers().then(updateBankAccountBalances).catch(displayError).finally(resetSpinner);

        function updateBankAccountBalances(){
            return vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
        }

        function displayError(error){
            if(error && error.appMessage){
                vm.errorMessages.push(error.appMessage);
            }
        }

        function resetSpinner(){
            vm.isClearingTransfers = false;
        }
    }

    function openAccountSchedule(bankAccount){
        const vm = this;
        vm.accountScheduleAccount = bankAccount;
        Vue.nextTick(() => {
            vm.$refs.accountScheduleModal.show();
        });
    }

    function updateSubAccountBalances(subAccount, parentBankAccount){
        const mainSubAccount = parentBankAccount.sub_accounts.find(sub_account => sub_account.sub_account_order === 0);
        if(subAccount !== mainSubAccount){
            correctInvalidBalanceAdjustments();
        }
        const remainingParentBalance = parentBankAccount.sub_accounts.filter(sub_account => sub_account.id !== mainSubAccount.id).reduce((acc, sub_account) => {
            return acc.minus(sub_account.balance_current);
        }, new Decimal(parentBankAccount.balance_current)).toDecimalPlaces(2).toNumber();
        mainSubAccount.balance_current = remainingParentBalance;
        mainSubAccount.balance_available = new Decimal(mainSubAccount.balance_available).plus(getChangedAmount(mainSubAccount)).toDecimalPlaces(2).toNumber();
        mainSubAccount.isDirty = true;

        if(subAccount.slug === 'cc_payoff'){
            subAccount.balance_available = new Decimal(subAccount.balance_current)
                .plus(subAccount.assignment_balance_adjustment)
                .plus(subAccount.allocation_balance_adjustment)
                .toDecimalPlaces(2).toNumber();
        } else {
            subAccount.balance_available = new Decimal(subAccount.balance_available).plus(getChangedAmount(subAccount)).toDecimalPlaces(2).toNumber();
        }

        function getChangedAmount(bankAccount){
            return new Decimal(bankAccount.balance_current)
                .minus(bankAccount.assignment_balance_adjustment)
                .plus(bankAccount.allocation_balance_adjustment)
                .minus(bankAccount.balance_available)
                .toDecimalPlaces(2).toNumber();
        }
        function correctInvalidBalanceAdjustments(){
            subAccount.balanceError = '';
            let maximumAllowedBalance = 0;
            if(subAccount.slug !== 'cc_payoff'){
                const isBalanceBelowMinimum = subAccount.balance_current < subAccount.assignment_balance_adjustment;
                if(isBalanceBelowMinimum){
                    subAccount.balance_current = subAccount.assignment_balance_adjustment;
                    subAccount.balanceError = `You must have at least $ ${subAccount.assignment_balance_adjustment.toFixed(2)} in this virtual account to cover your assigned credit card chages.`;
                }
                maximumAllowedBalance = mainSubAccount.balance_available >= 0
                    ? new Decimal(mainSubAccount.balance_available)
                        .plus(subAccount.balance_available)
                        .plus(subAccount.assignment_balance_adjustment)
                        .toDecimalPlaces(2).toNumber()
                    : new Decimal(mainSubAccount.balance_available)
                        .minus(parentBankAccount.balance_current)
                        .toDecimalPlaces(2).toNumber();
            } else {
                const isBalanceBelowMinimum = subAccount.balance_current < 0;
                if(isBalanceBelowMinimum){
                    subAccount.balance_current = 0;
                }
                maximumAllowedBalance = mainSubAccount.balance_available >= 0
                    ? new Decimal(mainSubAccount.balance_available)
                        .plus(subAccount.balance_available)
                        .minus(subAccount.assignment_balance_adjustment)
                        .toDecimalPlaces(2).toNumber()
                    : new Decimal(mainSubAccount.balance_available)
                        .minus(parentBankAccount.balance_current)
                        .toDecimalPlaces(2).toNumber();
            }
            maximumAllowedBalance = Math.max(maximumAllowedBalance, 0);

            const isBalanceAboveMaximum = subAccount.balance_current > maximumAllowedBalance;
            if(isBalanceAboveMaximum){
                subAccount.balance_current = maximumAllowedBalance;
                subAccount.balanceError = `You have a maximum of $ ${subAccount.balance_current.toFixed(2)} available to allocate into this virtual account.`;
            }
        }
    }

    function overrideInstitutionBalance(bankAccount){
        const vm = this;
        bankAccount.isDirty = true;
        bankAccount.is_balance_overridden = true;
        const mainSubAccount = bankAccount.sub_accounts.find(sub_account => sub_account.sub_account_order === 0);
        if(mainSubAccount){
            vm.updateSubAccountBalances(mainSubAccount, bankAccount);
        }
    }
}

function BankAccount(defaults = {}){
    this.parent_bank_account_id = defaults.parent_bank_account_id || null;
    this.sub_accounts = defaults.sub_accounts || [];
    this.sub_account_order = isNaN(parseInt(defaults.sub_account_order)) ? null : defaults.sub_account_order;
    this.name = defaults.name || '';
    this.slug = defaults.slug || null;
    this.color = defaults.color || '';
    this.purpose = defaults.purpose || 'none';
    this.is_balance_overridden = defaults.is_balance_overridden || false;
    this.appears_in_account_list = defaults.appears_in_account_list || false;
    this.balance_current = defaults.balance_current || 0;
    this.balance_available = defaults.balance_available || 0;
    this.allocation_balance_adjustment = defaults.allocation_balance_adjustment || 0;
    this.assignment_balance_adjustment = defaults.assignment_balance_adjustment || 0;
    this.id = defaults.id || null;
}
