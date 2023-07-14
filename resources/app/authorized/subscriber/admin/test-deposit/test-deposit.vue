<template>
    <b-container class="settings-dym-container">
        <b-row>
            <b-col cols="12">
                <section>
                    <b-card no-body class="settings-form mx-auto mt-3">
                        <b-card-body>
                            <section>
                                <b-row align-h="between">
                                    <b-col cols="12" sm="6">
                                        <h1>Test Deposit</h1>
                                    </b-col>
                                    <b-col cols="12" sm="6">
                                        <div class="searchInput">
                                            <i class="fas fa-search"></i>
                                            <input type="text" name="Search" v-model="userQuery.searchString" class="form-control rounded-pill" placeholder="Search" @keyup="debouncedHandlerGetAllUsers" />
                                        </div>
                                    </b-col>
                                </b-row>

                                <b-row>
                                    <b-col cols="12">
                                        <app-message
                                            type="error"
                                            :messages="errorMessages"
                                            @close="errorMessages = []">
                                        </app-message>
                                    </b-col>
                                </b-row>

                                <b-row>
                                    <b-col cols="12">
                                        <loading-spinner :showSpinner="loadingSpinner" customClass="overlay"></loading-spinner>
                                        <b-table
                                            class="user-list-table"
                                            striped small hover
                                            :items="users"
                                            :fields="userTableColumns"
                                            responsive
                                            no-local-sorting
                                            @sort-changed="handleChangeSort"
                                        >
                                            <template #cell(linked_bank_accounts)="row">
                                                <b-button size="sm" @click="row.toggleDetails(), loadUserBankAccounts(row.item)" class="mr-2 w-100" variant="primary">
                                                    {{ row.detailsShowing ? 'Hide' : 'View'}}
                                                </b-button>
                                            </template>
                                            <template #row-details="row">
                                                <loading-spinner :showSpinner="false" custom-class="p-1 text-center size-md">
                                                    <b-table class="table-nostriped" small show-empty :items="row.item.bankAccounts" :fields="bankAccountsTableColumns" :busy="row.item.isLoadingBankAccounts">
                                                        <template #table-busy>
                                                            <div class="text-center text-danger my-2">
                                                                <b-spinner class="align-middle"></b-spinner>
                                                                <strong>Loading...</strong>
                                                            </div>
                                                        </template>
                                                        <template #empty>
                                                            <p>No linked bank accounts found.</p>
                                                        </template>
                                                        <template #cell(actions)="{ item }">
                                                            <div class="d-flex flex-row">
                                                                <b-button size="sm" @click="openDepositModal(row.item, item)" class="mr-2" variant="secondary">
                                                                    Make Deposit
                                                                </b-button>
                                                            </div>
                                                        </template>
                                                    </b-table>
                                                </loading-spinner>
                                            </template>
                                        </b-table>
                                    </b-col>
                                </b-row>

                                <b-row align-h="end">
                                    <b-col cols="12">
                                        <pagination-bar :total-rows="totalUserCount" @change="handleChangePagination"></pagination-bar>
                                    </b-col>
                                </b-row>
                            </section>
                        </b-card-body>
                    </b-card>
                </section>
            </b-col>
        </b-row>

        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="make-deposit-modal"
            ref="makeDepositModal"
            @hide="preventCloseWhileLoading"
            @show="initializeDepositModal"
            @hidden="loadUserBankAccounts(depositModal.user)"
        >
            <div class="make-deposit-modal__header-wrapper">
                <div class="make-deposit-modal__header">
                    <h1 class="make-deposit-modal__title">Make deposit on {{ depositModal.bankAccount && depositModal.bankAccount.name }}</h1>
                    <h4 class="make-deposit-modal__sub-title">Negative amount will be assumed as a charge.</h4>
                </div>
                <i class="fas fa-times make-deposit-modal__close-icon" @click="$refs.makeDepositModal.hide()"></i>
            </div>
            <div>
                <app-message type="error" :messages="depositModal.apiErrors" @close="depositModal.apiErrors = []" />

                <div class="make-deposit-modal__form-row">
                    <label class="make-deposit-modal__label" for="amount">Date</label>
                    <date-picker
                        :ref="`date-picker`"
                        :use-utc="false"
                        format="MM/dd/yyyy"
                        :input-class="`datepicker form-control pr-0 d-inline-block bg-white`"
                        class="transaction__date transaction__date--picker"
                        placeholder="date"
                        v-model="depositModal.date"
                    />
                </div>
                <div class="make-deposit-modal__form-row">
                    <label class="make-deposit-modal__label" for="category">Category</label>
                    <v-select
                        :options="transactionCategories"
                        :clearable="false"
                        :searchable="false"
                        :selectable="option => option"
                        v-model="depositModal.category"
                        class="make-deposit-modal__account-select account-select"
                    >
                    </v-select>
                </div>
                <div class="make-deposit-modal__form-row">
                    <label class="make-deposit-modal__label" for="amount">Amount</label>
                    <currency-input
                        v-model.number="depositModal.amount"
                        class="make-deposit-modal__currency-input"
                        :class="{'border border-danger': depositModal.amountValidationError}"
                        @blur="validateDepositAmount"
                    />
                    <div class="make-deposit-modal__validation-error">
                        {{ depositModal.amountValidationError }}
                    </div>
                </div>

                <div class="make-deposit-modal__footer">
                    <b-button variant="primary" @click="makeDeposit" :disabled="isMakeDepositDisabled">
                        <loading-spinner :show-spinner="depositModal.isMakingDeposit" custom-class="size-auto">
                            Make Deposit
                        </loading-spinner>
                    </b-button>
                    <b-button variant="light" class="ml-3 d-none d-sm-inline-block" @click="$refs.makeDepositModal.hide()">
                        Cancel
                    </b-button>
                </div>
            </div>
        </b-modal>
    </b-container>
</template>
<script src="./test-deposit.controller.js"></script>
<style lang="scss" src="./_test-deposit.scss" scoped></style>
