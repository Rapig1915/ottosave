export default {
    data: data,
    methods: getMethods()
};

function data(){
    return {
        cancelNavigation: null,
        proceedWithNavigation: null
    };
}

function getMethods(){
    return {
        displayModal,
    };

    function displayModal(){
        const vm = this;
        return new Promise(showModal).finally(hideModal);

        function showModal(resolve, reject){
            vm.proceedWithNavigation = resolve;
            vm.cancelNavigation = reject;
            vm.$refs.leavingOrganizeModal.show();
        }
        function hideModal(){
            vm.$refs.leavingOrganizeModal.hide();
        }
    }
}
