<template>
    <div class="deactivateButtonComponent">
        <app-message type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>

        <transition name="fadeHeight" mode="out-in">
            <b-row v-if="!isDisplayingWarning" key="button">
                <b-col cols="12" sm="6" md="4" xl="3">
                    <b-button variant="outline-plain" size="md" class="w-100" @click="displayWarning">Delete Account</b-button>
                </b-col>
            </b-row>

            <b-row v-else key="warning">
                <b-col cols="12">
                    <p class="text-dark text-center">
                        We are sorry to see you go. Are you sure you want to delete your account? All bank account and transaction data associated with your account will be permanently deleted from our database.
                    </p>
                </b-col>

                <b-col cols="12" md="6">
                    <b-button variant="outline-primary" size="md" class="w-100" @click="isDisplayingWarning = false" :disabled="isDeactivatingAccount">
                        No, continue using Otto
                    </b-button>
                </b-col>

                <b-col cols="12" md="6">
                    <b-button variant="danger" size="md" class="w-100 mt-3 mt-md-0" @click="deactivateAccount" :disabled="isDeactivatingAccount">
                        <loading-spinner :show-spinner="isDeactivatingAccount" custom-class="size-auto">
                            Yes, delete my account
                        </loading-spinner>
                    </b-button>

                    <p class="smaller-text text-danger text-center px-3 pt-3">
                        Clicking this button will remove all account data and unlink any financial institutions associated with your account.
                    </p>
                </b-col>
            </b-row>
        </transition>

        <loading-spinner :show-spinner="isLoggingOut" custom-class="overlay"></loading-spinner>

        <b-modal
            v-model="isAccountDeactivated"
            lazy
            centered
            no-close-on-backdrop
            hide-footer
            @hide="logoutUser"
            title="Success"
            title-tag="h3"
        >
            Your account has been deleted.
        </b-modal>
    </div>
</template>

<script src="./deactivate-button.controller.js"></script>
<style lang="scss" src="./_deactivate-button.scss" scoped></style>
