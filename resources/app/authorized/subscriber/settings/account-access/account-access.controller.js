export default {
    components: {
    },
    created: created,
    computed: getComputed(),
    data: data,
    methods: getMethods(),
};

function created(){
    const vm = this;

    vm.isInitializingView = true;
    Promise.all([
        vm.getAccountUsers(),
        vm.getAccountInvites(),
    ]).finally(resetLoadingState);
    function resetLoadingState(){
        vm.isInitializingView = false;
    }
}

function getComputed(){
    return {
    };
}

function data(){
    return {
        errorMessages: [],
        successMessages: [],
        validationErrors: {},
        user: {
            all_role_names: ['coach']
        },

        accountUsers: [],
        accountInvites: [],

        isInitializingView: false,
        isLoadingAccountInvites: false,
        isLoadingAccountUsers: false,
        isSendingInvite: false,
    };
}

function getMethods(){
    return {
        getAccountUsers,
        getAccountInvites,
        sendInvite,
        resendInvite,
        deleteAccountUser,
        deleteAccountInvite,
        confirmDelete,
        displayErrorMessage,
        clearMessages,
    };

    function getAccountUsers(){
        const vm = this;
        vm.isLoadingAccountUsers = true;
        return Vue.appApi().authorized().account().accountUsers().getAccountUsers().then(setAccountUsers).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setAccountUsers({ data }){
            vm.accountUsers = data.map(initializeDisplayProperties).sort(sortByRole);
        }

        function initializeDisplayProperties(accountUser){
            accountUser.isOwner = accountUser.all_role_names && accountUser.all_role_names.includes('owner');
            accountUser.isDeleting = false;
            return accountUser;
        }

        function sortByRole(accountUser1, accountUser2){
            if(accountUser1.isOwner){
                return -1;
            }
            if(accountUser2.isOwner){
                return 1;
            }
            return 0;
        }

        function resetLoadingState(){
            vm.isLoadingAccountUsers = false;
        }
    }

    function getAccountInvites(){
        const vm = this;
        vm.isLoadingAccountInvites = true;
        return Vue.appApi().authorized().account().accountUsers().listInvites().then(setAccountInvites).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setAccountInvites({ data }){
            vm.accountInvites = data.filter(filterOnlyPending).map(initializeDisplayProperties);
        }

        function filterOnlyPending(accountInvite){
            return accountInvite.status === 'pending';
        }

        function initializeDisplayProperties(accountInvite){
            accountInvite.isResendingInvite = false;
            accountInvite.isDeletingInvite = false;
            return accountInvite;
        }

        function resetLoadingState(){
            vm.isLoadingAccountInvites = false;
        }
    }

    function sendInvite(){
        const vm = this;
        vm.clearMessages();
        vm.isSendingInvite = true;
        return Vue.appApi().authorized().account().accountUsers().createInvite(vm.user)
            .then(updateAccountInviteList)
            .catch(displayStoreUserErrors)
            .finally(resetLoadingState);

        function updateAccountInviteList({ data: accountInvite }){
            accountInvite.isResendingInvite = false;
            accountInvite.isDeletingInvite = false;
            vm.accountInvites.push(accountInvite);
            vm.successMessages.push(`Account invite sent to '${vm.user.email}'.`);

            vm.user.name = '';
            vm.user.email = '';
        }
        function resetLoadingState(){
            vm.isSendingInvite = false;
        }

        function displayStoreUserErrors(response){
            vm.errorMessages.push(response.appMessage);
            if(response.validation_errors){
                vm.validationErrors = response.validation_errors;
            }
        }
    }

    function resendInvite(accountInvite){
        const vm = this;
        vm.clearMessages();
        accountInvite.isResendingInvite = true;
        return Vue.appApi().authorized().account().accountUsers().resendAccountInvite(accountInvite.id).then(handleSuccess).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function handleSuccess(){
            vm.successMessages.push(`Account invite resent to '${accountInvite.email}'.`);
        }
        function resetLoadingState(){
            accountInvite.isResendingInvite = false;
        }
    }

    function deleteAccountUser(accountUser){
        const vm = this;
        vm.clearMessages();
        accountUser.isDeleting = true;
        return vm.confirmDelete('Delete this account user?').then(makeDeleteRequest);

        function makeDeleteRequest(isConfirmed){
            if(isConfirmed){
                return Vue.appApi().authorized().account().accountUsers(accountUser.id).deleteAccountUser().then(updateAccountUserList).catch(vm.displayErrorMessage).finally(resetLoadingState);
            } else {
                resetLoadingState();
            }
        }

        function updateAccountUserList(){
            const accountUserIndex = vm.accountUsers.findIndex(({ id }) => id === accountUser.id);
            vm.accountUsers.splice(accountUserIndex, 1);
            vm.successMessages.push(`Account user '${accountUser.user.name}' deleted.`);
        }
        function resetLoadingState(){
            accountUser.isDeleting = false;
        }
    }

    function deleteAccountInvite(accountInvite){
        const vm = this;
        vm.clearMessages();
        accountInvite.isDeletingInvite = true;
        return vm.confirmDelete('Delete this account invite?').then(makeDeleteRequest);

        function makeDeleteRequest(isConfirmed){
            if(isConfirmed){
                return Vue.appApi().authorized().account().accountUsers().deleteAccountInvite(accountInvite.id).then(updateAccountInviteList).catch(vm.displayErrorMessage).finally(resetLoadingState);
            } else {
                resetLoadingState();
            }
        }

        function updateAccountInviteList(){
            const accountInviteIndex = vm.accountInvites.findIndex(({ id }) => id === accountInvite.id);
            vm.accountInvites.splice(accountInviteIndex, 1);
            vm.successMessages.push(`Account invite to '${accountInvite.email}' deleted.`);
        }
        function resetLoadingState(){
            accountInvite.isDeletingInvite = false;
        }
    }

    function confirmDelete(message){
        const vm = this;
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

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function clearMessages(){
        const vm = this;
        vm.errorMessages = [];
        vm.successMessages = [];
        vm.validationErrors = {};
    }
}
