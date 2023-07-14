import toggleTourMixin from 'vue_root/components/tour-walkthrough/mixins/tourHelpers.mixin';

export default {
    mixins: [toggleTourMixin],
    props: {
        name: {
            type: String,
            required: true
        },
        hideFooter: {
            type: Boolean,
            default: false
        },
        hideCloseButton: {
            type: Boolean,
            default: false
        },
        size: {
            type: String,
            default: 'md'
        }
    },
    mounted,
    data,
    methods: getMethods()
};

function mounted(){
    const vm = this;
    vm.toggleWalkthrough();
}

function data(){
    return {
        modalClass: ['fade', 'tourModal']
    };
}

function getMethods(){
    return {
        closeModal,
        closeAndCompleteWalthrough,
        handleHideEvent
    };

    function closeModal(){
        const vm = this;
        vm.modalClass = [];
        const elementToAnimate = vm.$refs[`tour-modal-${vm.name}`]['$refs']['content'];
        return vm.animateExit(elementToAnimate).then(vm.closeWalkthrough).then(() => vm.resetAnimation(elementToAnimate));
    }
    function closeAndCompleteWalthrough(){
        const vm = this;
        vm.modalClass = [];
        const elementToAnimate = vm.$refs[`tour-modal-${vm.name}`]['$refs']['content'];
        return vm.animateExit(elementToAnimate).then(vm.completeWalkthrough).then(() => vm.resetAnimation(elementToAnimate));
    }
    function handleHideEvent(event){
        const vm = this;
        if(event.trigger === 'backdrop' || event.trigger === 'esc'){
            event.preventDefault();
            vm.closeModal().then(resetAnimation);
        } else {
            resetAnimation();
        }
        function resetAnimation(){
            vm.modalClass = ['fade', 'tourModal'];
            vm.onHideEvent(event);
        }
    }
}
