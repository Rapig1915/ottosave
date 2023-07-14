export default {
    components: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        isLoadingInstitutions: false,
        institutions: [],
        institutionFields: [
            {
                key: 'old_institution_id',
                label: 'Old ID',
                sortable: true
            },
            {
                key: 'new_institution_id',
                label: 'New ID',
                sortable: true
            },
            {
                key: 'transition_message',
                label: 'Message'
            },
            {
                key: 'number_of_institutions_to_migrate',
                label: '# Migrations Needed',
                sortable: true
            },
            {
                key: 'number_of_successful_migrations',
                label: '# Successful Migrations',
            },
            {
                key: 'number_of_failed_migrations',
                label: '# Failed Migrations',
            },
            {
                key: 'number_of_pending_migrations',
                label: '# Pending Migrations',
            },
            {
                key: 'migrate_users',
                label: 'Migrate Users'
            }
        ],
        isAddingInstitution: false,
        newInstitution: {},
        validationErrors: {},
        isSavingInstitution: false
    };
}

function getComputed(){
    return {};
}

function created(){
    const vm = this;
    vm.fetchInstitutions();
}

function getMethods(){
    return {
        displayErrorMessage,
        fetchInstitutions,
        createInstitution,
        cancelCreation,
        migrateUsers
    };

    function displayErrorMessage(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data;
        if(isValidationError){
            vm.validationErrors = error.data.errors;
        } else if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function fetchInstitutions(){
        const vm = this;
        vm.isLoadingInstitutions = true;
        vm.validationErrors = {};
        Vue.appApi().authorized().admin().finicity().getOauthInstitutions().then(setInstitutions).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setInstitutions(response){
            vm.institutions = response.data.map(addDisplayProperties);

            function addDisplayProperties(institution){
                institution.isMigratingUsers = false;
                return institution;
            }
        }
        function resetLoadingState(){
            vm.isLoadingInstitutions = false;
        }
    }

    function createInstitution(){
        const vm = this;
        vm.isSavingInstitution = true;
        Vue.appApi().authorized().admin().finicity().createOauthInstitution(vm.newInstitution).then(updateInstitutions).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function updateInstitutions(response){
            response.data.isMigratingUsers = false;
            vm.institutions.unshift(response.data);
            vm.isAddingInstitution = false;
        }
        function resetLoadingState(){
            vm.isSavingInstitution = false;
        }
    }

    function cancelCreation(){
        const vm = this;
        vm.newInstitution = {};
        vm.isAddingInstitution = false;
    }

    function migrateUsers(finicityOauthInstitution){
        const vm = this;
        finicityOauthInstitution.isMigratingUsers = true;
        return Vue.appApi().authorized().admin().finicity().migrateOauthInstitution(finicityOauthInstitution.id).then(updateInstitutionList).catch(vm.displayErrorMessage);

        function updateInstitutionList(response){
            Object.assign(finicityOauthInstitution, response.data);
            if(finicityOauthInstitution.number_of_pending_migrations > 0){
                setTimeout(getOauthInstitution, 2000);
            } else {
                finicityOauthInstitution.isMigratingUsers = false;
            }
        }
        function getOauthInstitution(){
            Vue.appApi().authorized().admin().finicity().getOauthInstitution(finicityOauthInstitution.id).then(updateInstitutionList).catch(vm.displayErrorMessage);
        }
    }
}
