export default {
    components: {},
    props: {
        bankAccount: {
            type: Object,
            required: true
        },
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: []
    };
}

function getComputed(){
    return {
        displayedInstitutionName(){
            const vm = this;
            const hasLinkedInstitution = vm.bankAccount.institution_account && vm.bankAccount.institution_account.institution;
            return hasLinkedInstitution ? vm.bankAccount.institution_account.institution.name : vm.bankAccount.name;
        },
        errorType(){
            const vm = this;
            const errorStatues = ['error', 'recoverable'];
            const errorDetected = vm.bankAccount.institution_account && errorStatues.includes(vm.bankAccount.institution_account.api_status);
            let type = null;
            if(errorDetected){
                if(vm.bankAccount.institution_account.api_status === 'recoverable'){
                    type = 'recoverable';
                } else if(vm.bankAccount.institution_account.api_status_message === 'Unknown status code'){
                    type = 'unknown';
                } else {
                    type = 'error';
                }
            }
            return type;
        }
    };
}

function created(){}

function getMethods(){
    return {};
}
