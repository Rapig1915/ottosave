const { inAppPurchase } = window;

export default {
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        apiErrors: [],
        loadingProducts: true,
        processingPurchase: false,
        products: [],
        selectedProductId: null,
        isModalShown: false
    };
}

function getComputed(){
    return {
        showSpinner(){
            const vm = this;
            return vm.loadingProducts || vm.processingPurchase;
        },
        selectedProduct(){
            const vm = this;
            return vm.products.find(({ productId }) => productId === vm.selectedProductId) || {};
        }
    };
}

function created(){
    const vm = this;
    vm.getIosSubscriptionProducts();
}

function getMethods(){
    return {
        getIosSubscriptionProducts,
        openModal,
        subscribeToProduct,
        displayError
    };

    function getIosSubscriptionProducts(){
        const vm = this;
        vm.loadingProducts = true;
        Vue.appApi().authorized().account().getIosSubscriptionProducts().then(getDetailsFromApple).catch(vm.displayError).finally(setLoadingFalse);

        function getDetailsFromApple(response){
            var productIds = response.data.map(({ product_id }) => product_id);
            return inAppPurchase.getProducts(productIds).then(setProducts);

            function setProducts(products){
                vm.products = products.map(mergeWithDymProduct);
                vm.selectedProductId = vm.products[0].productId;

                function mergeWithDymProduct(product){
                    const dymProduct = response.data.find(({ product_id }) => product_id === product.productId);
                    if(dymProduct){
                        product.dymProduct = dymProduct;
                    }
                    return product;
                }
            }
        }
        function setLoadingFalse(){
            vm.loadingProducts = false;
        }
    }

    function openModal(options){
        const vm = this;
        vm.isModalShown = true;
    }

    function subscribeToProduct(event){
        const vm = this;
        event.preventDefault();
        vm.processingPurchase = true;
        inAppPurchase.getProducts([vm.selectedProductId]).then(subscribe).catch(vm.displayError).finally(setProcessingFalse);

        function subscribe(){

            return inAppPurchase.subscribe(vm.selectedProductId).then(verifySubscription).then(handleSuccessfulPurchase);

            function verifySubscription(transactionData){
                return Vue.appApi().authorized().account().verifyItunesSubscriptionReceipt({ receipt: transactionData.receipt });
            }
            function handleSuccessfulPurchase(response){
                vm.$emit('paymentSubmittedSuccess');
                vm.isModalShown = false;
            }
        }
        function setProcessingFalse(){
            vm.processingPurchase = false;
        }
    }

    function displayError(error){
        const vm = this;
        if(typeof error === 'string'){
            vm.apiErrors = [error];
        } else if(error.appMessage){
            vm.apiErrors = [error.appMessage];
        } else if(error.message){
            vm.apiErrors = [error.message];
        }
    }
}
