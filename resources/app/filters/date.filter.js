// TODO: If this is intended for other locales, internationalization will be needed
export function dateFilter(_date){
    if(!_date){
        return '';
    }
    return Vue.moment(_date).locale('en').format('MMMM D, YYYY');
}
