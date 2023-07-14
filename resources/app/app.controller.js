import IosLoginOverlay from './components/ios-login-overlay/ios-login-overlay';

export default {
    components: {
        IosLoginOverlay
    },
    mounted,
    computed: getComputed(),
};

function mounted(){
    const vm = this;
    vm.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
        // prevent background page from scrolling instead of the modal, especially for mobile
        bvEvent.target.addEventListener('scroll', preventScrollPropagation);
        bvEvent.target.parentElement.addEventListener('scroll', preventScrollPropagation);
        bvEvent.target.addEventListener('touchmove', preventScrollPropagation);
        bvEvent.target.parentElement.addEventListener('touchmove', preventScrollPropagation);
        function preventScrollPropagation($event){
            $event.stopPropagation();
        }
    });
}

function getComputed(){
    return {
        currentViewKey(){
            const vm = this;
            return vm.$store.state.renderView.viewKey;
        }
    };
}
