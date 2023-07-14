export default {
    props: {
        totalRows: {
            type: Number
        }
    },
    data: data,
    computed: getComputed(),
    methods: getMethods()
};

function data(){
    return {
        rowsPerPage: 50,
        currentPage: 1,
        numRows: [20, 50, 100],

        emitChangeCache: {}
    };
}

function getComputed(){
    return {};
}

function getMethods(){
    return {
        handleChangePageNum,
        handleChangeRowsPerPage,
        emitChange,
    };

    function handleChangePageNum(event){
        this.emitChange({ pageNum: event, perPage: this.rowsPerPage });
    }

    function handleChangeRowsPerPage(event){
        this.emitChange({ pageNum: this.currentPage, perPage: event.target.value });
    }

    function emitChange(event){
        const vm = this;
        const shouldIgnore = event.pageNum === vm.emitChangeCache.pageNum && event.perPage === vm.emitChangeCache.perPage;
        if(shouldIgnore){
            return;
        }

        vm.emitChangeCache = event;
        vm.$emit('change', vm.emitChangeCache);
    }
}
