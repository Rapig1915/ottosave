<template>
    <div class="vueRegisterView">
        <b-container>
            <b-row>
                <b-card class="guest-form mx-auto mt-3" no-body>

                    <b-row class="m-4">
                        <h2 class="m-auto form-header">Create your account</h2>
                    </b-row>

                    <b-alert show variant="warning" class="my-2 text-center px-5">
                        Registration is currently in maintenance. <br/> Thank you for your patience.
                    </b-alert>

                    <!--                    <b-row class="mx-5 my-2" @keyup.enter="attemptRegisterUser(credentials)">-->

                    <!--                        <div class="form-group w-100">-->
                    <!--                            <validated-input v-model="credentials.name" type="text" name="name" placeholder="Name" :validation-errors="validationErrors" />-->
                    <!--                        </div>-->

                    <!--                        <div class="form-group w-100">-->
                    <!--                            <validated-input v-model="credentials.email" :type="'text'" :name="'email'" :placeholder="'Email'" :validationErrors="validationErrors"></validated-input>-->
                    <!--                        </div>-->

                    <!--                        <div class="form-group w-100">-->
                    <!--                            <validated-input v-model="credentials.password" :type="'password'" :name="'password'" :placeholder="'Password'" :validationErrors="validationErrors"></validated-input>-->
                    <!--                        </div>-->

                    <!--                        <app-message class="w-100" :type="'error'" :messages="apiErrors" @close="apiErrors = []"></app-message>-->

                    <!--                        <vue-recaptcha-->
                    <!--                            v-if="!recaptcha.disabled"-->
                    <!--                            class="mb-2"-->
                    <!--                            ref="recaptcha"-->
                    <!--                            :sitekey="recaptcha.siteKey"-->
                    <!--                            @verify="onVerifyRecaptcha"-->
                    <!--                        ></vue-recaptcha>-->

                    <!--                        <button class="btn btn-primary w-100 submit-button" title="Create Account at Otto" @click="attemptRegisterUser(credentials)" :disabled="!canRegister || registeringUser">-->
                    <!--                            <span v-show="!registeringUser">Create your account</span>-->
                    <!--                            <span v-show="registeringUser"><i class="fas fa-circle-notch fa-spin"></i></span>-->
                    <!--                        </button>-->

                    <!--                        <div class="w-100 text-center mt-2 terms-text">-->
                    <!--                            By creating an account, you agree to our-->
                    <!--                            <br><a href="https://www.ottosave.com/terms-of-service" target="_blank">Terms of Service</a>.-->
                    <!--                        </div>-->

                    <!--                    </b-row>-->

                    <b-card-footer class="mt-2 p-4 text-center">
                        Already have an account? <router-link to="login">Sign in</router-link>
                    </b-card-footer>

                </b-card>
            </b-row>
        </b-container>
    </div>
</template>
<script src="./register.controller.js"></script>
<style lang="scss" src="./_register.scss" scoped></style>
