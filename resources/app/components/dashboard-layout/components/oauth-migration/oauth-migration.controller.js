import FinicityConnect from 'vue_root/components/finicity-connect-service/finicity-connect.vue';

export default {
    components: {
        FinicityConnect
    },
    props: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        institution: null,
        isMigratingInstitution: false
    };
}

function getComputed(){
    return {};
}

function created(){
    const vm = this;
    vm.getInstitutionsToMigrate();
}

function getMethods(){
    return {
        displayErrorMessage,
        getInstitutionsToMigrate,
        migrateInstitutionToOauth,
        completeFinicityConnectFix
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function getInstitutionsToMigrate(){
        const vm = this;
        return Vue.appApi().authorized().institution().getInstitutionsToMigrate().then(setInstitutionsToMigrate).catch(vm.displayErrorMessage);

        function setInstitutionsToMigrate(response){
            vm.institution = response.data[0];
        }
    }

    function migrateInstitutionToOauth(){
        const vm = this;
        vm.isMigratingInstitution = true;
        Vue.appApi().authorized().institution(vm.institution.id).migrateInstitutionToOauth().then(promptForCredentialUpdate).catch(displayErrorMessage);

        function promptForCredentialUpdate(response){
            vm.$refs.finicityService.openFinicityConnectFix(vm.institution.id);
        }

        function displayErrorMessage(error){
            vm.isMigratingInstitution = false;
            vm.displayErrorMessage(error);
        }
    }

    function completeFinicityConnectFix(){
        const vm = this;
        vm.institution = null;
        vm.$store.dispatch('authorized/REFRESH_LINKED_ACCOUNTS');
        vm.getInstitutionsToMigrate();
    }
}
