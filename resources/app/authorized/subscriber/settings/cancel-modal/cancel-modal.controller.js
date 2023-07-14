export default {
    methods: getMethods(),
    computed: getComputed()
};

function getComputed(){
    return {
        currentAccount(){
            const vm = this;
            return vm.$store.state.guest.user.user.current_account;
        }
    };
}

function getMethods(){
    return {
        openModal,
        hideAndEmitOk,
    };

    function openModal(){
        const vm = this;
        vm.$refs.cancelModal.show();
    }

    function hideAndEmitOk(){
        const vm = this;
        vm.$emit('ok');
        vm.$refs.cancelModal.hide();
    }
}
