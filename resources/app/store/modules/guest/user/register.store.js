export default {
    namespaced: true,
    state: {
        referral_code: '',
    },
    actions: getActions(),
    mutations: getMutations(),
};

function getActions(){

    return {
        REGISTER_USER: registerUser,
    };
    function registerUser({ commit, dispatch, state }, credentials){
        const payload = {
            ...credentials,
            referral_code: state.referral_code
        };
        return Vue.appApi().guest().user().register(payload).then(registerUserSuccess);

        function registerUserSuccess(response){
            commit('SET_REFERRAL_CODE', '');
            return dispatch('user/REGISTER_USER_SUCCESS', response.data, { root: true });
        }
    }
}

function getMutations(){
    return {
        SET_REFERRAL_CODE: setReferralCode
    };

    function setReferralCode(state, code){
        state.referral_code = code;
    }
}
