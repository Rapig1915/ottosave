<template>
    <div class="editTransactionsComponent">
        <b-modal
            header-border-variant="0"
            hide-footer
            static
            ref="editTransactionsModal"
            size="xl"
            title-tag="div"
            @hide="confirmUnsavedChanges"
        >
            <div slot="modal-title">
                <h1>
                    Edit credit card charges
                    <info-popover id="edit-charges-info-popover">
                        <template v-slot:title>
                            Edit credit card charges
                        </template>
                        <template v-slot:content>
                            Use this modal to:
                            <ul>
                                <li>Split a charge in order to assign it to multiple accounts.</li>
                                <li>Edit the description of a charge for easier identification.</li>
                                <li>Delete credit card charges.</li>
                                <li>Manually add a credit card charge.</li>
                            </ul>
                        </template>
                    </info-popover>
                </h1>
            </div>

            <app-message
                type="error"
                :messages="apiErrors"
                @close="apiErrors = []">
            </app-message>
            <loading-spinner :showSpinner="isLoadingTransactions" :class="{ 'text-center': isLoadingTransactions }">
                <div class="transactions__container">
                    <div class="transaction" v-for="(transaction, index) in workingCopyOfUnassignedTransactions" :key="index">
                        <div class="transaction__delete-column">
                            <b-button
                                v-if="!transaction.parent_transaction_id"
                                variant="white"
                                @click="removeTransaction(transaction, index)"
                            >
                                <i class="far fa-trash-alt"></i>
                            </b-button>
                        </div>

                        <div class="transaction__card-icon">
                            <div v-if="transaction.id || creditCardAccounts.length < 2" class="bankIconWrapper">
                            </div>
                            <bank-account-icon
                                v-if="transaction.id || creditCardAccounts.length < 2"
                                :color="creditCardsKeyedById[transaction.bank_account_id].color"
                                :icon="creditCardsKeyedById[transaction.bank_account_id].icon"
                            />
                            <v-select
                                v-else
                                :options="creditCardAccounts"
                                :value="transaction.bank_account_id"
                                @input="transaction.bank_account_id = $event.id"
                                :clearable="false" :searchable="false" label="name"
                            >
                                <template v-slot:selected-option>
                                    <bank-account-icon :color="creditCardsKeyedById[transaction.bank_account_id].color" :icon="creditCardsKeyedById[transaction.bank_account_id].icon"/>
                                </template>

                                <template v-slot:option="creditCard">
                                    <bank-account-icon class="mr-2 d-inline-block" :color="creditCard.color" :icon="creditCard.icon"/>
                                    {{ creditCard.name }}
                                </template>
                            </v-select>
                        </div>

                        <date-picker
                            v-if="!transaction.remote_transaction_id && !transaction.parent_transaction_id"
                            :ref="`date-picker-${index}`"
                            :use-utc="false"
                            format="MM/dd/yyyy"
                            :input-class="`datepicker form-control pr-0 d-inline-block bg-white ${!transaction.remote_transaction_date ? 'placeholder' : ''}`"
                            class="transaction__date transaction__date--picker"
                            placeholder="date"
                            v-model="transaction.remote_transaction_date"
                            @input="handleRemoteTransactionDateChanged(transaction)"
                        />
                        <div
                            v-else
                            class="transaction__date"
                        >
                            {{ transaction.remote_transaction_date | shortDate }}
                        </div>

                        <div class="transaction__description">
                            <b-form-input
                                type="text"
                                placeholder="description"
                                v-model="transaction.merchant"
                                @change="transaction.isDirty = true"
                                @keyup.enter="$event.target.blur()"
                                enterkeyhint="done"
                            />
                        </div>

                        <div class="transaction__split-button">
                            <b-button
                                v-if="transaction.remote_transaction_id || transaction.parent_transaction_id"
                                variant="plain"
                                v-b-tooltip.hover
                                :title="transaction.is_assignable ? 'Split Charge' : 'Edit Split Charge'"
                                @click="splitTransaction(transaction)"
                            >
                                <i class="fas fa-code-branch fa-rotate-90" :class="{ 'text-primary': !transaction.is_assignable }"></i>
                            </b-button>
                        </div>

                        <div class="transaction__amount">
                            <currency-input
                                placeholder="amount"
                                :formatOnBlur="true"
                                :formatOnInput="false"
                                :disabled="!!transaction.remote_transaction_id || !!transaction.parent_transaction_id"
                                v-model="transaction.amount"
                                @input="transaction.isDirty = true"
                            />
                        </div>
                    </div>
                </div>

                <PastTransactions
                    class="mb-2"
                    ref="pastTransactions"
                    @transactions-downloaded="refreshTransactions"
                    :credit-card-accounts="linkedCreditCards"/>

                <b-row>
                    <b-col class="d-flex justify-content-between">
                        <b-button variant="link" @click="add" class="add-transaction-button">
                            <i class="fas fa-plus-circle"></i> Add a charge
                        </b-button>

                        <b-dropdown no-caret variant="gray-f" menu-class="p-0">
                            <template v-slot:button-content>
                                <i class="fas fa-ellipsis-h text-gray-a"></i>
                            </template>
                            <b-dropdown-item @click="deleteUnassignedTransactions">
                                <div class="py-2">
                                    Delete all unassigned charges
                                </div>
                            </b-dropdown-item>
                            <b-dropdown-item @click="$refs.pastTransactions.show()">
                                <div class="py-2">
                                    Upload past charges
                                </div>
                            </b-dropdown-item>
                        </b-dropdown>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col class="d-flex justify-content-center">
                        <b-button
                            v-if="dirtyTransactions.length"
                            @click="bulkSaveAndClose()"
                            :disabled="isBulkSaveInProgress"
                            variant="muted-success"
                            class="px-5 mr-3"
                        >
                            <loading-spinner :show-spinner="isBulkSaveInProgress" custom-class="size-auto">
                                Save
                            </loading-spinner>
                        </b-button>
                        <b-button
                            v-if="dirtyTransactions.length"
                            @click="getUnassignedTransactions"
                            variant="light"
                            class="px-5"
                        >
                            Cancel
                        </b-button>
                        <b-button v-else class="px-5" variant="light" @click="$refs.editTransactionsModal.hide()">
                            Close
                        </b-button>
                    </b-col>
                </b-row>
            </loading-spinner>
        </b-modal>
        <unsaved-changes-modal ref="confirmUnsavedChangesModal" @bulkSaveAndClose="bulkSaveAndClose" @closeWithoutSaving="closeWithoutSaving" :isSavingChanges="isBulkSaveInProgress"/>
        <SplitTransactionModal ref="splitTransactionModal" @close="refreshTransactions"/>
        <loading-spinner :show-spinner="isBulkDeletingTransactions" custom-class="overlay-fixed"></loading-spinner>
        <confirm-delete-modal ref="confirmDeleteModal">
            <div class="confirm-delete-message h2">
                Delete this transaction?
            </div>
        </confirm-delete-modal>
    </div>

</template>
<script type="text/javascript" src="./edit-transactions-modal.controller.js"></script>
<style lang="scss" src="vue_root/assets/scss/_table-input-modal.scss" scoped></style>
<style lang="scss" src="./_edit-transactions-modal.scss" scoped></style>
