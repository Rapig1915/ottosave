<template>
    <div>
        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="money-mover-modal"
            ref="moneyMoverModal"
            @hide="preventCloseWhileLoading"
            @hidden="initializeViewModel"
            @show="initializeViewModel"
        >
            <div class="money-mover-modal__header-wrapper">
                <div class="money-mover-modal__header">
                    <h1 class="money-mover-modal__title">Move money between virtual accounts</h1>
                    <h4 class="money-mover-modal__sub-title">Both virtual accounts must be associated with the same bank account.</h4>
                </div>
                <i class="fas fa-times money-mover-modal__close-icon" @click="$refs.moneyMoverModal.hide()"></i>
            </div>
            <div>
                <app-message type="error" :messages="apiErrors" @close="apiErrors = []" />
                <div class="money-mover-modal__form-row">
                    <label class="money-mover-modal__label" for="from_account">From</label>
                    <v-select
                        :options="fromAccountSelectOptions"
                        :clearable="false"
                        :searchable="false"
                        :selectable="option => option && option.value"
                        v-model="selectedFromAccountOption"
                        class="money-mover-modal__account-select account-select"
                    >
                        <template v-slot:selected-option>
                            <div class="account-select__select-option select-option">
                                <div class="select-option__account-name" :class="{'select-option__account-name--placeholder': !selectedFromAccountOption.value}">
                                    <bank-account-icon
                                        v-if="fromAccount"
                                        class="mr-2 d-inline-block"
                                        :color="fromAccount.color"
                                        :icon="fromAccount.icon"
                                    />
                                    {{ selectedFromAccountOption.label }}
                                </div>
                                <div class="select-option__account-balance" v-if="fromAccount">
                                    {{ fromAccount.balance_available | currency }}
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

                <div class="money-mover-modal__form-row">
                    <label class="money-mover-modal__label" for="to_account">To</label>
                    <v-select
                        :options="toAccountSelectOptions"
                        :clearable="false"
                        :searchable="false"
                        :selectable="option => option && !option.disabled"
                        v-model="selectedToAccountOption"
                        class="money-mover-modal__account-select account-select"
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

                <div class="money-mover-modal__form-row">
                    <label class="money-mover-modal__label" for="amount">Amount</label>
                    <currency-input
                        v-model.number="amount"
                        class="money-mover-modal__currency-input"
                        :class="{'border border-danger': amountValidationError}"
                        @blur="validateAmount"
                    />
                    <div class="money-mover-modal__validation-error">
                        {{ amountValidationError }}
                    </div>
                </div>

                <div class="money-mover-modal__footer">
                    <b-button variant="primary" @click="moveMoney" :disabled="isMoveButtonDisabled">
                        <loading-spinner :show-spinner="isMovingMoney" custom-class="size-auto">
                            Move
                        </loading-spinner>
                    </b-button>
                    <b-button variant="light" class="ml-3 d-none d-sm-inline-block" @click="$refs.moneyMoverModal.hide()">
                        Cancel
                    </b-button>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script src="./money-mover-modal.controller.js"></script>
<style lang="scss" src="./_money-mover-modal.scss" scoped></style>
