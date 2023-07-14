/*
    <div v-dym-access="{ permission: 'permissionToCheck', behavior: 'hide/disable', valueToTest: value }"></div>
*/
import subscriptionVerificationMixin from 'vue_root/mixins/subscriptionVerification.mixin';
import store from 'vue_root/app.store';
const { verifySubscriptionStatus, verifySubscriptionPlan } = subscriptionVerificationMixin.methods;

export default {
    bind: setupDirective,
    update: setupDirective
};

function setupDirective(el, binding, vnode){
    const settings = Object.assign(defaultSettings(), binding.value);

    if(settings.permission){
        const userHasPermission = checkPermission(settings);
        if(!userHasPermission){
            removeElementAccess(el, binding, vnode, settings);
        }
    } else {
        throw new Error("The 'access' directive requires a permission to check.");
    }

    function defaultSettings(){
        return {
            permission: null, // string: 'subscriptionPlan' | 'subscriptionStatus' | 'permission'
            behavior: 'hide', // string: 'hide' | 'disable'
            valueToTest: null // string: the value that you want to test, e.g. subscriptionPlan = 'plus'
        };
    }
}

function checkPermission({ permission, valueToTest }){
    let hasPermission = false;
    if(permission === 'subscriptionPlan'){
        hasPermission = verifySubscriptionPlan(valueToTest);
    } else if(permission === 'subscriptionStatus'){
        hasPermission = verifySubscriptionStatus(valueToTest);
    } else if(permission === 'permission'){
        hasPermission = verifyPermission(valueToTest);
    }
    return hasPermission;
}

function removeElementAccess(el, binding, vnode, settings){
    const behavior = settings.behavior;

    if(behavior === 'hide'){
        el.style.display = 'none';
    } else if(behavior === 'disable'){
        el.disabled = true;
    }
}

function verifyPermission(permissionValue){
    const user = store.state.guest.user.user;
    return user.current_account_user.all_permission_names.includes(permissionValue);
}
