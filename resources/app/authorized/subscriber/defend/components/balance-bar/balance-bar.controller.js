export default {
    props: {
        bankAccount: {
            type: Object,
            requried: true
        },
        allocations: {
            type: Array,
            default: []
        },
        userAllocations: {
            type: Array,
            default: []
        }
    },
    data,
    computed: getComputed(),
    mounted: mounted,
    methods: getMethods()
};

function data(){
    return {
        balanceAt100PercentWidth: 0,
        allocationAmount: 0,
        balanceSynced: false,
        defendedBankAccount: null
    };
}

function getComputed(){
    return {
        allocationsForBankAccount(){
            const vm = this;
            return vm.allocations.filter(allocation => allocation.from_account.id === vm.bankAccount.id || allocation.bank_account.id === vm.bankAccount.id);
        },
        transferAmount(){
            const vm = this;
            return vm.allocationsForBankAccount.reduce(sumAllocations, new Decimal(0)).toNumber();

            function sumAllocations(accumulator, allocation){
                if(allocation.transferred){
                    return accumulator.toDecimalPlaces(2);
                } else if(allocation.from_account.id === vm.bankAccount.id){
                    return accumulator.minus(allocation.amount || 0).toDecimalPlaces(2);
                } else if(allocation.bank_account.id === vm.bankAccount.id){
                    return accumulator.plus(allocation.amount || 0).toDecimalPlaces(2);
                }
            }
        },
        displayedBalance(){
            const vm = this;
            const currentBalance = vm.bankAccount.type === 'credit' ? Math.abs(vm.bankAccount.balance_current) : vm.bankAccount.balance_current;
            return new Decimal(currentBalance || 0).plus(vm.bankAccount.allocation_balance_adjustment || 0).toDecimalPlaces(2).toNumber();
        },
        projectedBalance(){
            const vm = this;
            return vm.displayedBalance + vm.transferAmount;
        },
        darkBalancePercentage(){
            const vm = this;
            let balancePercentage = 100;
            if(vm.transferAmount < 0){
                balancePercentage = Math.ceil((vm.projectedBalance) / vm.balanceAt100PercentWidth * 100);
            } else {
                balancePercentage = Math.ceil((vm.displayedBalance) / vm.balanceAt100PercentWidth * 100);
            }
            return Math.max(Math.min(balancePercentage, 100), 0);
        },
        lightBalancePercentage(){
            const vm = this;
            let balancePercentage = 100;
            if(vm.transferAmount < 0){
                balancePercentage = Math.ceil((vm.displayedBalance) / vm.balanceAt100PercentWidth * 100);
            } else {
                balancePercentage = Math.ceil((vm.projectedBalance) / vm.balanceAt100PercentWidth * 100);
            }
            return Math.max(Math.min(balancePercentage, 100), 0);
        },
    };
}

function mounted(){
    // set initial bar graph size
    const vm = this;
    const balanceRoundedToNextThousand = Math.ceil(Math.max(vm.displayedBalance, vm.projectedBalance, 1) / 1000) * 1000;
    vm.balanceAt100PercentWidth = balanceRoundedToNextThousand;
    Velocity(vm.$refs.darkBalance, { width: '0px' }, { duration: 0 });
    Velocity(vm.$refs.lightBalance, { width: '0px' }, { duration: 0 });
    vm.updateBarGraph();
    vm.setAllocationAmount();
    vm.setDefendedBankAccount();
}

function getMethods(){
    return {
        updateBarGraph,
        animateBalance,
        setAllocationAmount,
        setDefendedBankAccount
    };

    function updateBarGraph(){
        const vm = this;
        vm.animateBalance(vm.$refs.darkBalance, vm.darkBalancePercentage);
        vm.animateBalance(vm.$refs.lightBalance, vm.lightBalancePercentage);
        vm.balanceSynced = vm.projectedBalance === vm.displayedBalance;
    }

    function animateBalance(element, widthPercantage){
        var propertiesToAnimate = {
            width: `${widthPercantage}%`
        };
        if(widthPercantage === 100){
            propertiesToAnimate.borderRadius = '3px';
        } else {
            propertiesToAnimate.borderTopRightRadius = '0px';
            propertiesToAnimate.borderBottomRightRadius = '0px';
        }
        Velocity(element, propertiesToAnimate, { duration: 1000 });
    }

    function setAllocationAmount(){
        const vm = this;

        const userAllocationsForBankAccount = vm.userAllocations.filter(byBankAccount);
        vm.allocationAmount = userAllocationsForBankAccount.reduce(sumUserAllocations, new Decimal(0)).toNumber();

        function byBankAccount(userAllocation){
            if(vm.bankAccount.slug === 'income_deposit'){
                return userAllocation.transferred_from_id === vm.bankAccount.id;
            } else {
                return userAllocation.bank_account_id === vm.bankAccount.id;
            }
        }
        function sumUserAllocations(accumulator, userAllocation){
            const allocationAmount = userAllocation.amount || 0;
            if(vm.bankAccount.slug === 'income_deposit'){
                return accumulator.minus(allocationAmount).toDecimalPlaces(2);
            } else {
                return accumulator.plus(allocationAmount).toDecimalPlaces(2);
            }
        }
    }

    function setDefendedBankAccount(){
        const vm = this;
        vm.defendedBankAccount = JSON.parse(JSON.stringify(vm.bankAccount));
        vm.defendedBankAccount.balance_available = vm.projectedBalance;
        vm.defendedBankAccount.allocation_balance_adjustment = vm.allocationAmount;
    }
}
