import register from './user/register.store';
import login from './user/login.store';
import forgot_password from './user/forgot-password.store';
import reset_password from './user/reset-password.store';
import Vue from 'vue';

const state = {
    hasAccessToken: 'pending',
    user: null,
    loading: false,
    accessible_accounts: [],
    refreshTokenExpiration: null
};

export default {
    namespaced: true,
    state,
    mutations: getMutations(),
    actions: getActions(),
    getters: getGetters(),
    modules: {
        register,
        login,
        forgot_password,
        reset_password
    }
};

function getActions(){

    return {
        LOGOUT: sendLogoutRequest,
        LOGOUT_FRONTEND: logoutFrontend,
        LOGOUT_SUCCESS: logoutSuccess,
        GET_USER: getUser,
        START_NEW_TRIAL: startNewTrial,
        START_BASIC_PLAN: startBasicPlan,
        CANCEL_SUBSCRIPTION: cancelSubscription,
        PURCHASE_SUBSCRIPTION: purchaseSubscription,
        REGISTER_USER_SUCCESS: setAuthenticationAndUser,
        SET_AUTH_TOKENS: setAuthentication,
        REMEMBER_ME: storeUserEmail,
        GET_STORED_ACCESS_TOKEN: getStoredAccessToken,
        SAVE_NOTIFICATION_PREFERENCES: saveNotificationPreferences
    };

    function sendLogoutRequest({ commit, dispatch }){
        return Vue.appApi().guest().user().logout().then(logoutSuccess);

        function logoutSuccess(){
            return dispatch('LOGOUT_SUCCESS');
        }
    }
    function logoutFrontend({ commit, dispatch }){
        return dispatch('LOGOUT_SUCCESS');
    }
    function logoutSuccess({ commit }){
        commit('SET_HAS_ACCESS_TOKEN', false);
        delete window.axios.defaults.headers.common['Authorization'];
        delete Vue.clientStorage.removeItem('current_account_id');
        return Promise.all([
            Vue.clientStorage.removeItem('access_token'),
            Vue.clientStorage.removeItem('refresh_token'),
            Vue.clientStorage.removeItem('refresh_token_expiration')
        ]);
    }
    function getUser({ commit }){

        commit('SET_LOADING', true);
        return Vue.appApi().authorized().user().getUser().then(setUser).finally(resetLoadingState);

        function setUser(response){
            commit('SET_USER', response.data.user);
            commit('SET_ACCESSIBLE_ACCOUNTS', response.data.accessible_accounts || []);
            return Promise.resolve();
        }

        function resetLoadingState(){
            commit('SET_LOADING', false);
        }
    }
    function startNewTrial({ commit, dispatch }){
        return Vue.appApi().authorized().account().startNewTrial().then(updateUserState);

        function updateUserState(response){
            const user = response.data;

            if(user.current_account.status === 'free_trial'){
                return dispatch('GET_USER');
            }

            return Promise.reject(response);
        }
    }
    function startBasicPlan({ commit }){
        return Vue.appApi().authorized().account().startBasicPlan().then(subscriptionSuccess);

        function subscriptionSuccess(response){
            const user = response.data;
            commit('SET_USER', user);
            return Promise.resolve(user);
        }
    }
    function cancelSubscription({ commit }){
        return Vue.appApi().authorized().account().cancelSubscription().then(cancelSubSuccess).catch(cancelSubFailure);

        function cancelSubSuccess(response){
            const user = response.data;

            commit('SET_CURRENT_ACCOUNT_STATUS', user.current_account.status);

            if(user.current_account.status === 'grace' || user.current_account.subscription_plan === 'basic'){
                return Promise.resolve(response);
            }

            return Promise.reject(response);
        }

        function cancelSubFailure(error){
            return Promise.reject(error);
        }
    }
    function purchaseSubscription({ commit, dispatch }, subscriptionPayload){
        return Vue.appApi().authorized().account().purchaseSubscription(subscriptionPayload).then(upgradeSuccess).catch(upgradeFailure);

        function upgradeSuccess(response){
            return dispatch('GET_USER');
        }

        function upgradeFailure(error){
            return Promise.reject(error);
        }
    }

    function setAuthenticationAndUser({ commit, dispatch }, payload){
        commit('SET_USER', payload.user);
        commit('SET_ACCESSIBLE_ACCOUNTS', payload.accessible_accounts || []);
        commit('SET_HAS_ACCESS_TOKEN', true);

        return dispatch('SET_AUTH_TOKENS', payload.token);
    }
    function setAuthentication({ commit, dispatch }, payload){
        const refreshTokenLifetimeInSeconds = payload.refresh_expires_in;
        const now = Vue.moment();
        const refreshTokenExpiration = now.add(refreshTokenLifetimeInSeconds, 'seconds').format();
        const storagePromises = [
            Vue.clientStorage.setItem('access_token', payload.access_token),
            Vue.clientStorage.setItem('refresh_token', payload.refresh_token),
            Vue.clientStorage.setItem('refresh_token_expiration', refreshTokenExpiration),
        ];
        window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + payload.access_token;
        commit('SET_REFRESH_TOKEN_EXPIRATION', refreshTokenExpiration);
        return Promise.all(storagePromises);
    }
    function storeUserEmail(state, payload){
        const promises = [];
        if(payload.remember_me){
            promises.push(Vue.clientStorage.setItem('email', payload.email));
        } else {
            promises.push(Vue.clientStorage.getItem('email').then(removeSavedEmail));
        }
        return Promise.all(promises);
        function removeSavedEmail(storedEmail){
            const removeSavedEmail = storedEmail === payload.email;
            if(removeSavedEmail){
                return Vue.clientStorage.removeItem('email');
            }
        }
    }
    function getStoredAccessToken({ commit }){
        return Vue.clientStorage.getItem('access_token').then(updateState).catch(handleError);
        function updateState(storedToken){
            commit('SET_HAS_ACCESS_TOKEN', (!!storedToken));
        }
        function handleError(caughtError){
            commit('SET_HAS_ACCESS_TOKEN', false);
        }
    }
    function saveNotificationPreferences({ commit }, payload){
        return Vue.appApi().authorized().account().notificationPreferences().storeNotificationPreferences(payload).then(setNotificationPreferences);
        function setNotificationPreferences(response){
            commit('SET_NOTIFICATION_PREFERENCES', response.data);
        }
    }
}

function getMutations(){

    return {
        SET_USER: setUserState,
        SET_LOADING: setLoadingState,
        SET_ACCESSIBLE_ACCOUNTS: setAccessibleAccounts,
        SET_CURRENT_ACCOUNT_STATUS: setCurrentAccountStatus,
        SET_CURRENT_ACCOUNT: setCurrentAccount,
        SET_HAS_ACCESS_TOKEN: setHasAccessToken,
        SET_NOTIFICATION_PREFERENCES: setNotificationPreferences,
        SET_REFRESH_TOKEN_EXPIRATION: setRefreshTokenExpiration
    };

    function setUserState(state, user){
        state.user = user;
        setUserIdWithGoogleAnalytics(user.id);

        function setUserIdWithGoogleAnalytics(user_id){
            const isGoogleAnalyticsConfigured = typeof window.gtag === 'function' && window.appEnv.ga_id;
            if(isGoogleAnalyticsConfigured){
                window.gtag('set', { 'user_id': user_id });
            }
        }
    }
    function setLoadingState(state, loading){
        state.loading = loading;
    }
    function setAccessibleAccounts(state, accounts){
        state.accessible_accounts = accounts;
    }
    function setCurrentAccountStatus(state, status){
        state.user.current_account.status = status;
    }
    function setCurrentAccount(state, account){
        state.user.current_account = account;
        Vue.clientStorage.setItem('current_account_id', account.id);
    }
    function setHasAccessToken(state, payload){
        state.hasAccessToken = payload;
    }
    function setNotificationPreferences(state, payload){
        state.user.current_account_user.notification_preferences = payload;
    }
    function setRefreshTokenExpiration(state, payload){
        state.refreshTokenExpiration = payload;
    }
}

function getGetters(){
    return {
        user,
        isLoading,
        currentAccountId,
        currentAccountUserId,
        hasPermissionTo,
    };

    function user(state){
        return state.user;
    }

    function isLoading(){
        return state.loading;
    }

    function currentAccountId(state, getters){
        let currentAccountId;
        if(getters.user && getters.user.current_account){
            currentAccountId = getters.user.current_account.id;
        }
        return currentAccountId;
    }

    function currentAccountUserId(state, getters){
        let currentAccountUserId;
        if(getters.user && getters.user.current_account_user){
            currentAccountUserId = getters.user.current_account_user.id;
        }
        return currentAccountUserId;
    }

    function hasPermissionTo(state, getters){
        return function hasPermissionTo(permissionToCheck){
            const userPermissions = (getters.user && getters.user.current_account_user) ? getters.user.current_account_user.all_permission_names : [];
            return userPermissions.includes(permissionToCheck);
        };
    }
}
