<template>
    <b-container class="settings-page">
        <b-row>
            <b-col cols="12">
                <section>
                    <b-card no-body class="settings-form mx-auto">
                        <b-card-body>
                            <section class="d-flex align-items-center justify-content-between">
                                <b-row>
                                    <b-col cols="12" sm="auto">
                                        <h1 class="settings-page__title">Account Details</h1>
                                    </b-col>
                                    <b-col v-if="currentAccount.status === 'free_trial'" cols="12" sm="auto" class="mx-auto">
                                        <div class="alert-info text-center trial-box">
                                            Your trial
                                            <span v-if="daysRemainingOnAccount">will expire in {{ daysRemainingOnAccount }} days.</span>
                                            <span v-else>expires today!</span>
                                        </div>
                                    </b-col>
                                    <b-col v-else-if="currentAccount.status === 'grace'" cols="12" sm="auto" class="mx-auto">
                                        <div class="alert-info text-center trial-box">
                                            Your subscription was cancelled with {{ daysRemainingOnAccount }} days left on your account.
                                        </div>
                                    </b-col>
                                    <b-col v-else-if="currentAccount.status === 'expired'" cols="12" class="pl-0 mb-4">
                                        <div class="alert-danger text-center trial-box">
                                            Your subscription expired on {{ currentAccount.expire_date | moment("MMM Do YYYY") }}
                                            <br>
                                            Manage your subscription info below to continue with Otto!
                                        </div>
                                    </b-col>
                                </b-row>
                                <div class="text-primary text-right" v-if="canInviteToAccount">
                                    <router-link :to="{ name: 'account-access' }">
                                        <i class="fas fa-solid fa-user-plus mr-1 d-block d-md-inline" />
                                        User Access
                                    </router-link>
                                </div>
                            </section>
                            <section>
                                <form>
                                    <b-row>
                                        <b-col>
                                            <h3 class="text-muted-light font-weight-normal d-flex">
                                                Name
                                                <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                            </h3>
                                        </b-col>
                                    </b-row>
                                    <b-row class="mt-20">
                                        <b-col cols="12" sm="5" md="3">
                                            <validated-input v-model="user.name"
                                                type="text" name="name" placeholder="User Name" :validationErrors="validationErrors" :readOnly="!canUpdateAccountSettings"></validated-input>
                                        </b-col>
                                        <b-col cols="12" sm="4" md="3" class="pl-sm-0 mt-4 mt-sm-0" v-if="canUpdateAccountSettings">
                                            <loading-spinner :showSpinner="storingUser">
                                                <button @click.prevent="storeUser" class="btn btn-md w-100 btn-primary form-button">Change Name</button>
                                            </loading-spinner>
                                        </b-col>
                                    </b-row>
                                    <b-row v-if="storeUserErrors.length || storeUserSuccess.length">
                                        <b-col cols="12" class="mt-20">
                                            <app-message
                                                type="error"
                                                :messages="storeUserErrors"
                                                @close="storeUserErrors = []">
                                            </app-message>
                                            <app-message type="success" :messages="storeUserSuccess" @close="storeUserSuccess = []">
                                            </app-message>
                                        </b-col>
                                    </b-row>
                                </form>
                            </section>
                            <section>
                                <form @submit.prevent="confirmPassword">
                                    <b-row>
                                        <b-col>
                                            <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                                Email
                                                <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                            </h3>
                                        </b-col>
                                    </b-row>
                                    <b-row>
                                        <b-col cols="12" sm="5" md="3">
                                            <validated-input v-model="user.email" type="text" name="email" :validationErrors="validationErrors" :readOnly="!canUpdateAccountSettings"></validated-input>
                                        </b-col>
                                        <b-col cols="12" sm="4" md="3" class="pl-sm-0 mt-4 mt-sm-0" v-if="canUpdateAccountSettings">
                                            <loading-spinner :showSpinner="changingEmail">
                                                <button @click.prevent="confirmPassword" class="btn btn-md w-100 btn-primary form-button">Change Email</button>
                                            </loading-spinner>
                                        </b-col>
                                    </b-row>
                                    <b-row v-if="changeEmailErrors.length || changeEmailSuccess.length">
                                        <b-col cols="12" class="mt-20">
                                            <app-message
                                                type="error"
                                                :messages="changeEmailErrors"
                                                @close="changeEmailErrors = []">
                                            </app-message>
                                            <app-message type="success" :messages="changeEmailSuccess" @close="changeEmailSuccess = []">
                                            </app-message>
                                        </b-col>
                                    </b-row>
                                </form>
                                <password-modal ref="passwordModal"
                                    @ok="changeEmail"
                                ></password-modal>
                            </section>
                            <section v-if="canUpdateAccountSettings">
                                <form>
                                    <b-row>
                                        <b-col>
                                            <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                                Change Password
                                                <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                            </h3>
                                        </b-col>
                                    </b-row>
                                    <b-row>
                                        <b-col cols="12" md="4" xl="3" class="pr-md-0">
                                            <label :for="'current_password'">Current Password</label>
                                            <validated-input v-model="user.current_password" type="password" name="current_password" :validationErrors="validationErrors"></validated-input>
                                        </b-col>
                                        <b-col cols="12" md="4" xl="3" class="pr-md-0">
                                            <label :for="'password'">New Password</label>
                                            <validated-input v-model="user.password" type="password" name="password" :validationErrors="validationErrors"></validated-input>
                                        </b-col>
                                        <b-col cols="12" md="4" xl="3">
                                            <label :for="'confirm-new-password'">Confirm New Password</label>
                                            <input v-model="user.password_confirmation" type="password" name="confirm-new-password" class="form-control">
                                        </b-col>
                                        <b-col cols="12" md="4" xl="2" class="pl-xl-0 pr-md-0">
                                            <b-button variant="primary" block @click.prevent="changePassword" class="form-button mt-32" :disabled="changingPassword">
                                                <loading-spinner :showSpinner="changingPassword">
                                                    Save
                                                </loading-spinner>
                                            </b-button>
                                        </b-col>
                                    </b-row>
                                    <b-row v-if="changePasswordErrors.length || changePasswordSuccess.length">
                                        <b-col cols="12" class="mt-20">
                                            <app-message
                                                type="error"
                                                :messages="changePasswordErrors"
                                                @close="changePasswordErrors = []">
                                            </app-message>
                                            <app-message type="success" :messages="changePasswordSuccess" @close="changePasswordSuccess = []">
                                            </app-message>
                                        </b-col>
                                    </b-row>
                                </form>
                            </section>
                            <section v-if="currentAccount.subscription_provider === 'braintree' && !$store.getters.isInDemoMode && canUpdateAccountSettings">
                                <b-row>
                                    <b-col>
                                        <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                            Update Subscription
                                            <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                        </h3>
                                    </b-col>
                                </b-row>
                                <b-row v-if="isCurrentPlusSubscriberWithPaymentOnFile">
                                    <b-col cols="12">
                                        <b-row>
                                            <b-col cols="12">
                                                <div class="px-3">
                                                    Your next payment is scheduled for <span class="text-primary">{{ currentAccount.expire_date | moment('MM/DD/YYYY') }}</span>
                                                    <info-popover id="payment-date-info-popover">
                                                        <template v-slot:content>
                                                            We will begin processing your payment on the indicated date. Processing can take several business days. Your actual payment will likely post a few days later.
                                                        </template>
                                                    </info-popover>
                                                </div>
                                            </b-col>
                                        </b-row>
                                        <b-row>
                                            <b-col cols="12" sm="6" md="6" lg="4" order="1" class="pt-3">
                                                <b-form-select v-model="user.current_account.subscription_type" name="billing_interval" :options="subscriptionTypes" class="mt-1">
                                                </b-form-select>
                                            </b-col>
                                            <b-col cols="12" sm="4" md="3" lg="2" class="pl-sm-0" order="3" order-sm="2">
                                                <b-button @click.prevent="saveSubscriptionInterval" variant="primary" size="md" class="w-100 form-button mt-20">
                                                    <loading-spinner :showSpinner="updatingSubscription" custom-class="size-auto">
                                                        Save
                                                    </loading-spinner>
                                                </b-button>
                                            </b-col>
                                            <b-col cols="12" lg="7" order="2" order-sm="3" class="mt-lg-4 pt-2" v-if="activeDiscountPercent">
                                                <div class="px-3 px-lg-2">
                                                    Applied Discount: {{ activeDiscountPercent }}%
                                                </div>
                                                <div class="px-3 px-lg-2">
                                                    Your Price: {{ subscriptionPriceLessDiscount | currency }} for the next {{ activeDiscountDuration }}, then {{ selectedSubscriptionPrice | currency }} thereafter
                                                </div>
                                            </b-col>
                                        </b-row>
                                        <b-row class="mt-2">
                                            <b-col>
                                                <div class="d-flex align-items-stretch">
                                                    <loading-spinner :showSpinner="updatingSubscription" custom-class="size-auto">
                                                        <b-button variant="link" @click="updatePaymentMethod()" :disabled="updatingSubscription">
                                                            <i class="fas fa-arrow-up"></i> Update Payment Method
                                                        </b-button>
                                                        <b-button variant="link" @click="confirmCancel()" :disabled="updatingSubscription">
                                                            <i class="icon-dym-remove"></i> Cancel Subscription
                                                        </b-button>
                                                    </loading-spinner>
                                                </div>
                                            </b-col>
                                        </b-row>
                                    </b-col>
                                </b-row>
                                <b-row v-else-if="currentAccount.subscription_plan === 'plus'">
                                    <loading-spinner :showSpinner="updatingSubscription" custom-class="size-auto">
                                        <b-button variant="link" @click="upgradeSubscription()" :disabled="updatingSubscription">
                                            <span v-if="currentAccount.status === 'free_trial'"><i class="fas fa-plus"></i> Add Payment Method</span>
                                            <span v-else><i class="fas fa-arrow-up"></i> Restart Subscription</span>
                                        </b-button>
                                        <b-button variant="link" @click="confirmCancel()" :disabled="updatingSubscription" v-if="currentAccount.status !== 'grace'">
                                            <i class="icon-dym-remove"></i> Cancel Subscription
                                        </b-button>
                                    </loading-spinner>
                                </b-row>
                                <b-row v-else-if="currentAccount.subscription_plan === 'basic'">
                                    <b-col>
                                        <div class="d-flex align-items-stretch">
                                            <loading-spinner :showSpinner="updatingSubscription" custom-class="size-auto">
                                                <b-button variant="link" @click="upgradeSubscription()" :disabled="updatingSubscription">
                                                    <i class="fas fa-arrow-up"></i> Start your subscription
                                                </b-button>
                                            </loading-spinner>
                                        </div>
                                    </b-col>
                                </b-row>
                                <b-row class="mt-3">
                                    <b-col cols="12" lg="6">
                                        <div class="d-flex align-items-stretch">
                                            <b-input-group>
                                                <b-form-input type="text" v-model="couponCode" placeholder="Code" @keyup.enter="redeemCoupon"></b-form-input>

                                                <b-input-group-append>
                                                    <b-button variant="primary" @click="redeemCoupon" :disabled="isRedeemingCoupon">
                                                        <loading-spinner custom-class="size-auto" :show-spinner="isRedeemingCoupon" :class="{'px-4': isRedeemingCoupon}">
                                                            Apply Code
                                                        </loading-spinner>
                                                    </b-button>
                                                </b-input-group-append>
                                            </b-input-group>
                                        </div>
                                    </b-col>
                                </b-row>
                                <b-row>
                                    <b-col cols="12">
                                        <app-message
                                            class="mt-20"
                                            type="error"
                                            :messages="updateSubscriptionErrors"
                                            @close="updateSubscriptionErrors = []"
                                        />
                                        <app-message
                                            class="mt-20"
                                            type="success"
                                            :messages="updateSubscriptionSuccess"
                                            @close="updateSubscriptionSuccess = []"
                                        />
                                    </b-col>
                                </b-row>
                                <purchase-component ref="paymentComponent" @paymentSubmittedSuccess="paymentSubmitted"/>
                            </section>
                            <section v-else-if="currentAccount.subscription_provider === 'itunes' && !$store.getters.isInDemoMode">
                                <b-row>
                                    <b-col>
                                        <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                            Manage Subscription
                                            <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                        </h3>
                                    </b-col>
                                </b-row>
                                <b-row>
                                    <b-col>
                                        Head to the <a href="https://buy.itunes.apple.com/WebObjects/MZFinance.woa/wa/manageSubscriptions">iTunes Store</a> to manage your subscription.
                                    </b-col>
                                </b-row>
                                <b-row v-if="currentAccount.status === 'expired'" class="pt-3">
                                    <b-col>
                                        <b-button variant="link" @click="confirmCancel()" :disabled="updatingSubscription" class="px-0">
                                            Downgrade Your Subscription
                                        </b-button>
                                        to use the free "basic" version and continue without live account updates.
                                    </b-col>
                                </b-row>
                            </section>
                            <section>
                                <b-row>
                                    <b-col>
                                        <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                            Notification Preferences
                                            <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                        </h3>
                                    </b-col>
                                </b-row>
                                <loading-spinner :showSpinner="loadingNotificationPreferences">
                                    <b-row>
                                        <b-col cols="12" sm="6">
                                            <label for="assignment_reminder">Reminder: Assign Credit Card Charges</label>
                                            <b-form-select v-model="notificationPreferences.assignment_reminder_frequency" name="assignment_reminder" :options="notificationOptions" class="text-capitalize" :disabled="!canUpdateAccountSettings">
                                            </b-form-select>
                                        </b-col>
                                    </b-row>
                                    <b-row v-if="canUpdateAccountSettings">
                                        <b-col cols="12" sm="4" md="3" lg="2" class="mt-4">
                                            <b-button @click.prevent="updateNotificationPreferences" variant="primary" size="md" class="w-100 form-button">
                                                <loading-spinner :showSpinner="updatingNotificationPreferences" custom-class="size-auto">
                                                    Save
                                                </loading-spinner>
                                            </b-button>
                                        </b-col>
                                    </b-row>
                                </loading-spinner>
                                <b-row>
                                    <b-col cols="12">
                                        <app-message
                                            class="mt-20"
                                            type="error"
                                            :messages="updateNotificationPreferencesErrors"
                                            @close="updateNotificationPreferencesErrors = []"
                                        />
                                        <app-message
                                            class="mt-20"
                                            type="success"
                                            :messages="updateNotificationPreferencesSuccess"
                                            @close="updateNotificationPreferencesSuccess = []"
                                        />
                                    </b-col>
                                </b-row>
                            </section>
                            <section v-if="isDeactivationButtonShown && canUpdateAccountSettings">
                                <b-row>
                                    <b-col>
                                        <h3 class="text-muted-light font-weight-normal d-flex mt-4 mb-4">
                                            Manage Account
                                            <span class="align-self-center border-bottom border-muted d-inline-block flex-fill ml-3"></span>
                                        </h3>
                                    </b-col>
                                </b-row>

                                <deactivate-button />
                            </section>

                            <b-row v-if="currentAccount.status === 'expired'">
                                <b-col cols="12" sm="4" md="3" lg="2" class="mt-4">
                                    <b-button variant="outline-primary" @click="logout" class="w-100 form-button" :disabled="loggingOut">
                                        <loading-spinner :showSpinner="loggingOut" customClass="size-auto">
                                            Logout
                                        </loading-spinner>
                                    </b-button>
                                </b-col>
                            </b-row>
                            <cancel-modal ref="cancelModal" @ok="cancelSubscription"></cancel-modal>
                        </b-card-body>
                    </b-card>
                </section>
            </b-col>
        </b-row>
    </b-container>
</template>
<script src="./settings.controller.js"></script>
<style lang="scss" src="./_settings.scss" scoped></style>
