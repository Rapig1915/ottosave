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
        purpose: 'primary_checking',
        confirmationString: '',
        confirmationFunction: null,
        cancelFunction: null
    };
}

function getComputed(){
    return {
        isContinueButtonEnabled(){
            const vm = this;
            return vm.confirmationString === 'YES';
        },
        title(){
            const vm = this;
            return vm.purpose === 'primary_checking' ? 'Primary Checking' : 'Primary Savings';
        }
    };
}

function created(){}

function getMethods(){
    return {
        openModal,
        confirmDelete,
        cancel,
        cleanupModal,
    };

    function openModal(purpose){
        const vm = this;
        if(purpose){
            vm.purpose = purpose;
            vm.$refs.confirmPurposeChangeModal.show();
            return new Promise(function(resolve, reject){
                vm.confirmationFunction = resolve;
                vm.cancelFunction = reject;
            });
        }
    }

    function confirmDelete(){
        const vm = this;
        vm.confirmationFunction();
        vm.$refs.confirmPurposeChangeModal.hide();
    }

    function cancel(){
        const vm = this;
        vm.cancelFunction();
        vm.cancelFunction = null;
        vm.$refs.confirmPurposeChangeModal.hide();
    }

    function cleanupModal(){
        const vm = this;
        vm.confirmationString = '';
        if(vm.cancelFunction){
            vm.cancelFunction();
        }
        vm.confirmationFunction = null;
        vm.cancelFunction = null;
    }
}
