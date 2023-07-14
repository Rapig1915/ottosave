export default {
    props: {
        emailLocation: {
            type: String,
            default: ''
        }
    },
    data: data,
    watch: getWatchers(),
    created,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        emailContent: '',
        isLoadingEmail: false
    };
}

function getWatchers(){
    return {
        emailLocation
    };
    function emailLocation(newVal, oldVal){
        const vm = this;
        vm.getRenderedEmailNotification();
    }
}

function created(){
    const vm = this;
    vm.getRenderedEmailNotification();
}

function getMethods(){
    return {
        getRenderedEmailNotification
    };

    function getRenderedEmailNotification(){
        const vm = this;
        vm.isLoadingEmail = true;
        vm.emailContent = '';
        if(vm.emailLocation){
            Vue.appApi().authorized().admin().getRenderedEmailNotification({ email: vm.emailLocation }).then(displayEmailContent).catch(displayError);
        }
        function displayEmailContent(response){
            vm.emailContent = response.data;
            vm.isLoadingEmail = false;
        }
        function displayError(response){
            vm.isLoadingEmail = false;
            if(response.appMessage){
                vm.apiErrors = [response.appMessage];
            }
        }
    }
}
