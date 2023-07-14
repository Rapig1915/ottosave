import AccountScheduleModal from 'vue_root/components/account-schedule-modal/account-schedule-modal';

export default {
    components: {
        AccountScheduleModal
    },
    props: {
        allocationAccounts: {
            type: Array,
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
        selectedBankAccount: null
    };
}

function getComputed(){
    return {};
}

function created(){}

function getMethods(){
    return {
        openAccountSchedule
    };

    function openAccountSchedule(bankAccount){
        const vm = this;
        vm.selectedBankAccount = bankAccount;
        Vue.nextTick(() => {
            vm.$refs.accountScheduleModal.show();
        });
    }
}
