import { Plugins } from '@capacitor/core';
import router from 'vue_root/router';
import store from 'vue_root/app.store';

export default iosKeychainPlugin();

function iosKeychainPlugin(){
    return {
        install
    };

    function install(Vue, options){
        const { Keychain, Fingerprint } = window;
        const { SplashScreen } = Plugins;

        const platform = window.appEnv.clientPlatform;

        Vue.iosKeychainPlugin = {
            loginViaStoredCredentials,
            retrieveStoredCredentials,
            storeCredentials,
            removeCredentials,
            getBiometricAvailability
        };
        loginViaStoredCredentials();

        function loginViaStoredCredentials(){
            return displayIosLoginOverlay().then(checkBiometricStatus).then(retrieveStoredCredentials).then(login).then(continueToApp).catch(logoutIos).finally(hideOverlayAndSplashscreen);

            function displayIosLoginOverlay(){
                if(platform === 'ios'){
                    store.commit('SET_SHOW_IOS_OVERLAY', true);
                }
                return Promise.resolve();
            }
            function checkBiometricStatus(){
                return Promise.all([
                    Vue.clientStorage.getItem('biometric_login_enabled').then(skipBiometricsIfDisabled),
                    getBiometricAvailability()
                ]);

                function skipBiometricsIfDisabled(biometricsEnabled){
                    if(biometricsEnabled === 'true'){
                        return Promise.resolve();
                    } else {
                        return Promise.reject('biometric login not enabled');
                    }
                }
            }
            function login(credentials){
                if(credentials){
                    return store.dispatch('user/login/LOGIN', credentials);
                } else {
                    return Promise.reject();
                }
            }
            function continueToApp(){
                const currentRouteRequiresAuth = router.history.current.matched.some(({ meta }) => meta.requiresAuth);
                if(!currentRouteRequiresAuth){
                    return router.push({ name: 'dashboard' });
                }
            }
            function logoutIos(){
                if(platform === 'ios'){
                    return store.dispatch('user/LOGOUT_FRONTEND').then(logoutSuccess);
                }
                function logoutSuccess(){
                    return router.push({ name: 'login' });
                }
            }
            function hideOverlayAndSplashscreen(){
                return new Promise(function(resolve, reject){
                    if(platform === 'ios'){
                        store.commit('SET_SHOW_IOS_OVERLAY', false);
                        SplashScreen.hide();
                    }
                    Vue.nextTick(resolve);
                });
            }
        }
        function retrieveStoredCredentials(){
            return authenticateForCredentials().then(fetchCredentialsFromKeychain);

            function authenticateForCredentials(){
                return new Promise(function(resolve, reject){
                    Fingerprint.show({
                        localizedReason: 'Authenticate to access your Otto account.'
                    }, resolve, reject);
                });
            }
            function fetchCredentialsFromKeychain(){
                return new Promise(function(resolve, reject){
                    const canAccessKeychain = platform === 'ios' && Keychain;
                    if(canAccessKeychain){
                        Keychain.getJson(resolve, reject, 'dym-login-credentials');
                    } else {
                        reject();
                    }
                });
            }
        }
        function storeCredentials(credentials){
            return new Promise(setCredentialsInKeychain).then(enableBiometricLogin);

            function setCredentialsInKeychain(resolve, reject){
                if(platform === 'ios'){
                    const requireTouchId = false; //set to false since incompatible with Face ID
                    Keychain.setJson(resolve, reject, 'dym-login-credentials', credentials, requireTouchId);
                } else {
                    resolve();
                }
            }
            function enableBiometricLogin(){
                return Vue.clientStorage.setItem('biometric_login_enabled', 'true');
            }
        }
        function removeCredentials(){
            return disableBiometricLogin().then(removeCredentialsFromKeychain);

            function removeCredentialsFromKeychain(){
                return new Promise(function(resolve, reject){
                    if(platform === 'ios'){
                        Keychain.remove(resolve, reject, 'dym-login-credentials');
                    } else {
                        resolve();
                    }
                });
            }
            function disableBiometricLogin(){
                return Vue.clientStorage.removeItem('biometric_login_enabled');
            }
        }
        function getBiometricAvailability(){
            return new Promise(checkAvailability);

            function checkAvailability(resolve, reject){
                let biometricAvailability = false;
                if(platform === 'ios' && Fingerprint){
                    Fingerprint.isAvailable(handleSuccess, handleError);
                } else {
                    resolve(biometricAvailability);
                }
                function handleSuccess(availability){
                    if(availability === 'finger'){
                        biometricAvailability = 'Touch ID';
                    } else if(availability === 'face'){
                        biometricAvailability = 'Face ID';
                    }
                    resolve(biometricAvailability);
                }
                function handleError(){
                    resolve(biometricAvailability);
                }
            }
        }
    }
}
