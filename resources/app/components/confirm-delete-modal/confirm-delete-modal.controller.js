export default {
    components: {},
    props: {
        requiredConfirmationString: {
            type: String,
            default: ''
        },
        confirmButtonText: {
            type: String,
            default: 'Yes, delete'
        },
        cancelButtonText: {
            type: String,
            default: 'Cancel'
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        isConfirmed: false,
        isModalShown: false,
        confirmationString: '',
        confirmFunction: null,
        cancelFunction: null
    };
}

function getComputed(){
    return {
        isDeleteButtonEnabled(){
            const vm = this;
            return vm.confirmationString === vm.requiredConfirmationString;
        }
    };
}

function created(){}

function getMethods(){
    return {
        openModal,
        cleanupModal,
        removeResolutionFunctions
    };

    function openModal(){
        const vm = this;
        return new Promise(function(resolve, reject){
            vm.confirmedFunction = resolve;
            vm.cancelFunction = reject;
            vm.isModalShown = true;

            vm.confirmFunction = () => {
                vm.removeResolutionFunctions();
                vm.isConfirmed = true;
                resolve(vm.isConfirmed);
                vm.isModalShown = false;
            };
            vm.cancelFunction = () => {
                vm.removeResolutionFunctions();
                vm.isConfirmed = false;
                vm.isModalShown = false;
                reject(vm.isConfirmed);
            };
        });
    }

    function cleanupModal(){
        const vm = this;
        if(vm.cancelFunction){
            vm.cancelFunction();
        }
        vm.confirmationString = '';
    }

    function removeResolutionFunctions(){
        const vm = this;
        if(vm.confirmedFunction){
            vm.confirmedFunction = null;
        }
        if(vm.cancelFunction){
            vm.cancelFunction = null;
        }
    }
}
