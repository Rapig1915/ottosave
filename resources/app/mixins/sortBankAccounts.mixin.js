export default {
    methods: {
        byModifiedStoreOrder
    }
};

function byModifiedStoreOrder(a, b){
    const vm = this;
    const aIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === a.id);
    const bIndex = vm.$store.state.authorized.bankAccounts.bankAccounts.findIndex(({ id }) => id === b.id);
    let position = 0;
    if([a.slug, b.slug].includes('income_deposit')){
        position = a.slug === 'income_deposit' ? -1 : 1;
    } else if([a.slug, b.slug].includes('monthly_bills')){
        position = a.slug === 'monthly_bills' ? -1 : 1;
    } else if([a.slug, b.slug].includes('everyday_checking')){
        position = a.slug === 'everyday_checking' ? -1 : 1;
    } else if([a.type, b.type].includes('credit')){
        position = a.type === 'credit' ? 1 : -1;
    } else if([a.slug, b.slug].includes('cc_payoff')){
        position = a.slug === 'cc_payoff' ? 1 : -1;
    } else if(aIndex >= 0 && bIndex >= 0){
        position = aIndex < bIndex ? -1 : 1;
    }
    return position;
}
