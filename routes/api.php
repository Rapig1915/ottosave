<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
# v1.0 API
Route::group(['prefix' => 'v1'], function(){
    // Unauthenticated API Routes:
    Route::group([], function(){
        // POST: /api/v1/ios/status-notifications
        Route::post('ios/status-notifications', 'Api\V1\External\IosController@handleSubscriptionStatusNotification');

        // GET: /api/v1/credentials
        Route::get('credentials', 'Api\VersionController@endpointObsolete');

        // POST: /api/v1/user/verify-email
        Route::post('user/verify-email', 'Api\V1\Guest\User\RegisterController@verifyEmailAddress');

        // GET: /api/v1/finicity/redirect-handler
        Route::get('finicity/redirect-handler', 'Api\V1\External\FinicityController@connectRedirectHandler');
    });

    // Authenticated routes
    Route::group(['middleware' => 'auth:api'], function(){
        // GET:/api/v1/braintree/client-token
        Route::get('/braintree/client-token', 'Api\V1\Authorized\BraintreeController@getClientAuthToken');

        // POST: /api/v1/plaid/link-error
        Route::post('plaid/link-error', 'Api\VersionController@endpointObsolete');

        Route::group(['prefix' => 'user'], function () {
            // GET: /api/v1/user
            Route::get('', 'Api\V1\Authorized\UserController@getUser');
            // PUT: /api/v1/user
            Route::put('', 'Api\V1\Authorized\UserController@store');
            // PUT: /api/v1/user/change-password
            Route::put('change-password', 'Api\V1\Authorized\UserController@changePassword');
            // PUT: /api/v1/user/change-email
            Route::put('change-email', 'Api\V1\Authorized\UserController@changeEmail');
            // GET: /api/v1/user/verify-email
            Route::get('verify-email', 'Api\V1\Authorized\UserController@resendVerificationEmail');
            // POST: /api/v1/user/accept-invite
            Route::post('accept-invite', 'Api\V1\Authorized\AccountUserController@acceptAccountInvite');
        });

        // /api/v1/admin
        Route::group(['prefix' => 'admin', 'middleware' => ['require-super-user-access','verified']], function() {
            // GET: /api/v1/admin/users
            Route::get('users', 'Api\VersionController@endpointObsolete');
            // GET: /api/v1/admin/list-notifications
            Route::get('list-notifications', 'Api\V1\Authorized\AdminController@getEmailNotificationViews');
            // POST: /api/v1/admin/render-notification
            Route::post('render-notification', 'Api\V1\Authorized\AdminController@getRenderedEmailNotification');
            // POST: /api/v1/admin/accounts/{account_id}/reactivate
            Route::post('accounts/{account}/reactivate', 'Api\V1\Authorized\AdminController@reactivateAccount');
            // POST: /api/v1/admin/accounts/{account_id}/demo-reset
            Route::post('accounts/{account}/demo-reset', 'Api\V1\Authorized\AdminController@resetAccountToDemoMode');
            // POST: /api/v1/admin/make-deposit
            Route::post('make-deposit', 'Api\V1\Authorized\AdminController@makeDeposit');
            // /api/v1/admin/coupons
            Route::group(['prefix' => 'coupons'], function() {
                // GET: /api/v1/admin/coupons
                Route::get('/', 'Api\V1\Authorized\CouponController@listCoupons');
                // POST: /api/v1/admin/coupons/create
                Route::post('/create', 'Api\V1\Authorized\CouponController@createCoupon');
                // GET: /api/v1/admin/coupons/select-options
                Route::get('/select-options', 'Api\V1\Authorized\CouponController@getSelectOptions');
            });
            // /api/v1/admin/finicity
            Route::group(['prefix' => 'finicity/oauth-institutions'], function() {
                // GET: /api/v1/admin/finicity/oauth-institutions
                Route::get('/', 'Api\V1\External\FinicityController@getOauthInstitutions');
                // POST: /api/v1/admin/finicity/oauth-institutions/create
                Route::post('/create', 'Api\V1\External\FinicityController@createOauthInstitution');
                // GET: /api/v1/admin/finicity/oauth-institutions/{oauth_institution_id}
                Route::get('/{oauth_institution_id}', 'Api\V1\External\FinicityController@getOauthInstitution');
                // GET: /api/v1/admin/finicity/oauth-institutions/{oauth_institution_id}/migrate
                Route::get('/{oauth_institution_id}/migrate', 'Api\V1\External\FinicityController@migrateOauthInstitution');
            });
            // /api/v1/admin/subscriptions
            Route::group(['prefix' => 'subscriptions'], function() {
                // GET: /api/v1/admin/subscriptions/finicity
                Route::get('finicity', 'Api\V1\Authorized\AdminController@getFinicitySubscriptions');
                // GET: /api/v1/admin/subscriptions/system
                Route::get('system', 'Api\V1\Authorized\AdminController@getSystemSubscriptions');
            });
            // /api/v1/admin/commands
            Route::group(['prefix' => 'commands'], function() {
                // POST: /api/v1/admin/commands/invoke
                Route::post('invoke', 'Api\V1\Authorized\AdminController@invokeCommand');
                // GET: /api/v1/admin/commands/output
                Route::get('output/{code}', 'Api\V1\Authorized\AdminController@getCommandOutput');
            });
        });

        // /api/v1/logout
        Route::post('/logout', 'Api\V1\Guest\User\LoginController@logout');

        // /api/v1/account
        Route::group(['prefix' => 'account', 'middleware' => ['check-account','verified']], function() {
            // PATCH: /api/v1/account/
            Route::patch('/', 'Api\V1\Authorized\AccountController@patch');
            // POST: /api/v1/account/startNewTrial
            Route::post('/startNewTrial', 'Api\V1\Authorized\AccountController@startNewTrial');
            // POST: /api/v1/account/start-basic-plan
            Route::post('/start-basic-plan', 'Api\V1\Authorized\AccountController@startBasicPlan');
            // POST: /api/v1/account/purchase-subscription
            Route::post('/purchase-subscription', 'Api\V1\Authorized\AccountController@purchaseSubscription');
            // GET: /api/v1/account/subscription-types
            Route::get('/subscription-types', 'Api\V1\Authorized\AccountController@getSubscriptionTypes');
            // POST: /api/v1/account/update-billing-interval
            Route::post('/update-billing-interval', 'Api\V1\Authorized\AccountController@updateSubscriptionInterval');
            // POST: /api/v1/account/update-payment-method
            Route::post('/update-payment-method', 'Api\V1\Authorized\AccountController@updateSubscriptionPayment');
            // GET: /api/v1/account/cancel-subscription
            Route::get('/cancel-subscription', 'Api\V1\Authorized\AccountController@cancelSubscription');
            // GET: /api/v1/account/deactivate
            Route::get('/deactivate', 'Api\V1\Authorized\AccountController@deactivateAccount');
            // POST: /api/v1/account/coupons/redeem
            Route::post('/coupons/redeem', 'Api\V1\Authorized\CouponController@redeemCoupon');
            // POST: /api/v1/account/switch
            Route::post('/switch/{account_id}', 'Api\V1\Authorized\AccountController@switchAccount')->middleware('require-account-access');
            // /api/v1/account/ios
            Route::group(['prefix' => 'ios'], function(){
                // GET: /api/v1/account/ios/products
                Route::get('/products', 'Api\V1\External\IosController@getIosSubscriptionProducts');
                // POST: /api/v1/account/ios/verify-receipt
                Route::post('/verify-receipt', 'Api\V1\External\IosController@verifyItunesSubscriptionReceipt');
            });

            // /api/v1/account/bank-accounts
            Route::group(['prefix' => 'bank-accounts'], function(){
                // GET: /api/v1/account/bank-accounts/assignable-accounts
                Route::get('/assignable-accounts', 'Api\VersionController@endpointObsolete');
                // GET: /api/v1/account/bank-accounts/allocation-accounts
                Route::get('/allocation-accounts', 'Api\V1\Authorized\BankAccountsController@getAllocationAccounts');

                // GET: /api/v1/account/bank-accounts/loadWithLinkedInstitutionAccounts
                Route::get('/loadWithLinkedInstitutionAccounts', 'Api\VersionController@endpointObsolete');

                // POST: /api/v1/account/bank-accounts/createOrUpdate
                Route::post('/createOrUpdate', 'Api\VersionController@endpointObsolete');

                // GET: /api/v1/account/bank-accounts/savingsAccessCC
                Route::get('/savingsAccessCC', 'Api\VersionController@endpointObsolete');

                // GET: /api/v1/account/bank-accounts/everyday-checking-account-overview
                Route::get('/everyday-checking-account-overview', 'Api\V1\Authorized\BankAccountsController@getSpendingAccountOverview');

                // GET: /api/v1/account/bank-accounts/savings-access-credit-card-overview
                Route::get('/savings-access-credit-card-overview', 'Api\VersionController@endpointObsolete');
                // GET: /api/v1/account/bank-accounts/credit-card-overviews
                Route::get('/credit-card-overviews', 'Api\V1\Authorized\BankAccountsController@getCreditCardOverviews');
                // GET: /api/v1/account/bank-accounts/income-account-overview
                Route::get('/income-account-overview', 'Api\V1\Authorized\BankAccountsController@getIncomeAccountOverview');
                // GET: /api/v1/account/bank-accounts/payoff-account-overview
                Route::get('/payoff-account-overview', 'Api\V1\Authorized\BankAccountsController@getCCPayoffAccountOverview');

                // /api/v1/account/bank-accounts/assignments
                Route::group(['prefix' => 'assignments'], function(){
                    // GET: /api/v1/account/bank-accounts/assignments/unassigned-transactions
                    Route::get('/unassigned-transactions', 'Api\VersionController@endpointObsolete');
                    // DELETE: /api/v1/account/bank-accounts/assignments/unassigned-transactions
                    Route::delete('/unassigned-transactions', 'Api\V1\Authorized\AssignmentController@deleteUnassignedTransactions');
                    // GET: /api/v1/account/bank-accounts/assignments/by-date
                    Route::get('/by-date', 'Api\V1\Authorized\AssignmentController@indexAssignmentsByTransactionDate');

                    // POST: /api/v1/account/bank-accounts/assignments/assign-transaction
                    Route::post('/assign-transaction', 'Api\V1\Authorized\AssignmentController@setAssignTransaction');

                    // DELETE: /api/v1/account/bank-accounts/assignments/unassign-transaction
                    Route::delete('{assignmentId}', 'Api\V1\Authorized\AssignmentController@deleteAssignment');
                });

                // /api/v1/account/bank-accounts/schedule-item
                Route::group(['prefix' => 'schedule-item'], function(){
                    // PUT: /api/v1/account/bank-accounts/schedule-item
                    Route::put('/', 'Api\V1\Authorized\BankAccount\ScheduleItemController@storeScheduleItem');
                    // POST: /api/v1/account/bank-accounts/schedule-item/calculateMonthlyAmount
                    Route::post('/calculateMonthlyAmount', 'Api\V1\Authorized\BankAccount\ScheduleItemController@calculateMonthlyAmount');
                    // DELET: /api/v1/account/bank-accounts/schedule-item/{id}
                    Route::delete('/{id}', 'Api\V1\Authorized\BankAccount\ScheduleItemController@destroy');
                });

                // /api/v1/account/bank-accounts/{bankAccountId}
                Route::group(['prefix' => '/{bankAccountId}'], function(){
                    // GET: /api/v1/account/bank-accounts/{id}
                    Route::get('/', 'Api\VersionController@endpointObsolete');
                    // DELETE: /api/v1/account/bank-accounts/{id}
                    Route::delete('/', 'Api\V1\Authorized\BankAccountsController@destroy');
                    // GET: /api/v1/account/bank-accounts/{bankAccountId}/clear-transfers
                    Route::get('/clear-transfers', 'Api\VersionController@endpointObsolete');
                    // GET: /api/v1/account/bank-accounts/{bankAccountId}/recent-transactions-history
                    Route::get('/recent-transactions-history', 'Api\V1\Authorized\BankAccountsController@loadRecentBankTransactionsHistory');


                    Route::group(['prefix' => '/schedule-item'], function(){
                        // GET: /api/v1/account/bank-accounts/{bankAccountId}/schedule-item
                        Route::get('/', 'Api\V1\Authorized\BankAccountsController@getScheduleItems');
                        // GET: /api/v1/account/bank-accounts/{bankAccountId}/schedule-item/{scheduleItemId}
                        Route::get('{scheduleItemId}', 'Api\V1\Authorized\BankAccountsController@getScheduleItems');
                    });

                    Route::group(['prefix' => '/transaction/{transactionId}'], function () {
                        // GET: /api/v1/account/bank-accounts/{bankAccountId}/transaction/{transactionId}
                        Route::get('', 'Api\V1\Authorized\BankAccountsController@getParentTransaction');
                        // PUT: /api/v1/account/bank-accounts/{bankAccountId}/transaction/{transactionId}
                        Route::put('', 'Api\V1\Authorized\BankAccountsController@storeTransaction');
                        // DELETE: /api/v1/account/bank-accounts/{bankAccountId}/transaction/{transactionId}
                        Route::delete('', 'Api\V1\Authorized\BankAccountsController@removeTransaction');
                        // POST: /api/v1/account/bank-accounts/{bankAccountId}/transaction/{transactionId}
                        Route::post('move', 'Api\V1\Authorized\BankAccountsController@moveTransaction');
                    });

                    // POST: /api/v1/account/bank-accounts/{bankAccountId}/request-transaction-download
                    Route::post('/request-transaction-download', 'Api\V1\Authorized\BankAccountsController@requestTransactionDownload');
                });

            });

            // /api/v1/account/defense
            Route::group(['prefix' => 'defense'], function(){
                // GET: /api/v1/account/defense
                Route::get('', 'Api\V1\Authorized\DefendController@indexDefenses');
                // POST: /api/v1/account/defense/create
                Route::post('/create', 'Api\V1\Authorized\DefendController@createDefense');
                // POST: /api/v1/account/defense/{defense_id}/transfer-funds
                Route::post('/{defense_id}/transfer-funds', 'Api\VersionController@endpointObsolete');
            });

            // /api/v1/account/notification-preferences
            Route::group(['prefix' => 'notification-preferences'], function(){
                // GET: /api/v1/account/notification-preferences
                Route::get('/', 'Api\V1\Authorized\UserController@getNotificationPreferences');
                // PUT: /api/v1/account/notification-preferences
                Route::put('/', 'Api\V1\Authorized\UserController@storeNotificationPreferences');
            });

            // /api/v1/account Group for 'plus' subscribers only
            Route::group(['middleware' => 'require-plus-subscription-plan'], function(){
                // GET: /api/v1/account/finicity-refresh-logs
                Route::get('/finicity-refresh-logs', 'Api\V1\External\FinicityController@getFinicityRefreshLogs');

                // /api/v1/account/institution/
                Route::group(['prefix' => 'institution'], function(){
                    // POST: /api/v1/account/institution/create
                    Route::post('/create', 'Api\VersionController@endpointObsolete');
                    // POST: /api/v1/account/institution/update
                    Route::post('/update', 'Api\VersionController@endpointObsolete');
                    // GET: /api/v1/account/institution/pending-oauth-migration
                    Route::get('/pending-oauth-migration', 'Api\V1\Authorized\InstitutionController@getInstitutionsToMigrate');

                    Route::group(['prefix' => '/{institution_id}'], function(){
                        // DELETE: /api/v1/account/institution/{institution_id}/
                        Route::delete('', 'Api\V1\Authorized\InstitutionController@deleteInstitution');
                        // GET: /api/v1/account/institution/{institution_id}/public-token
                        Route::get('/public-token', 'Api\VersionController@endpointObsolete');
                        // GET: /api/v1/account/institution/{institution_id}/finicity-link
                        Route::get('/finicity-link', 'Api\V1\External\FinicityController@getFinicityConnectLink');
                        // GET: /api/v1/account/institution/{institution_id}/update-balanaces
                        Route::get('/update-balanaces', 'Api\VersionController@endpointObsolete');
                        // GET: /api/v1/account/institution/{institution_id}/refresh-finicity
                        Route::get('/refresh-finicity', 'Api\V1\External\FinicityController@refreshFinicityInstitution');
                        // GET: /api/v1/account/institution/{institution_id}/refresh-finicity-async
                        Route::get('/refresh-finicity-async', 'Api\V1\External\FinicityController@refreshFinicityInstitutionAsync');
                        // GET: /api/v1/account/institution/{institution_id}/migrate-to-oauth
                        Route::get('/migrate-to-oauth', 'Api\V1\Authorized\InstitutionController@migrateInstitutionToOauth');
                    });
                });

                // /api/v1/account/institution-accounts
                Route::group(['prefix' => 'institution-accounts'], function(){
                    // GET: /api/v1/account/institution-accounts/loadUnlinked
                    Route::get('/loadUnlinked', 'Api\VersionController@endpointObsolete');
                    // POST: /api/v1/account/institution-accounts/finicity-create
                    Route::post('/finicity-create', 'Api\VersionController@endpointObsolete');
                    // GET: /api/v1/account/institution-accounts/finicity-refresh
                    Route::get('/finicity-refresh', 'Api\V1\External\FinicityController@refreshFinicityAccounts');

                    Route::group(['prefix' => '{institution_account_id}'], function () {
                        // POST: /api/v1/account/institution-accounts/{institution_account_id}
                        Route::post('', 'Api\V1\Authorized\InstitutionAccountsController@updateInstitutionAccount');
                        // DELETE: /api/v1/account/institution-accounts/{institution_account_id}
                        Route::delete('', 'Api\V1\Authorized\InstitutionAccountsController@destroy');
                        // POST: /api/v1/account/institution-accounts/{institution_account_id}/fetch-past-transactions
                        Route::post('/fetch-past-transactions', 'Api\V1\Authorized\InstitutionAccountsController@downloadPastTransactions');
                    });
                });

                Route::group(['prefix' => 'bank-accounts'], function(){
                    // POST: /api/v1/account/bank-accounts/linkInstitutionAccount
                    Route::post('/linkInstitutionAccount', 'Api\VersionController@endpointObsolete');
                    // POST: /api/v1/account/bank-accounts/unlinkInstitutionAccount
                    Route::post('/unlinkInstitutionAccount', 'Api\VersionController@endpointObsolete');
                });
            });

            // /api/v1/account/{account_id}  //all routes that need an account
            Route::group(['prefix' => '{account_id}', 'middleware' => 'require-account-access'], function() {
                // /api/v1/account/{account_id}/account-users
                Route::group(['prefix' => 'account-users', 'middleware' => 'permission:edit account-users'], function() {
                    // GET: /api/v1/account/{account_id}/account-users
                    Route::get('/', 'Api\V1\Authorized\AccountUserController@getAccountUsers');
    
                    // /api/v1/account/{account_id}/account-users/{account_user_id}
                    Route::group(['prefix' => '{account_user_id}'], function () {
                        // DELETE: /api/v1/account/{account_id}/account-users/{account_user_id}
                        Route::delete('/', 'Api\V1\Authorized\AccountUserController@deleteAccountUser');
                    });
    
                    // /api/v1/account/{account_id}/account-users/invite
                    Route::group(['prefix' => 'invite', 'middleware' => 'permission:invite account-users'], function () {
                        // POST: /api/v1/account/{account_id}/account-users/invite
                        Route::post('/', 'Api\V1\Authorized\AccountUserController@createInvite');
                        // GET: /api/v1/account/{account_id}/account-users/invite
                        Route::get('/', 'Api\V1\Authorized\AccountUserController@listInvites');
                        // DELETE: /api/v1/account/{account_id}/account-users/invite/{account_invite_id}
                        Route::delete('/{account_invite_id}', 'Api\V1\Authorized\AccountUserController@deleteAccountInvite');
                        // POST: /api/v1/account/{account_id}/account-users/invite/{account_invite_id}/resend
                        Route::post('/{account_invite_id}/resend', 'Api\V1\Authorized\AccountUserController@resendAccountInvite');
                    });
                });
            });
        });
    });
});
# v2.0 API
Route::group(['prefix' => 'v2'], function(){
    // Authenticated routes
    Route::group(['middleware' => ['auth:api','verified']], function(){
        // /api/v2/admin
        Route::group(['prefix' => 'admin', 'middleware' => 'require-super-user-access'], function() {
            // GET: /api/v2/admin/users
            Route::get('users', 'Api\V2\Authorized\AdminController@getAllUsers');
            Route::delete('users/{user}', 'Api\V2\Authorized\AdminController@deleteUser');
            Route::put('users/{user}/lock', 'Api\V2\Authorized\AdminController@lockUser');
            Route::put('users/{user}/grant-access', 'Api\V2\Authorized\AdminController@grantUserAccess');
        });

        // /api/v2/account
        Route::group(['prefix' => 'account', 'middleware' => 'check-account'], function() {
            // /api/v2/account/bank-accounts
            Route::group(['prefix' => 'bank-accounts'], function() {
                // GET: /api/v2/account/bank-accounts/assignable-accounts
                Route::get('/assignable-accounts', 'Api\V2\Authorized\BankAccountsController@getAssignableAccounts');
                // GET: /api/v2/account/bank-accounts/with-linked
                Route::get('/with-linked', 'Api\VersionController@endpointObsolete');
                // POST: /api/v2/account/bank-accounts/create-update
                Route::post('/create-update', 'Api\VersionController@endpointObsolete');
                // GET: /api/v2/account/bank-accounts/savings-cc
                Route::get('/savings-cc', 'Api\V2\Authorized\BankAccountsController@getSavingsAccessCC');

                // /api/v2/account/bank-accounts/{bankAccountId}
                Route::group(['prefix' => '/{bankAccountId}'], function() {
                    // GET: /api/v2/account/bank-accounts/{bankAccountId}
                    Route::get('/', 'Api\VersionController@endpointObsolete');
                    // GET: /api/v2/account/bank-accounts/{bankAccountId}/clear-transfers
                    Route::get('/clear-transfers', 'Api\VersionController@endpointObsolete');
                });
            });

            // /api/v2/account Group for 'plus' subscribers only
            Route::group(['middleware' => 'require-plus-subscription-plan'], function() {
                // /api/v2/account/institution/
                Route::group(['prefix' => 'institution'], function() {
                    Route::group(['prefix' => '/{institution_id}'], function() {
                        // GET: /api/v2/account/institution/{institution_id}/finicity-link
                        Route::get('/finicity-link', 'Api\V2\External\FinicityController@getFinicityConnectLink')->middleware('permission:manage finicity-accounts');
                    });
                });
                // /api/v2/account/institution-accounts
                Route::group(['prefix' => 'institution-accounts'], function() {
                    // GET: /api/v2/account/institution-accounts/load-unlinked
                    Route::get('/load-unlinked', 'Api\VersionController@endpointObsolete');
                    // POST: /api/v2/account/institution-accounts/finicity-create
                    Route::post('/finicity-create', 'Api\V2\External\FinicityController@createFinicityInstitutionAccounts');
                });

                // /api/v2/account/bank-accounts
                Route::group(['prefix' => 'bank-accounts'], function() {
                    // POST: /api/v2/account/bank-accounts/link
                    Route::post('/link', 'Api\VersionController@endpointObsolete');
                });
            });

            // /api/v2/account/defense
            Route::group(['prefix' => 'defense'], function(){
                // POST: /api/v2/account/defense/{defense_id}/transfer-funds
                Route::post('/{defense_id}/transfer-funds', 'Api\V2\Authorized\DefendController@transferFunds');
            });

            // /api/v2/account/bank-accounts
            Route::group(['prefix' => 'bank-accounts'], function(){
                // /api/v2/account/bank-accounts/assignments
                Route::group(['prefix' => 'assignments'], function(){
                    // GET: /api/v2/account/bank-accounts/assignments/unassigned-transactions
                    Route::get('/unassigned-transactions', 'Api\V2\Authorized\AssignmentController@getUnassignedTransactions');
                });
            });
        });
    });
});
Route::group(['prefix' => 'v3'], function(){
    // Authenticated routes
    Route::group(['middleware' => ['auth:api','verified']], function(){
        // /api/v3/account
        Route::group(['prefix' => 'account', 'middleware' => 'check-account'], function() {
            // /api/v3/account/bank-accounts
            Route::group(['prefix' => 'bank-accounts'], function() {
                // GET: /api/v3/account/bank-accounts/with-linked
                Route::get('/with-linked', 'Api\V3\Authorized\BankAccountsController@loadWithLinkedInstitutionAccounts');
                // GET: /api/v3/account/bank-accounts/with-linked
                Route::get('/with-linked-of/{account_id}', 'Api\V3\Authorized\BankAccountsController@loadWithLinkedInstitutionAccountsOf')->middleware('require-super-user-access');
                // POST: /api/v3/account/bank-accounts/create-update
                Route::post('/create-update', 'Api\V3\Authorized\BankAccountsController@createOrUpdate');

                // /api/v3/account/bank-accounts/{bankAccountId}
                Route::group(['prefix' => '/{bankAccountId}'], function() {
                    // GET: /api/v3/account/bank-accounts/{bankAccountId}
                    Route::get('/', 'Api\V3\Authorized\BankAccountsController@retrieve');
                    // GET: /api/v3/account/bank-accounts/{bankAccountId}/clear-transfers
                    Route::get('/clear-transfers', 'Api\V3\Authorized\BankAccountsController@clearPendingTransfers');
                });
            });
        });
    });
});