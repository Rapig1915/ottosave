import RefreshAccountsButton from './components/refresh-accounts/refresh-accounts';
import OauthMigrationComponent from './components/oauth-migration/oauth-migration';
import CanUserMixin from 'vue_root/mixins/can-user.mixin.js';
import { Plugins } from '@capacitor/core';
const { Keyboard } = Plugins;

export default {
    components: {
        RefreshAccountsButton,
        OauthMigrationComponent
    },
    mixins: [CanUserMixin],
    data: data,
    computed: getComputed(),
    watch: getWatchers(),
    mounted,
    beforeDestroy,
    methods: getMethods(),
};

function data(){
    return {
        adminLinks: [
            { routeName: 'admin-user-list', icon: 'fas fa-users', description: 'User List' },
            { routeName: 'admin-notifications-preview', icon: 'fas fa-envelope', description: 'Preview Notifications' },
            { routeName: 'admin-coupons', icon: 'fas fa-barcode', description: 'Manage Coupons' },
            { routeName: 'admin-finicity-oauth', icon: 'fas fa-university', description: 'Finicity Oauth Transitions' },
            { routeName: 'admin-finicity-customers', icon: 'fas fa-users', description: 'Finicity Customers' },
            { routeName: 'admin-test-deposit', icon: 'fas fa-credit-card', description: 'Test Deposit' },
        ],
        showMenu: false,
        isAdminNavOpen: false,
        isFinicityRecoveryWarningDismissed: false,
        isFinicityErrorCodeMessageDismissed: false,
        isSidebarTransitionDone: true,
        isKeyboardActive: false,
        clientPlatform: window.appEnv.clientPlatform || 'web',
        currentActiveAccount: null,
    };
}

function getComputed(){
    return {
        user(){
            return this.$store.state.guest.user.user;
        },
        finicityRefreshStatus(){
            const vm = this;
            return vm.$store.state.authorized.finicityRefreshStatus;
        },
        isPlusSubscriber(){
            const vm = this;
            return vm.user.current_account.subscription_plan === 'plus';
        },
        shouldDisplayCCPayoffWarning(){
            const vm = this;
            return vm.$store.state.authorized.shouldDisplayCCPayoffWarning;
        },
        countOfUnassignedTransactions(){
            return this.$store.getters['authorized/transactions/countOfUnassignedTransactions'];
        },
        routerLinks(){
            const vm = this;
            return [
                { routeName: 'dashboard', icon: 'icon-dym-dashboard', description: 'Dashboard', exact: true, iosPosition: 'footer-menu' },
                { routeName: 'assign', icon: 'icon-dym-credit-card', description: 'Credit Card', notificationCount: vm.countOfUnassignedTransactions, notificationLength: vm.countOfUnassignedTransactions.toString().length, iosPosition: 'footer-menu' },
                { routeName: 'organize', icon: 'icon-dym-organize', description: 'Organize', exact: true, iosPosition: 'footer-menu' },
                { routeName: 'transfer', icon: 'icon-dym-transfer', description: 'Transfer', iosPosition: 'sidebar' },
                { routeName: 'reports', icon: 'fas fa-chart-bar', description: 'Reports', iosPosition: 'sidebar' },
                { routeName: 'accounts', icon: 'icon-dym-accounts', description: 'Accounts', iosPosition: 'sidebar' },
                { routeName: 'settings', icon: 'icon-dym-settings', description: 'Settings', iosPosition: 'sidebar', permissions: ['update account-settings'] },
            ];
        },
        isFinicityRecoveryWarningDisplayed: {
            get(){
                const vm = this;
                return vm.finicityRefreshStatus !== 'pending' && vm.$store.getters['authorized/bankAccounts/bankAccountsWithRecoverableErrors'].length && !vm.isFinicityRecoveryWarningDismissed;
            },
            set(value){
                const vm = this;
                vm.isFinicityRecoveryWarningDismissed = value;
            }
        },
        isFinicityErrorCodeMessageDisplayed: {
            get(){
                const vm = this;
                return vm.finicityRefreshStatus !== 'pending' && vm.finicityErrorCodes.length && !vm.isFinicityErrorCodeMessageDismissed;
            },
            set(value){
                const vm = this;
                vm.isFinicityErrorCodeMessageDismissed = value;
            }
        },
        finicityErrorCodes(){
            const vm = this;
            const unrecoverableErrorCodes = vm.$store.getters['authorized/bankAccounts/bankAccountsWithUnrecoverableErrors'].map(({ institution_account }) => institution_account.remote_status_code);
            const unknownErrorCodes = vm.$store.getters['authorized/bankAccounts/bankAccountsWithUnknownErrors'].map(({ institution_account }) => institution_account.remote_status_code);
            const allErrorCodes = [].concat(unknownErrorCodes, unrecoverableErrorCodes);
            const uniqueErrorCodes = allErrorCodes.filter((item, index, array) => array.indexOf(item) === index);
            return uniqueErrorCodes;
        },
        finicityErroredInstitutionList(){
            const vm = this;
            const unrecoverableErrorInstitutions = vm.$store.getters['authorized/bankAccounts/bankAccountsWithUnrecoverableErrors'].map(({ institution_account }) => institution_account.institution.name);
            const unknownErrorInstitutions = vm.$store.getters['authorized/bankAccounts/bankAccountsWithUnknownErrors'].map(({ institution_account }) => institution_account.institution.name);
            const allErrorInstitutions = [].concat(unrecoverableErrorInstitutions, unknownErrorInstitutions);
            const uniqueErrorInstitutions = allErrorInstitutions.filter((item, index, array) => array.indexOf(item) === index);
            if(uniqueErrorInstitutions.length > 1){
                uniqueErrorInstitutions[uniqueErrorInstitutions.length - 1] = 'and ' + uniqueErrorInstitutions[uniqueErrorInstitutions.length - 1];
            }
            const institutionList = uniqueErrorInstitutions.join(', ');
            return institutionList;
        },
        totalNotificationCount(){
            const vm = this;
            return vm.routerLinks.reduce((accumulator, link) => accumulator + (link.notificationCount || 0), 0);
        },
        useIosMenu(){
            return window.appEnv.clientPlatform === 'ios';
        },
        sidebarLinks(){
            const vm = this;
            const availableLinks = vm.useIosMenu ? vm.routerLinks.filter(({ iosPosition }) => iosPosition === 'sidebar') : vm.routerLinks;
            return availableLinks.filter(checkPermission);

            function checkPermission(link){
                const hasPermission = link.permissions && link.permissions.length > 0;
                if(!hasPermission){
                    return true;
                }

                return link.permissions.every(permission => vm.canUser(permission));
            }
        },
        iosFooterLinks(){
            const vm = this;
            return vm.routerLinks.filter(({ iosPosition }) => iosPosition === 'footer-menu');
        },
        accessibleAccounts(){
            const vm = this;
            return (vm.$store.state.guest.user.accessible_accounts || []).map(setLabelOnOption);

            function setLabelOnOption(account){
                account.label = account.user.name;
                return account;
            }
        },
        accountSwitchable(){
            const vm = this;
            return vm.accessibleAccounts && vm.accessibleAccounts.length > 1;
        }
    };
}

function getWatchers(){
    return {
        showMenu(newVal){
            const vm = this;
            if(newVal){
                vm.$refs.sidebarMenu.focus();
            }
        }
    };
}

function mounted(){
    const vm = this;
    const viewingAdminRoute = vm.$route.matched.some(({ name }) => name === 'admin');
    if(viewingAdminRoute){
        vm.isAdminNavOpen = true;
    }
    if(vm.isPlusSubscriber){
        vm.$store.dispatch('authorized/CHECK_FINICITY_REFRESH_STATUS');
    }
    if(vm.useIosMenu){
        const keyboardOptions = { isVisible: true };
        Keyboard.setAccessoryBarVisible(keyboardOptions);
        Keyboard.addListener('keyboardWillShow', () => {
            vm.isKeyboardActive = true;
        });
        Keyboard.addListener('keyboardWillHide', () => {
            vm.isKeyboardActive = false;
        });
    }

    vm.currentActiveAccount = vm.accessibleAccounts.find(account => account.account_id === vm.$store.getters['user/currentAccountId']);
}

function beforeDestroy(){
    const vm = this;
    if(vm.useIosMenu){
        Keyboard.removeAllListeners();
    }
}

function getMethods(){
    return {
        hideMenuAndAdminNav,
        logout,
        toggleSidebar,
        onSwitchAccount,
    };

    function hideMenuAndAdminNav(){
        const vm = this;
        vm.$refs.sidebarMenu.blur();
        vm.isAdminNavOpen = false;
    }
    function logout(){
        const vm = this;
        vm.$store.dispatch('user/LOGOUT').then(logoutSuccess);

        function logoutSuccess(){
            if(vm.clientPlatform === 'web'){
                vm.$router.go();
            } else {
                vm.$router.replace({ name: 'login' });
            }
        }
    }
    function toggleSidebar(blurEvent){
        const vm = this;
        const clickedOnAccountSelect = blurEvent && blurEvent.currentTarget.contains(blurEvent.relatedTarget);
        if(clickedOnAccountSelect){
            return;
        }

        if(vm.isSidebarTransitionDone){
            vm.isSidebarTransitionDone = false;
            vm.showMenu = !vm.showMenu;
            setTimeout(() => {
                vm.isSidebarTransitionDone = true;
            }, 200);
        }
    }

    function onSwitchAccount(){
        const vm = this;
        Vue.appApi().authorized().account().switchAccount(vm.currentActiveAccount.account_id).then(handleSuccess).catch(vm.logout);

        function handleSuccess(response){
            vm.$store.commit('user/SET_CURRENT_ACCOUNT', response.data);
            if(vm.showMenu){
                vm.toggleSidebar();
            }

            vm.$store.dispatch('user/GET_USER').then(() => {
                vm.$store.dispatch('renderView/FORCE_REMOUNT');
            });
        }
    }
}
