import tourHelpersMixin from 'vue_root/components/tour-walkthrough/mixins/tourHelpers.mixin';

export default {
    mixins: [tourHelpersMixin],
    props: {
        name: {
            type: String,
            required: true
        },
        verticalAlign: {
            type: String,
            default: 'bottom',
            validator: function(alignment){
                const validAlignments = ['center', 'top', 'bottom'];
                return validAlignments.includes(alignment);
            }
        },
        horizontalAlign: {
            type: String,
            default: 'left',
            validator: function(alignment){
                const validAlignments = ['center', 'left', 'right'];
                return validAlignments.includes(alignment);
            }
        }
    },
    data: data,
    watch: getWatchers(),
    mounted,
    methods: getMethods()
};
function data(){
    return {
        highlightElement: null,
        closing: false
    };
}
function getWatchers(){
    return {
        showWalkthrough(newVal, oldVal){
            const vm = this;
            if(newVal){
                vm.onTourShow();
            } else {
                vm.onTourHide();
            }
        }
    };
}
function mounted(){
    const vm = this;
    vm.$root.$on(vm.name + '-target-ready', vm.linkHighlightElement);
    vm.$root.$emit(vm.name + '-popover-mounted');
    vm.toggleWalkthrough();
}
function getMethods(){
    return {
        linkHighlightElement,
        onTourShow,
        onTourHide,
        close,
        closeAndCompleteWalthrough,
        getPopoverElement,
        getTransformOffsets,
        closeWithoutNavigation
    };
    function linkHighlightElement($event){
        const vm = this;
        vm.highlightElement = $event.element;
        vm.toggleWalkthrough();
    }
    function onTourShow(){
        const vm = this;
        vm.highlightElement.classList.add('tour-popover-highlight');
        vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_WITHOUT_NAVIGATION_METHOD', vm.closeWithoutNavigation);
        vm.$store.dispatch('tourWalkthrough/SET_CLOSE_TOUR_METHOD', vm.close);
        const scrollToTop = vm.verticalAlign === 'bottom';
        Vue.nextTick(() => { vm.highlightElement.scrollIntoView(scrollToTop); });
    }
    function onTourHide(event){
        const vm = this;
        if(vm.showWalkthrough && vm.getPopoverElement()){
            vm.close();
        } else if(vm.showWalkthrough){
            vm.removeTourQuery();
        }
        vm.highlightElement.classList.remove('tour-popover-highlight');
    }
    function close(){
        const vm = this;
        const popoverElement = vm.getPopoverElement();
        vm.highlightElement.classList.remove('tour-popover-highlight');
        if(popoverElement){
            return vm.animateExit(popoverElement, vm.getTransformOffsets(popoverElement)).then(vm.closeWalkthrough).then(() => vm.resetAnimation(popoverElement));
        } else {
            return Promise.resolve().then(vm.closeWalkthrough);
        }
    }
    function closeAndCompleteWalthrough(){
        const vm = this;
        const popoverElement = vm.getPopoverElement();
        vm.highlightElement.classList.remove('tour-popover-highlight');
        if(popoverElement){
            return vm.animateExit(popoverElement, vm.getTransformOffsets(popoverElement)).then(vm.completeWalkthrough).then(() => vm.resetAnimation(popoverElement));
        } else {
            return Promise.resolve().then(vm.completeWalkthrough);
        }
    }
    function getTransformOffsets(element){
        const vm = this;
        const offsets = {
            translateXOffset: 0,
            translateYOffset: 0
        };
        const elemRect = element.getBoundingClientRect();
        if(vm.horizontalAlign === 'center'){
            offsets.translateXOffset = elemRect.width / -2;
        }
        if(vm.verticalAlign === 'center'){
            offsets.translateYOffset = elemRect.height / -2;
        }
        return offsets;
    }
    function getPopoverElement(){
        const vm = this;
        const componentNode = vm.$refs.tourHighlightedModal;
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
            vm.highlightElement.classList.remove('tour-popover-highlight');
            return vm.animateExit(popoverElement, vm.getTransformOffsets(popoverElement));
        } else {
            return Promise.resolve();
        }
    }
}
