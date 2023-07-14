import { VueRecaptcha } from 'vue-recaptcha';

export default {
    name: 'UserRegister',
    components: { VueRecaptcha },
    data,
    computed: getComputed(),
    mounted,
    methods: getMethods(),
};

function data(){
    return {
        credentials: {
            name: '',
            email: '',
            password: '',
        },
        apiErrors: [],
        validationErrors: {},
        registeringUser: false,
        clientPlatform: window.appEnv.clientPlatform || 'web',

        recaptcha: {
            disabled: window.appEnv.recaptchaDisabled,
            siteKey: window.appEnv.recaptchaSiteKey,
            verified: false
        }
    };
}

function mounted(){
    const vm = this;
    vm.$store.commit('user/register/SET_REFERRAL_CODE', (vm.$route.query.ref || ''));
}

function getComputed(){
    return {
        canRegister,
    };

    function canRegister(){
        const vm = this;
        return vm.recaptcha.disabled || vm.recaptcha.verified;
    }
}

function getMethods(){

    return {
        attemptRegisterUser: attemptRegisterUser,
        onVerifyRecaptcha,
    };

    function attemptRegisterUser(credentials){

        var vm = this;

        if(!vm.canRegister){
            return;
        }

        vm.registeringUser = true;
        vm.apiErrors = [];
        vm.validationErrors = {};

        vm.$store.dispatch('user/register/REGISTER_USER', credentials)
            .then(redirectAfterLogin)
            .catch(registerError)
            .finally(resetLoadingState);

        function redirectAfterLogin(){
            const router = vm.$router;
            const redirectedFromAcceptInvitation = router.redirectedFrom && router.redirectedFrom.name === 'accept-invite';
            if(redirectedFromAcceptInvitation){
                router.redirectAfterLogin();
            } else {
                router.redirectAfterLogin({ name: 'dashboard' });
            }
        }
        function registerError(response){
            vm.apiErrors.push(response.appMessage || response.data.message);
            vm.validationErrors = response.data.errors || {};
        }
        function resetLoadingState(){
            vm.registeringUser = false;
        }
    }

    function onVerifyRecaptcha(response){
        if(response){
            this.recaptcha.verified = true;
        }
    }
}
