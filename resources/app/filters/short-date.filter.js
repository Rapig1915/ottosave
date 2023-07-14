export function shortDateFilter(date){
    if(!date){
        return '';
    }
    return Vue.moment(date).format('MM/DD/YYYY');
}
