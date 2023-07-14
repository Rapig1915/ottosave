export default {
    props: {
        logoLeftOnSmallScreen: {
            type: Boolean,
            default: false,
        },
        logoSize: {
            type: String,
            default: 'md',
        },
        linkLocation: {
            type: Object,
            required: false
        }
    },
};
