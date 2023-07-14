export default {
    props: {
        color: {
            type: String,
            required: true,
        },
        size: {
            type: String,
            default: '13px',
        },
        icon: {
            type: String,
            default: 'square'
        },
        editable: {
            type: Boolean,
            default: false
        }
    },
    mounted(){
        this.isMounted = true;
    },
    data(){
        return {
            isMounted: false
        };
    },
    computed: {
        iconClass(){
            const vm = this;
            let iconClass = 'icon-dym-accounts';
            if(vm.icon === 'credit-card'){
                iconClass = 'icon-dym-credit-card';
            }
            return iconClass;
        },
        iconStyles(){
            const vm = this;
            return {
                'font-size': vm.size,
                'cursor': (vm.editable ? 'pointer' : 'default')
            };
        }
    },
    methods: {
        openColorPicker(){
            const vm = this;
            if(vm.editable){
                vm.$refs.colorPicker.open();
            }
        }
    }
};
