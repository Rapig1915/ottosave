export default {
    components: {},
    props: {},
    data,
    computed: getComputed(),
    mounted,
    methods: getMethods()
};

function data(){
    return {};
}

function getComputed(){
    return {};
}

function mounted(){}

function getMethods(){
    return {
        openModal,
    };

    function openModal(){
        const vm = this;
        vm.$refs.linkSuccessModalRef.show();
    }
}
