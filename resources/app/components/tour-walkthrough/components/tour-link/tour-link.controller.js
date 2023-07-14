export default {
    props: {
        id: {
            type: String,
            required: true
        }
    },
    data,
    computed: getComputed(),
    watch: getWatchers(),
    mounted,
};

function data(){
    return {
        tourAnimationClass: [],
    };
}

function getComputed(){
    return {
        tourLinks(){
            const vm = this;
            return vm.$store.state.tourWalkthrough.tourLinks;
        },
        currentTourStep(){
            const vm = this;
            const savedTourStep = vm.$store.state.tourWalkthrough.lastTourStep;
            const link = isSavedStepInTour() ? savedTourStep : vm.tourLinks[0];
            return link || '';

            function isSavedStepInTour(){
                const savedTourQuery = savedTourStep && savedTourStep.query ? savedTourStep.query.tour : '';
                const savedTourRouteName = savedTourStep && savedTourStep.name ? savedTourStep.name : '';
                return vm.tourLinks.find(({ query, name }) => query.tour === savedTourQuery && name === savedTourRouteName);
            }
        }
    };
}

function getWatchers(){
    return {
        currentTourStep
    };
    function currentTourStep(newVal, oldVal){
        const vm = this;
        const tourStepIndex = vm.tourLinks.findIndex(({ query }) => query.tour === vm.currentTourStep.query.tour);
        vm.$store.commit('tourWalkthrough/SET_NEXT_TOUR_STEP', vm.tourLinks[tourStepIndex + 1]);
        vm.$store.commit('tourWalkthrough/SET_PREVIOUS_TOUR_STEP', vm.tourLinks[tourStepIndex - 1]);
    }
}

function mounted(){
    const vm = this;
    vm.$root.$on('tour-closed', animateTourLink);
    vm.$root.$on('dym::restart-tour', restartTour);

    function animateTourLink(){
        vm.tourAnimationClass.push('rubberBand');
        setTimeout(resetAnimationClass, 1000);
        function resetAnimationClass(){
            vm.tourAnimationClass = [];
        }
    }

    function restartTour(){
        vm.$router.push(vm.tourLinks[0]);
    }
}
