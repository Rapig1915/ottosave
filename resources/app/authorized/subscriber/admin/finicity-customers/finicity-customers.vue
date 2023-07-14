<template>
    <b-container>
        <div class="finicityCustomersComponent">
            <app-message class="mt-2" type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>

            <b-row>
                <b-col cols="12" md="6">
                    <b-card no-body class="mb-4">
                        <b-card-body>
                            <div class="w-100 d-flex justify-content-between">
                                <h2>
                                    Finicity Subscriptions
                                    <info-popover id="finicity-customers-info-popover">
                                        <template slot="title">
                                            Get Finicity Subscriptions
                                        </template>
                                        <template slot="content">
                                            <p>
                                                Get the total/active/idle number of subscribers from Finicity. Idle subscriptions are ones where we don’t have a matching account in our database.
                                            </p>
                                        </template>
                                    </info-popover>
                                </h2>
                                <div class="refreshButton"
                                    v-b-tooltip.hover
                                    title="Refresh Finicity Customers"
                                >
                                    <i class="fas fa-sync-alt" :class="dynamicClassesFinicityRefresh" @click="refreshFinicitySubscriptions"></i>
                                </div>
                            </div>

                            <div v-if="!finicitySubscriptionsEverLoaded" class="py-2">
                                <span>Refresh to see current totals.</span><br/>
                            </div>
                            <template v-else>
                                <div class="py-2">
                                    <span>Total: {{ finicitySubscriptions.total || 0 }}</span><br/>
                                    <span>Active: {{ finicitySubscriptions.active || 0 }}</span><br/>
                                    <span>Idle: {{ finicitySubscriptions.idle || 0 }}</span><br/>
                                </div>

                                <div class="d-flex align-items-center justify-content-end">
                                    <loading-spinner :showSpinner="isInvokingCommand" custom-class="size-auto">
                                        <b-button @click="runCleanUpIdleCustomers(false)" variant="outline-primary" class="ml-2 btn-sm">Clean Up</b-button>
                                        <b-button @click="runCleanUpIdleCustomers(true)" variant="outline-primary" class="ml-2 btn-sm">Clean Up (Check Only)</b-button>
                                    </loading-spinner>
                                </div>
                            </template>
                        </b-card-body>
                    </b-card>
                </b-col>
                <b-col cols="12" md="6">
                    <b-card no-body class="mb-4">
                        <b-card-body>
                            <div class="d-flex justify-content-between">
                                <h2>
                                    System Subscriptions
                                    <info-popover id="system-customers-info-popover">
                                        <template slot="title">
                                            Get System Subscriptions
                                        </template>
                                        <template slot="content">
                                            <p>
                                                Get the total/active/orphaned accounts within our system. Orphaned accounts are accounts within our system that are either downgraded or non-active.
                                            </p>
                                        </template>
                                    </info-popover>
                                </h2>
                                <div class="refreshButton"
                                    v-b-tooltip.hover
                                    title="Refresh System Customers"
                                >
                                    <i class="fas fa-sync-alt" :class="dynamicClassesSystemRefresh" @click="refreshSystemSubscriptions"></i>
                                </div>
                            </div>

                            <div v-if="!systemSubscriptionsEverLoaded" class="py-2">
                                <span>Refresh to see current totals.</span><br/>
                            </div>
                            <template v-else>
                                <div class="py-2">
                                    <span>Total: {{ systemSubscriptions.total || 0 }}</span><br/>
                                    <span>Active: {{ systemSubscriptions.active || 0 }}</span><br/>
                                    <span>Orphaned: {{ systemSubscriptions.orphaned || 0 }}</span><br/>
                                </div>

                                <div class="d-flex align-items-center justify-content-end">
                                    <loading-spinner :showSpinner="isInvokingCommand" custom-class="size-auto">
                                        <b-button @click="runCleanUpOrphanedCustomers(false)" variant="outline-primary" class="ml-2 btn-sm">Clean Up</b-button>
                                        <b-button @click="runCleanUpOrphanedCustomers(true)" variant="outline-primary" class="ml-2 btn-sm btn-outlined">Clean Up (Check Only)</b-button>
                                    </loading-spinner>
                                </div>
                            </template>
                        </b-card-body>
                    </b-card>
                </b-col>

                <b-col cols="12">
                    <b-card no-body class="mb-2">
                        <b-card-body>
                            <div class="d-flex justify-content-between">
                                <h2>
                                    Command Output
                                    <info-popover id="command-history-output-info-popover">
                                        <template slot="title">
                                            Get Command Output
                                        </template>
                                        <template slot="content">
                                            <p>
                                                Command is run on server side and leave output. Turn sync on to get real-time update or download the full content as file.
                                            </p>
                                            <p>
                                                You can save the command code and use it to get output later in this page.
                                            </p>
                                        </template>
                                    </info-popover>
                                </h2>
                            </div>

                            <div class="alert alert-success mx-auto" endAttributes v-if="recentCommands.length">
                                <div v-for="(command, index) in recentCommands" v-bind:key="index">
                                    [{{ command.time }}] Command <a href="#inputCommandCode" @click="startSyncCommandOutputWithCode(command.code)">#{{ command.code }}</a> started to {{ command.job }} [{{ command.command }}]
                                </div>
                            </div>

                            <div class="py-2">
                                <div class="d-flex align-items-stretch" id="inputCommandCode">
                                    <b-input-group class="d-flex align-items-center">
                                        <b-form-input
                                            type="text"
                                            class="mr-2"
                                            :disabled="isSyncingCommandOutput"
                                            v-model="currentCommandCode"
                                            placeholder="Enter Command Code"
                                            @keyup.enter="toggleSyncingCommandOutput(true)"
                                        ></b-form-input>

                                        <b-input-group-append>
                                            <b-form-checkbox button-variant="primary" v-model="isSyncingCommandOutput" name="enable-sync-button" switch>
                                                Turn Sync <b>({{ isSyncingCommandOutput ? 'ON' : 'OFF' }})</b>
                                            </b-form-checkbox>
                                        </b-input-group-append>
                                    </b-input-group>
                                </div>
                            </div>

                            <transition name="fadeHeight">
                                <textarea v-if="isSyncingCommandOutput" id="txt_command_output" class="my-1 w-100" disabled rows="20" v-model="currentCommandOutput">
                                </textarea>
                            </transition>

                            <div class="text-right">
                                <loading-spinner :showSpinner="isDownloadingCommandOutput" custom-class="size-md">
                                    <b-button @click="downloadCommandOutput()" variant="primary" :disabled="!currentCommandCode">Download #{{ currentCommandCode }} Output</b-button>
                                </loading-spinner>
                            </div>
                        </b-card-body>
                    </b-card>
                </b-col>
            </b-row>
            <b-modal
                class="confirm-clean-up-modal"
                v-model="isConfirmingCleanUp"
                size="lg"
                hide-header
                hide-footer
                centered
                static
            >
                <div class="">
                    <i class="fas fa-times text-muted float-right" @click="isConfirmingCleanUp = false"></i>
                    <h3 class="text-center font-weight-normal pt-4 mb-4">Are you sure you want to {{ pendingCommand.job }}? {{ pendingCommand.checkOnly ? '(Check-Only)' : '' }}</h3>
                    <b-button
                        class="mx-auto d-block px-5 mb-3"
                        variant="danger"
                        @click="isConfirmingCleanUp = false, runCommand()"
                    >
                        Confirm
                    </b-button>
                </div>
            </b-modal>
        </div>
    </b-container>
</template>

<script src="./finicity-customers.controller.js"></script>
<style lang="scss" src="./_finicity-customers.scss" scoped></style>
