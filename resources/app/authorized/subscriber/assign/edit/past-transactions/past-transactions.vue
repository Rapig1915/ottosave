<template>
    <div class="pastTransactionsComponent">
        <transition name="fadeHeight">
            <b-container fluid v-show="isComponentDisplayed" class="border rounded p-4 mt-3">
                <app-message type="error" :messages="errorMessages" @close="errorMessages = []" />

                <loading-spinner :show-spinner="isUpdatingAccounts" :class="{'py-3 text-center': isUpdatingAccounts}">
                    <template v-slot:loading-text>
                        <h3 class="text-muted mt-2">Pulling latest account information...</h3>
                    </template>

                    <b-row v-if="!hasInstitutionAccount">
                        <b-col cols="12" >
                            <h3 class="text-muted">You'll need an institution account to use this functionality.</h3>
                        </b-col>
                    </b-row>

                    <b-row v-if="hasInstitutionAccount">
                        <b-col cols="12">
                            <h3 class="text-muted">Upload Past Charges</h3>
                        </b-col>
                        <b-col cols="12" md="4">
                            <div class="form-group">
                                <label>Select a credit card:</label>
                                <select class="form-control mb-3" v-model="selectedInstitutionAccountId">
                                    <option v-for="(option, index) in creditCardSelectOptions" :key="index" :value="option.value">{{ option.text }}</option>
                                </select>
                            </div>
                        </b-col>

                        <b-col cols="12" sm="6" md="4">
                            <div class="form-group">
                                <label>Start Date:</label>
                                <date-picker
                                    format="MM/dd/yyyy"
                                    input-class="datepicker form-control bg-white w-100 pl-3"
                                    wrapper-class="d-block"
                                    class="d-inline-block text-wrap"
                                    v-model="startDate"
                                    maximum-view="day"
                                    :disabled-dates="disabledStartDates"
                                />
                            </div>
                        </b-col>

                        <b-col cols="12" sm="6" md="4">
                            <div class="form-group">
                                <label>End Date:</label>
                                <date-picker
                                    format="MM/dd/yyyy"
                                    input-class="datepicker form-control bg-white w-100 pl-3"
                                    wrapper-class="d-block"
                                    class="d-inline-block text-wrap"
                                    v-model="endDate"
                                    maximum-view="day"
                                    :disabled-dates="disabledEndDates"
                                />
                            </div>
                        </b-col>
                    </b-row>

                    <b-row v-if="hasInstitutionAccount">
                        <b-col class="d-flex justify-content-end">
                            <b-button variant="light" @click="hide()" class="mx-2">
                                Cancel
                            </b-button>

                            <b-button variant="primary" @click="downloadPastCharges" :disabled="isLoadingCharges">
                                <loading-spinner :showSpinner="isLoadingCharges" custom-class="size-auto" :class="{'px-3': isLoadingCharges}">
                                    Submit
                                </loading-spinner>
                            </b-button>
                        </b-col>
                    </b-row>
                </loading-spinner>
            </b-container>
        </transition>
    </div>
</template>

<script src="./past-transactions.controller.js"></script>
<style lang="scss" src="./_past-transactions.scss" scoped></style>
