import Vue from 'vue';

export default {
    namespaced: true,
    state: {
        bankAccounts: [],
        isFetchingBankAccounts: false
    },
    actions: getActions(),
    mutations: getMutations(),
    getters: getGetters(),
};

function getActions(){
    return {
        INITIALIZE_STATE: initializeState,
        FETCH_BANK_ACCOUNTS: fetchBankAccounts,
        DELETE_BANK_ACCOUNT: deleteBankAccount,
        UPDATE_BANK_ACCOUNT: updateBankAccount,
        UPDATE_INSTITUTION_ACCOUNT: updateInstitutionAccount
    };

    function initializeState({ commit, dispatch }){
        commit('RESET_STATE');
        return dispatch('FETCH_BANK_ACCOUNTS');
    }

    function fetchBankAccounts({ commit }){
        commit('SET_IS_FETCHING_BANK_ACCOUNTS', true);
        return Vue.appApi().authorized().bankAccount().loadWithInstitutionAccounts()
            .then(setBankAccounts)
            .finally(clearLoadingState);

        function setBankAccounts(response){
            commit('SET_BANK_ACCOUNTS', response.data);
            return JSON.parse(JSON.stringify(response));
        }
        function clearLoadingState(){
            commit('SET_IS_FETCHING_BANK_ACCOUNTS', false);
        }
    }

    function deleteBankAccount({ commit, dispatch, getters }, bankAccountId){
        let deletePromise = Promise.resolve();
        const bankAccount = getters.getBankAccountById(bankAccountId);
        const isLinkedAccount = bankAccount && bankAccount.institution_account_id;
        const isRequiredAccount = bankAccount && bankAccount.is_required;
        const isParentAccount = bankAccount && bankAccount.sub_accounts && bankAccount.sub_accounts.length;
        if(!bankAccount){
            deletePromise = Promise.reject(`Unable to find bank account with id: ${bankAccountId}`);
        } else if(isLinkedAccount){
            deletePromise = Vue.appApi().authorized().institution().destroyInstitutionAccount(bankAccount.institution_account_id);
        } else {
            deletePromise = Vue.appApi().authorized().bankAccount(bankAccount.id).destroy();
        }

        return deletePromise.then(removeAccountFromState);
        function removeAccountFromState(){
            let updateStatePromise = Promise.resolve();
            commit('REMOVE_BANK_ACCOUNT_BY_ID', bankAccountId);
            dispatch('authorized/transactions/FETCH_UNASSIGNED_TRANSACTIONS', null, { root: true });
            if(isRequiredAccount || isParentAccount){
                updateStatePromise = dispatch('FETCH_BANK_ACCOUNTS');
            }
            return updateStatePromise;
        }
    }

    function updateBankAccount({ commit, dispatch, state }, payload){
        const oldBankAccountData = state.bankAccounts.find(({ id }) => id === +payload.id);
        return Vue.appApi().authorized().bankAccount().createOrUpdate(payload)
            .then(updateLocalBankAccounts);
        function updateLocalBankAccounts(response){
            commit('UPDATE_BANK_ACCOUNT', response.data);
            const parentBankAccount = response.data.parent_bank_account_id ? state.bankAccounts.find(({ id }) => id === +response.data.parent_bank_account_id) : null;
            if(parentBankAccount){
                const updatedParent = JSON.parse(JSON.stringify(parentBankAccount));
                const subAccountIndex = updatedParent.sub_accounts.findIndex(({ id }) => id === response.data.id);
                if(subAccountIndex >= 0){
                    updatedParent.sub_accounts.splice(subAccountIndex, 1, response.data);
                } else {
                    updatedParent.sub_accounts.push(response.data);
                }
                updatedParent.sub_accounts.sort((a, b) => a.sub_account_order - b.sub_account_order);
                commit('UPDATE_BANK_ACCOUNT', updatedParent);
            }
            let updatePromise = Promise.resolve();
            const isPurposeUpdated = oldBankAccountData && response.data.purpose !== oldBankAccountData.purpose;
            const defaultAccountsUpdated = response.data.is_required && (!oldBankAccountData || isPurposeUpdated);
            if(defaultAccountsUpdated){
                updatePromise = dispatch('FETCH_BANK_ACCOUNTS');
            }
            if(isPurposeUpdated){
                dispatch('authorized/transactions/FETCH_UNASSIGNED_TRANSACTIONS', null, { root: true });
            }
            return updatePromise.then(() => JSON.parse(JSON.stringify(response)));
        }
    }

    function updateInstitutionAccount({ commit }, { bank_account_id, institutionPayload }){
        const institutionAccountId = institutionPayload.id;
        return Vue.appApi().authorized().institution().institutionAccounts(institutionAccountId).updateInstitutionAccount(institutionPayload)
            .then(updateLocalCopy);
        function updateLocalCopy(response){
            commit('UPDATE_INSTITUTION_ACCOUNT', { bank_account_id, payload: response.data });
            return response;
        }
    }
}

function getMutations(){
    return {
        RESET_STATE: resetState,
        SET_IS_FETCHING_BANK_ACCOUNTS: setIsFetchingBankAccounts,
        SET_BANK_ACCOUNTS: setBankAccounts,
        REMOVE_BANK_ACCOUNT_BY_ID: removeBankAccountById,
        UPDATE_BANK_ACCOUNT: updateBankAccount,
        UPDATE_BANK_BALANCE_PROPERTIES: updateBankBalanceProperties,
        UPDATE_INSTITUTION_ACCOUNT: updateInstitutionAccount,
    };

    function resetState(state){
        const defaultState = {
            bankAccounts: [],
            isFetchingBankAccounts: false
        };
        Object.keys(state).forEach((key) => {
            delete state[key];
        });
        Object.keys(defaultState).forEach((key) => {
            Vue.set(state, key, defaultState[key]);
        });
    }

    function setIsFetchingBankAccounts(state, payload){
        Vue.set(state, 'isFetchingBankAccounts', payload);
    }

    function setBankAccounts(state, payload){
        Vue.set(state, 'bankAccounts', getSortedBankAccounts(payload));
    }

    function removeBankAccountById(state, bankAccountId){
        const bankAccountIndex = state.bankAccounts.findIndex(({ id }) => id === +bankAccountId);
        if(bankAccountIndex >= 0){
            state.bankAccounts.splice(bankAccountIndex, 1);
        }
    }

    function updateBankAccount(state, bankAccount){
        const bankAccountIndex = state.bankAccounts.findIndex(({ id }) => id === +bankAccount.id);
        if(bankAccountIndex >= 0){
            state.bankAccounts.splice(bankAccountIndex, 1, bankAccount);
        } else {
            state.bankAccounts.push(bankAccount);
            Vue.set(state, 'bankAccounts', getSortedBankAccounts(state.bankAccounts));
        }
    }

    function updateBankBalanceProperties(state, payload){
        const propertiesToUpdate = [
            'balance_available',
            'balance_current',
            'assignment_balance_adjustment',
            'allocation_balance_adjustment'
        ];
        const bankAccount = state.bankAccounts.find(({ id }) => id === +payload.id);
        if(bankAccount){
            propertiesToUpdate.forEach((property) => {
                if(payload[property] !== undefined){
                    Vue.set(bankAccount, property, payload[property]);
                }
            });
        }
    }

    function updateInstitutionAccount(state, { bank_account_id, payload }){
        const bankAccount = state.bankAccounts.find(({ id }) => id === +bank_account_id);
        Vue.set(bankAccount, 'institution_account', payload);
    }

}

function getGetters(){
    return {
        bankAccountsWithInstitutionErrors,
        bankAccountsWithRecoverableErrors,
        bankAccountsWithUnknownErrors,
        bankAccountsWithUnrecoverableErrors,
        unrecoverableInstitutionErrorCodes,
        getBankAccountById
    };

    function bankAccountsWithInstitutionErrors(state){
        const errorStatues = ['error', 'recoverable'];
        return state.bankAccounts.filter(({ institution_account }) => institution_account && errorStatues.includes(institution_account.api_status));
    }
    function bankAccountsWithRecoverableErrors(state, getters){
        return getters.bankAccountsWithInstitutionErrors.filter(({ institution_account }) => institution_account.api_status === 'recoverable');
    }
    function bankAccountsWithUnknownErrors(state, getters){
        return getters.bankAccountsWithInstitutionErrors.filter(({ institution_account }) => institution_account.api_status !== 'recoverable' && institution_account.api_status_message === 'Unknown status code');
    }
    function bankAccountsWithUnrecoverableErrors(state, getters){
        return getters.bankAccountsWithInstitutionErrors.filter(({ institution_account }) => institution_account.api_status !== 'recoverable' && institution_account.api_status_message !== 'Unknown status code');
    }
    function unrecoverableInstitutionErrorCodes(state, getters){
        const allErrorCodes = getters.bankAccountsWithUnrecoverableErrors.map(({ institution_account }) => institution_account.remote_status_code);
        const uniqueErrorCodes = allErrorCodes.filter((item, index, array) => array.indexOf(item) === index);
        return uniqueErrorCodes;
    }
    function getBankAccountById(state){
        return function _getById(bankAccountId){
            return state.bankAccounts.find(({ id }) => id === +bankAccountId);
        };
    }
}

function getSortedBankAccounts(bankAccounts){
    const primaryCheckingAccount = bankAccounts.find(({ slug }) => slug === 'primary_checking');
    const primarySavingsAccount = bankAccounts.find(({ slug }) => slug === 'primary_savings');
    return bankAccounts.sort(byPurpose);

    function byPurpose(a, b){
        const aPurpose = getPurposeToCompare(a);
        const bPurpose = getPurposeToCompare(b);
        const accountPurposeOrder = [
            'unassigned',
            'primary_checking',
            'income',
            'bills',
            'spending',
            'primary_savings',
            'savings',
            'cc_payoff',
            'credit',
            'none'
        ];
        let isHigher = false;
        if(aPurpose === bPurpose){
            isHigher = a.parent_bank_account_id ? a.sub_account_order < b.sub_account_order : a.created_at < b.created_at;
        } else {
            const checkedPurposes = [];
            accountPurposeOrder.forEach(purpose => {
                isHigher = isHigher || aPurpose === purpose && !checkedPurposes.includes(bPurpose);
                if(!isHigher){
                    checkedPurposes.push(purpose);
                }
            });
        }
        return isHigher ? -1 : 0;

        function getPurposeToCompare(bankAccount){
            let purposeToCompare = bankAccount.purpose;
            if(primaryCheckingAccount && bankAccount.parent_bank_account_id === primaryCheckingAccount.id){
                purposeToCompare = 'primary_checking';
            } else if(primarySavingsAccount && bankAccount.parent_bank_account_id === primarySavingsAccount.id){
                purposeToCompare = 'primary_savings';
            }
            return purposeToCompare;
        }
    }
}
