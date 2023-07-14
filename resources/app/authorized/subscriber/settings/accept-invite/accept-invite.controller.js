export default {
    computed: getComputed(),
    created,
    mounted,
    data: data,
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
}

function mounted(){
    const vm = this;
    vm.autoPopulateCode();
}

function data(){
    return {
        errorMessages: [],
        successMessages: [],
        accessCode: '',
        accountName: '',
        isAcceptingInvite: false,
    };
}

function getMethods(){
    return {
        displayErrorMessage,
        autoPopulateCode,
        goToDashboard,
        acceptInvite,
        clearMessages,
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function clearMessages(){
        const vm = this;
        vm.successMessages = [];
        vm.errorMessages = [];
    }

    function autoPopulateCode(){
        const vm = this;
        const { invite_code, account } = vm.$route.query;
        vm.accessCode = invite_code || '';
        vm.accountName = account || '';
    }

    function goToDashboard(){
        this.$router.replace({ name: 'dashboard' });
    }

    function acceptInvite(){
        const vm = this;
        const threeSeconds = 3000;
        const minimumSpinnerDisplayTime = threeSeconds;
        const requestStartTime = Date.now();

        vm.clearMessages();
        vm.isAcceptingInvite = true;
        return Vue.appApi().authorized().user().acceptAccountInvite({ invite_code: vm.accessCode })
            .then(refreshUser)
            .then(redirectToDashboard)
            .catch(handleInviteError);

        function refreshUser({ data }){
            vm.$store.commit('user/SET_USER', data);
            return vm.$store.dispatch('user/GET_USER');
        }
        function redirectToDashboard(){
            const currentTime = Date.now();
            const remainingSpinnerTime = requestStartTime - currentTime + minimumSpinnerDisplayTime;
            if(remainingSpinnerTime > 0){
                setTimeout(redirectToDashboard, remainingSpinnerTime);
            } else {
                vm.$router.replace({ name: 'dashboard' });
                vm.isAcceptingInvite = false;
            }
        }
        function handleInviteError(response){
            vm.displayErrorMessage(response);
            vm.isAcceptingInvite = false;
        }
    }
}
