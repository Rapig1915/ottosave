<template>
    <div class="verifyEmailComponent">
        <b-container>
            <div class="text-center mt-4">
                <router-link :to="{ name: 'dashboard' }">
                    <img class="logo" src="~vue_root/assets/images/logo.png"/>
                </router-link>
            </div>
            <b-row class="mt-4">
                <b-col>
                    <app-message type="error" :messages="apiErrors" @close="apiErrors = []"></app-message>
                    <loading-spinner :showSpinner="isVerifyingEmail || isNavigating" customClass="overlay"></loading-spinner>
                    <div v-if="!user.email_verified">
                        <h2 class="text-center">Verifying Email</h2>
                    </div>
                    <div v-else class="w-100">
                        <p class="text-center text-dark largest-text">Thank you, your email has been successfully verified.</p>
                        <div class="d-flex justify-content-center">
                            <b-button variant="primary" @click="navigateTo({ name: 'dashboard' })">
                                Continue to your Dashboard
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

<script src="./verify-email.controller.js"></script>
<style lang="scss" src="./_verify-email.scss" scoped></style>
