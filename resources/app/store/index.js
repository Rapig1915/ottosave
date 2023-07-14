import guest from './modules/guest.store';
import authorized from './modules/authorized.store';
import tourWalkthrough from './modules/tour-walkthrough.store';
import renderView from './modules/render-view.store';

const debug = process.env.NODE_ENV !== 'production';

export default {
    modules: {
        guest,
        authorized,
        tourWalkthrough,
        renderView
    },
    state: {
        showIosLoginOverlay: false
    },
    mutations: getMutations(),
    getters: getGetters(),
    strict: debug,
};

function getMutations(){
    return {
        SET_SHOW_IOS_OVERLAY: setShowIosOverlay
    };

    function setShowIosOverlay(state, payload){
        state.showIosLoginOverlay = payload;
    }
}

function getGetters(){
    return {
        user,
        freeTrialUsed,
        isInDemoMode
    };

    function user(state){
        return state.guest.user.user;
    }

    function freeTrialUsed(state, getters){
        const user = getters.user;
        const isPlusAccount = user && user.current_account.subscription_plan === 'plus';
        const accountHasPaymentOnFile = user && user.current_account.is_trial_used;
        return isPlusAccount || accountHasPaymentOnFile;
    }

    function isInDemoMode(state, getters){
        const user = getters.user;
        return user && user.current_account && user.current_account.status === 'demo';
    }
}
