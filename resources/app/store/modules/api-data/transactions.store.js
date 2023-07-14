import Vue from 'vue';

export default {
    namespaced: true,
    state: {
        unassignedTransactions: [],
        isFetchingTransactions: false
    },
    actions: getActions(),
    mutations: getMutations(),
    getters: getGetters(),
};

function getActions(){
    return {
        INITIALIZE_STATE: initializeState,
        FETCH_UNASSIGNED_TRANSACTIONS: fetchUnassignedTransactions,
    };

    function initializeState({ commit, dispatch }){
        commit('RESET_STATE');
        return dispatch('FETCH_UNASSIGNED_TRANSACTIONS');
    }

    function fetchUnassignedTransactions({ commit }){
        commit('SET_IS_FETCHING_TRANSACTIONS', true);
        return Vue.appApi().authorized().bankAccount().assignment().getUnassignedTransactions()
            .then(setAssignableTransactions)
            .finally(clearLoadingState);

        function setAssignableTransactions(response){
            commit('SET_UNASSIGNED_TRANSACTIONS', response.data);
            return response;
        }
        function clearLoadingState(){
            commit('SET_IS_FETCHING_TRANSACTIONS', false);
        }
    }
}

function getMutations(){
    return {
        RESET_STATE: resetState,
        SET_IS_FETCHING_TRANSACTIONS: setIsFetchingTransactions,
        SET_UNASSIGNED_TRANSACTIONS: setUnassignedTransactions,
        REMOVE_UNASSIGNED_TRANSACTION: removeUnassignedTransaction,
        ADD_UNASSIGNED_TRANSACTION: addUnassignedTransaction
    };

    function resetState(state){
        const defaultState = {
            unassignedTransactions: [],
            isFetchingTransactions: false
        };
        Object.keys(state).forEach((key) => {
            delete state[key];
        });
        Object.keys(defaultState).forEach((key) => {
            Vue.set(state, key, defaultState[key]);
        });
    }

    function setIsFetchingTransactions(state, payload){
        Vue.set(state, 'isFetchingTransactions', payload);
    }

    function setUnassignedTransactions(state, payload){
        Vue.set(state, 'unassignedTransactions', payload);
    }

    function removeUnassignedTransaction(state, payload){
        const transactionIndex = state.unassignedTransactions.findIndex(({ id }) => id === payload.id);
        if(transactionIndex > -1){
            state.unassignedTransactions.splice(transactionIndex, 1);
        }
    }

    function addUnassignedTransaction(state, payload){
        const transactionIndex = state.unassignedTransactions.findIndex(({ id }) => id === payload.id);
        if(transactionIndex > -1){
            state.unassignedTransactions.splice(transactionIndex, 1, payload);
        } else {
            state.unassignedTransactions.push(payload);
        }
    }
}

function getGetters(){
    return {
        countOfUnassignedTransactions
    };

    function countOfUnassignedTransactions(state){
        return state.unassignedTransactions.length;
    }
}
