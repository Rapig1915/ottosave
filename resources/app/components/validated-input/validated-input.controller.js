export default {
    props: [
        'name',
        'type',
        'placeholder',
        'validationErrors',
        'value',
        'readOnly',
    ],
    data: data,
    watch: {
        'inputValue': updateParent,
        'value': syncValue
    },
    created: syncValue
};

function data(){
    return {
        inputValue: ''
    };
}

function updateParent(){
    this.$emit('input', this.inputValue);
}

function syncValue(){
    this.inputValue = this.value;
}
