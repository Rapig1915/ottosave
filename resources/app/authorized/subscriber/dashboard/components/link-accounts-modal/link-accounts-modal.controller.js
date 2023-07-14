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
        Vue.clientStorage.getItem(`link_accounts_modal_shown_at`).then(showModalEveryTwoHours);

        function showModalEveryTwoHours(lastOpened){
            const hasNotBeenOpened = lastOpened === null;
            const twoHoursFromLastShowing = Vue.moment(lastOpened).add(2, 'hours');
            const wasShownMoreThanTwoHoursAgo = Vue.moment().isAfter(twoHoursFromLastShowing);
            if(hasNotBeenOpened || wasShownMoreThanTwoHoursAgo){
                Vue.clientStorage.setItem(`link_accounts_modal_shown_at`, Vue.moment().format());
                vm.$refs.linkAccountsModalRef.show();
            }
        }
    }
}
