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
        bankAccount: {},
        confirmationString: ''
    };
}

function getComputed(){
    return {
        isDeleteButtonEnabled(){
            const vm = this;
            return vm.confirmationString === 'DELETE';
        }
    };
}

function created(){}

function getMethods(){
    return {
        openModal,
        confirmDelete,
        cleanupModal,
    };

    function openModal(bankAccount){
        const vm = this;
        if(bankAccount){
            vm.bankAccount = bankAccount;
            vm.$refs.confirmDeleteModal.show();
        }
    }

    function confirmDelete(){
        const vm = this;
        vm.$emit('delete-confirmed', vm.bankAccount);
        vm.$refs.confirmDeleteModal.hide();
    }

    function cleanupModal(){
        const vm = this;
        vm.confirmationString = '';
    }
}
