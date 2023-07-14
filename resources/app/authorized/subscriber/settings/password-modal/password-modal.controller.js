export default {
    data: data,
    methods: getMethods(),
};

function data(){
    return {
        currentPassword: '',
        validationErrors: []
    };
}

function getMethods(){
    return {
        openModal,
        hideAndEmitOk,
    };

    function openModal(){
        const vm = this;
        vm.currentPassword = '';
        vm.$refs.passwordModal.show();
    }

    function hideAndEmitOk(){
        const vm = this;
        vm.$emit('ok', vm.currentPassword);
        vm.currentPassword = '';
        vm.$refs.passwordModal.hide();
    }
}
