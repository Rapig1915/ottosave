export default {
    components: {},
    props: {
        targetRef: {
            type: HTMLElement,
            required: true
        },
        currentColor: {
            type: String,
            default: ''
        },
        closeOnSelect: {
            type: Boolean,
            default: false
        }
    },
    data,
    computed: getComputed(),
    created,
    methods: getMethods()
};

function data(){
    return {
        isOpen: false,
        availableColors: [
            { value: 'custom-1', name: 'Dark blue', hexCode: '#2B14BC' },
            { value: 'pink', name: 'Blue', hexCode: '#133CF2' },
            { value: 'purple', name: 'Light Blue', hexCode: '#1778FF' },
            { value: 'yellow', name: 'Aqua', hexCode: '#09EAF2' },
            { value: 'green', name: 'Green', hexCode: '#00D19D' },
            { value: 'violet', name: 'Purple', hexCode: '#8A179E' },
            { value: 'orange', name: 'Pink', hexCode: '#FF346C' },
            { value: 'cyan', name: 'Yellow', hexCode: '#FFB617' },
            { value: 'gray-alt', name: 'Black', hexCode: '#0B102A' },
            { value: 'gray', name: 'Silver', hexCode: '#D6D5D5' },
            { value: 'gold', name: 'Gold', hexCode: '#D4AF37' },
            { value: 'bronze', name: 'Bronze', hexCode: '#CD7F32' },
        ]
    };
}

function getComputed(){
    return {};
}

function created(){}

function getMethods(){
    return {
        selectColor(color){
            const vm = this;
            vm.$emit('input', color.value);
            if(vm.closeOnSelect){
                vm.isOpen = false;
            }
        },
        open(){
            const vm = this;
            vm.isOpen = true;
        },
        focusColorPicker(){
            const vm = this;
            vm.$refs.colorPicker.focus();
        },
        closePicker(event){
            const vm = this;
            vm.isOpen = false;
        }
    };
}
