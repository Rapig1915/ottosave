<template>
    <div class="splitTransactionModal">
        <b-modal
            header-border-variant="0"
            hide-footer
            ref="splitTransactionsModal"
            size="xl"
            title-tag="div"
            @hide="confirmUnsavedChanges"
            static
        >
            <div slot="modal-title">
                <h1>Split</h1>
            </div>

            <app-message
                class="mt-3"
                type="error"
                :messages="errorMessages"
                @close="errorMessages = []">
            </app-message>

            <loading-spinner :showSpinner="isLoadingParentTransaction"
                :class="{ 'text-center my-3': isLoadingParentTransaction }">
                <div class="split-transaction__container">
                    <div
                        v-for="(splitTransaction, index) in parentTransaction.split_transactions"
                        :key="index"
                        class="split-transaction"
                    >
                        <b-form-input type="text"
                            v-model="splitTransaction.merchant"
                            :disabled="!!splitTransaction.assignment"
                            @input="splitTransaction.isDirty = true"
                            @keyup.enter="$event.target.blur()"
                            enterkeyhint="done"
                            class="split-transaction__description"
                        />

                        <div class="split-transaction__amount" :id="`split-amount-input-${index}`">
                            <currency-input
                                :formatOnBlur="true"
                                :formatOnInput="false"
                                :disabled="!!splitTransaction.assignment || index === 0"
                                v-model="splitTransaction.displayedAmount"
                                @blur="validateSplitAmount(splitTransaction)"
                                @input="splitTransaction.isDirty = true"
                                :class="{ 'border border-danger': splitTransaction.validationMessage }"
                                @keyup.enter="$event.target.blur()"
                                enterkeyhint="done"
                            />
                            <b-tooltip variant="danger" :target="`split-amount-input-${index}`" triggers="hover" :disabled="!splitTransaction.validationMessage">
                                {{splitTransaction.validationMessage}}
                            </b-tooltip>
                        </div>

                        <b-button
                            v-show="index > 0 && !splitTransaction.assignment"
                            variant="white"
                            class="split-transaction__delete-button"
                            @click="removeSplitTransaction(splitTransaction, index)"
                        >
                            <i class="far fa-trash-alt"></i>
                        </b-button>
                    </div>
                </div>

                <div>
                    <b-button variant="link" @click="splitAgain" class="split-transaction__split-again-button">
                        <i class="fas fa-plus-circle"></i> Split again
                    </b-button>
                </div>

                <div class="split-transaction__action-buttons">
                    <b-button
                        v-if="dirtyTransactions.length"
                        variant="muted-success"
                        @click="save"
                        :disabled="isSaveButtonDisabled"
                    >
                        <loading-spinner :showSpinner="isSaving" customClass="size-auto">
                            Save
                        </loading-spinner>
                    </b-button>

                    <b-button
                        v-if="dirtyTransactions.length"
                        variant="light"
                        @click="cancelChanges"
                    >
                        Cancel
                    </b-button>

                    <b-button
                        v-else
                        variant="light"
                        @click="hideModal(true)"
                    >
                        Close
                    </b-button>
                </div>
            </loading-spinner>
            <b-modal
                ref="confirmUnsavedChangesModal"
                size="sm"
                hide-header
                centered
                no-close-on-backdrop
                no-close-on-esc
                footer-class="justify-content-around">
                You have unsaved changes, are you sure you want to close?
                <template slot="modal-footer">
                    <b-button variant="light" @click="$refs.confirmUnsavedChangesModal.hide()" class="px-3">
                        Go Back
                    </b-button>
                    <b-button variant="light" @click="hideModal(true)" class="px-3">
                        Close
                    </b-button>
                </template>
            </b-modal>
        </b-modal>
    </div>
</template>

<script src="./split-transaction-modal.controller.js"></script>
<style lang="scss" src="./_split-transaction-modal.scss" scoped></style>
