const state = {
    viewKey: 1,
};
export default {
    namespaced: true,
    state,
    actions: getActions(),
    mutations: getMutations(),
};

function getActions(){
    return {
        FORCE_REMOUNT: forceRemount,
    };

    function forceRemount({ commit }){
        commit('INCREASE_VIEW_KEY');
        return Promise.resolve();
    }
}

function getMutations(){
    return {
        INCREASE_VIEW_KEY: increaseViewKey,
    };
    function increaseViewKey(state){
        state.viewKey = state.viewKey + 1;
    }
}
