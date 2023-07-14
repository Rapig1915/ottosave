<template>
    <div>
        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="transaction-mover-modal"
            ref="transactionMoverModal"
            @hide="preventCloseWhileLoading"
            @hidden="() => initializeViewModel(false)"
            @show="initializeViewModel"
        >
            <div class="transaction-mover-modal__header-wrapper">
                <div class="transaction-mover-modal__header">
                    <h1 class="transaction-mover-modal__title">Move transaction</h1>
                </div>
                <i class="fas fa-times transaction-mover-modal__close-icon" @click="$refs.transactionMoverModal.hide()"></i>
            </div>
            <div>
                <app-message type="error" :messages="apiErrors" @close="apiErrors = []" />
                <div class="transaction-mover-modal__transaction-row" v-if="transaction">
                    <input class="description" v-model="transaction.merchant" @keyup="validateMove()" />
                    <span class="amount">{{ -transaction.amount | currency }}</span>
                    <b-button variant="white"
                        @click="showDeleteTransactionConfirmModal"
                        :disabled="isDeleting"
                    >
                        <loading-spinner :show-spinner="isDeleting" custom-class="size-auto">
                            <i class="far fa-trash-alt"></i>
                        </loading-spinner>
                    </b-button>
                </div>

                <div class="transaction-mover-modal__form-row">
                    <v-select
                        :options="toAccountSelectOptions"
                        :clearable="false"
                        :searchable="false"
                        :selectable="option => option && !option.disabled"
                        v-model="selectedToAccountOption"
                        class="transaction-mover-modal__account-select account-select"
                    >
                        <template v-slot:selected-option>
                            <div class="account-select__select-option select-option">
                                <div class="select-option__account-name" :class="{'select-option__account-name--placeholder': !selectedToAccountOption.value}">
                                    <bank-account-icon
                                        v-if="toAccount"
                                        class="mr-2 d-inline-block"
                                        :color="toAccount.color"
                                        :icon="toAccount.icon"
                                    />
                                    {{ selectedToAccountOption.label }}
                                </div>
                                <div class="select-option__account-balance" v-if="toAccount">
                                    {{ toAccount.balance_available | currency }}
                                </div>
                            </div>
                        </template>

                        <template v-slot:option="option">
                            <div class="account-select__select-option select-option" v-show="option.value !== null">
                                <div class="select-option__account-name">
                                    <bank-account-icon
                                        v-if="option.value"
                                        class="mr-2 d-inline-block"
                                        :color="option.value.color"
                                        :icon="option.value.icon"
                                    />
                                    {{ option.label }}
                                </div>
                                <div class="select-option__account-balance" v-if="option.value">
                                    {{ option.value.balance_available | currency }}
                                </div>
                            </div>
                        </template>
                    </v-select>
                </div>

                <div class="transaction-mover-modal__validation-error">
                    {{ validationError }}
                </div>

                <div class="transaction-mover-modal__footer">
                    <b-button variant="primary" @click="move" :disabled="isMoveButtonDisabled">
                        <loading-spinner :show-spinner="isMoving" custom-class="size-auto">
                            Move transaction
                        </loading-spinner>
                    </b-button>
                </div>
            </div>
        </b-modal>

        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="delete-transaction-confirm-modal"
            ref="deleteTransactionConfirmModal"
        >
            <div class="delete-transaction-confirm-modal__header-wrapper">
                <div class="delete-transaction-confirm-modal__header">
                    <h3 class="delete-transaction-confirm-modal__title">Delete this transaction?</h3>
                </div>
            </div>
            <div>
                <div class="delete-transaction-confirm-modal__transaction-row" v-if="transaction">
                    <span class="date">{{ transaction.remote_transaction_date | formatDate }}</span>
                    <span class="description">{{ transaction.merchant }}</span>
                    <span class="amount">{{ -transaction.amount | currency }}</span>
                </div>

                <div class="delete-transaction-confirm-modal__footer">
                    <b-button variant="danger" @click="onConfirmDeleteTransaction(true)">
                        Yes, delete
                    </b-button>
                    <b-button variant="light" @click="onConfirmDeleteTransaction(false)">
                        Cancel
                    </b-button>
                </div>

                <div class="delete-transaction-confirm-modal__remark">
                    *Deleting a transaction will not affect the the balances of your accounts.
                </div>
            </div>
        </b-modal>

        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="update-description-confirm-modal"
            ref="updateDescriptionConfirmModal"
        >
            <div class="update-description-confirm-modal__header-wrapper">
                <div class="update-description-confirm-modal__header">
                    <h3 class="update-description-confirm-modal__title">Save edited description?</h3>
                </div>
            </div>
            <div>
                <div class="update-description-confirm-modal__footer">
                    <b-button variant="light" @click="revertDescriptionAndClose" :disabled="isSavingDescription">
                        Cancel
                    </b-button>
                    <b-button variant="primary" @click="updateDescription" :disabled="isSavingDescription">
                        <loading-spinner :show-spinner="isSavingDescription" custom-class="size-auto">
                            Save
                        </loading-spinner>
                    </b-button>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script src="./transaction-mover-modal.controller.js"></script>
<style lang="scss" src="./_transaction-mover-modal.scss" scoped></style>
