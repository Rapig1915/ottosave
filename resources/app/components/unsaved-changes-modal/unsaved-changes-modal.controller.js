export default {
    props: {
        isSavingChanges: {
            type: Boolean,
            required: true
        }
    },
    methods: {
        show(){
            const vm = this;
            vm.$refs.confirmUnsavedChangesModal.show();
        },
        hide(){
            const vm = this;
            vm.$refs.confirmUnsavedChangesModal.hide();
        },
    }
};
