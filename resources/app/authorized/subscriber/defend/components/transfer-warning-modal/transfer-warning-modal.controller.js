export default {
    data: data,
    methods: getMethods()
};

function data(){
    return {
        shouldPermanentlyDismiss: this.$store.state.guest.user.user.current_account_user.notification_preferences.transfer_warning_modal_dismissed,
        transferMadeFunction: null,
        notTransferMadeFunction: null
    };
}

function getMethods(){
    return {
        displayModal,
        handleHiddenEvent
    };
    function displayModal(){
        const vm = this;
        if(!vm.shouldPermanentlyDismiss){
            return new Promise(showModal).finally(hideModal);
        } else {
            return Promise.resolve();
        }

        function showModal(resolve, reject){
            vm.transferMadeFunction = resolve;
            vm.notTransferMadeFunction = reject;
            vm.$refs.transferWarningModal.show();
        }
        function hideModal(){
            vm.$refs.transferWarningModal.hide();
        }
    }
    function handleHiddenEvent(){
        const vm = this;
        if(vm.shouldPermanentlyDismiss){
            permanentlyDismissModal();
        }

        function permanentlyDismissModal(){
            const notificationPreferences = Object.assign({}, vm.$store.state.guest.user.user.current_account_user.notification_preferences);
            notificationPreferences.transfer_warning_modal_dismissed = true;
            vm.$store.dispatch('user/SAVE_NOTIFICATION_PREFERENCES', notificationPreferences);
        }
    }
}
