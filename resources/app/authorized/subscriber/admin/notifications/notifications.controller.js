import PreviewEmailComponent from '../components/preview-email/preview-email';

export default {
    components: {
        PreviewEmailComponent
    },
    data,
    watch: getWatchers(),
    created,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        notificationType: 'email',
        notificationEmails: [
            { value: null, text: 'Select an Email Notification to preview...' }
        ],
        selectedNotification: null,
        isLoadingNotifications: false
    };
}

function getWatchers(){
    return {
        selectedNotification,
        $route
    };
    function selectedNotification(){
        const vm = this;
        const { name, params } = vm.$route;
        vm.$router.push({ name, params, query: { type: vm.notificationType, selected: vm.selectedNotification }});
    }
    function $route(){
        const vm = this;
        vm.displayQueriedNotification();
    }
}

function created(){
    const vm = this;
    const dataFetchPromises = [
        vm.getEmailNotificationViews()
    ];
    Promise.all(dataFetchPromises).then(vm.displayQueriedNotification);
}

function getMethods(){
    return {
        getEmailNotificationViews,
        displayQueriedNotification
    };

    function getEmailNotificationViews(){
        const vm = this;
        vm.isLoadingNotifications = true;
        return Vue.appApi().authorized().admin().getEmailNotificationViews().then(displayEmailSelectOptions).catch(displayError);

        function displayEmailSelectOptions(response){
            const emailSelectOptions = parseFilenamesIntoOptions(Object.values(response.data));
            vm.notificationEmails.push(...emailSelectOptions);
            vm.isLoadingNotifications = false;

            function parseFilenamesIntoOptions(filenames){
                return filenames.map(filename => {
                    filename = filename.split('.php')[0];
                    return {
                        value: filename,
                        text: filename.split(/(?=[A-Z])/).join(' ')
                    };
                });
            }
        }
        function displayError(response){
            if(response.appMessage){
                vm.apiErrors = [response.appMessage];
            }
            vm.isLoadingNotifications = false;
        }
    }

    function displayQueriedNotification(){
        const vm = this;
        vm.notificationType = vm.$route.query.type || 'email';
        vm.selectedNotification = vm.$route.query.selected || null;
    }
}
