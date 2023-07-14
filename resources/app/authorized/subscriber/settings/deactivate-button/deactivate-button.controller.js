export default {
    props: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        isDisplayingWarning: false,
        isDeactivatingAccount: false,
        isAccountDeactivated: false,
        isLoggingOut: false
    };
}

function getComputed(){
    return {};
}

function created(){}

function getMethods(){
    return {
        displayErrorMessage,
        displayWarning,
        deactivateAccount,
        logoutUser
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function displayWarning(){
        const vm = this;
        vm.isDisplayingWarning = true;
        window.scrollTo(0, document.body.scrollHeight);
    }

    function deactivateAccount(){
        const vm = this;
        vm.isDeactivatingAccount = true;
        Vue.appApi().authorized().account().deactivateAccount().then(setDeactivationStatus).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setDeactivationStatus(){
            vm.isAccountDeactivated = true;
        }
        function resetLoadingState(){
            vm.isDeactivatingAccount = false;
        }
    }

    function logoutUser(){
        const vm = this;
        vm.isLoggingOut = true;
        vm.$store.dispatch('user/LOGOUT').then(refreshPage);
        function refreshPage(){
            Vue.iosKeychainPlugin.removeCredentials();
            vm.$router.go();
        }
    }
}
