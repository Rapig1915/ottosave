<template>
    <div class="login-view my-4" v-on:keyup.enter="login()">
        <b-container>
            <b-row>
                <b-card class="guest-form mx-auto" no-body>

                    <b-row class="m-4">
                        <h2 class="m-auto form-header">Log In</h2>
                    </b-row>

                    <b-row class="mx-5 my-2" v-if="!isInitializing">

                        <div class="form-group w-100">
                            <validated-input v-model="credentials.email" :type="'text'" :name="'email'" :placeholder="'Email'" :validationErrors="validationErrors"></validated-input>
                        </div>

                        <div class="form-group w-100">
                            <validated-input v-model="credentials.password" :type="'password'" :name="'password'" :placeholder="'Password'" :validationErrors="validationErrors"></validated-input>
                        </div>

                        <app-message class="w-100" :type="'error'" :messages="apiErrors" @close="apiErrors = []"></app-message>

                        <div class="w-100 d-flex align-items-center justify-content-between mb-4">
                            <div v-if="!biometricAvailability">
                                <b-form-checkbox v-model="credentials.remember_me" class="primary"><span class="text-reduced">Remember me</span></b-form-checkbox>
                            </div>
                            <div v-else class="d-flex align-items-center pt-1">
                                <b-button
                                    variant="outline-primary"
                                    @click="loginWithBiometrics"
                                    class="login-view__biometric-button"
                                >
                                    <i class="fas fa-fingerprint" v-if="biometricAvailability === 'Touch ID'"></i>
                                    <i class="icon-dym-face-id-logo" v-else></i>
                                </b-button>
                                <span class="text-reduced">Use {{ biometricAvailability }}<sup>&reg;</sup></span>
                            </div>
                            <div>
                                <router-link class="text-reduced" to="forgot">Forgot password?</router-link>
                            </div>
                        </div>

                        <b-button variant="primary" block class="mb-3" title="Log in to Otto" @click="login()" :disabled="loggingIn">
                            <loading-spinner :show-spinner="loggingIn" custom-class="size-auto">
                                Log in
                            </loading-spinner>
                        </b-button>
                    </b-row>
                    <loading-spinner v-else :show-spinner="isInitializing" customClass="overlay"></loading-spinner>

                    <b-card-footer class="mt-2 p-4 text-center">
                        Don't have an account? <router-link to="register">Sign up</router-link>
                    </b-card-footer>

                </b-card>
            </b-row>
        </b-container>
    </div>
</template>
<script src="./login.controller.js"></script>
<style lang="scss" src="./_login.scss" scoped></style>
