<template>
    <div class="verificationRequiredWarning">
        <transition name="fadeHeight">
            <div v-if="shouldDisplayWarning" class="pt-3 px-3">
                <transition-group name="fadeHeight" mode="out-in">
                    <div class="alert alert-danger d-flex justify-content-between" v-if="!successMessages.length" key="warning">
                        <div>
                            We need to verify your email address. We have sent an email to {{ user.email }}. <router-link :to="{ name: 'settings', params: {} }">Update</router-link>
                            <br>
                            If you do not see this email, please check your spam, or we can <b-button variant="link" @click="resendVerificationEmail" class="p-0">resend the email</b-button>.
                        </div>
                        <i class="fas fa-times align-self-center mb-1" @click="isWarningDismissed = true"></i>
                    </div>
                    <app-message v-else type="success" :messages="successMessages" @close="successMessages = []" key="success"/>
                </transition-group>
            </div>
        </transition>
    </div>
</template>

<script src="./verification-required.controller.js"></script>
<style lang="scss" src="./_verification-required.scss" scoped></style>
