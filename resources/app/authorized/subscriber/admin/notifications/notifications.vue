<template>
    <div class="adminNotificationsView">
        <b-container>
            <b-row>
                <b-col md="6">
                    <loading-spinner :showSpinner="isLoadingNotifications" customClass="overlay">
                        <b-form-select v-model="selectedNotification" :options="notificationEmails" v-if="notificationType === 'email'" class="mt-3"/>
                    </loading-spinner>
                </b-col>
            </b-row>
            <b-row v-if="notificationType === 'email' && selectedNotification" class="mt-4">
                <preview-email-component :emailLocation="selectedNotification"></preview-email-component>
            </b-row>
        </b-container>
    </div>
</template>

<script src="./notifications.controller.js"></script>
<style lang="scss" src="./_notifications.scss" scoped></style>
