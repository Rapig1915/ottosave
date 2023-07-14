import axios from 'axios';
import router from 'vue_root/router';
import store from '../app.store';

export default appApi();

function appApi(){

    return {
        install: install,
    };

    function install(Vue, options){

        const appHttp = axios.create({
            baseURL: window.appEnv.baseURL
        });

        let refreshAuthenticationPromise = null;

        appHttp.interceptors.response.use(response => response, catchAllResponseFailures);
        appHttp.interceptors.request.use(modifyAllRequestConfigs, error => error);

        Vue.appApi = _appApi;

        function _appApi(){

            return {
                guest: guest,
                authorized: authorized,
            };
            // Vue.appApi().guest()
            function guest(){

                return {
                    user,
                    braintree
                };

                // Vue.appApi().guest().user()
                function user(){

                    return {
                        register: register,
                        logout: logout,
                        login: login,
                        forgotPassword: forgotPassword,
                        resetPassword: resetPassword,
                        verifyEmailAddress
                    };

                    // Vue.appApi().guest().user().register()
                    function register(payload){
                        return appHttp.put(`/v1/user/register`, payload);
                    }
                    // Vue.appApi().guest().user().login()
                    function login(payload){
                        return appHttp.post(`/v1/user/login`, payload);
                    }
                    // Vue.appApi().guest().user().logout()
                    function logout(){
                        return appHttp.post(`/api/v1/logout`);
                    }
                    // Vue.appApi().guest().user().forgotPassword()
                    function forgotPassword(payload){
                        return appHttp.post(`/v1/user/forgot-password`, payload);
                    }
                    // Vue.appApi().guest().user().resetPassword()
                    function resetPassword(payload){
                        return appHttp.post(`/v1/user/reset`, payload);
                    }

                    // Vue.appApi().guest().user().verifyEmailAddress(payload)
                    function verifyEmailAddress(payload){
                        return appHttp.post(`/api/v1/user/verify-email`, payload);
                    }
                }
                // Vue.appApi().guest().braintree()
                function braintree(){
                    return {
                        getClientAuthToken
                    };
                    // Vue.appApi().guest().braintree().getClientAuthToken()
                    function getClientAuthToken(){
                        return appHttp.get(`/api/v1/braintree/client-token`);
                    }
                }
            }
            // Vue.appApi().authorized()
            function authorized(){

                return {
                    user: user,
                    institution: institution,
                    defense: defense,
                    bankAccount: bankAccount,
                    account,
                    admin,
                };

                function user(){
                    return {
                        store: store,
                        changePassword: changePassword,
                        changeEmail: changeEmail,
                        getUser: getUser,
                        refreshTokens: refreshTokens,
                        resendVerificationEmail,
                        acceptAccountInvite,
                    };

                    // Vue.appApi().authorized().user().store(payload)
                    function store(payload){
                        return appHttp.put('/api/v1/user', payload);
                    }

                    // Vue.appApi().authorized().user().changePassword(payload)
                    function changePassword(payload){
                        return appHttp.put(`/api/v1/user/change-password`, payload);
                    }

                    // Vue.appApi().authorized().user().changeEmail(payload)
                    function changeEmail(payload){
                        return appHttp.put(`/api/v1/user/change-email`, payload);
                    }

                    // Vue.appApi().authorized().user().getUser()
                    function getUser(){
                        return appHttp.get(`/api/v1/user`);
                    }

                    // Vue.appApi().authorized().user().refreshTokens()
                    function refreshTokens(){
                        return Vue.clientStorage.getItem('refresh_token').then(refreshAuthToken);
                    }

                    // Vue.appApi().authorized().user().resendVerificationEmail()
                    function resendVerificationEmail(){
                        return appHttp.get(`/api/v1/user/verify-email`);
                    }
                    // Vue.appApi().authorized().user().acceptAccountInvite(payload)
                    function acceptAccountInvite(payload){
                        return appHttp.post(`/api/v1/user/accept-invite`, payload);
                    }
                }

                function defense(defenseId){
                    return {
                        indexDefenses,
                        transferFunds,
                        createDefense
                    };
                    // Vue.appApi().authorized().defense().indexDefenses(query)
                    function indexDefenses(query){
                        return appHttp.get(`/api/v1/account/defense`, { params: query });
                    }
                    // Vue.appApi().authorized().defense(defenseId).transferFunds(payload)
                    function transferFunds(payload){
                        return appHttp.post(`/api/v2/account/defense/${defenseId}/transfer-funds`, payload);
                    }
                    // Vue.appApi().authorized().defense().createDefense()
                    function createDefense(payload){
                        return appHttp.post(`/api/v1/account/defense/create`, payload);
                    }
                }

                function institution(institutionId){
                    return {
                        destroyInstitutionAccount,
                        getFinicityConnectLink,
                        createFinicityInstitutionAccounts,
                        refreshFinicityInstitution,
                        refreshFinicityInstitutionAsync,
                        refreshFinicityAccounts,
                        getInstitutionsToMigrate,
                        migrateInstitutionToOauth,
                        deleteInstitution,
                        institutionAccounts
                    };
                    // Vue.appApi().authorized().institution().destroyInstitutionAccount(institutionAccountId)
                    function destroyInstitutionAccount(institutionAccountId){
                        return appHttp.delete('/api/v1/account/institution-accounts/' + institutionAccountId);
                    }

                    // Vue.appApi().authorized().institution(institutionId).getFinicityConnectLink(query)
                    function getFinicityConnectLink(query){
                        return appHttp.get(`/api/v2/account/institution/${institutionId}/finicity-link`, { params: query });
                    }

                    // Vue.appApi().authorized().institution().createFinicityInstitutionAccounts()
                    function createFinicityInstitutionAccounts(){
                        return appHttp.post(`/api/v2/account/institution-accounts/finicity-create`);
                    }

                    // Vue.appApi().authorized().institution(institutionId).refreshFinicityInstitution()
                    function refreshFinicityInstitution(){
                        return appHttp.get(`/api/v1/account/institution/${institutionId}/refresh-finicity`);
                    }

                    // Vue.appApi().authorized().institution(institutionId).refreshFinicityInstitutionAsync()
                    function refreshFinicityInstitutionAsync(){
                        return appHttp.get(`/api/v1/account/institution/${institutionId}/refresh-finicity-async`);
                    }

                    // Vue.appApi().authorized().institution().refreshFinicityAccounts()
                    function refreshFinicityAccounts(){
                        return appHttp.get(`/api/v1/account/institution-accounts/finicity-refresh`);
                    }

                    // Vue.appApi().authorized().institution().getInstitutionsToMigrate()
                    function getInstitutionsToMigrate(){
                        return appHttp.get(`/api/v1/account/institution/pending-oauth-migration`);
                    }

                    // Vue.appApi().authorized().institution(institutionId).migrateInstitutionToOauth()
                    function migrateInstitutionToOauth(){
                        return appHttp.get(`/api/v1/account/institution/${institutionId}/migrate-to-oauth`);
                    }

                    // Vue.appApi().authorized().institution(institutionId).deleteInstitution()
                    function deleteInstitution(){
                        return appHttp.delete(`/api/v1/account/institution/${institutionId}`);
                    }
                    // Vue.appApi().authorized().institution().institutionAccounts(institutionAccountId)
                    function institutionAccounts(institutionAccountId){
                        return {
                            downloadPastTransactions,
                            updateInstitutionAccount,
                        };
                        // Vue.appApi().authorized().institution(institutionId).institutionAccounts(institutionAccountId).downloadPastTransactions(payload)
                        function downloadPastTransactions(payload){
                            return appHttp.post(`/api/v1/account/institution-accounts/${institutionAccountId}/fetch-past-transactions`, payload);
                        }
                        // Vue.appApi().authorized().institution().institutionAccounts(institutionAccountId).updateInstitutionAccount(payload)
                        function updateInstitutionAccount(payload){
                            return appHttp.post(`/api/v1/account/institution-accounts/${institutionAccountId}`, payload);
                        }
                    }
                }

                // Vue.appApi().authorized().bankAccount()
                function bankAccount(bankAccountId){
                    return {
                        loadWithInstitutionAccounts,
                        loadWithInstitutionAccountsOf,
                        getAssignableAccounts,
                        createOrUpdate,
                        get,
                        spendingAccount,
                        savingsAccessCreditCard,
                        destroy,
                        scheduleItem,
                        assignment,
                        transaction,
                        getIncomeAccountOverview,
                        getCCPayoffAccountOverview,
                        getAllocationAccounts,
                        clearPendingTransfers,
                        getCreditCardOverviews,
                        requestTransactionDownload,
                        loadRecentBankTransactionsHistory,
                    };

                    // Vue.appApi().authorized().bankAccount().loadWithInstitutionAccounts(queryObject)
                    function loadWithInstitutionAccounts(queryObject = {}){
                        return appHttp.get(`/api/v3/account/bank-accounts/with-linked`, { params: queryObject });
                    }

                    // Vue.appApi().authorized().bankAccount().loadWithInstitutionAccountsOf(accountId, queryObject)
                    function loadWithInstitutionAccountsOf(accountId, queryObject = {}){
                        return appHttp.get(`/api/v3/account/bank-accounts/with-linked-of/${accountId}`, { params: queryObject });
                    }

                    // Vue.appApi().authorized().bankAccount().getAssignableAccounts()
                    function getAssignableAccounts(){
                        const cancelToken = axios.CancelToken;
                        const cancelTokenSource = cancelToken.source();
                        var promise = appHttp.get(`/api/v2/account/bank-accounts/assignable-accounts`,
                            { cancelToken: cancelTokenSource.token });
                        promise.cancelToken = cancelTokenSource;
                        return promise;
                    }

                    // Vue.appApi().authorized().bankAccount().createOrUpdate(payload)
                    function createOrUpdate(payload){
                        return appHttp.post(`/api/v3/account/bank-accounts/create-update`, payload);
                    }

                    // Vue.appApi().authorized().bankAccount(bankAccountId).get(queryObject)
                    function get(queryObject){
                        return appHttp.get(`/api/v3/account/bank-accounts/` + bankAccountId, { params: queryObject });
                    }

                    // Vue.appApi().authorized().bankAccount().spendingAccount().getOverview()
                    function spendingAccount(){
                        return {
                            getOverview: getOverview
                        };
                        function getOverview(){
                            return appHttp.get('/api/v1/account/bank-accounts/everyday-checking-account-overview');
                        }
                    }

                    function savingsAccessCreditCard(){
                        return {
                            get: get
                        };

                        // Vue.appApi().authorized().bankAccount().savingsAccessCreditCard().get()
                        function get(){
                            return appHttp.get(`/api/v2/account/bank-accounts/savings-cc`);
                        }
                    }

                    // Vue.appApi().authorized().bankAccount(bankAccountId).destroy()
                    function destroy(){
                        return appHttp.delete(`/api/v1/account/bank-accounts/` + bankAccountId);
                    }
                    // Vue.appApi().authorized().bankAccount(bankAccountId).clearPendingTransfers()
                    function clearPendingTransfers(){
                        return appHttp.get(`/api/v3/account/bank-accounts/${bankAccountId}/clear-transfers`);
                    }
                    // Vue.appApi().authorized().bankAccount(bankAccountId).loadRecentBankTransactionsHistory()
                    function loadRecentBankTransactionsHistory(){
                        return appHttp.get(`/api/v1/account/bank-accounts/${bankAccountId}/recent-transactions-history`);
                    }
                    // Vue.appApi().authorized().bankAccount().getCreditCardOverviews()
                    function getCreditCardOverviews(){
                        return appHttp.get(`/api/v1/account/bank-accounts/credit-card-overviews`);
                    }

                    function scheduleItem(scheduleId){
                        return {
                            calculateMonthlyAmount,
                            destroy,
                            get,
                            store,
                        };

                        // Vue.appApi().authorized().bankAccount().scheduleItem().calculateMonthlyAmount(payload)
                        function calculateMonthlyAmount(payload){
                            const appHttpCancelToken = axios.CancelToken;
                            const sourceAppHttpCancelToken = appHttpCancelToken.source();
                            const promise = appHttp.post(
                                `/api/v1/account/bank-accounts/schedule-item/calculateMonthlyAmount`,
                                payload,
                                {
                                    cancelToken: sourceAppHttpCancelToken.token,
                                }
                            );
                            promise.sourceAppHttpCancelToken = sourceAppHttpCancelToken;
                            return promise;
                        }

                        function get(){
                            if(scheduleId){
                                // Vue.appApi().authorized().bankAccount(bankAccountId).scheduleItem(scheduleId).get()
                                return appHttp.get(`/api/v1/account/bank-accounts/${bankAccountId}/schedule-item/${scheduleId}`);
                            } else {
                                // Vue.appApi().authorized().bankAccount(bankAccountId).scheduleItem().get()
                                return appHttp.get(`/api/v1/account/bank-accounts/${bankAccountId}/schedule-item`);
                            }
                        }

                        // Vue.appApi().authorized().bankAccount().scheduleItem().destroy({id})
                        function destroy(id){
                            return appHttp.delete(`/api/v1/account/bank-accounts/schedule-item/${id}`);
                        }

                        // Vue.appApi().authorized().bankAccount().scheduleItem().store(payload)
                        function store(payload){
                            return appHttp.put(`/api/v1/account/bank-accounts/schedule-item`, payload);
                        }
                    }

                    // Vue.appApi().authorized().bankAccount().assignment()
                    function assignment(assignmentId){
                        return {
                            getUnassignedTransactions,
                            deleteUnassignedTransactions,
                            postAssignTransaction,
                            deleteAssignment,
                            indexAssignmentsByTransactionDate
                        };

                        // Vue.appApi().authorized().bankAccount().assignment().getUnassignedTransactions()
                        function getUnassignedTransactions(){
                            return appHttp.get(`/api/v2/account/bank-accounts/assignments/unassigned-transactions`);
                        }

                        // Vue.appApi().authorized().bankAccount().assignment().deleteUnassignedTransactions()
                        function deleteUnassignedTransactions(){
                            return appHttp.delete(`/api/v1/account/bank-accounts/assignments/unassigned-transactions`);
                        }

                        // Vue.appApi().authorized().bankAccount(bankAccountId).assignment().postAssignTransaction(payload)
                        function postAssignTransaction(payload){
                            return appHttp.post(`/api/v1/account/bank-accounts/assignments/assign-transaction`,
                                {
                                    bank_account_id: bankAccountId,
                                    ...payload
                                }
                            );
                        }

                        // Vue.appApi().authorized().bankAccount().assignment(assignmentId).deleteAssignment()
                        function deleteAssignment(){
                            return appHttp.delete(`/api/v1/account/bank-accounts/assignments/${assignmentId}`);
                        }

                        // Vue.appApi().authorized().bankAccount().assignment().indexAssignmentsByTransactionDate(query)
                        function indexAssignmentsByTransactionDate(query){
                            return appHttp.get(`/api/v1/account/bank-accounts/assignments/by-date`, { params: query });
                        }
                    }

                    function transaction(transactionId){
                        return {
                            storeTransaction: storeTransaction,
                            removeTransaction: removeTransaction,
                            getParentTransaction: getParentTransaction,
                            moveTransaction,
                        };

                        // Vue.appApi().authorized().bankAccount(bankAccountId).transaction(transactionId).storeTransaction()
                        function storeTransaction(transaction){
                            return appHttp.put(`/api/v1/account/bank-accounts/${bankAccountId}/transaction/${transaction.id}`, transaction);
                        }

                        // Vue.appApi().authorized().bankAccount(bankAccountId).transaction(transactionId).removeTransaction()
                        function removeTransaction(){
                            return appHttp.delete(`/api/v1/account/bank-accounts/${bankAccountId}/transaction/${transactionId}`);
                        }

                        // Vue.appApi().authorized().bankAccount(bankAccountId).transaction(transactionId).getParentTransaction()
                        function getParentTransaction(){
                            return appHttp.get(`/api/v1/account/bank-accounts/${bankAccountId}/transaction/${transactionId}`);
                        }

                        // Vue.appApi().authorized().bankAccount(bankAccountId).transaction(transactionId).moveTransaction(payload)
                        function moveTransaction(payload){
                            return appHttp.post(`/api/v1/account/bank-accounts/${bankAccountId}/transaction/${transactionId}/move`, payload);
                        }
                    }
                    // Vue.appApi().authorized().bankAccount().getIncomeAccountOverview()
                    function getIncomeAccountOverview(){
                        return appHttp.get('/api/v1/account/bank-accounts/income-account-overview');
                    }
                    // Vue.appApi().authorized().bankAccount().getCCPayoffAccountOverview()
                    function getCCPayoffAccountOverview(){
                        const cancelToken = axios.CancelToken;
                        const cancelTokenSource = cancelToken.source();
                        var promise = appHttp.get('/api/v1/account/bank-accounts/payoff-account-overview',
                            { cancelToken: cancelTokenSource.token });
                        promise.cancelToken = cancelTokenSource;
                        return promise;
                    }
                    // Vue.appApi().authorized().bankAccount().getAllocationAccounts()
                    function getAllocationAccounts(){
                        return appHttp.get('/api/v1/account/bank-accounts/allocation-accounts');
                    }
                    // Vue.appApi().authorized().bankAccount(bankAccountId).requestTransactionDownload(payload)
                    function requestTransactionDownload(payload){
                        return appHttp.post(`/api/v1/account/bank-accounts/${bankAccountId}/request-transaction-download`, payload);
                    }
                }

                // Vue.appApi().authorized().account()
                function account(accountId = store.getters['user/currentAccountId']){
                    return {
                        startNewTrial,
                        startBasicPlan,
                        cancelSubscription,
                        purchaseSubscription,
                        getSubscriptionTypes,
                        updateSubscriptionInterval,
                        updateSubscriptionPayment,
                        notificationPreferences,
                        getIosSubscriptionProducts,
                        verifyItunesSubscriptionReceipt,
                        deactivateAccount,
                        getFinicityRefreshLogs,
                        patch,
                        coupons,
                        accountUsers,
                        switchAccount,
                    };
                    // Vue.appApi().authorized().account().startNewTrial()
                    function startNewTrial(){
                        return appHttp.post(`/api/v1/account/startNewTrial`);
                    }

                    // Vue.appApi().authorized().account().startBasicPlan()
                    function startBasicPlan(payload){
                        return appHttp.post(`/api/v1/account/start-basic-plan`, payload);
                    }

                    // Vue.appApi().authorized().account().cancelSubscription()
                    function cancelSubscription(){
                        return appHttp.get(`/api/v1/account/cancel-subscription`);
                    }
                    // Vue.appApi().authorized().account().purchaseSubscription(payload)
                    function purchaseSubscription(payload){
                        return appHttp.post(`/api/v1/account/purchase-subscription`, payload);
                    }
                    // Vue.appApi().authorized().account().purchaseSubscription()
                    function getSubscriptionTypes(){
                        return appHttp.get(`/api/v1/account/subscription-types`);
                    }
                    // Vue.appApi().authorized().account().updateSubscriptionInterval(payload)
                    function updateSubscriptionInterval(payload){
                        return appHttp.post(`/api/v1/account/update-billing-interval`, payload);
                    }
                    // Vue.appApi().authorized().account().updateSubscriptionPayment(payload)
                    function updateSubscriptionPayment(payload){
                        return appHttp.post(`/api/v1/account/update-payment-method`, payload);
                    }
                    // Vue.appApi().authorized().account().getIosSubscriptionProducts()
                    function getIosSubscriptionProducts(){
                        return appHttp.get(`/api/v1/account/ios/products`);
                    }
                    // Vue.appApi().authorized().account().verifyItunesSubscriptionReceipt(payload)
                    function verifyItunesSubscriptionReceipt(payload){
                        return appHttp.post(`/api/v1/account/ios/verify-receipt`, payload);
                    }
                    // Vue.appApi().authorized().account().deactivateAccount()
                    function deactivateAccount(){
                        return appHttp.get(`/api/v1/account/deactivate`);
                    }
                    // Vue.appApi().authorized().account().getFinicityRefreshLogs()
                    function getFinicityRefreshLogs(){
                        return appHttp.get(`/api/v1/account/finicity-refresh-logs`);
                    }
                    // Vue.appApi().authorized().account().patch(payload)
                    function patch(payload){
                        return appHttp.patch(`/api/v1/account`, payload);
                    }
                    // Vue.appApi().authorized().account().notificationPreferences()
                    function notificationPreferences(){
                        return {
                            getNotificationPreferences,
                            storeNotificationPreferences
                        };
                        // Vue.appApi().authorized().account().notificationPreferences().getNotificationPreferences()
                        function getNotificationPreferences(){
                            return appHttp.get(`/api/v1/account/notification-preferences`);
                        }
                        // Vue.appApi().authorized().account().notificationPreferences().storeNotificationPreferences(payload)
                        function storeNotificationPreferences(payload){
                            return appHttp.put(`/api/v1/account/notification-preferences`, payload);
                        }
                    }
                    // Vue.appApi().authorized().account().coupons()
                    function coupons(){
                        return {
                            redeemCoupon
                        };
                        // Vue.appApi().authorized().account().coupons().redeemCoupon(payload)
                        function redeemCoupon(payload){
                            return appHttp.post(`/api/v1/account/coupons/redeem`, payload);
                        }
                    }
                    // Vue.appApi().authorized().account(accountId).accountUsers(accountUserId)
                    function accountUsers(accountUserId){
                        return {
                            getAccountUsers,
                            deleteAccountUser,
                            createInvite,
                            listInvites,
                            deleteAccountInvite,
                            resendAccountInvite,
                        };
                        // Vue.appApi().authorized().account().accountUsers().getAccountUsers()
                        function getAccountUsers(){
                            return appHttp.get(`/api/v1/account/${accountId}/account-users`);
                        }
                        // Vue.appApi().authorized().account().accountUsers(accountUserId).deleteAccountUser()
                        function deleteAccountUser(){
                            return appHttp.delete(`/api/v1/account/${accountId}/account-users/${accountUserId}`);
                        }
                        // Vue.appApi().authorized().account().accountUsers().createInvite(payload)
                        function createInvite(payload){
                            return appHttp.post(`/api/v1/account/${accountId}/account-users/invite`, payload);
                        }
                        // Vue.appApi().authorized().account().accountUsers().listInvites()
                        function listInvites(){
                            return appHttp.get(`/api/v1/account/${accountId}/account-users/invite`);
                        }
                        // Vue.appApi().authorized().account().accountUsers().deleteAccountInvite(accountInviteId)
                        function deleteAccountInvite(accountInviteId){
                            return appHttp.delete(`/api/v1/account/${accountId}/account-users/invite/${accountInviteId}`);
                        }
                        // Vue.appApi().authorized().account().accountUsers().resendAccountInvite(accountInviteId)
                        function resendAccountInvite(accountInviteId){
                            return appHttp.post(`/api/v1/account/${accountId}/account-users/invite/${accountInviteId}/resend`);
                        }
                    }
                    // Vue.appApi().authorized().account(accountId).switchAccount(accountId)
                    function switchAccount(accountId){
                        return appHttp.post(`/api/v1/account/switch/${accountId}`);
                    }
                }

                // Vue.appApi().authorized().admin()
                function admin(){
                    return {
                        getAllUsers,
                        deleteUser,
                        lockUser,
                        grantUserAccess,
                        getEmailNotificationViews,
                        getRenderedEmailNotification,
                        coupons,
                        finicity,
                        reactivateAccount,
                        resetAccountToDemoMode,
                        subscriptions,
                        commands,
                        makeDeposit,
                    };

                    // Vue.appApi().authorized().admin().getAllUsers()
                    function getAllUsers(payload){
                        const queryParams = `pageNum=${payload.pageNum || 1}&perPage=${payload.perPage || 50}&searchString=${payload.searchString || ''}&sortBy=${payload.sortBy}&sortOrder=${payload.sortOrder}`;
                        return appHttp.get(`/api/v2/admin/users?${queryParams}`);
                    }
                    // Vue.appApi().authorized().admin().deleteUser(userId)
                    function deleteUser(userId){
                        return appHttp.delete(`/api/v2/admin/users/${userId}`);
                    }
                    // Vue.appApi().authorized().admin().lockUser(userId, flag)
                    function lockUser(userId, flag){
                        return appHttp.put(`/api/v2/admin/users/${userId}/lock`, {
                            flag: flag ? 1 : 0
                        });
                    }
                    // Vue.appApi().authorized().admin().grantUserAccess(userId, flag)
                    function grantUserAccess(userId, flag){
                        return appHttp.put(`/api/v2/admin/users/${userId}/grant-access`, {
                            flag: flag ? 1 : 0
                        });
                    }
                    // Vue.appApi().authorized().admin().getEmailNotificationViews()
                    function getEmailNotificationViews(){
                        return appHttp.get(`/api/v1/admin/list-notifications`);
                    }
                    // Vue.appApi().authorized().admin().getRenderedEmailNotification(payload)
                    function getRenderedEmailNotification(payload){
                        return appHttp.post(`/api/v1/admin/render-notification`, payload);
                    }
                    // Vue.appApi().authorized().admin().reactivateAccount(accountId)
                    function reactivateAccount(accountId){
                        return appHttp.post(`/api/v1/admin/accounts/${accountId}/reactivate`);
                    }
                    // Vue.appApi().authorized().admin().resetAccountToDemoMode(accountId)
                    function resetAccountToDemoMode(accountId){
                        return appHttp.post(`/api/v1/admin/accounts/${accountId}/demo-reset`);
                    }
                    // Vue.appApi().authorized().admin().makeDeposit(payload)
                    function makeDeposit(payload){
                        return appHttp.post(`/api/v1/admin/make-deposit`, payload);
                    }
                    // Vue.appApi().authorized().admin().coupons()
                    function coupons(){
                        return {
                            listCoupons,
                            createCoupon,
                            getSelectOptions
                        };
                        // Vue.appApi().authorized().admin().coupons().listCoupons()
                        function listCoupons(){
                            return appHttp.get(`/api/v1/admin/coupons`);
                        }
                        // Vue.appApi().authorized().admin().coupons().createCoupon(payload)
                        function createCoupon(payload){
                            return appHttp.post(`/api/v1/admin/coupons/create`, payload);
                        }
                        // Vue.appApi().authorized().admin().coupons().getSelectOptions()
                        function getSelectOptions(){
                            return appHttp.get(`/api/v1/admin/coupons/select-options`);
                        }
                    }

                    // Vue.appApi().authorized().admin().finicity()
                    function finicity(){
                        return {
                            createOauthInstitution,
                            getOauthInstitutions,
                            getOauthInstitution,
                            migrateOauthInstitution,
                        };
                        // Vue.appApi().authorized().admin().finicity().createOauthInstitution(payload)
                        function createOauthInstitution(payload){
                            return appHttp.post(`/api/v1/admin/finicity/oauth-institutions/create`, payload);
                        }
                        // Vue.appApi().authorized().admin().finicity().getOauthInstitutions()
                        function getOauthInstitutions(){
                            return appHttp.get(`/api/v1/admin/finicity/oauth-institutions`);
                        }
                        // Vue.appApi().authorized().admin().finicity().getOauthInstitution(institutionId)
                        function getOauthInstitution(oauth_institution_id){
                            return appHttp.get(`/api/v1/admin/finicity/oauth-institutions/${oauth_institution_id}`);
                        }
                        // Vue.appApi().authorized().admin().finicity().migrateOauthInstitution(oauth_institution_id)
                        function migrateOauthInstitution(oauth_institution_id){
                            return appHttp.get(`/api/v1/admin/finicity/oauth-institutions/${oauth_institution_id}/migrate`);
                        }
                    }

                    // Vue.appApi().authorized().admin().subscriptions()
                    function subscriptions(){
                        return {
                            getFinicitySubscriptions,
                            getSystemSubscriptions,
                        };

                        // Vue.appApi().authorized().admin().subscriptions().getFinicitySubscriptions()
                        function getFinicitySubscriptions(){
                            return appHttp.get(`/api/v1/admin/subscriptions/finicity`);
                        }
                        // Vue.appApi().authorized().admin().subscriptions().getSystemSubscriptions()
                        function getSystemSubscriptions(){
                            return appHttp.get(`/api/v1/admin/subscriptions/system`);
                        }
                    }

                    // Vue.appApi().authorized().admin().commands()
                    function commands(){
                        return {
                            invoke,
                            getOutput,
                        };

                        // Vue.appApi().authorized().admin().commands().invoke(command)
                        function invoke(command){
                            return appHttp.post(`/api/v1/admin/commands/invoke`, {
                                command
                            });
                        }
                        // Vue.appApi().authorized().admin().commands().getOutput(code)
                        function getOutput(code){
                            return appHttp.get(`/api/v1/admin/commands/output/${code}`);
                        }
                    }
                }
            }
        }
        function modifyAllRequestConfigs(config){
            return new Promise((resolve) => {
                config.headers['Authorization'] = window.axios.defaults.headers.common['Authorization'];

                if(store.state.guest.user.user && store.state.guest.user.user.current_account && store.state.guest.user.user.current_account.id){
                    config.headers['current-account-id'] = store.state.guest.user.user.current_account.id;
                    resolve(config);
                } else {
                    Vue.clientStorage.getItem('current_account_id').then(updateConfigWithSavedAccountId);
                }

                function updateConfigWithSavedAccountId(savedAccountId){
                    if(savedAccountId){
                        config.headers['current-account-id'] = savedAccountId;
                    }

                    resolve(config);
                }
            });
        }
        function catchAllResponseFailures(error){
            if(error instanceof axios.Cancel){
                Promise.reject(error.message);
            }
            var originalRequest = error.config;

            var endpointNotSupported = error.response && error.response.status === 410 && error.response.data && error.response.data.slug === 'endpoint_obsolete';
            if(endpointNotSupported){
                return router.push({ name: 'upgrade-required' });
            }

            var errorStatusIsUnauthorized = error.response && error.response.status === 401;
            var requestHasNotBeenTriedAgain = !originalRequest._triedAgain;

            if(errorStatusIsUnauthorized && requestHasNotBeenTriedAgain){
                originalRequest._triedAgain = true;
                if(!refreshAuthenticationPromise){
                    refreshAuthenticationPromise = Vue.clientStorage.getItem('refresh_token').then(refreshAuthToken);
                }
                return refreshAuthenticationPromise.then(getTokenSuccess).catch(getTokenError);
            }

            if(error.response && error.response.statusText){
                error.response.appMessage = error.response.statusText;
            }

            var errorHasMessageProperty = error.response && error.response.data && error.response.data.message;
            if(errorHasMessageProperty && error.response.data.message !== ''){
                error.response.appMessage += ': ' + error.response.data.message;
            }

            var errorIsValidationError = error.response && error.response.status === 422 && error.response.data.errors;
            if(errorIsValidationError){
                if(error.response){
                    error.response.appMessage = 'Validation Error: Check above and try again.';
                    error.response.validation_errors = error.response.data.errors;
                }
            }
            return Promise.reject(error.response || error);

            function getTokenSuccess(response){
                window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + response.data.access_token;
                originalRequest.headers['Authorization'] = 'Bearer ' + response.data.access_token;
                return window.axios(originalRequest);
            }
        }
        function refreshAuthToken(refreshToken){
            return appHttp.post('/v1/user/login/refresh', { refreshToken }).then(storeTokens);

            function storeTokens(response){
                refreshAuthenticationPromise = null;
                return store.dispatch('user/SET_AUTH_TOKENS', response.data).then(returnResponse);
                function returnResponse(){
                    return response;
                }
            }
        }
        function getTokenError(){
            store.dispatch('user/LOGOUT_FRONTEND').then(logoutSuccess);

            function logoutSuccess(){
                router.go();
            }
        }
    }
}
