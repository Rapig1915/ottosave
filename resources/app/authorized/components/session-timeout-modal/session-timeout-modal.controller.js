export default {
    data,
    computed: getComputed(),
    watch: getWatchers(),
    created,
    beforeDestroy,
    methods: getMethods()
};
function data(){
    return {
        displaySessionTimeoutModal: false,
        sessionInactivityTimer: null,
        sessionTimeoutWarningBuffer: 300000, // five minutes
        visibilityEventPrefix: ''
    };
}
function getComputed(){
    return {
        refreshTokenExpiration
    };

    function refreshTokenExpiration(){
        const vm = this;
        return Vue.moment(vm.$store.state.guest.user.refreshTokenExpiration);
    }
}
function getWatchers(){
    return {
        refreshTokenExpiration
    };
    function refreshTokenExpiration(){
        const vm = this;
        vm.startSessionInactivityTimer();
    }
}
function created(){
    const vm = this;
    Vue.clientStorage.getItem('refresh_token_expiration').then(setRefreshTokenExpiration).then(vm.startSessionInactivityTimer);
    vm.watchPageVisibilityEvent();
    function setRefreshTokenExpiration(expirationDate){
        vm.$store.commit('user/SET_REFRESH_TOKEN_EXPIRATION', expirationDate);
    }
}
function beforeDestroy(){
    const vm = this;
    vm.clearTimers();
    if(typeof vm.visibilityEventPrefix !== 'undefined'){
        document.removeEventListener(vm.visibilityEventPrefix + 'visibilitychange', vm.handleVisibilityChange);
    }
}
function getMethods(){
    return {
        startSessionInactivityTimer,
        startSessionExpiredRedirectTimer,
        resetSessionInactivityTimer,
        forceLogout,
        clearTimers,
        watchPageVisibilityEvent,
        handleVisibilityChange
    };
    function startSessionInactivityTimer(){
        const vm = this;
        vm.clearTimers();
        vm.displaySessionTimeoutModal = false;
        const oneMinute = 60000;
        const now = Vue.moment();
        const remainingTokenLifetime = vm.refreshTokenExpiration.diff(now);
        if(remainingTokenLifetime < oneMinute){
            vm.forceLogout();
        } else if(remainingTokenLifetime <= vm.sessionTimeoutWarningBuffer){
            showSessionTimeoutModal();
        } else {
            vm.sessionInactivityTimer = setTimeout(showSessionTimeoutModal, remainingTokenLifetime - vm.sessionTimeoutWarningBuffer);
        }
        function showSessionTimeoutModal(){
            vm.displaySessionTimeoutModal = true;
            vm.startSessionExpiredRedirectTimer();
        }
    }
    function startSessionExpiredRedirectTimer(){
        const vm = this;
        const oneMinute = 60000;
        vm.sessionExpiredRedirectTimer = setTimeout(vm.forceLogout, oneMinute);
    }
    function resetSessionInactivityTimer(){
        const vm = this;
        vm.clearTimers();
        vm.displaySessionTimeoutModal = false;
        return Vue.appApi().authorized().user().refreshTokens().then(vm.startSessionInactivityTimer).catch(vm.forceLogout);
    }
    function forceLogout(){
        const vm = this;
        vm.$store.dispatch('user/LOGOUT_FRONTEND').then(logoutSuccess);
        function logoutSuccess(){
            const clientPlatform = window.appEnv.clientPlatform || 'web';
            if(clientPlatform === 'web'){
                vm.$router.go();
            } else {
                vm.$router.replace({ name: 'login' });
            }
        }
    }
    function clearTimers(){
        const vm = this;
        clearTimeout(vm.sessionInactivityTimer);
        clearTimeout(vm.sessionExpiredRedirectTimer);
    }
    function watchPageVisibilityEvent(){
        const vm = this;
        vm.visibilityEventPrefix = getPrefix();
        const eventName = vm.visibilityEventPrefix + 'visibilitychange';
        if(typeof vm.visibilityEventPrefix !== 'undefined'){
            document.addEventListener(eventName, handleVisibilityChange.bind(vm));
        }

        function getPrefix(){
            var prefixes = ['webkit', 'moz', 'ms', 'o'];

            if('hidden' in document){
                return '';
            }
            const prefix = prefixes.find(prefix => `${prefix}Hidden` in document);
            return prefix;
        }

    }
    function handleVisibilityChange(){
        const vm = this;
        const hiddenProperty = vm.visibilityEventPrefix ? `${vm.visibilityEventPrefix}Hidden` : 'hidden';
        if(!document[hiddenProperty]){
            vm.startSessionInactivityTimer();
        }
    }
}
