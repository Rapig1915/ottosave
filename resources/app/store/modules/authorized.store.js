import transactions from './api-data/transactions.store';
import bankAccounts from './api-data/bank-accounts.store';

const state = {
    displayUpgradeModal: false,
    finicityRefreshStatus: '',
    finicityRefreshPromise: null,
};
export default {
    namespaced: true,
    state,
    actions: getActions(),
    mutations: getMutations(),
    getters: getGetters(),
    modules: {
        transactions,
        bankAccounts
    },
};

function getActions(){
    return {
        INITIALIZE_STATE: initializeState,
        DISPLAY_UPGRADE_MODAL: displayUpgradeModal,
        REFRESH_LINKED_ACCOUNTS: refreshLinkedAccounts,
        CHECK_FINICITY_REFRESH_STATUS: checkFinicityRefreshStatus,
    };

    function initializeState({ commit, dispatch }){
        return Promise.all([
            dispatch('transactions/INITIALIZE_STATE'),
            dispatch('bankAccounts/INITIALIZE_STATE'),
            dispatch('tourWalkthrough/INITIALIZE_TOUR_STATE', null, { root: true })
        ]);
    }

    function displayUpgradeModal({ commit, state }){
        commit('TOGGLE_UPGRADE_MODAL', true);
        return Promise.resolve();
    }
    function refreshLinkedAccounts({ commit, state, dispatch }, institutionId = 'all'){
        if(!state.finicityRefreshPromise){
            commit('SET_FINICITY_REFRESH_STATUS', 'pending');
            const finicityRefreshPromise = Vue.appApi().authorized().institution(institutionId).refreshFinicityInstitutionAsync().then(pollRefreshStatus).catch(setRefreshError);
            commit('SET_FINICITY_REFRESH_PROMISE', finicityRefreshPromise);
        }

        return state.finicityRefreshPromise;

        function pollRefreshStatus(){
            return dispatch('CHECK_FINICITY_REFRESH_STATUS');
        }
        function setRefreshError(){
            commit('SET_FINICITY_REFRESH_PROMISE', null);
            commit('SET_FINICITY_REFRESH_STATUS', 'error');
        }
    }
    function checkFinicityRefreshStatus({ commit, state, dispatch, getters }){
        return new Promise(promiseToCheckFinicityStatus);

        function promiseToCheckFinicityStatus(resolve, reject){
            recursivelyCheckPendingRefreshes();

            function recursivelyCheckPendingRefreshes(){
                return Vue.appApi().authorized().account().getFinicityRefreshLogs().then(pollPendingRefresh);

                function pollPendingRefresh(response){
                    const finicityRefreshes = response.data;
                    const isRefreshPending = finicityRefreshes.some(matchRecentPendingRefresh);
                    if(isRefreshPending){
                        commit('SET_FINICITY_REFRESH_STATUS', 'pending');
                        setTimeout(recursivelyCheckPendingRefreshes, 2000);
                    } else {
                        const wasRefreshPending = getters.finicityRefreshStatus === 'pending';
                        const dataRefreshPromises = [];
                        if(wasRefreshPending){
                            dataRefreshPromises.push(dispatch('authorized/transactions/FETCH_UNASSIGNED_TRANSACTIONS', null, { root: true }));
                            dataRefreshPromises.push(dispatch('authorized/bankAccounts/FETCH_BANK_ACCOUNTS', null, { root: true }));
                        }
                        commit('SET_FINICITY_REFRESH_PROMISE', null);
                        commit('SET_FINICITY_REFRESH_STATUS', 'complete');
                        Promise.all(dataRefreshPromises).finally(resolve);
                    }

                    function matchRecentPendingRefresh(finicityRefresh){
                        const isPending = finicityRefresh.status === 'pending';
                        const oneHourAgo = Vue.moment().subtract(1, 'hour');
                        const isRecent = Vue.moment(finicityRefresh.updated_at).isAfter(oneHourAgo);
                        return isPending && isRecent;
                    }
                }
            }
        }
    }
}

function getMutations(){
    return {
        TOGGLE_UPGRADE_MODAL: toggleUpgradeModal,
        SET_FINICITY_REFRESH_STATUS: setFinicityRefreshStatus,
        SET_FINICITY_REFRESH_PROMISE: setFinicityRefreshPromise,
    };

    function toggleUpgradeModal(state, isShown){
        if(typeof isShown === 'undefined'){
            state.displayUpgradeModal = !state.displayUpgradeModal;
        } else {
            state.displayUpgradeModal = isShown;
        }
    }
    function setFinicityRefreshStatus(state, finicityRefreshStatus){
        state.finicityRefreshStatus = finicityRefreshStatus;
    }
    function setFinicityRefreshPromise(state, finicityRefreshPromise){
        state.finicityRefreshPromise = finicityRefreshPromise;
    }
}

function getGetters(){
    return {
        finicityRefreshStatus
    };

    function finicityRefreshStatus(state){
        return state.finicityRefreshStatus;
    }
}
