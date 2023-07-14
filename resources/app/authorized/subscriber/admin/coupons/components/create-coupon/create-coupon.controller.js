export default {
    props: {
        rewardTypes: {
            type: Array,
            required: true
        },
        couponTypes: {
            type: Array,
            required: true
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        errorMessages: [],
        newCoupon: {},
        isSavingCoupon: false,
        validationErrors: {}
    };
}

function getComputed(){
    return {
        rewardTypeOptions(){
            const vm = this;
            return vm.rewardTypes.map(formatForSelectInput);
        },
        couponTypeOptions(){
            const vm = this;
            return vm.couponTypes.map(formatForSelectInput);
        }
    };

    function formatForSelectInput(slug){
        return {
            text: slug.split('_').join(' '),
            value: slug
        };
    }
}

function created(){
    const vm = this;
    vm.initializeNewCoupon();
}

function getMethods(){
    return {
        emitError,
        initializeNewCoupon,
        createCoupon,
        cancelCreation
    };

    function emitError(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data;
        if(isValidationError){
            vm.validationErrors = error.data.errors;
        } else if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.$emit('error', errorMessage);
        }
    }

    function initializeNewCoupon(){
        const vm = this;
        const firstCouponType = vm.couponTypes.length ? vm.couponTypes[0] : undefined;
        const firstRewardType = vm.rewardTypes.length ? vm.rewardTypes[0] : undefined;
        vm.newCoupon = {
            type_slug: firstCouponType,
            reward_type: firstRewardType,
            number_of_uses: 1,
            amount: 1,
            reward_duration_in_months: 0
        };
    }

    function createCoupon(){
        const vm = this;
        const payload = vm.newCoupon;
        vm.isSavingCoupon = true;
        vm.validationErrors = {};
        if(payload.expiration_date){
            payload.expiration_date = Vue.moment(payload.expiration_date).format('YYYY-MM-DD 00:00:00');
        }

        if(!payload.code){
            delete payload.code;
        }

        Vue.appApi().authorized().admin().coupons().createCoupon(payload).then(updateParent).catch(vm.emitError).finally(resetLoadingState);

        function updateParent(response){
            vm.$emit('coupon-created', response.data);
        }

        function resetLoadingState(){
            vm.isSavingCoupon = false;
        }
    }

    function cancelCreation(){
        const vm = this;
        vm.$emit('creation-cancelled');
    }
}
