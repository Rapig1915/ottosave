export default {
    data,
    mounted,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        isVerifyingEmail: false,
        isNavigating: false,
        user: {}
    };
}

function mounted(){
    const vm = this;
    const emailToVerify = vm.$route.query.email;
    const verificationToken = vm.$route.query.token;
    if(emailToVerify && verificationToken){
        vm.verifyEmailAddress({ email: emailToVerify, token: verificationToken });
    }
}

function getMethods(){
    return {
        displayApiErrors,
        verifyEmailAddress,
        navigateTo
    };

    function displayApiErrors({ appMessage }){
        const vm = this;
        if(appMessage){
            vm.apiErrors = [appMessage];
        }
    }

    function verifyEmailAddress(payload){
        const vm = this;
        vm.isVerifyingEmail = true;
        Vue.appApi().guest().user().verifyEmailAddress(payload).then(setUser).catch(vm.displayApiErrors).finally(resetSpinner);

        function setUser(response){
            vm.user = response.data;
            vm.$store.commit('user/SET_USER', vm.user);
        }
        function resetSpinner(){
            vm.isVerifyingEmail = false;
        }
    }

    function navigateTo(routerLink){
        const vm = this;
        vm.isNavigating = true;
        vm.$router.push(routerLink);
    }
}
