export default {
    computed: getComputed(),
    created,
    mounted,
    methods: getMethods()
};

function getComputed(){
    return {
        user(){
            const vm = this;
            return vm.$store.state.guest.user.user;
        }
    };
}

function created(){
    const vm = this;
    const isAccountDeactivated = vm.user && vm.user.current_account && vm.user.current_account.status === 'deactivated';
    if(!isAccountDeactivated){
        vm.$router.replace({ name: 'dashboard' });
    }
}

function mounted(){
    const vm = this;
    vm.logout();
}

function getMethods(){
    return {
        displayErrorMessage,
        logout
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function logout(){
        const vm = this;
        vm.$store.dispatch('user/LOGOUT');
    }
}
