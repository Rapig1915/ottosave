<template>
    <div class="finicityOauthComponent">
        <app-message class="mt-2" type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>

        <b-row class="my-3">
            <b-col class="align-items-end">
                <transition name="fade">
                    <b-button @click="isAddingInstitution = true" v-if="!isAddingInstitution" variant="primary" class="float-right">Add Institution</b-button>
                </transition>
            </b-col>
        </b-row>

        <transition name="fadeHeight">
            <div v-if="isAddingInstitution">
                <b-row class="my-3">
                    <b-col cols="3">
                        <label for="old_institution_id">Old Institution Id:</label>
                        <input class="form-control" type="text" name="old_institution_id" v-model="newInstitution.old_institution_id">
                        <span v-if="validationErrors.old_institution_id" class="smaller-text text-danger">{{ validationErrors.old_institution_id.join(' ') }}</span>
                    </b-col>
                    <b-col cols="3">
                        <label for="new_institution_id">New Institution Id:</label>
                        <input class="form-control" type="text" name="new_institution_id" v-model="newInstitution.new_institution_id">
                        <span v-if="validationErrors.new_institution_id" class="smaller-text text-danger">{{ validationErrors.new_institution_id.join(' ') }}</span>
                    </b-col>
                    <b-col cols="6" v-b-tooltip.hover title="Message shown to users who have been force migrated to the new connection.">
                        <label for="transition_message">Transition Message:</label>
                        <input class="form-control" type="text" name="transition_message" v-model="newInstitution.transition_message">
                        <span v-if="validationErrors.transition_message" class="smaller-text text-danger">{{ validationErrors.transition_message.join(' ') }}</span>
                    </b-col>
                </b-row>
                <b-row class="my-3">
                    <b-col cols="12">
                        <div class="float-right">
                            <b-button variant="outline-plain" @click="cancelCreation" class="mr-3" :disabled="isSavingInstitution">Cancel</b-button>
                            <b-button variant="primary" @click="createInstitution" :disabled="isSavingInstitution">
                                <loading-spinner :show-spinner="isSavingInstitution" custom-class="size-auto">
                                    Save Institution
                                </loading-spinner>
                            </b-button>
                        </div>
                    </b-col>
                </b-row>
            </div>
        </transition>

        <loading-spinner :show-spinner="isLoadingInstitutions" custom-class="overlay">
            <b-table striped hover responsive :items="institutions" :fields="institutionFields">
                <template #cell(migrate_users)="row">
                    <b-button size="sm"
                        class="mr-2"
                        variant="primary"
                        @click="migrateUsers(row.item)"
                        :disabled="!!row.item.number_of_pending_migrations"
                        v-if="row.item.number_of_institutions_to_migrate > 0"
                    >
                        <loading-spinner :show-spinner="row.item.isMigratingUsers" custom-class="size-auto">
                            Migrate Users
                        </loading-spinner>
                    </b-button>
                </template>
            </b-table>
        </loading-spinner>
    </div>
</template>

<script src="./finicity-oauth.controller.js"></script>
<style lang="scss" src="./_finicity-oauth.scss" scoped></style>
