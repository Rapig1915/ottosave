<template>
    <div class="vueResetPasswordVue my-4">
        <b-container>
            <b-row>
                <b-card class="guest-form mx-auto" no-body>

                    <b-row class="m-4">
                        <h2 class="m-auto form-header">Reset Your Password</h2>
                    </b-row>

                    <b-row class="mx-5 my-2">

                        <div class="mb-2 p-4 text-center">
                            Enter your email address and your new password to reset your password.
                        </div>

                        <div class="form-group w-100">
                            <validated-input v-model="credentials.email" :type="'email'" :name="'email'" :placeholder="'Your Email'" :validationErrors="validationErrors"></validated-input>
                        </div>

                        <div class="form-group w-100">
                            <validated-input v-model="credentials.password" :type="'password'" :name="'password'" :placeholder="'Enter New Password'" :validationErrors="validationErrors"></validated-input>
                        </div>

                        <div class="form-group w-100">
                            <input v-model="credentials.password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Confirm New Password">
                        </div>

                        <app-message :type="'error'" :messages="apiErrors" @close="apiErrors = []"></app-message>
                        <app-message :type="'success'" :messages="successMessages" @close="successMessages = []">
                            <div>Please proceed to <router-link :to="{ name: 'login' }">Login</router-link></div>
                        </app-message>

                        <button class="btn btn-primary w-100 submit-button my-4" title="Password Recovery" v-on:click="requestPasswordReset(credentials)" v-show="!requestingPasswordReset">Reset Password</button>
                        <span v-show="requestingPasswordReset"><i class="fas fa-circle-notch fa-spin fa-2x"></i></span>

                    </b-row>

                    <b-card-footer class="mt-2 p-4 text-center">
                        Know your password? <router-link to="login">Sign in</router-link>
                    </b-card-footer>

                </b-card>
            </b-row>
        </b-container>
    </div>
</template>

<script src="./reset-password.controller.js"></script>
