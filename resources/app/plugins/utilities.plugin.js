export default utilitiesPlugin();

function utilitiesPlugin(){
    return {
        install
    };

    function install(Vue, options){
        Vue.dymUtilities = {
            cloneObject
        };

        function cloneObject(object){
            return JSON.parse(JSON.stringify(object));
        }
    }
}
