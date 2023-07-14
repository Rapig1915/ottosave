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

        getAllUsersErrors: [],
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
                key: 'created_at',
                label: 'Created',
                sortable: true,
                tdClass: 'text-nowrap',
                formatter: (value) => value ? Vue.moment(value).format('M/D/YY, hh:mm A') : ''
            },
            {
                key: 'account.subscription_plan',
                label: 'Plan',
                sortable: false,
            },
            {
                key: 'account.subscription_origin',
                label: 'Origin',
                sortable: false,
            },
            {
                key: 'status',
                label: 'Status',
                sortable: false,
            },
            {
                key: 'accounts_linked',
                label: ' '
            },
            {
                key: 'institution_details',
                label: 'Institutions'
            },
            {
                key: 'actions',
                label: ''
            },
        ],
        institutionTableColumns: [
            {
                key: 'id',
                label: 'Institution ID',
                sortable: true
            },
            {
                key: 'name',
                label: 'Name',
                sortable: true
            },
            {
                key: 'updated_at',
                label: 'Updated',
                sortable: true,
                formatter: (value) => value ? Vue.moment(value).format('M/D/YY, hh:mm A') : ''
            },
        ],
    };
}

function getComputed(){
    return {
        displayedUsers
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
}

function getMethods(){
    return {
        getAllUsers,
        reactivateAccount,
        resetAccountToDemoMode,
        deleteUser,
        lockUser,
        grantUserAccess,
        handleChangePagination,
        handleChangeSort,
    };

    function getAllUsers(){
        const vm = this;
        const accessibleAccounts = vm.$store.state.guest.user.accessible_accounts || [];
        vm.loadingSpinner = true;
        Vue.appApi().authorized().admin().getAllUsers(vm.userQuery).then(setUsers).catch(displayError).finally(resetLoadingState);
        function setUsers(response){
            const { total, users } = response.data;
            vm.totalUserCount = total;
            vm.users = users.map(setDisplayProperties);
            function setDisplayProperties(user){
                if(!user.account_users){
                    user.account = null;
                    return user;
                }

                const mainAccountUserIndex = user.account_users.findIndex(accountUser => accountUser.all_role_names.includes('owner'));
                if(mainAccountUserIndex === -1 || !user.account_users[mainAccountUserIndex]){
                    user.account = null;
                    return user;
                }

                const mainAccountId = user.account_users[mainAccountUserIndex].account_id;
                const mainAccountIndex = user.accounts.findIndex(account => account.id === mainAccountId);
                if(mainAccountIndex === -1 || !user.accounts[mainAccountIndex]){
                    user.account = null;
                    return user;
                }

                user.account = user.accounts[mainAccountIndex];
                user.account.isReactivatingAccount = false;
                user.account.isConfirmingReactivation = false;
                user.adminAccessGranted = accessibleAccounts.findIndex(accessibleAccount => accessibleAccount.account_id === mainAccountId) !== -1;

                return user;
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }

            vm.totalUserCount = 0;
        }
        function resetLoadingState(){
            vm.loadingSpinner = false;
        }
    }

    function reactivateAccount(account){
        const vm = this;
        account.isReactivatingAccount = true;
        return Vue.appApi().authorized().admin().reactivateAccount(account.id).then(updateLocalAccount).catch(displayError).finally(resetLoadingState);

        function updateLocalAccount(response){
            const userIndex = vm.users.findIndex((user) => user.account.id === account.id);
            if(userIndex > -1){
                vm.users[userIndex].account = response.data;
            }
        }

        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }
        }
        function resetLoadingState(){
            account.isReactivatingAccount = false;
        }
    }

    function resetAccountToDemoMode(account){
        const vm = this;
        confirmResetOperation()
            .then(makeResetRequest)
            .catch(displayError)
            .finally(resetLoadingState);

        function confirmResetOperation(){
            return vm.$bvModal.msgBoxConfirm('This will erase all linked account data and place the account back into demo mode.', {
                title: 'Are you sure?',
                buttonSize: 'sm',
                okVariant: 'outline-danger',
                okTitle: 'Continue',
                footerClass: 'p-2',
                hideHeaderClose: false,
                centered: true
            });
        }
        function makeResetRequest(isConfirmed){
            if(!isConfirmed){
                return false;
            }
            vm.$set(account, 'isResettingToDemoMode', true);
            return Vue.appApi().authorized().admin().resetAccountToDemoMode(account.id).then(updateLocalAccount);
            function updateLocalAccount(response){
                Object.assign(account, response.data);
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }
        }
        function resetLoadingState(){
            vm.$set(account, 'isResettingToDemoMode', false);
        }
    }

    function deleteUser(user){
        const vm = this;
        confirmDeleteOperation()
            .then(makeDeleteRequest)
            .catch(displayError)
            .finally(resetLoadingState);

        function confirmDeleteOperation(){
            return vm.$bvModal.msgBoxConfirm(
                `This will the delete user [${user.email}] with all information associated with this account.`,
                {
                    title: 'Are you sure?',
                    buttonSize: 'sm',
                    okVariant: 'outline-danger',
                    okTitle: 'Confirm',
                    footerClass: 'p-2',
                    hideHeaderClose: false,
                    centered: true
                }
            );
        }
        function makeDeleteRequest(isConfirmed){
            if(!isConfirmed){
                return false;
            }
            vm.$set(user, 'isDeleting', true);
            return Vue.appApi().authorized().admin().deleteUser(user.id).then(updateUserList);
            function updateUserList(response){
                const index = vm.users.findIndex(u => u.id === user.id);
                vm.users.splice(index, 1);
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }
        }
        function resetLoadingState(){
            vm.$set(user, 'isDeleting', false);
        }
    }

    function lockUser(user){
        const vm = this;
        const currentLockStatus = user.is_owner_account_locked;
        confirmLockOperation()
            .then(makeLockRequest)
            .catch(displayError)
            .finally(resetLoadingState);

        function confirmLockOperation(){
            return vm.$bvModal.msgBoxConfirm(
                `Do you want to ${currentLockStatus ? 'unlock' : 'lock'} this account(${user.name}, ${user.email})?`,
                {
                    title: 'Are you sure?',
                    buttonSize: 'sm',
                    okVariant: 'outline-danger',
                    okTitle: 'Confirm',
                    footerClass: 'p-2',
                    hideHeaderClose: false,
                    centered: true
                }
            );
        }
        function makeLockRequest(isConfirmed){
            if(!isConfirmed){
                return false;
            }
            vm.$set(user, 'isLocking', true);
            return Vue.appApi().authorized().admin().lockUser(user.id, !currentLockStatus).then(updateUserStatus);
            function updateUserStatus(response){
                user.is_owner_account_locked = !currentLockStatus;
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }
        }
        function resetLoadingState(){
            vm.$set(user, 'isLocking', false);
        }
    }

    function grantUserAccess(user){
        const vm = this;
        const currentAccessStatus = user.adminAccessGranted;
        confirmAccessOperation()
            .then(makeGrantAccessRequest)
            .catch(displayError);

        function confirmAccessOperation(){
            return vm.$bvModal.msgBoxConfirm(
                `Do you want to ${currentAccessStatus ? 'remove' : 'grant'} access to this account(${user.name}, ${user.email})?`,
                {
                    title: 'Are you sure?',
                    buttonSize: 'sm',
                    okVariant: 'outline-primary',
                    okTitle: 'Confirm',
                    footerClass: 'p-2',
                    hideHeaderClose: false,
                    centered: true
                }
            );
        }
        function makeGrantAccessRequest(isConfirmed){
            if(!isConfirmed){
                return false;
            }
            vm.$set(user, 'isGrantingAccess', true);
            return Vue.appApi().authorized().admin().grantUserAccess(user.id, !currentAccessStatus).then(updateUserStatus).catch(displayError);
            function updateUserStatus(response){
                user.adminAccessGranted = !currentAccessStatus;
                vm.$set(user, 'isGrantingAccess', false);
                vm.$store.dispatch('user/GET_USER');//.then(() => vm.$forceUpdate());
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.getAllUsersErrors.push(response.appMessage);
            }

            vm.$set(user, 'isGrantingAccess', false);
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
}
