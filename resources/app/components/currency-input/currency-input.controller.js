import formatAsDecimal from 'vue_root/mixins/formatAsDecimal.mixin';
import { Plugins } from '@capacitor/core';
const { Keyboard } = Plugins;

export default {
    props: {
        disabled: {
            type: Boolean,
            default: false,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
        id: {
            type: String,
            default: '',
        },
        tooltipTitle: {
            type: String,
            default: '',
        },
        value: {
            type: [Number, String],
            default: 0,
        },
        formatOnInput: {
            type: Boolean,
            default: true
        },
        formatOnBlur: {
            type: Boolean,
            default: false
        },
        autofillValue: {
            type: [Number, String],
            default: 0
        },
        enableAutofill: {
            type: Boolean,
            default: false
        },
        placeholder: {
            type: String,
            default: ''
        }
    },
    computed: computed(),
    mixins: [formatAsDecimal],
    methods: getMethods(),
};

function computed(){
    return {
        inputListeners(){
            const vm = this;
            return Object.assign({},
                this.$listeners, {
                    blur: function(event){
                        const id = vm.id;
                        if(vm.formatOnBlur){
                            const value = vm.parseDecimal(event.target.value);
                            vm.$emit('input', value);
                        }
                        vm.$emit('blur', id);
                    },
                    input: function(event){
                        if(vm.formatOnInput){
                            const value = vm.parseDecimal(event.target.value);
                            vm.$emit('input', value);
                        } else {
                            vm.$emit('input', event.target.value);
                        }
                    }
                }
            );
        },
        currentValue: {
            get(){
                if(typeof this.value !== 'undefined'){
                    const valueAsDecimal = this.formatAsDecimal(this.value);
                    return valueAsDecimal.split('-').join('- ');
                }
            },
            set(value){
                return this.value;
            }
        },
        isAutofillPlaceholderShown(){
            const vm = this;
            let isShown = false;
            if(vm.enableAutofill){
                const currentValueAsDecimal = vm.parseDecimal(vm.currentValue);
                const hasCurrentValue = currentValueAsDecimal > 0 || currentValueAsDecimal < 0;
                isShown = !hasCurrentValue;
            }
            return isShown;
        }
    };
}

function getMethods(){
    return {
        setInputValue,
        selectInput
    };
    function setInputValue(payload){
        const id = payload[0];
        const value = payload[1];
        this.$listeners.input(value);
        this.$listeners.blur(id);
    }
    function selectInput(){
        const vm = this;
        if(!vm.disabled){
            const keyboardOptions = { isVisible: true };
            Keyboard.setAccessoryBarVisible(keyboardOptions)
                .catch(ignoreError)
                .finally(handleInputSelect);
        }
        function ignoreError(){
            // ingore error where Keyboard is not implemented e.g. in web app
        }
        function handleInputSelect(){
            vm.$refs['ref-input-element'].select();
            const hasCurrentValue = vm.currentValue > 0 || vm.currentValue < 0;
            const useAutofillValue = !hasCurrentValue && vm.enableAutofill && vm.autofillValue;
            if(useAutofillValue){
                vm.$emit('input', vm.autofillValue);
            }
        }
    }
}
