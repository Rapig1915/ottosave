import * as _ from 'lodash';

export default {
    created: created,
    computed: getComputed(),
    data: data,
    methods: getMethods(),
};

function created(){
    const vm = this;
    vm.getAllUsers();
    vm.debouncedHandlerGetAllUsers = _.debounce(vm.getAllUsers, 500);
}

function data(){
    return {
        loadingSpinner: false,

        users: [],
        totalUserCount: 0,
        debouncedHandlerGetAllUsers: null,

        userQuery: {
            searchString: '',
            numPage: 1,
            perPage: 50,
            sortBy: '',
            sortOrder: 'asc'
        },

        errorMessages: [],
        userTableColumns: [
            {
                key: 'id',
                label: 'User ID',
                sortable: true
            },
            {
                key: 'name',
                label: 'Name',
                sortable: false,
                tdClass: 'text-nowrap'
            },
            {
                key: 'email',
                label: 'Email',
                sortable: true
            },
            {
                key: 'linked_bank_accounts',
                label: 'Linked Bank Accounts'
            },
        ],
        bankAccountsTableColumns: [
            {
                key: 'id',
                label: 'Bank Account ID',
                sortable: true
            },
            {
                key: 'name',
                label: 'Name',
                sortable: true,
            },
            {
                key: 'slug',
                label: 'Slug',
                sortable: true
            },
            {
                key: 'purpose',
                label: 'purpose',
                sortable: true
            },
            {
                key: 'balance_available',
                label: 'Available Balance',
                sortable: true,
                class: 'text-right',
                formatter: (value) => `${value || 0}$`
            },
            {
                key: 'actions',
                label: ''
            },
        ],
        transactionCategories: [
            '',
            'Shopping',
            'Restaurants',
            'Income',
            'Credit Card Payment',
            'Other',
        ],

        isLoadingUserBankAccount: false,

        depositModal: {
            user: null,
            bankAccount: null,
            category: null,
            amount: null,
            date: null,
            amountValidationError: '',
            isMakingDeposit: false,
            apiErrors: []
        }
    };
}

function getComputed(){
    return {
        displayedUsers,
        isMakeDepositDisabled,
    };

    function displayedUsers(){
        const vm = this;
        return vm.users.filter(bySearchString);

        function bySearchString(user){
            if(!vm.searchString){
                return true;
            }
            const name = (user.name || '');
            const email = (user.email || '');
            const plan = ((user.account && user.account.subscription_plan) || '');
            const origin = ((user.account && user.account.subscription_origin) || '');
            const status = ((user.account && user.account.status) || '');
            const braintreeId = ((user.account && user.account.braintree_customer_id) || '');
            const userInfoToSearch = name.concat(email, plan, origin, status, braintreeId).toLowerCase();
            return userInfoToSearch.includes(vm.searchString.toLowerCase());
        }
    }

    function isMakeDepositDisabled(){
        const vm = this;
        const isDisabled = vm.depositModal.isMakingDeposit || !vm.depositModal.amount || !vm.depositModal.category || !!vm.depositModal.amountValidationError;
        return isDisabled;
    }
}

function getMethods(){
    return {
        getAllUsers,
        handleChangePagination,
        handleChangeSort,
        loadUserBankAccounts,

        validateDepositAmount,
        makeDeposit,
        openDepositModal,
        initializeDepositModal,
        preventCloseWhileLoading,
    };

    function getAllUsers(){
        const vm = this;
        vm.loadingSpinner = true;
        Vue.appApi().authorized().admin().getAllUsers(vm.userQuery).then(setUsers).catch(displayError).finally(resetLoadingState);
        function setUsers(response){
            const { total, users } = response.data;
            vm.totalUserCount = total;
            vm.users = users.map(setDisplayProperties);
            function setDisplayProperties(user){
                user.account = user.accounts[0];
                user.account.isReactivatingAccount = false;
                user.account.isConfirmingReactivation = false;
                return user;
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.errorMessages.push(response.appMessage);
            }

            vm.totalUserCount = 0;
        }
        function resetLoadingState(){
            vm.loadingSpinner = false;
        }
    }

    function handleChangePagination(event){
        const vm = this;
        Object.assign(vm.userQuery, event);
        vm.getAllUsers();
    }

    function handleChangeSort(event){
        const vm = this;
        Object.assign(vm.userQuery, {
            sortBy: event.sortBy,
            sortOrder: event.sortDesc ? 'desc' : 'asc'
        });
        vm.getAllUsers();
    }

    function loadUserBankAccounts(user){
        if(!user){
            return;
        }

        const vm = this;
        user.isLoadingBankAccounts = true;
        const ownerAccountId = user.accounts.length && user.accounts[0].id;
        Vue.appApi().authorized().bankAccount().loadWithInstitutionAccountsOf(ownerAccountId)
            .then(handleSuccess)
            .catch(displayError)
            .finally(resetLoadingState);

        function handleSuccess(response){
            user.bankAccounts = response.data.filter(filterOnlyWithInstituion).map(formatName);

            function filterOnlyWithInstituion(bankAccount){
                return !!bankAccount.institution_account;
            }

            function formatName(bankAccount){
                if(bankAccount.slug === 'primary_checking'){
                    bankAccount.name = 'Primary Checking';
                } else if(bankAccount.slug === 'primary_savings'){
                    bankAccount.name = 'Primary Savings';
                }

                return bankAccount;
            }
        }

        function displayError(response){
            if(response.appMessage){
                vm.errorMessages.push(response.appMessage);
            }
        }

        function resetLoadingState(){
            user.isLoadingBankAccounts = false;
            vm.$forceUpdate();
        }
    }

    function validateDepositAmount(){
        const vm = this;
        vm.depositModal.amountValidationError = '';
        const checkBalance = vm.depositModal.bankAccount.type !== 'credit';
        if(checkBalance && vm.depositModal.amount < 0 && vm.depositModal.bankAccount.balance_available < -vm.depositModal.amount){
            vm.depositModal.amountValidationError = `There is only $ ${vm.depositModal.bankAccount.balance_available.toFixed(2)} available in your ${vm.depositModal.bankAccount.name}.`;
        }
    }

    function makeDeposit(){
        const vm = this;
        vm.depositModal.isMakingDeposit = true;
        vm.depositModal.apiErrors = [];
        const payload = {
            bankAccountId: vm.depositModal.bankAccount.id,
            amount: vm.depositModal.amount,
            category: vm.depositModal.category,
            date: Vue.moment(vm.depositModal.date).format('YYYY-MM-DD')
        };

        return Vue.appApi().authorized().admin().makeDeposit(payload)
            .then(handleSuccess)
            .catch(displayError);
        function handleSuccess(){
            vm.$store.dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS');
            closeModal();
        }
        function closeModal(){
            vm.depositModal.isMakingDeposit = false;
            vm.$refs.makeDepositModal.hide();
        }
        function displayError(error){
            vm.depositModal.isMakingDeposit = false;
            if(error.appMessage){
                vm.depositModal.apiErrors.push(error.appMessage);
            }
        }
    }

    function openDepositModal(user, bankAccount){
        const vm = this;
        vm.depositModal.user = user;
        vm.depositModal.bankAccount = bankAccount;

        vm.$refs.makeDepositModal.show();
    }

    function initializeDepositModal(){
        const vm = this;
        vm.depositModal.apiErrors = [];
        vm.depositModal.amountValidationError = '';
        vm.depositModal.isMakingDeposit = false;
        vm.depositModal.amount = 0;
        vm.depositModal.date = new Date();
    }

    function preventCloseWhileLoading(event){
        const vm = this;
        if(vm.depositModal.isMakingDeposit){
            event.preventDefault();
        }
    }
}
