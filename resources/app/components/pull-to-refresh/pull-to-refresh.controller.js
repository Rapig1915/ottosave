import PullToRefresh from 'pulltorefreshjs';

export default {
    props: {
        mainElement: {
            type: String,
            default: 'body'
        },
        triggerElement: {
            type: String,
            default: 'body'
        },
        refreshTimeout: {
            type: Number,
            default: 300
        },
        instructionsRefreshing: {
            type: String,
            default: 'Refreshing'
        },
        instructionsPullToRefresh: {
            type: String,
            default: 'Pull down to refresh'
        },
        instructionsReleaseToRefresh: {
            type: String,
            default: 'Release to refresh'
        },
        iconArrow: {
            type: String,
            default: '<i class="fas fa-arrow-down"></i>'
        },
        iconRefreshing: {
            type: String,
            default: '<i class="fas fa-spin fa-circle-notch"></i>'
        }
    },
    data,
    computed: getComputed(),
    mounted,
    beforeDestroy,
    methods: getMethods(),
};

function data(){
    return {
        isRefreshing: false
    };
}

function getComputed(){
    return {};
}

function mounted(){
    const vm = this;
    PullToRefresh.init({
        mainElement: vm.mainElement,
        triggerElement: vm.triggerElement,
        iconArrow: vm.iconArrow,
        iconRefreshing: vm.iconRefreshing,
        instructionsRefreshing: vm.instructionsRefreshing,
        instructionsPullToRefresh: vm.instructionsPullToRefresh,
        instructionsReleaseToRefresh: vm.instructionsReleaseToRefresh,
        refreshTimeout: vm.refreshTimeout,
        onRefresh: vm.onRefresh,
        resistanceFunction: t => Math.min(1, t / 4),
    });
}

function beforeDestroy(){
    PullToRefresh.destroyAll();
}

function getMethods(){
    return {
        onRefresh
    };

    function onRefresh(){
        const vm = this;
        vm.$emit('refreshTriggered');
        vm.isRefreshing = true;
        setTimeout(resetRefresh, vm.refreshTimeout);
        function resetRefresh(){
            vm.$emit('refreshComplete');
            vm.isRefreshing = false;
        }
    }
}
