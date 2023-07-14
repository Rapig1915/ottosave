/*
    <div v-tour-highlight="{ name: 'tourName', target: true }"></div> // <-- Setting target to true specifies the element as target for a related tour-popover component
*/

export default {
    bind: setupDirective,
    update: setupDirective
};

function setupDirective(el, binding, vnode){
    const hasMultipleTourStepsAttached = Array.isArray(binding.value);
    if(hasMultipleTourStepsAttached){
        binding.value.forEach(linkWithTourStep);
    } else {
        linkWithTourStep(binding.value);
    }

    function linkWithTourStep(value){
        if(value.target === true){
            // Emit immediately in case popover is already mounted. If not, wait for the mounted event.
            vnode.context.$root.$emit(value.name + '-target-ready', { element: el });

            vnode.context.$root.$on(value.name + '-popover-mounted', function(){
                vnode.context.$root.$emit(value.name + '-target-ready', { element: el });
            });

        }
    }
}
