import CreateCoupon from './components/create-coupon/create-coupon';

export default {
    components: {
        CreateCoupon
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        isLoadingCoupons: false,
        isLoadingOptions: false,
        coupons: [],
        searchString: '',
        couponFields: [
            {
                key: 'code',
                label: 'Coupon Code',
                sortable: true
            },
            {
                key: 'reward_type',
                label: 'Reward Type',
                sortable: true
            },
            {
                key: 'amount',
                label: 'Reward Amount',
                sortable: true
            },
            {
                key: 'reward_duration_in_months',
                label: 'Reward Duration',
                sortable: true
            },
            {
                key: 'type_slug',
                label: 'Coupon Type',
                sortable: true
            },
            {
                key: 'expiration_date',
                label: 'Expires',
                sortable: true,
                formatter: (value) => value ? Vue.moment(value).format('M/D/YY') : 'Never'
            },
            {
                key: 'number_of_uses',
                label: 'Remaining Uses',
                sortable: true,
            }
        ],
        isAddingCoupon: false,
        rewardTypes: [],
        couponTypes: [],
    };
}

function getComputed(){
    return {
        displayedCoupons
    };

    function displayedCoupons(){
        const vm = this;
        return vm.coupons.filter(bySearchString);

        function bySearchString(coupon){
            return !vm.searchString || coupon.code.toLowerCase().includes(vm.searchString.toLowerCase());
        }
    }
}

function created(){
    const vm = this;
    vm.fetchCoupons();
    vm.fetchSelectOptions();
}

function getMethods(){
    return {
        displayErrorMessage,
        fetchCoupons,
        fetchSelectOptions,
        addCouponToList
    };

    function displayErrorMessage(error){
        const vm = this;
        if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function fetchCoupons(){
        const vm = this;
        vm.isLoadingCoupons = true;
        Vue.appApi().authorized().admin().coupons().listCoupons().then(setCoupons).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setCoupons(response){
            vm.coupons = response.data;
        }
        function resetLoadingState(){
            vm.isLoadingCoupons = false;
        }
    }

    function fetchSelectOptions(){
        const vm = this;
        vm.isLoadingOptions = true;
        Vue.appApi().authorized().admin().coupons().getSelectOptions().then(setSelectOptions).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setSelectOptions(response){
            vm.rewardTypes = response.data.reward_types;
            vm.couponTypes = response.data.coupon_types;
        }
        function resetLoadingState(){
            vm.isLoadingOptions = false;
        }
    }

    function addCouponToList(coupon){
        const vm = this;
        vm.coupons.push(coupon);
        vm.isAddingCoupon = false;
    }
}
