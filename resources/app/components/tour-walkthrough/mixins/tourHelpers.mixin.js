export default {
    data: data,
    computed: getComputed(),
    watch: getWatchers(),
    methods: getMethods()
};
function data(){
    return {
        showWalkthrough: false
    };
}
function getComputed(){
    let cachedPreviousStep = null;
    let cachedNextStep = null;
    return {
        previousTourStep(){
            const vm = this;
            if(vm.showWalkthrough){
                cachedPreviousStep = vm.$store.state.tourWalkthrough.previousTourStep;
            }
            return cachedPreviousStep;
        },
        nextTourStep(){
            const vm = this;
            if(vm.showWalkthrough){
                cachedNextStep = vm.$store.state.tourWalkthrough.nextTourStep;
            }
            return cachedNextStep;
        }
    };
}
function getWatchers(){
    return {
        '$route'(newVal, oldVal){
            const vm = this;
            if(newVal.query.tour !== oldVal.query.tour){
                vm.toggleWalkthrough();
            }
        }
    };
}
function getMethods(){
    return {
        toggleWalkthrough,
        closeWalkthrough,
        completeWalkthrough,
        onHideEvent,
        removeTourQuery,
        animateExit,
        resetAnimation
    };
    function toggleWalkthrough(){
        const vm = this;
        const queryParts = vm.$route.query;
        const isTourRequested = Object.prototype.hasOwnProperty.call(queryParts, 'tour');
        const isCurrentTour = ((vm.name === queryParts['tour'] || (!queryParts['tour'] && !vm.name)));
        vm.showWalkthrough = (isTourRequested && isCurrentTour);
        if(vm.showWalkthrough){
            vm.$store.dispatch('tourWalkthrough/SET_TOUR_STEP', Object.assign(
                {},
                vm.$route,
                { matched: null }
            ));
        }
    }

    function closeWalkthrough(){
        const vm = this;
        vm.showWalkthrough = false;
        vm.removeTourQuery();
    }
    function completeWalkthrough(){
        const vm = this;
        vm.closeWalkthrough();
        vm.$router.push({ name: 'assign' });
        vm.$store.dispatch('tourWalkthrough/FINISH_TOUR');
    }
    function onHideEvent(event){
        const vm = this;
        if(event.trigger === 'backdrop' || event.trigger === 'esc'){
            vm.removeTourQuery();
        }
    }
    function removeTourQuery(){
        const vm = this;
        const queryString = Object.assign({}, vm.$router.history.current.query);
        delete queryString.tour;
        vm.$router.push({ query: queryString, hash: vm.$router.history.current.hash });
        vm.$root.$emit('tour-closed');
    }

    function animateExit(element, offsets = {}){
        const tourIconId = (window.innerWidth < 992 || window.appEnv.clientPlatform === 'ios') ? 'mobile-tour-link-icon' : 'tour-link-icon';
        const tourIconElement = document.getElementById(tourIconId);
        const { translateXOffset = 0, translateYOffset = 0 } = offsets;
        element.classList.add('animatedTourExit');
        return positionElementFromOffsets().then(shrinkElement).then(animatePositionToTourIcon);

        function positionElementFromOffsets(){
            return Velocity(element, { translateX: translateXOffset, translateY: translateYOffset }, { duration: 0 });
        }
        function shrinkElement(){
            const finalSize = tourIconElement ? '28px' : '0px';
            return Velocity(element, { width: finalSize, height: finalSize, borderRadius: '50%' }, { duration: 700 });
        }
        function animatePositionToTourIcon(){
            if(tourIconElement){
                const tourIconRect = tourIconElement.getBoundingClientRect();
                const elemRect = element.getBoundingClientRect();
                const translateX = parseInt(tourIconRect.left - elemRect.left + (translateXOffset)) + 'px';
                const translateY = parseInt(tourIconRect.top - elemRect.top + (translateYOffset)) + 'px';

                return Velocity(element, { translateX, translateY: [translateY, 'easeOutQuart'], opacity: [0, 'easeInQuart'] }, { duration: 1000 });
            } else {
                return Velocity(element, { translateX: '100vw', translateY: ['100vh', 'easeOutQuart'], opacity: [0, 'easeInQuart'] }, { duration: 0 });
            }
        }
    }

    function resetAnimation(element){
        element.classList.remove('animatedTourExit');
        element.style.height = 'auto';
        return Velocity(element, { translateX: 0, translateY: 0, width: '100%', opacity: 1, borderRadius: '0.3rem' }, { duration: 0, delay: 300 });
    }
}
