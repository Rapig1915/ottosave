export default {
    components: {},
    props: {
        bankAccount: {
            type: Object,
            required: true
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
        validationErrors: {},
        isEditing: false,
        isSavingBankAccount: false,
        localBankAccount: {}
    };
}

function getComputed(){
    return {};
}

function created(){
    const vm = this;
    vm.initializeView();
}

function getMethods(){
    return {
        updateBankAccount,
        cancel,
        openWindow,
        initializeView,
        handleHideEvent,
        resetView,
        resetErrors
    };

    function updateBankAccount(){
        const vm = this;
        vm.isSavingBankAccount = true;
        vm.resetErrors();
        Vue.appApi().authorized().bankAccount().createOrUpdate(vm.localBankAccount)
            .then(displaySuccessMessage)
            .catch(updateBankAccountFailure)
            .finally(resetLoadingState);

        function displaySuccessMessage(response){
            const successMessage = `Your bank account link has been saved!`;
            vm.$bvToast.toast(successMessage, {
                title: 'Success',
                variant: 'success',
                toaster: 'b-toaster-top-left'
            });
            vm.isEditing = false;
            vm.$emit('updated', response.data);
        }

        function updateBankAccountFailure(error){
            vm.errorMessages.push(error.appMessage || error.data.message);
            if(error.validation_errors){
                vm.validationErrors = error.validation_errors;
            }
        }
        function resetLoadingState(){
            vm.isSavingBankAccount = false;
            vm.$refs.popoverTarget.focus();
        }
    }

    function cancel(){
        const vm = this;
        vm.isEditing = false;
        if(!vm.bankAccount.online_banking_url){
            vm.$root.$emit('bv::hide::popover', `${vm.bankAccount.id}-link-target`);
        }
    }

    function openWindow(url){
        const vm = this;
        window.open(url, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=900');
        vm.$root.$emit('bv::hide::popover', `${vm.bankAccount.id}-link-target`);
    }

    function initializeView(){
        const vm = this;
        vm.localBankAccount = JSON.parse(JSON.stringify(vm.bankAccount));
        vm.resetErrors();
    }

    function handleHideEvent($event){
        const vm = this;
        if(vm.isEditing){
            $event.preventDefault();
        }
    }

    function resetView(){
        const vm = this;
        if(!vm.isSavingBankAccount){
            vm.isEditing = false;
            vm.resetErrors();
        }
    }

    function resetErrors(){
        const vm = this;
        vm.errorMessages = [];
        vm.validationErrors = {};
    }
}
