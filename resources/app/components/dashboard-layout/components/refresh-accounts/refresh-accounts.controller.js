export default {
    components: {},
    props: {
        iconClass: {
            type: String,
            default: ''
        },
        mobile: {
            type: Boolean,
            default: false
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
    };
}

function getComputed(){
    return {
        dynamicClasses(){
            const vm = this;
            const dynamicClasses = [vm.iconClass];
            if(vm.isUpdatingLinkedAccounts){
                dynamicClasses.push('fa-spin disabled');
            }
            return dynamicClasses;
        },
        isUpdatingLinkedAccounts(){
            const vm = this;
            return vm.$store.state.authorized.finicityRefreshStatus === 'pending';
        }
    };
}

function created(){}

function getMethods(){
    return {
        displayErrorMessage,
        updateLinkedAccountBalances
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function updateLinkedAccountBalances(){
        const vm = this;
        if(!vm.isUpdatingLinkedAccounts){
            vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS');
        }
    }
}
