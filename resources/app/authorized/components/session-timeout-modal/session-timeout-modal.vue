<template>
    <b-modal
        v-model="displaySessionTimeoutModal"
        hide-header
        lazy
        centered
        no-close-on-backdrop
        no-close-on-esc
    >
        <b-container>
            <h3><i class="fas fa-exclamation-circle text-danger"></i> Session Warning</h3>
            <div class="text-dark">
                Would you like to continue using the site?
            </div>
        </b-container>
        <div slot='modal-footer' class="mx-auto">
            <b-button variant="outline-secondary" @click="forceLogout" class="mx-2">
                Sign Out
            </b-button>
            <b-button variant="primary" @click="resetSessionInactivityTimer" class="mx-2">
                Continue
            </b-button>
        </div>
    </b-modal>
</template>
<script src="./session-timeout-modal.controller.js"></script>
