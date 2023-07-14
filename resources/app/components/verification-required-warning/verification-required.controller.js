export default {
    components: {},
    props: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        successMessages: [],
        isWarningDismissed: false
    };
}

function getComputed(){
    return {
        user(){
            const vm = this;
            return vm.$store.getters.user;
        },
        shouldDisplayWarning(){
            const vm = this;
            const isEmailVerified = vm.user.email_verified;
            const userRegisteredMoreThanThreeDaysAgo = Vue.moment().subtract(3, 'days').isAfter(vm.user.created_at);
            return !vm.isWarningDismissed && !isEmailVerified && userRegisteredMoreThanThreeDaysAgo;
        }
    };
}

function created(){}

function getMethods(){
    return {
        displayErrorMessage,
        resendVerificationEmail,
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }
    function resendVerificationEmail(){
        const vm = this;
        Vue.appApi().authorized().user().resendVerificationEmail().then(displaySuccess).catch(vm.displayErrorMessage);

        function displaySuccess(){
            vm.successMessages = ['Email sent, please allow a few minutes for delivery.'];
        }
    }
}
