export default {
    components: {},
    data,
    computed: getComputed(),
    created,
    methods: getMethods(),
    destroyed
};

function data(){
    return {
        errorMessages: [],

        isRefreshingFinicitySubscriptions: false,
        isRefreshingSystemSubscriptions: false,

        finicitySubscriptions: null,
        systemSubscriptions: null,

        isConfirmingCleanUp: false,
        pendingCommand: {
            command: '',
            job: '',
            checkOnly: null,
        },
        recentCommands: [],
        isInvokingCommand: false,

        currentCommandCode: null,
        isSyncingCommandOutput: false,
        isDownloadingCommandOutput: false,
        currentCommandOutput: '',

        syncCommandOutputSyncHandlerIntervalId: null,
    };
}

function getComputed(){
    return {
        dynamicClassesFinicityRefresh(){
            const vm = this;
            const dynamicClasses = [];
            if(vm.isRefreshingFinicitySubscriptions){
                dynamicClasses.push('fa-spin disabled');
            }
            return dynamicClasses;
        },
        dynamicClassesSystemRefresh(){
            const vm = this;
            const dynamicClasses = [];
            if(vm.isRefreshingSystemSubscriptions){
                dynamicClasses.push('fa-spin disabled');
            }
            return dynamicClasses;
        },
        finicitySubscriptionsEverLoaded(){
            return !!this.finicitySubscriptions;
        },
        systemSubscriptionsEverLoaded(){
            return !!this.systemSubscriptions;
        }
    };
}

function created(){
    const vm = this;
    vm.initSyncCommandOutputHandler();
}

function destroyed(){
    const vm = this;
    if(vm.syncCommandOutputSyncHandlerIntervalId){
        clearInterval(vm.syncCommandOutputSyncHandlerIntervalId);
    }
}

function getMethods(){
    return {
        displayErrorMessage,
        refreshFinicitySubscriptions,
        refreshSystemSubscriptions,
        runCommand,
        runCleanUpIdleCustomers,
        runCleanUpOrphanedCustomers,
        initSyncCommandOutputHandler,
        startSyncCommandOutputWithCode,
        toggleSyncingCommandOutput,
        readCommandOuput,
        downloadCommandOutput,
    };

    function displayErrorMessage(error){
        const vm = this;
        const isValidationError = error && error.status === 422 && error.data;
        if(isValidationError){
            vm.validationErrors = error.data.errors;
        } else if(error){
            const errorMessage = typeof error === 'string' ? error : (error.appMessage || error.message);
            vm.errorMessages.push(errorMessage);
        }
    }

    function initSyncCommandOutputHandler(){
        const vm = this;
        vm.syncCommandOutputSyncHandlerIntervalId = setInterval(vm.readCommandOuput, 2000);
    }

    function refreshFinicitySubscriptions(){
        const vm = this;
        vm.isRefreshingFinicitySubscriptions = true;
        Vue.appApi().authorized().admin().subscriptions().getFinicitySubscriptions()
            .then(setFinicitySubscriptions).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setFinicitySubscriptions(response){
            const isDataValid = response && response.data;
            if(isDataValid){
                vm.finicitySubscriptions = response.data;
            } else {
                vm.finicitySubscriptions = {};
            }
        }

        function resetLoadingState(){
            vm.isRefreshingFinicitySubscriptions = false;
        }
    }

    function refreshSystemSubscriptions(){
        const vm = this;
        vm.isRefreshingSystemSubscriptions = true;
        Vue.appApi().authorized().admin().subscriptions().getSystemSubscriptions()
            .then(setSystemSubscriptions).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function setSystemSubscriptions(response){
            const isDataValid = response && response.data;
            if(isDataValid){
                vm.systemSubscriptions = response.data;
            } else {
                vm.systemSubscriptions = {};
            }
        }

        function resetLoadingState(){
            vm.isRefreshingSystemSubscriptions = false;
        }
    }

    function startSyncCommandOutputWithCode(newCommandCode){
        const vm = this;
        vm.currentCommandCode = newCommandCode;
        vm.toggleSyncingCommandOutput(true);
    }

    function toggleSyncingCommandOutput(forceSetMode = null){
        const vm = this;
        if(forceSetMode === null){
            vm.isSyncingCommandOutput = !vm.isSyncingCommandOutput;
        } else {
            vm.isSyncingCommandOutput = forceSetMode;
        }
    }

    function runCleanUpIdleCustomers(checkOnly = false){
        const vm = this;
        vm.pendingCommand.command = 'dym:remove-idle-finicity-customers';
        vm.pendingCommand.job = 'clean up idle finicity customers';
        vm.pendingCommand.checkOnly = checkOnly;
        this.isConfirmingCleanUp = true;
    }

    function runCleanUpOrphanedCustomers(checkOnly = false){
        const vm = this;
        vm.pendingCommand.command = 'dym:remove-orphan-finicity-customers';
        vm.pendingCommand.job = 'clean up orphaned system customers';
        vm.pendingCommand.checkOnly = checkOnly;
        this.isConfirmingCleanUp = true;
    }

    function runCommand(){
        const vm = this;
        const isValidCommand = vm.pendingCommand.command && vm.pendingCommand.job;
        if(!isValidCommand){
            vm.displayErrorMessage('You tried to run an invalid command.');
            return;
        }

        const command = `${vm.pendingCommand.command} --checkonly=${vm.pendingCommand.checkOnly ? 'yes' : 'no'}`;
        vm.isInvokingCommand = true;
        Vue.appApi().authorized().admin().commands().invoke(command)
            .then(handleNewCommandStarted).catch(vm.displayErrorMessage).finally(resetLoadingState);

        function handleNewCommandStarted(response){
            const success = response && response.data && response.data.code;

            if(success){
                const now = Vue.moment().format('hh:mm:ss');
                vm.recentCommands.push({
                    time: now,
                    job: vm.pendingCommand.job,
                    command: command,
                    code: response.data.code
                });

                vm.startSyncCommandOutputWithCode(response.data.code);
            }
        }

        function resetLoadingState(){
            vm.isInvokingCommand = false;
            vm.pendingCommand.command = '';
            vm.pendingCommand.job = '';
        }
    }

    function readCommandOuput(isDownload = false){
        const vm = this;
        const canRead = vm.currentCommandCode && (isDownload || vm.isSyncingCommandOutput);
        if(!canRead){
            return;
        }

        if(isDownload){
            vm.isDownloadingCommandOutput = true;
        }
        Vue.appApi().authorized().admin().commands().getOutput(vm.currentCommandCode)
            .then(setCommandOutput).catch(vm.displayErrorMessage).finally(resetLoadingState);

        const textAreaOutput = document.getElementById('txt_command_output');
        function setCommandOutput(response){
            if(response.data){
                if(isDownload){
                    const fileName = `Output-${vm.currentCommandCode}.log`;
                    saveAsFile(fileName, response.data);
                } else {
                    const shouldScroll = textAreaOutput.scrollTop >= textAreaOutput.scrollHeight - textAreaOutput.clientHeight;
                    vm.currentCommandOutput = response.data;
                    if(shouldScroll){
                        scrollOutputToBottomIfNeeded();
                    }
                }
            } else {
                vm.currentCommandOutput = '';
            }
        }

        function scrollOutputToBottomIfNeeded(){
            textAreaOutput.scrollTop = textAreaOutput.scrollHeight;
        }

        function saveAsFile(fileName, data){
            const blob = new Blob([data], { type: 'text' });
            if(window.navigator.msSaveOrOpenBlob){
                window.navigator.msSaveBlob(blob, fileName);
            } else {
                const elem = window.document.createElement('a');
                elem.href = window.URL.createObjectURL(blob);
                elem.download = fileName;
                document.body.appendChild(elem);
                elem.click();
                document.body.removeChild(elem);
            }
        }

        function resetLoadingState(){
            if(isDownload){
                vm.isDownloadingCommandOutput = false;
            }
        }
    }

    function downloadCommandOutput(){
        const vm = this;
        if(!vm.currentCommandCode){
            vm.displayErrorMessage('Please enter a command code.');
            return;
        }

        vm.readCommandOuput(true);
    }
}
