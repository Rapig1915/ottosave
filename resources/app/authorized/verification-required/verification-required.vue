<template>
    <div class="verificationRequiredComponent">
        <b-container>
            <div class="text-center mt-4">
                <img class="logo" src="~vue_root/assets/images/logo.png"/>
            </div>
            <b-row class="mt-4">
                <b-col>
                    <app-message type="success" :messages="successMessages" @close="successMessages = []"></app-message>
                    <app-message type="error" :messages="apiErrors" @close="apiErrors = []"></app-message>
                    <div>
                        <div class="text-center">
                            <h2>Email Verification Required</h2>
                            <p>A message has been sent to your email address. Follow the link in the message to confirm your account and get started with Otto.</p>
                            <b-button @click="returnToLogin()">
                                Return to Login
                            </b-button>
                            <br>
                            <b-button @click="proceedToApplication()" class="mt-3" v-if="user">
                                I already verified
                            </b-button>
                        </div>
                        <div class="text-center mt-4" v-if="user">
                            <h3>Didn't get an email?</h3>
                            <p>Click the button below to resend the verificaiton email.</p>
                            <b-button @click="resendVerificationEmail" :disabled="isSendingEmail">
                                <loading-spinner :showSpinner="isSendingEmail" customClass="size-auto" :class="{'px-4': isSendingEmail}">
                                    Resend Email
                                </loading-spinner>
                            </b-button>
                        </div>
                    </div>
                </b-col>
            </b-row>
            <div class="col-12 mt-5 pt-4 text-center border-top copyright">
                &copy; {{ Date.now() | moment("YYYY") }} Defend Your Money, LLC. All rights reserved.
            </div>
        </b-container>
    </div>
</template>

<script src="./verification-required.controller.js"></script>
<style lang="scss" src="./_verification-required.scss" scoped></style>
