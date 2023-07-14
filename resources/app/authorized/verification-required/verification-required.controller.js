export default {
    data,
    computed: getComputed(),
    mounted,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        successMessages: [],
        isSendingEmail: false,
    };
}

function getComputed(){
    return {
        user,
    };

    function user(){
        const vm = this;
        return vm.$store.getters.user;
    }
}

function mounted(){
    const vm = this;
    vm.proceedToApplication(true);
}

function getMethods(){
    return {
        resendVerificationEmail,
        displayApiErrors,
        returnToLogin,
        proceedToApplication,
    };

    function resendVerificationEmail(){
        const vm = this;
        vm.isSendingEmail = true;
        Vue.appApi().authorized().user().resendVerificationEmail().then(displaySuccess).catch(vm.displayApiErrors).finally(resetSpinner);

        function displaySuccess(){
            vm.successMessages = ['Email sent, please allow a few minutes for delivery.'];
        }

        function resetSpinner(){
            vm.isSendingEmail = false;
        }
    }

    function displayApiErrors({ appMessage }){
        const vm = this;
        if(appMessage){
            vm.apiErrors = [appMessage];
        }
    }

    function returnToLogin(){
        const vm = this;
        vm.$store.dispatch('user/LOGOUT_FRONTEND').catch(redirectToLogin).finally(redirectToLogin);

        function redirectToLogin(){
            vm.$router.push({ name: 'login' });
        }
    }

    function proceedToApplication(isInitialLoad){
        const vm = this;
        const destination = vm.user.current_account.status ? { name: 'dashboard' } : vm.$store.state.tourWalkthrough.tourLinks[0];
        vm.$store.dispatch('user/GET_USER').then(redirectIfVerified).catch(vm.displayApiErrors);

        function redirectIfVerified(){
            if(vm.user && vm.user.email_verified){
                vm.$router.push(destination);
            } else if(!isInitialLoad){
                vm.apiErrors = ['We were unable to confirm your email address.'];
            }
        }
    }
}
