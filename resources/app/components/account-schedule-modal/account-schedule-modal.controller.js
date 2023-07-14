import UnsavedChangesModal from 'vue_root/components/unsaved-changes-modal/unsaved-changes-modal';
import ConfirmDeleteModal from 'vue_root/components/confirm-delete-modal/confirm-delete-modal';

export default {
    components: {
        UnsavedChangesModal,
        ConfirmDeleteModal
    },
    props: {
        bankAccount: {
            type: Object,
            required: true,
        },
    },
    data,
    created,
    computed: getComputed(),
    watch: getWatchers(),
    methods: getMethods(),
};

function data(){
    return {
        defaultScheduleItem: {
            amount_monthly: '',
            amount_total: undefined,
            bank_account_id: null,
            description: '',
            id: null,
            isCalculatingMonthlyAmount: false,
            isDirty: true,
            isSaving: false,
            isDeleting: false,
            is_selected: true,
            type: 'monthly',
            approximate_due_date: null,
        },
        defaultTypeOptions: [
            { value: null, text: 'frequency', disabled: true },
            { value: 'monthly', text: 'Monthly' },
            { value: 'quarterly', text: 'Quarterly' },
            { value: 'yearly', text: 'Yearly' },
            { value: 'target_date', text: 'Target Date' },
        ],
        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        daysOptions: [],
        monthsOptions: [],
        errorMessages: [],
        scheduleItems: [],
        pristineScheduleItems: [],
        isUpdatingDefenseInterval: false,
        isCloseConfirmed: false,
        isBulkSaveInProgress: false,
        loadingScheduleItems: false,
        isModalShown: false,
    };
}

function getComputed(){
    return {
        scheduledAmount(){
            const vm = this;
            return new Decimal(vm.totalSelectedItems || 0).dividedBy(vm.projectedDefensesPerMonth).toDecimalPlaces(2).toNumber();
        },
        totalSelectedItems(){
            const vm = this;
            const selectedScheduleItems = vm.scheduleItems.filter(({ is_selected }) => is_selected);
            const total = selectedScheduleItems.reduce((sum, scheduleItem) => {
                return sum.plus(scheduleItem.amount_monthly || 0).toDecimalPlaces(2);
            }, new Decimal(0));
            return total.toNumber();
        },
        bankAccountId(){
            const vm = this;
            return vm.bankAccount.id;
        },
        bankAccountName(){
            const vm = this;
            return vm.bankAccount.name;
        },
        selectedDefensesPerMonth: {
            get(){
                const vm = this;
                return vm.$store.getters.user.current_account.projected_defenses_per_month;
            },
            set(value){}
        },
        projectedDefensesPerMonth(){
            const vm = this;
            return vm.$store.getters.user.current_account.projected_defenses_per_month;
        },
        dirtyScheduleItems(){
            const vm = this;
            return vm.scheduleItems.filter(({ isDirty, isSaving }) => isDirty && !isSaving);
        },
        isEveryItemSelected: {
            get(){
                const vm = this;
                return vm.scheduleItems.length && vm.scheduleItems.every(({ is_selected }) => is_selected);
            },
            set(value){
                const vm = this;
                vm.scheduleItems.forEach((scheduleItem) => {
                    scheduleItem.is_selected = value;
                });
            }
        }
    };
}

function created(){
    const vm = this;
    initializeDayOptions();
    initializeMonthOptions();

    function initializeDayOptions(){
        vm.daysOptions = [
            { value: null, text: '~ due date', disabled: true }
        ];
        for(var i = 1; i <= 31; i++){
            vm.daysOptions.push({ value: i, text: i });
        }
    }
    function initializeMonthOptions(){
        vm.monthsOptions = [
            { value: null, text: '~ due date', disabled: true }
        ];
        vm.months.forEach(month => {
            vm.monthsOptions.push({ value: month, text: month });
        });
    }
}

function getWatchers(){
    return {
        bankAccountId: {
            handler: function(newVal, oldVal){
                const vm = this;
                vm.loadScheduleItems(newVal).then(vm.resetWorkingCopyOfScheduleItems);
            },
            immediate: true,
        },
        loadingScheduleItems: {
            handler: function(newVal){
                const vm = this;
                vm.$emit('loading', newVal);
            },
            immediate: true
        },
        pristineScheduleItems: {
            handler: function(newVal){
                const vm = this;
                vm.$emit('schedule-items-updated', newVal);
            },
            immediate: true
        },
        scheduledAmount: {
            handler: function(newVal){
                const vm = this;
                vm.$emit('total-updated', newVal);
            },
            immediate: true
        }
    };
}

function getMethods(){
    return {
        show,
        loadScheduleItems,
        resetWorkingCopyOfScheduleItems,
        sortScheduleItems,
        initializeScheduleItemDisplayProperties,
        addScheduleItem,
        cancelChangesToScheduleItem,
        deleteScheduleItem,
        onChangeTotalTypeOrDate,
        saveScheduleItem,
        updateDefensesPerMonth,
        displayErrorMessage,
        confirmUnsavedChanges,
        closeWithoutSaving,
        bulkSave,
    };
    function show(){
        const vm = this;
        vm.$refs.bankAccountScheduleModal.show();
    }

    function loadScheduleItems(bankAccountId){
        const vm = this;
        if(bankAccountId > 0){
            vm.loadingScheduleItems = true;
            vm.defaultScheduleItem.bank_account_id = bankAccountId;
            return Vue.appApi().authorized().bankAccount(bankAccountId).scheduleItem().get().then(setScheduleItems).catch(vm.displayErrorMessage).finally(resetLoadingState);
        } else {
            return Promise.resolve();
        }

        function setScheduleItems(response){
            vm.pristineScheduleItems = response.data;
        }
        function resetLoadingState(){
            vm.loadingScheduleItems = false;
        }
    }

    function resetWorkingCopyOfScheduleItems(){
        const vm = this;
        const newWorkingCopy = JSON.parse(JSON.stringify(vm.pristineScheduleItems)).map(vm.initializeScheduleItemDisplayProperties);
        vm.scheduleItems = vm.sortScheduleItems(newWorkingCopy);
    }

    function sortScheduleItems(scheduleItems){
        const vm = this;
        return scheduleItems.sort(byTypeAndDueDate);

        function byTypeAndDueDate(a, b){
            const typeOrder = ['monthly', 'quarterly', 'yearly', 'target_date'];
            let sort = typeOrder.indexOf(a.type) - typeOrder.indexOf(b.type);
            if(sort === 0){
                if(a.type === 'monthly' || a.type === 'quarterly'){
                    if(!a.approximate_due_date){
                        sort = 1;
                    } else if(!b.approximate_due_date){
                        sort = -1;
                    } else {
                        sort = a.approximate_due_date - b.approximate_due_date;
                    }
                } else if(a.type === 'yearly'){
                    if(!vm.months.includes(a.approximate_due_date)){
                        sort = 1;
                    } else if(!vm.months.includes(b.approximate_due_date)){
                        sort = -1;
                    } else {
                        sort = vm.months.indexOf(a.approximate_due_date) - vm.months.indexOf(b.approximate_due_date);
                    }
                } else {
                    if(!a.date_end){
                        sort = 1;
                    } else if(!b.date_end){
                        sort = -1;
                    } else {
                        sort = Vue.moment(a.date_end).isAfter(b.date_end) ? 1 : -1;
                    }
                }
            }
            return sort;
        }
    }

    function initializeScheduleItemDisplayProperties(scheduleItem){
        scheduleItem.isCalculatingMonthlyAmount = false;
        scheduleItem.isDirty = false;
        scheduleItem.isSaving = false;
        scheduleItem.isDeleting = false;
        scheduleItem.is_selected = true;
        return scheduleItem;
    }

    function addScheduleItem(){
        const vm = this;
        vm.scheduleItems.push(JSON.parse(JSON.stringify(vm.defaultScheduleItem)));
    }

    function cancelChangesToScheduleItem(scheduleItemIndex){
        const vm = this;
        const scheduleItem = vm.scheduleItems[scheduleItemIndex];

        if(scheduleItem.id){
            const pristineScheduleItem = vm.pristineScheduleItems.find(({ id }) => id === scheduleItem.id);
            const clonedScheduleItem = JSON.parse(JSON.stringify(pristineScheduleItem));
            vm.initializeScheduleItemDisplayProperties(clonedScheduleItem);
            Object.assign(vm.scheduleItems[scheduleItemIndex], clonedScheduleItem);
        } else {
            vm.scheduleItems.splice(scheduleItemIndex, 1);
        }
    }

    function deleteScheduleItem(scheduleItemIndex){
        const vm = this;
        const scheduleItem = vm.scheduleItems[scheduleItemIndex];
        scheduleItem.isDeleting = true;
        vm.$refs.confirmDeleteModal.openModal().then(removeScheduleItem).catch(cancelDelete);
        function removeScheduleItem(){
            const scheduleItemId = scheduleItem.id;

            if(scheduleItemId){
                Vue.appApi().authorized().bankAccount().scheduleItem().destroy(vm.scheduleItems[scheduleItemIndex].id)
                    .then(removeScheduleItemFromDisplay)
                    .catch(vm.displayErrorMessage)
                    .finally(resetLoadingState);
            } else {
                removeScheduleItemFromDisplay();
            }
            function removeScheduleItemFromDisplay(response){
                if(scheduleItemId){
                    scheduleItemIndex = vm.scheduleItems.findIndex(({ id }) => id === scheduleItemId);
                }
                vm.scheduleItems.splice(scheduleItemIndex, 1);
                if(response){
                    const pristineScheduleItemIndex = vm.pristineScheduleItems.findIndex(({ id }) => id === scheduleItemId);
                    vm.pristineScheduleItems.splice(pristineScheduleItemIndex, 1);
                }
            }
            function resetLoadingState(){
                scheduleItem.isDeleting = false;
            }
        }
        function cancelDelete(){
            scheduleItem.isDeleting = false;
        }
    }

    function onChangeTotalTypeOrDate(scheduleItem, changeType){
        const vm = this;

        scheduleItem.isCalculatingMonthlyAmount = true;
        scheduleItem.isDirty = true;
        if(changeType === 'type'){
            scheduleItem.approximate_due_date = null;
        }

        if(scheduleItem.sourceAppHttpCancelToken){
            scheduleItem.sourceAppHttpCancelToken.cancel();
            scheduleItem.sourceAppHttpCancelToken = false;
        }
        const promiseCalculateMonthlyAmount = Vue.appApi().authorized().bankAccount().scheduleItem().calculateMonthlyAmount(scheduleItem);
        scheduleItem.sourceAppHttpCancelToken = promiseCalculateMonthlyAmount.sourceAppHttpCancelToken;
        promiseCalculateMonthlyAmount
            .then(updateMonthlyAmount)
            .catch(vm.displayErrorMessage)
            .finally(_ => {
                scheduleItem.isCalculatingMonthlyAmount = false;
                scheduleItem.sourceAppHttpCancelToken = false;
            });

        function updateMonthlyAmount(response){
            scheduleItem.amount_monthly = response.data.amount_monthly;
        }
    }

    function saveScheduleItem(scheduleItem){
        const vm = this;

        scheduleItem.isSaving = true;
        return Vue.appApi().authorized().bankAccount().scheduleItem().store(scheduleItem)
            .then(updateScheduleItem)
            .catch(vm.displayErrorMessage)
            .finally(clearFlags);

        function updateScheduleItem(response){
            scheduleItem.id = response.data.id;
            scheduleItem.amount_monthly = response.data.amount_monthly;
            scheduleItem.isDirty = false;
            const pristineScheduleItemIndex = vm.pristineScheduleItems.findIndex(({ id }) => id === scheduleItem.id);
            if(pristineScheduleItemIndex >= 0){
                vm.pristineScheduleItems.splice(pristineScheduleItemIndex, 1, response.data);
            } else {
                vm.pristineScheduleItems.push(response.data);
            }
        }

        function clearFlags(){
            scheduleItem.isSaving = false;
        }
    }

    function updateDefensesPerMonth(projectedDefensesPerMonth){
        const vm = this;
        projectedDefensesPerMonth = Array.isArray(projectedDefensesPerMonth) ? projectedDefensesPerMonth[projectedDefensesPerMonth.length - 1] : projectedDefensesPerMonth;
        if(projectedDefensesPerMonth){
            vm.isUpdatingDefenseInterval = true;
            const payload = {
                projected_defenses_per_month: projectedDefensesPerMonth
            };
            return Vue.appApi().authorized().account().patch(payload).then(updateState).catch(vm.displayErrorMessage).finally(resetLoadingState);
        }

        function updateState(response){
            vm.$store.commit('user/SET_CURRENT_ACCOUNT', response.data);
        }

        function resetLoadingState(){
            vm.isUpdatingDefenseInterval = false;
        }
    }

    function displayErrorMessage(error){
        const vm = this;
        if(error.appMessage){
            vm.errorMessages.push(error.appMessage);
        }
    }

    function confirmUnsavedChanges(event){
        const vm = this;
        const hasUnsavedChanges = vm.dirtyScheduleItems.length;
        if(!vm.isCloseConfirmed && hasUnsavedChanges){
            event.preventDefault();
            vm.$refs.confirmUnsavedChangesModal.show();
        }
    }

    function closeWithoutSaving(){
        const vm = this;
        vm.isCloseConfirmed = true;
        vm.$refs.confirmUnsavedChangesModal.hide();
        vm.$refs.bankAccountScheduleModal.hide();
        vm.resetWorkingCopyOfScheduleItems();
    }

    function bulkSave(closeModal = true){
        const vm = this;
        vm.isBulkSaveInProgress = true;
        const savePromises = vm.dirtyScheduleItems.map(vm.saveScheduleItem);
        return Promise.all(savePromises).then(closeModals).catch(vm.displayApiErrors).finally(resetState);

        function closeModals(){
            vm.scheduleItems = vm.sortScheduleItems(vm.scheduleItems);
            if(closeModal){
                vm.isCloseConfirmed = true;
                vm.$refs.bankAccountScheduleModal.hide();
            }
        }
        function resetState(){
            vm.isBulkSaveInProgress = false;
            vm.$refs.confirmUnsavedChangesModal.hide();
        }
    }
}
