import Vue from 'vue';
import VueRouter from 'vue-router';
import appRoutes from './app.routes';
import store from './app.store';
import PageNotFound from './page-not-found.vue';

Vue.use(VueRouter);

const router = new VueRouter({
    mode: 'history',
    routes: [
        appRoutes,
        { name: 'page-not-found', path: '/page-not-found', component: PageNotFound },
        { path: '*', component: PageNotFound },
    ],
    scrollBehavior(to, from, savedPosition){
        return new Promise(function(resolve, reject){
            setTimeout(() => {
                var scrollPosition = { x: 0, y: 0 };
                resolve(scrollPosition);
            }, 0);
        });
    }
});

router.beforeEach(checkIfAuthorized);
router.beforeEach(checkIfSubscribed);
router.beforeEach(checkForPermission);
router.beforeEach(checkForEmailVerification);
router.beforeEach(closeTourBeforeNavigatingOut);
router.beforeEach(updatePageTitle);
router.redirectAfterLogin = redirectAfterLogin;

export default router;

function checkIfAuthorized(toRoute, fromRoute, next){

    const authenticationIsRequired = toRoute.matched.some((route) => route.meta.requiresAuth);
    const userIsAuthorized = store.state.guest.user.hasAccessToken;
    const userWantsAuthRouteButNotLoggedIn = authenticationIsRequired && !userIsAuthorized;

    if(userIsAuthorized === 'pending'){
        return store.dispatch('user/GET_STORED_ACCESS_TOKEN').then(() => checkIfAuthorized(toRoute, fromRoute, next));
    } else if(userWantsAuthRouteButNotLoggedIn){
        router.redirectedFrom = toRoute;
        next({ name: 'login' });
    } else {
        next();
    }
}

function checkIfSubscribed(toRoute, fromRoute, next){

    const isSubscriptionRequired = toRoute.matched.some((route) => route.meta.requiresSubscription);

    if(isSubscriptionRequired){
        const userDataLoaded = store.state.guest.user.user;
        const userIsSubscriber = userDataLoaded && store.state.guest.user.user.current_account.status;
        const subscriptionExpired = userDataLoaded && store.state.guest.user.user.current_account.status === 'expired';
        const isAccountDeactivated = userDataLoaded && store.state.guest.user.user.current_account.status === 'deactivated';
        if(!userDataLoaded){
            store.dispatch('user/GET_USER').then(() => {
                checkIfSubscribed(toRoute, fromRoute, next);
            });
        } else if(!userIsSubscriber || subscriptionExpired){
            next({ name: 'manage-subscription' });
        } else if(isAccountDeactivated){
            next({ name: 'deactivated-account' });
        } else {
            next();
        }
    } else {
        next();
    }
}

function checkForPermission(toRoute, fromRoute, next){
    const requiredPermissions = toRoute.matched.reduce(consolidatePermissions, []);
    const user = store.state.guest.user.user;

    if(requiredPermissions.length && !user){
        store.dispatch('user/GET_USER').then(() => {
            checkForPermission(toRoute, fromRoute, next);
        });
    } else {
        const userHasPermission = requiredPermissions.every(checkUserPermission);
        if(userHasPermission){
            next();
        } else {
            next({ name: 'dashboard' });
        }
    }
    function consolidatePermissions(accumulator, route){
        return accumulator.concat(route.meta.permissions || []);
    }
    function checkUserPermission(permission){
        const hasPermission = user.current_account_user.all_permission_names.includes(permission);
        const isSuperAdmin = user.current_account_user.all_role_names.includes('super-admin');
        return hasPermission || isSuperAdmin;
    }
}

function checkForEmailVerification(to, from, next){
    const verifiedEmailRequired = to.matched.some(({ meta }) => meta.requiresVerifiedEmail);
    const user = store.getters.user;
    if(!verifiedEmailRequired){
        next();
    } else if(!user){
        store.dispatch('user/GET_USER').then(() => {
            checkForEmailVerification(to, from, next);
        });
    } else if(user.email_verified){
        next();
    } else {
        next({ name: 'verification-required' });
    }
}

function redirectAfterLogin(toRoute){
    if(!toRoute && router.redirectedFrom){
        toRoute = {};
        toRoute.name = router.redirectedFrom.name;
        toRoute.params = router.redirectedFrom.params;
        toRoute.query = router.redirectedFrom.query;
        delete router.redirectedFrom;
    } else if(!toRoute){
        toRoute = { name: 'dashboard' };
    }
    router.push(toRoute);
}

function closeTourBeforeNavigatingOut(toRoute, fromRoute, next){
    const navigatingOutOfTour = fromRoute.query.tour && !toRoute.query.tour;
    if(navigatingOutOfTour){
        store.dispatch('tourWalkthrough/CLOSE_TOUR_WITHOUT_NAVIGATION').then(next);
    } else {
        next();
    }
}

function updatePageTitle(toRoute, fromRoute, next){
    Vue.nextTick(setPageTitle); // use nextTick to ensure proper page in history gets the title
    next();
    function setPageTitle(){
        const inheritedRoutes = toRoute.matched.slice().reverse();
        const closestRouteWithTitle = inheritedRoutes.find(route => route.meta && route.meta.title);
        if(closestRouteWithTitle){
            document.title = closestRouteWithTitle.meta.title + ' | Otto';
        } else {
            document.title = 'Otto App';
        }
        sendGoogleAnalytics();

        function sendGoogleAnalytics(){
            const isGoogleAnalyticsConfigured = typeof window.gtag === 'function' && window.appEnv.ga_id;
            if(isGoogleAnalyticsConfigured){
                const pageviewDetails = {
                    page_title: document.title,
                    page_path: toRoute.fullPath,
                    page_location: window.location.origin
                };
                const screenviewDetails = {
                    app_name: window.appEnv.clientPlatform,
                    screen_name: toRoute.name
                };
                window.gtag('config', window.appEnv.ga_id, pageviewDetails);
                window.gtag('event', 'screen_view', screenviewDetails);
            }
        }
    }
}
