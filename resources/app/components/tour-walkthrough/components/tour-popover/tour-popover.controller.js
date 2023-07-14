import tourHelpersMixin from 'vue_root/components/tour-walkthrough/mixins/tourHelpers.mixin';

export default {
    mixins: [tourHelpersMixin],
    props: {
        name: {
            type: String,
            required: true
        },
    },
    data: data,
    mounted,
    methods: getMethods()
};
function data(){
    return {
        targetElement: null,
        closing: false
    };
}
function mounted(){
    const vm = this;
    vm.$root.$on(vm.name + '-target-ready', vm.linkPopoverTarget);
    vm.$root.$emit(vm.name + '-popover-mounted');
    vm.toggleWalkthrough();
}
function getMethods(){
    return {
        linkPopoverTarget,
        onTourShow,
        onTourHide,
        close,
        closeAndCompleteWalthrough,
        getPopoverElement,
        closeWithoutNavigation
    };
    function linkPopoverTarget($event){
        const vm = this;
        vm.targetElement = $event.element;
        vm.toggleWalkthrough();
    }
    function onTourShow(){
        const vm = this;
        vm.targetElement.classList.add('tour-popover-highlight');
        vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', vm.closeWithoutNavigation);
        vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_METHOD', vm.close);
        Vue.nextTick(() => { vm.targetElement.scrollIntoView(false); });
    }
    function onTourHide(event){
        const vm = this;
        event.preventDefault();
        if(vm.showWalkthrough && vm.getPopoverElement()){
            vm.close();
        } else if(vm.showWalkthrough){
            vm.removeTourQuery();
        }
        vm.targetElement.classList.remove('tour-popover-highlight');
    }
    function close(){
        const vm = this;
        const popoverElement = vm.getPopoverElement();
        vm.targetElement.classList.remove('tour-popover-highlight');
        if(popoverElement){
            return vm.animateExit(popoverElement, getTransformOffsets(popoverElement)).then(vm.closeWalkthrough).then(() => vm.resetAnimation(popoverElement));
        } else {
            return Promise.resolve().then(vm.closeWalkthrough);
        }
    }
    function closeAndCompleteWalthrough(){
        const vm = this;
        const popoverElement = vm.getPopoverElement();
        vm.targetElement.classList.remove('tour-popover-highlight');
        if(popoverElement){
            return vm.animateExit(popoverElement, getTransformOffsets(popoverElement)).then(vm.completeWalkthrough).then(() => vm.resetAnimation(popoverElement));
        } else {
            return Promise.resolve().then(vm.completeWalkthrough);
        }
    }
    function getTransformOffsets(element){
        const offsets = {
            translateXOffset: 0,
            translateYOffset: 0
        };
        const bootstrapTransform = element.style.transform;
        if(bootstrapTransform && bootstrapTransform.includes('translate3d')){
            const bootstrapTranslateCoords = bootstrapTransform.split('translate3d(').join('').split(',');
            offsets.translateXOffset = parseInt(bootstrapTranslateCoords[0]);
            offsets.translateYOffset = parseInt(bootstrapTranslateCoords[1]);
        }
        return offsets;
    }
    function getPopoverElement(){
        const vm = this;
        const componentNode = vm.$refs.tourPopoverComponent;
        var popoverElement = null;
        if(componentNode){
            componentNode.childNodes.forEach(node => {
                if(node.classList && node.classList.contains('popover')){
                    popoverElement = node;
                }
            });
        }
        return popoverElement;
    }
    function closeWithoutNavigation(){
        const vm = this;
        const popoverElement = vm.getPopoverElement();
        if(vm.showWalkthrough && popoverElement){
            vm.targetElement.classList.remove('tour-popover-highlight');
            return vm.animateExit(popoverElement, getTransformOffsets(popoverElement));
        } else {
            return Promise.resolve();
        }
    }
}
