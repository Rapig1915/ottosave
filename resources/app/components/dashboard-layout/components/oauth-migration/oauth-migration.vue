<template>
    <div class="oauthMigrationComponent">
        <transition name="fadeHeight">
            <div class="mb-3" v-if="institution">
                <b-row class="py-3 mx-0 rounded bg-white text-center">
                    <b-col>
                        <h2>New Connection Available</h2>
                        <app-message type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>
                        <p>To ensure the most stable and secure connection to {{ institution.name }}, we recommend reconnecting your accounts to take advantage of the latest features.</p>
                        <div class="w-100 d-flex justify-content-center">
                            <b-button @click="institution = false" class="mx-1" variant="outline-plain">
                                Dismiss
                            </b-button>
                            <b-button @click="migrateInstitutionToOauth" variant="primary" :disabled="isMigratingInstitution" class="mx-1">
                                <loading-spinner :show-spinner="isMigratingInstitution" custom-class="size-auto" :class="{ 'px-5': isMigratingInstitution }">
                                    Reconnect Now
                                </loading-spinner>
                            </b-button>
                        </div>
                    </b-col>
                </b-row>
            </div>
        </transition>
        <!-- Finicity Connect component should remain outside the conditional rendering to ensure events are handled correctly -->
        <FinicityConnect ref="finicityService"
            @finicity-connect-complete="completeFinicityConnectFix"
            @finicity-connect-error="displayErrorMessage"
            @finicity-connect-cancelled="completeFinicityConnectFix"
            @finicity-connect-shown="isMigratingInstitution = false"
        />
    </div>
</template>

<script src="./oauth-migration.controller.js"></script>
<style lang="scss" src="./_oauth-migration.scss" scoped></style>
