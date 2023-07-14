export default {
    data: data,
    created,
    methods: getMethods(),
};

function data(){
    return {
        apiErrors: [],
        validationErrors: {},
        credentials: {
            email: '',
            password: '',
            remember_me: false,
            enableTouchId: false
        },
        loggingIn: false,
        clientPlatform: window.appEnv.clientPlatform || 'web',
        isInitializing: false,
        biometricAvailability: false
    };
}

function created(){
    const vm = this;
    vm.isInitializing = true;
    const initializationPromises = [
        Vue.clientStorage.getItem('email').then(setupCredentials),
        Vue.iosKeychainPlugin.getBiometricAvailability().then(setBiometricAvailability)
    ];
    Promise.all(initializationPromises).finally(setInitComplete);

    function setupCredentials(storedEmail){
        vm.credentials.email = storedEmail;
        vm.credentials.remember_me = !!storedEmail;
    }
    function setBiometricAvailability(availability){
        vm.biometricAvailability = availability;
        vm.credentials.enableTouchId = !!availability;
    }
    function setInitComplete(){
        vm.isInitializing = false;
    }
}

function getMethods(){

    return {
        login,
        handleLoginSuccess,
        loginWithBiometrics
    };

    function login(){

        var vm = this;
        vm.apiErrors = [];
        vm.validationErrors = {};
        vm.loggingIn = true;

        vm.$store.dispatch('user/login/LOGIN', vm.credentials).then(vm.handleLoginSuccess).catch(getUserError);

        function getUserError(response){
            vm.loggingIn = false;
            if(response.data.validation_errors){
                vm.validationErrors = response.data.validation_errors;
            }
            if(response.data.message === 'CSRF token mismatch.'){
                vm.apiErrors = ['Please refresh the page and try again'];
            } else if(response.data.message === 'Account is locked.'){
                vm.apiErrors = ['Your account has been locked. Please <a href="mailto:support@ottsave.com">contact support</a>'];
            } else {
                vm.apiErrors.push('Username or password is incorrect.');
            }
        }
    }

    function handleLoginSuccess(){
        const vm = this;
        const currentAccount = vm.$store.getters.user.current_account;
        const shouldInitiateTrial = currentAccount.subscription_plan === 'basic' &&
            !currentAccount.is_trial_used &&
            !currentAccount.status === 'demo';
        if(shouldInitiateTrial){
            return vm.$store.dispatch('user/START_NEW_TRIAL').then(() => {
                vm.$router.redirectAfterLogin({ name: 'dashboard' });
            });
        } else {
            vm.$router.redirectAfterLogin();
        }
    }

    function loginWithBiometrics(){
        const vm = this;
        if(!vm.loggingIn){
            vm.apiErrors = [];
            vm.validationErrors = {};
            vm.loggingIn = true;
            Vue.iosKeychainPlugin.retrieveStoredCredentials().then(makeLoginRequest).catch(handleError).finally(resetLoadingState);
        }

        function makeLoginRequest(credentials){
            return vm.$store.dispatch('user/login/LOGIN', credentials).then(vm.handleLoginSuccess);
        }
        function handleError(){
            vm.apiErrors = [`Login to enable ${vm.biometricAvailability}`];
        }
        function resetLoadingState(){
            vm.loggingIn = false;
        }
    }
}
