<template>
    <div class="subscriber-view container-fluid">
        <dashboard-layout v-if="!isMiniLayoutMode">
            <b-container>
                <b-alert variant="info" :show="hasMultipleAccountAccess && !!currentAccountName" class="subscriber-view__account-name">
                    {{ currentAccountName }}
                </b-alert>
                <b-alert variant="info" :show="isInDemoMode" class="subscriber-view__demo-mode-alert">
                    <span>You are currently in <strong class="text-primary">DEMO MODE.</strong></span>
                    <b-button variant="primary" @click="isConfirmingDemoExit = true">
                        Start FREE trial
                    </b-button>
                </b-alert>
            </b-container>
            <verification-required-warning />
            <router-view></router-view>

            <b-modal
                class="exit-demo-modal"
                v-model="isConfirmingDemoExit"
                size="lg"
                hide-header
                hide-footer
                centered
                static
            >
                <div class="">
                    <i class="fas fa-times text-muted float-right" @click="isConfirmingDemoExit = false"></i>
                    <h3 class="text-center font-weight-normal pt-4 mb-4">You are now leaving DEMO MODE. All demo data will be removed.</h3>
                    <b-button
                        class="mx-auto d-block px-5 mb-3"
                        variant="primary"
                        :disabled="isStartingTrial"
                        @click="exitDemoMode"
                    >
                        <loading-spinner :show-spinner="isStartingTrial" custom-class="size-auto">
                            Continue
                        </loading-spinner>
                    </b-button>
                    <app-message type="error" class="mb-3" :messages="trialErrors" @close="trialErrors = []" />
                </div>
            </b-modal>
        </dashboard-layout>
        <div v-else>
            <router-view></router-view>
        </div>
    </div>
</template>
<script src="./subscriber.controller.js"></script>
<style lang="scss" src="./_subscriber.scss" scoped></style>
