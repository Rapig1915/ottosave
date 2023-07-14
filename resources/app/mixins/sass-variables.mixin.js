import sassVariables from 'vue_root/assets/scss/_variables.module.scss';

export default {
    data(){
        return {
            sassVariables
        };
    },
    methods: getMethods()
};

function getMethods(){
    return {
        getAccountColor
    };

    function getAccountColor(bankAccount){
        const vm = this;
        let color = vm.sassVariables.primary;
        if(bankAccount.color){
            const colorParts = bankAccount.color.split('-');
            colorParts.forEach((string, index) => {
                colorParts[index] = string.charAt(0).toUpperCase() + string.slice(1);
            });
            color = vm.sassVariables[`accountColor${colorParts.join('')}`];
        }
        return color;
    }
}
