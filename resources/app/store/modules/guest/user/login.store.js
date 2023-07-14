export default {
    namespaced: true,
    state: {
        isRequestingLogin: false
    },
    actions: getActions(),
    mutations: getMutations(),
};

function getActions(){

    return {
        LOGIN: postLogin,
        ENABLE_TOUCH_ID: storeCredentialsInIosKeychain
    };
    function postLogin({ commit, dispatch }, credentials){
        commit('SET_REQUESTING_LOGIN', true);
        return new Promise(postLoginPromise);

        function postLoginPromise(postLoginResolve, postLoginReject){

            Vue.appApi().guest().user().login(credentials).then(loginSuccess).catch(loginError);

            function loginSuccess(response){
                const promises = [
                    dispatch('user/REMEMBER_ME', credentials, { root: true }),
                    dispatch('ENABLE_TOUCH_ID', credentials),
                    dispatch('user/REGISTER_USER_SUCCESS', response.data, { root: true })
                ];
                Promise.all(promises).then(resolveResponse);
                function resolveResponse(){
                    commit('SET_REQUESTING_LOGIN', false);
                    postLoginResolve(response);
                }
            }
            function loginError(error){
                commit('SET_REQUESTING_LOGIN', false);
                postLoginReject(error);
            }
        }
    }
    function storeCredentialsInIosKeychain({ commit }, payload){
        if(payload.enableTouchId){
            return Vue.iosKeychainPlugin.storeCredentials(payload);
        } else {
            return Promise.resolve();
        }
    }
}

function getMutations(){
    return {
        SET_REQUESTING_LOGIN: setRequestingLogin
    };

    function setRequestingLogin(state, payload){
        state.isRequestingLogin = payload;
    }
}
