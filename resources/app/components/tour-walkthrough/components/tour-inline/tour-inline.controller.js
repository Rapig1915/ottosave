import toggleTourMixin from 'vue_root/components/tour-walkthrough/mixins/tourHelpers.mixin';

export default {
    mixins: [toggleTourMixin],
    props: {
        name: {
            type: String,
            required: true
        },
    },
    watch: getWatchers(),
    mounted,
    methods: getMethods()
};

function getWatchers(){
    return {
        showWalkthrough: showWalkthoughWatcher
    };

    function showWalkthoughWatcher(showWalkthrough){
        const vm = this;
        if(showWalkthrough){
            vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', vm.closeWithoutNavigation);
            vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_METHOD', vm.close);
        }
    }
}

function mounted(){
    const vm = this;
    vm.toggleWalkthrough();
}

function getMethods(){
    return {
        close,
        closeAndCompleteWalthrough,
        closeWithoutNavigation
    };

    function close(){
        const vm = this;
        return vm.animateExit(vm.$el).then(vm.closeWalkthrough).then(() => vm.resetAnimation(vm.$el));
    }

    function closeAndCompleteWalthrough(){
        const vm = this;
        return vm.animateExit(vm.$el).then(vm.completeWalkthrough).then(() => vm.resetAnimation(vm.$el));
    }
    function closeWithoutNavigation(){
        const vm = this;
        return vm.animateExit(vm.$el).then(setShowWalkthroughFalse).then(() => vm.resetAnimation(vm.$el));
        function setShowWalkthroughFalse(){
            vm.showWalkthrough = false;
        }
    }
}
