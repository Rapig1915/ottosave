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
        institutionToDelete: {},
        isDeletingInstitution: false
    };
}

function getComputed(){
    return {};
}

function created(){}

function getMethods(){
    return {
        displayErrorMessage,
        openModal,
        cleanupModal,
        deleteInstitution
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function openModal(institution){
        const vm = this;
        if(institution){
            vm.institutionToDelete = institution;
            vm.$refs.deleteInstitutionModal.show();
        }
    }

    function cleanupModal(){
        const vm = this;
        vm.institutionToDelete = {};
        vm.isDeletingInstitution = false;
    }

    function deleteInstitution(){
        const vm = this;
        vm.isDeletingInstitution = true;
        Vue.appApi().authorized().institution(vm.institutionToDelete.id).deleteInstitution().then(emitInstitutionDeleted).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function emitInstitutionDeleted(){
            vm.$emit('institution-deleted', vm.institutionToDelete.id);
            vm.$refs.deleteInstitutionModal.hide();
        }
        function resetLoadingState(){
            vm.isDeletingInstitution = false;
        }
    }
}
