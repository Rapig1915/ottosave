import UpgradeModal from './components/upgrade-modal/upgrade-modal';
import TrialEndingModal from './components/trial-ending-modal/trial-ending-modal';
import SessionTimeoutModal from './components/session-timeout-modal/session-timeout-modal';
import UserflowService from 'vue_root/components/tour-walkthrough/components/userflow/userflow';

export default {
    components: {
        UpgradeModal,
        TrialEndingModal,
        SessionTimeoutModal,
        UserflowService
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        loadingUser: false,
        isInitializingView: false
    };
}

function getComputed(){
    return {
        user(){
            const vm = this;
            return vm.$store.state.guest.user.user;
        }
    };
}

function created(){
    const vm = this;
    vm.isInitializingView = true;
    vm.getUser().then(initializeTourState).then(promptForUpgrade).finally(resetLoadingState);

    vm.$root.$on('bv::modal::show', closeTourPriorToShowingModal);

    function initializeTourState(){
        return vm.$store.dispatch('authorized/INITIALIZE_STATE');
    }

    function promptForUpgrade(){
        const isUserOnTrial = ['trial_grace', 'free_trial'].includes(vm.user.current_account.status);
        if(isUserOnTrial){
            vm.$refs.trialEndingModal.openModal(vm.user);
        }
    }

    function closeTourPriorToShowingModal(openModalEvent){
        const isOpeningModalPartOfTour = openModalEvent.vueTarget.modalClass && openModalEvent.vueTarget.modalClass.includes('tourModal');
        const canTourBeClosed = vm.$store.state.tourWalkthrough.closeTourMethod; // prevents infinite loop edge case
        const shouldCloseTourBeforeOpeningModal = !isOpeningModalPartOfTour && canTourBeClosed;
        if(shouldCloseTourBeforeOpeningModal){
            openModalEvent.preventDefault();
            vm.$store.dispatch('tourWalkthrough/CLOSE_TOUR').then(openModalEvent.vueTarget.show);
        }
    }

    function resetLoadingState(){
        vm.isInitializingView = false;
    }
}

function getMethods(){
    return {
        getUser
    };

    function getUser(){
        const vm = this;
        const userDataLoaded = vm.user;

        if(!userDataLoaded){
            vm.loadingUser = true;
            return loadUserData();
        } else {
            return Promise.resolve();
        }

        function loadUserData(){
            return vm.$store.dispatch('user/GET_USER').then(displayView);
            function displayView(){
                vm.loadingUser = false;
            }
        }
    }
}
