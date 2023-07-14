const state = {
    lastTourStep: '',
    previousTourStep: '',
    nextTourStep: '',
    closeTourMethod: null,
    closeTourWithoutNavigationMethod: null,
    tourLinks: [],
};
export default {
    namespaced: true,
    state,
    actions: getActions(),
    mutations: getMutations(),
};

function getActions(){
    return {
        SET_TOUR_STEP: setTourStep,
        INITIALIZE_TOUR_STATE: initializeTourState,
        FINISH_TOUR: finishTour,
        CLOSE_TOUR: closeTour,
        CLOSE_TOUR_WITHOUT_NAVIGATION: closeTourWithoutNavigation,
        SET_CLOSE_TOUR_METHOD: setCloseTourMethod,
        SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD: setCloseTourWithoutNavigationMethod,
    };

    function setTourStep({ commit, rootState }, tourStep){
        return Vue.clientStorage.getItem('tour_progress').then(updateTourProgress);
        function updateTourProgress(tourProgress){
            const userId = rootState.guest.user.user.id;
            tourProgress = JSON.parse(tourProgress) || {};
            tourProgress[userId] = tourStep;
            commit('SET_TOUR_STEP', { tourStep });
            return Vue.clientStorage.setItem('tour_progress', JSON.stringify(tourProgress));
        }
    }
    function initializeTourState({ commit, rootState }){
        const user = rootState.guest.user.user;
        commit('INITIALIZE_TOUR_LINKS');
        return Vue.clientStorage.getItem('tour_progress').then(initializeTour);

        function initializeTour(stringifiedTourProgress){
            const storedTourProgress = JSON.parse(stringifiedTourProgress) || {};
            const tourStep = storedTourProgress[user.id];
            storedTourProgress[user.id] = tourStep;
            commit('SET_TOUR_STEP', { tourStep });
            return Vue.clientStorage.setItem('tour_progress', JSON.stringify(storedTourProgress));
        }
    }
    function finishTour({ commit, rootState }){
        return Vue.clientStorage.getItem('tour_progress').then(resetTour);
        function resetTour(stringifiedTourProgress){
            const userId = rootState.guest.user.user.id;
            const storedTourProgress = JSON.parse(stringifiedTourProgress) || {};
            delete storedTourProgress[userId];
            commit('FINISH_TOUR');
            return Vue.clientStorage.setItem('tour_progress', JSON.stringify(storedTourProgress));
        }
    }
    function closeTour({ commit, state }){
        if(state.closeTourMethod){
            const closeTourMethod = state.closeTourMethod;
            commit('SET_CLOSE_TOUR_METHOD', null);
            commit('SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', null);
            return closeTourMethod();
        } else {
            return Promise.resolve();
        }
    }
    function closeTourWithoutNavigation({ commit, state }){
        if(state.closeTourWithoutNavigationMethod){
            const closeTourWithoutNavigationMethod = state.closeTourWithoutNavigationMethod;
            commit('SET_CLOSE_TOUR_METHOD', null);
            commit('SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', null);
            return closeTourWithoutNavigationMethod();
        } else {
            return Promise.resolve();
        }
    }
    function setCloseTourMethod({ commit }, method){
        commit('SET_CLOSE_TOUR_METHOD', method);
        return Promise.resolve();
    }
    function setCloseTourWithoutNavigationMethod({ commit }, method){
        commit('SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', method);
        return Promise.resolve();
    }
}

function getMutations(){
    return {
        INITIALIZE_TOUR_LINKS: setLinksForEnvironment,
        SET_TOUR_STEP: setTourStep,
        FINISH_TOUR: finishTour,
        SET_CLOSE_TOUR_METHOD: setCloseTourMethod,
        SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD: setCloseTourWithoutNavigationMethod,
        SET_NEXT_TOUR_STEP: setNextTourStep,
        SET_PREVIOUS_TOUR_STEP: setPreviousTourStep,
    };
    function setLinksForEnvironment(state){
        const clientPlatform = window.appEnv.clientPlatform || 'web';
        const iosTourLinks = [{ name: 'dashboard', query: { tour: 'welcome' }}];
        const webTourLinks = [{ name: 'dashboard', query: { tour: 'welcome' }}];
        state.tourLinks = clientPlatform === 'web' ? webTourLinks : iosTourLinks;
    }
    function setTourStep(state, { tourStep }){
        state.lastTourStep = tourStep;
    }
    function finishTour(state){
        state.lastTourStep = null;
    }
    function setCloseTourMethod(state, method){
        state.closeTourMethod = method;
    }
    function setCloseTourWithoutNavigationMethod(state, method){
        state.closeTourWithoutNavigationMethod = method;
    }
    function setNextTourStep(state, tourStep){
        state.nextTourStep = tourStep;
    }
    function setPreviousTourStep(state, tourStep){
        state.previousTourStep = tourStep;
    }
}
