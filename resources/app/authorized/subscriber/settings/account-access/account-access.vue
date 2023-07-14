<template>
    <b-container class="account-access-page">
        <loading-spinner :show-spinner="isInitializingView" custom-class="overlay fixed"></loading-spinner>
        <b-row>
            <b-col cols="12">
                <section>
                    <b-card no-body class="account-access-form mx-auto">
                        <b-card-body>
                            <section>
                                <b-row>
                                    <b-col cols="12" sm="auto" class="d-flex">
                                        <h1 class="account-access-page__title mr-2">User Access</h1>
                                        <info-popover id="override-info-popover">
                                            <template slot="title">
                                                Grant Access
                                            </template>
                                            <template slot="content">
                                                <p>
                                                    If desired, an Owner can share access to their account with a “Manager.”
                                                    A Manager has access to all account features on Otto with the following exceptions:<br/>
                                                    <ul class="py-2">
                                                        <li>A Manager cannot add or delete financial institutions.</li>
                                                        <li>A Manager does not have access to the Settings page or any of its functions.</li>
                                                    </ul>
                                                </p>

                                                <p>
                                                    Otto gives access to information only.<br/>
                                                    No one, including the Owner, can transfer
                                                    or access the money at any financial
                                                    institution using Otto.
                                                </p>
                                            </template>
                                        </info-popover>
                                    </b-col>
                                </b-row>
                            </section>
                            <section>
                                <form>
                                    <b-row>
                                        <b-col>
                                            <h3 class="text-muted-light font-weight-normal d-flex">
                                                <span class="align-self-center border-bottom border-muted d-inline-block flex-fill"></span>
                                            </h3>
                                        </b-col>
                                    </b-row>

                                    <b-row class="account-user-row" v-for="(accountUser, index) in accountUsers" :key="`user-${index}`">
                                        <b-col cols="12" md="3" class="d-flex align-items-center">
                                            <span class="ml-2 font-weight-semibold">{{ accountUser.user.name }}</span>
                                        </b-col>
                                        <b-col cols="12" md="3" class="d-flex align-items-center">
                                            <span class="ml-2">{{ accountUser.user.email }}</span>
                                        </b-col>
                                        <b-col cols="12" md="2" class="d-flex align-items-center justify-content-between">
                                            <span class="ml-2">{{ accountUser.isOwner ? 'Owner' : 'Manager' }}</span>
                                            <loading-spinner v-if="!accountUser.isOwner" :showSpinner="accountUser.isDeleting" customClass="size-auto" class="d-md-none">
                                                <b-button variant="white">
                                                    <i @click.prevent="deleteAccountUser(accountUser)" class="far fa-trash-alt invite-delete-button" />
                                                </b-button>
                                            </loading-spinner>
                                        </b-col>
                                        <b-col cols="12" md="3" class="d-flex align-items-center">
                                        </b-col>
                                        <b-col v-if="!accountUser.isOwner" cols="1" md="1" class="mt-3 mt-md-0 d-none d-md-flex align-items-center justify-content-center">
                                            <loading-spinner :showSpinner="accountUser.isDeleting" customClass="size-md">
                                                <b-button variant="white">
                                                    <i @click.prevent="deleteAccountUser(accountUser)" class="far fa-trash-alt invite-delete-button" />
                                                </b-button>
                                            </loading-spinner>
                                        </b-col>
                                    </b-row>

                                    <b-row class="account-user-row" v-for="(accountInvite, index) in accountInvites" :key="`invite-${index}`">
                                        <b-col cols="12" md="3" class="d-flex align-items-center">
                                            <span class="ml-2 font-weight-semibold">{{ accountInvite.name || '' }}</span>
                                        </b-col>
                                        <b-col cols="12" md="3" class="d-flex align-items-center">
                                            <span class="ml-2">{{ accountInvite.email }}</span>
                                        </b-col>
                                        <b-col cols="12" md="2" class="d-flex align-items-center">
                                            <span class="ml-2 text-warning">Pending</span>
                                        </b-col>
                                        <b-col cols="12" md="3" class="mt-3 mt-md-0 d-flex align-items-center justify-content-between">
                                            <loading-spinner :showSpinner="accountInvite.isResendingInvite" class="flex-grow-1" customClass="size-md">
                                                <button @click.prevent="resendInvite(accountInvite)" class="btn btn-md w-100 btn-primary form-button">Resend Invite</button>
                                            </loading-spinner>
                                            <loading-spinner :showSpinner="accountInvite.isDeletingInvite" customClass="size-auto" class="d-md-none ml-3 ml-md-0">
                                                <b-button variant="white">
                                                    <i @click.prevent="deleteAccountInvite(accountInvite)" class="far fa-trash-alt invite-delete-button" />
                                                </b-button>
                                            </loading-spinner>
                                        </b-col>
                                        <b-col cols="12" md="1" class="mt-3 mt-md-0 d-none d-md-flex align-items-center justify-content-center">
                                            <loading-spinner :showSpinner="accountInvite.isDeletingInvite" customClass="size-md">
                                                <b-button variant="white">
                                                    <i @click.prevent="deleteAccountInvite(accountInvite)" class="far fa-trash-alt invite-delete-button" />
                                                </b-button>
                                            </loading-spinner>
                                        </b-col>
                                    </b-row>

                                    <b-row class="account-user-row">
                                        <b-col cols="12" md="3">
                                            <validated-input v-model="user.name"
                                                type="text" name="name" placeholder="Name" :validationErrors="validationErrors"></validated-input>
                                        </b-col>
                                        <b-col cols="12" md="3">
                                            <validated-input v-model="user.email"
                                                type="email" name="email" placeholder="Email" :validationErrors="validationErrors"></validated-input>
                                        </b-col>
                                        <b-col cols="12" md="2"></b-col>
                                        <b-col cols="12" md="3" class="mt-3 mt-md-0 d-flex align-items-center justify-content-end">
                                            <loading-spinner :showSpinner="isSendingInvite" class="flex-grow-1">
                                                <button @click.prevent="sendInvite" class="btn btn-md w-100 btn-primary form-button">Send Invite</button>
                                            </loading-spinner>
                                        </b-col>
                                    </b-row>

                                    <b-row v-if="errorMessages.length || successMessages.length">
                                        <b-col cols="12" class="mt-20">
                                            <app-message
                                                type="error"
                                                :messages="errorMessages"
                                                @close="errorMessages = []">
                                            </app-message>
                                            <app-message type="success" :messages="successMessages" @close="successMessages = []">
                                            </app-message>
                                        </b-col>
                                    </b-row>
                                    <b-row>
                                        <b-col>
                                            <h3 class="text-muted-light font-weight-normal d-flex mt-32">
                                                <span class="align-self-center border-bottom border-muted d-inline-block flex-fill"></span>
                                            </h3>
                                        </b-col>
                                    </b-row>
                                </form>
                            </section>
                        </b-card-body>
                    </b-card>
                </section>
            </b-col>
        </b-row>
    </b-container>
</template>
<script src="./account-access.controller.js"></script>
<style lang="scss" src="./_account-access.scss" scoped></style>
