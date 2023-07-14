<template>
    <div class="accept-invite-component">
        <div class="grayBackground">
            <div class="pageContainer">
                <div class="content mobileFont">
                    <div class="logoWrapper">
                        <img src="~vue_root/assets/images/logo.png" @click="goToDashboard"/>
                    </div>

                    <p>
                        Hi {{user.name}}!
                        <br/>
                        <br/>
                        You've been invited to access the following account:
                        <br/>
                        <br/>
                        <center><b>{{ accountName }}</b></center>
                        <br/>
                    </p>
                    <div class="form-group w-100 pl-4 pr-4 d-none">
                        <input class="codeInput" v-model="accessCode" type="text" name="accessCode" placeholder="Access Code" />
                    </div>

                    <div class="buttons">
                        <b-button class="text-white text-decoration-none mobileButton" @click="acceptInvite">
                            <loading-spinner :show-spinner="isAcceptingInvite" custom-class="size-auto" class="text-center">
                                Accept Invite
                            </loading-spinner>
                        </b-button>

                        <span class="mt-4 link-dashboard" @click="goToDashboard">
                            Go to Dashboard
                        </span>
                    </div>

                    <b-row v-if="errorMessages.length || successMessages.length">
                        <b-col cols="12" class="mt-4">
                            <app-message
                                type="error"
                                :messages="errorMessages"
                                @close="errorMessages = []">
                            </app-message>
                            <app-message type="success" :messages="successMessages" @close="successMessages = []">
                            </app-message>
                        </b-col>
                    </b-row>
                </div>
            </div>
        </div>
    </div>
</template>

<script src="./accept-invite.controller.js"></script>
<style lang="scss" src="./_accept-invite.scss" scoped></style>
