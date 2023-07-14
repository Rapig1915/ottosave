export default {
    methods: getMethods(),
};

function getMethods(){
    return {
        canUser,
    };

    function canUser(permission){
        return this.$store.getters['user/hasPermissionTo'](permission);
    }
}
