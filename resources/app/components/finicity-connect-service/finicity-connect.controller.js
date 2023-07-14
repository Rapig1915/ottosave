import { Plugins } from '@capacitor/core';

export default {
    components: {},
    data: data,
    beforeDestroy,
    methods: getMethods(),
};

function data(){
    return {
        connectLink: null,
        apiErrors: [],
        clientPlatform: window.appEnv.clientPlatform || 'web'
    };
}

function beforeDestroy(){
    window.finicityConnect.destroy();
}

function getMethods(){
    return {
        openFinicityConnect,
        openFinicityConnectFix,
        getConnectLink,
        displayApiError,
        emitComplete,
        emitFixComplete,
        emitCacelled,
        emitError,
        emitShown
    };

    function openFinicityConnect(){
        const vm = this;
        vm.getConnectLink().then(displayFinicityConnect).catch(vm.emitError);

        function displayFinicityConnect(){
            if(vm.clientPlatform === 'ios'){
                const { Browser } = Plugins;
                Browser.addListener('browserFinished', handleConnectCompletion);
                Browser.open({ url: vm.connectLink });
            } else {
                window.scrollTo(0, 0);
                window.finicityConnect.launch(vm.connectLink, {
                    success: handleConnectCompletion,
                    cancel: vm.emitCacelled,
                    error: vm.emitError
                });
            }

            function handleConnectCompletion(){
                Vue.appApi().authorized().institution().createFinicityInstitutionAccounts().then(response => {
                    vm.emitComplete(response.data);
                }).catch(vm.emitError);
            }
        }
    }

    function openFinicityConnectFix(institutionId){
        const vm = this;
        vm.getConnectLink(institutionId).then(displayFinicityConnect).catch(vm.emitError);

        function displayFinicityConnect(){
            if(vm.clientPlatform === 'ios'){
                const { Browser } = Plugins;
                Browser.addListener('browserFinished', vm.emitFixComplete);
                Browser.open({ url: vm.connectLink });
            } else {
                window.scrollTo(0, 0);
                window.finicityConnect.launch(vm.connectLink, {
                    success: vm.emitFixComplete,
                    cancel: vm.emitCacelled,
                    error: vm.emitError,
                    loaded: vm.emitShown
                });
            }
        }
    }

    function getConnectLink(institutionId = 0){
        const vm = this;
        var query = {
            type: institutionId ? 'fix' : 'aggregation',
            exclude_redirect_link: vm.clientPlatform !== 'ios'
        };
        return Vue.appApi().authorized().institution(institutionId).getFinicityConnectLink(query).then(setConnectLink).catch(vm.displayApiError);

        function setConnectLink(response){
            vm.connectLink = response.data.connect_link;
        }
    }

    function displayApiError(response){
        const vm = this;
        if(response && response.appMessage){
            vm.apiErrors = [response.appMessage];
        }
    }

    function emitComplete(accounts = []){
        const vm = this;
        vm.$emit('finicity-connect-complete', accounts);
    }

    function emitFixComplete(){
        const vm = this;
        vm.$emit('finicity-connect-fix-complete');
    }

    function emitCacelled(){
        const vm = this;
        vm.$emit('finicity-connect-cancelled');
    }

    function emitError(error){
        const vm = this;
        let errorMessage = '';
        if(typeof error === 'string'){
            errorMessage = error;
        } else if(error && error.appMessage){
            errorMessage = error.appMessage;
        } else if(error && error.message){
            errorMessage = error.message;
        }
        vm.$emit('finicity-connect-error', errorMessage);
    }

    function emitShown(){
        const vm = this;
        vm.$emit('finicity-connect-shown');
    }
}
