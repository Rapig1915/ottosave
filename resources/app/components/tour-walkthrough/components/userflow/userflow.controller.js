import userflow from 'userflow.js';

export default {
    components: {},
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    data,
    computed: getComputed(),
    watch: getWatchers(),
    created,
    methods: getMethods()
};

function data(){
    return {
        isUserflowFriendlyEnvironment: false
    };
}

function getComputed(){
    return {};
}

function created(){
    const vm = this;
    const clientPlatform = window.appEnv.clientPlatform || 'web';
    vm.isUserflowFriendlyEnvironment = clientPlatform === 'web' && window.appEnv.userflow_token;
    vm.initializeUserflow();
    vm.setUserflowUser().then(vm.displayUserflow);
}

function getWatchers(){
    return {
        '$route'(newVal, oldVal){
            const vm = this;
            if(newVal.query.userflow_content_id !== oldVal.query.userflow_content_id){
                vm.displayUserflow();
            }
        },
        user(){
            const vm = this;
            vm.setUserflowUser();
        }
    };
}

function getMethods(){
    return {
        initializeUserflow,
        setUserflowUser,
        displayUserflow
    };

    function initializeUserflow(){
        const vm = this;
        if(vm.isUserflowFriendlyEnvironment){
            userflow.setInferenceAttributeFilter('id', [
                // Ignore ids ending in numbers
                id => !id.match(/\d$/),
                // Ignore ids with numbers followed by double underscore
                id => !id.match(/\d__/)
            ]);
            userflow.init(window.appEnv.userflow_token);
        }
    }

    function setUserflowUser(){
        const vm = this;
        const user = vm.user;
        let initPromise = Promise.resolve();
        if(user && vm.isUserflowFriendlyEnvironment){
            const viewportWidth = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
            initPromise = userflow.identify(user.id, {
                name: user.name,
                email: user.email,
                signed_up_at: Vue.moment(user.created_at).toISOString(),
                view_port: viewportWidth
            });
        }
        return initPromise;
    }

    function displayUserflow(){
        const vm = this;
        const userflowContentId = vm.$route.query.userflow_content_id;
        if(userflowContentId && vm.isUserflowFriendlyEnvironment){
            userflow.start(userflowContentId);
        }
    }
}
