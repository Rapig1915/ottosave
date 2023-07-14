<template>
    <div class="balancesPanelComponent">
        <b-card class="balance-panel-card">
            <div class="otto-card-body-px">
                <div class="d-flex mb-2 align-items-center justify-content-between">
                    <div>
                        <slot name="title"></slot>
                    </div>
                    <b-button variant="link" to="accounts" class="py-0 pr-0" v-show="!hideEditButton && !useCustomEditAction">
                        Edit
                    </b-button>
                    <b-button variant="link" @click="$emit('edit')" class="py-0 pr-0" v-show="useCustomEditAction">
                        Move money
                    </b-button>
                </div>
                <div class="bank-account-row-box my-3" v-for="(bankAccount, index) in filteredAccounts" :key="bankAccount.id" :id="`bank-account-row-box-${bankAccount.id}`">
                    <div
                        v-bind:key="index"
                        class="bank-account-row btn-block btn-md"
                        :class="[{'bank-account-row--credit': bankAccount.type === 'credit', 'expanded': bankAccountTransactionVisibility[bankAccount.id]}, bankAccount.color]"
                    >
                        <div class="bank-account-row-content" :id="`bank-account-row-content-${bankAccount.id}`">
                            <div class="bank-account-row__account-name" :class="{ 'clickable': bankAccount.hasAccountSchedule }">
                                <span class="mr-2" @click="openAccountSchedule(bankAccount)">
                                    <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon"/>
                                </span>
                                <span class="text-truncate account-name pl-1" @click="openAccountSchedule(bankAccount)">
                                    {{ bankAccount.name }}
                                </span>
                                <BankConectionErrorIcon :bankAccount="bankAccount" />
                            </div>

                            <div class="bank-account-row__account-balance d-flex justify-content-end align-items-center">
                                <span v-if="bankAccount.type !== 'credit'" :class="{'text-danger': bankAccount.balance_available < 0}" :id="`${bankAccount.id}-account-balance-dashboard`" tabindex="-1">
                                    <span :id="`${bankAccount.id}-negative-balance-warning`">
                                        <i v-if="bankAccount.balance_available < 0"
                                            class="fas fa-exclamation-triangle"
                                        ></i>
                                    </span>
                                    <span class="credit-balance">{{ bankAccount.balance_available | currency }}</span>
                                </span>
                                <span v-else class="credit-balance" :id="`${bankAccount.id}-account-balance-dashboard`" tabindex="-1">{{ bankAccount.balance_available | currency }}</span>
                                <calculator-popover
                                    :bank-account="bankAccount"
                                    :target-ref="`${bankAccount.id}-account-balance-dashboard`"
                                    :popoverTriggers="popoverTriggers"
                                    :show-assignment-adjustment="true"
                                    id="account-balances-panel"
                                    hide-icon
                                />

                                <div class="ml-2">
                                    <i
                                        class="fas fa-caret-up collapse-arrow"
                                        aria-hidden="true"
                                        @click="toggleBankAccountTransactions(bankAccount)"
                                        :class="[{'fa-rotate-180': bankAccountTransactionVisibility[bankAccount.id], 'hasTransactions': (bankAccountTransactions[bankAccount.id] && bankAccountTransactions[bankAccount.id].length)}, bankAccount.color]"
                                    >
                                    </i>
                                </div>
                            </div>
                        </div>
                        <loading-spinner :show-spinner="bankAccountTransactionLoading[bankAccount.id]" custom-class="size-auto py-2">
                            <!-- Individual transactions -->
                            <b-collapse
                                :id="`collapse-transaction-list-${bankAccount.id}`"
                                :visible="bankAccountTransactionVisibility[bankAccount.id]">
                                <div v-if="bankAccountTransactions[bankAccount.id] && bankAccountTransactions[bankAccount.id].length" class="transaction-rows">
                                    <b-row
                                        no-gutters
                                        v-for="transaction in (bankAccountTransactions[bankAccount.id] || [])"
                                        v-bind:key="transaction.id"
                                        class="d-flex transaction-row flex-row flex-nowrap custom-card-content justify-content-between align-items-center"
                                    >
                                        <div class="text-nowrap">
                                            <i class="fas fa-landmark"></i>
                                        </div>
                                        <div class="pl-2 ml-1 transactionDate">
                                            {{ transaction.remote_transaction_date | formatDate }}
                                        </div>
                                        <div class="pl-2 transactionDescription">
                                            {{ transaction.merchant }}
                                        </div>
                                        <div class="text-right pl-2 pr-0 transactionAmount text-nowrap">
                                            {{ -transaction.amount | currency }}
                                            <div class="calculatorPlaceholder"></div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <b-button
                                                @click="$emit('move-transaction', transaction, bankAccount)"
                                                variant="link"
                                                class="move-button px-0"
                                            >
                                                <span class="fa-stack">
                                                    <i class="fas fa-circle fa-stack-2x"></i>
                                                    <i class="fas fa-solid fa-long-arrow-alt-up fa-stack-1x fa-inverse"></i>
                                                    <i class="fas fa-solid fa-long-arrow-alt-down fa-stack-1x fa-inverse"></i>
                                                </span>
                                            </b-button>
                                        </div>
                                    </b-row>
                                </div>
                                <div v-else class="transaction-rows">
                                    <b-row
                                        no-gutters
                                        class="d-flex transaction-row flex-row flex-nowrap custom-card-content justify-content-between align-items-center"
                                    >
                                        No transactions
                                    </b-row>
                                </div>
                                <b-row class="d-flex justify-content-end m-0">
                                    <i
                                        class="fas fa-caret-up collapse-arrow"
                                        :class="[{'hasTransactions': bankAccountTransactions[bankAccount.id] && bankAccountTransactions[bankAccount.id].length}, bankAccount.color]"
                                        aria-hidden="true"
                                        @click="toggleBankAccountTransactions(bankAccount)"
                                    >
                                    </i>
                                </b-row>
                            </b-collapse>
                        </loading-spinner>
                    </div>
                </div>
                <div class="bank-account-row-box is-sticky" key="sticky-scrolling-bank-account" v-if="scrollingBankAccount">
                    <div
                        class="bank-account-row btn-block btn-md"
                        :class="[scrollingBankAccount.color]"
                    >
                        <div class="bank-account-row-content">
                            <div class="bank-account-row__account-name" :class="{ 'clickable': scrollingBankAccount.hasAccountSchedule }">
                                <span class="mr-2" @click="openAccountSchedule(scrollingBankAccount)">
                                    <bank-account-icon :color="scrollingBankAccount.color" :icon="scrollingBankAccount.icon"/>
                                </span>
                                <span class="text-truncate pl-1" @click="openAccountSchedule(scrollingBankAccount)">
                                    {{ scrollingBankAccount.name }}
                                </span>
                                <BankConectionErrorIcon :bankAccount="scrollingBankAccount" />
                            </div>

                            <div class="bank-account-row__account-balance d-flex justify-content-end align-items-center">
                                <span v-if="scrollingBankAccount.type !== 'credit'" :class="{'text-danger': scrollingBankAccount.balance_available < 0}">
                                    <span :id="`${scrollingBankAccount.id}-negative-balance-warning`">
                                        <i v-if="scrollingBankAccount.balance_available < 0"
                                            class="fas fa-exclamation-triangle"
                                        ></i>
                                    </span>
                                    {{ scrollingBankAccount.balance_available | currency }}
                                </span>
                                <span v-else class="credit-balance">{{ scrollingBankAccount.balance_available | currency }}</span>

                                <div class="ml-2">
                                    <i
                                        class="fas fa-caret-up collapse-arrow"
                                        aria-hidden="true"
                                        @click="toggleBankAccountTransactions(scrollingBankAccount)"
                                        :class="[{'fa-rotate-180': bankAccountTransactionVisibility[scrollingBankAccount.id], 'hasTransactions': (bankAccountTransactions[scrollingBankAccount.id] && bankAccountTransactions[scrollingBankAccount.id].length)}, scrollingBankAccount.color]"
                                    >
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center py-2 px-3">
                    <b-col class="pl-0 account-name total-label text-nowrap flex-shrink-1">
                        <bank-account-icon color="gray" icon="square" class="mr-2 opacity-0" />
                        <slot name="total-label">
                            Total
                        </slot>
                    </b-col>
                    <b-col class="px-0 total-balance text-right">
                        <strong>
                            {{ total | currency }}
                        </strong>
                        <i class="fas fa-calculator opacity-0 float-sm-right" tabindex="-1"></i>
                    </b-col>
                </div>
            </div>
            <div :class="{'justify-content-center d-flex bg-white pt-4 otto-card-body-px': $slots.footer}">
                <slot name="footer"></slot>
            </div>
        </b-card>
        <div class="negativeBalancePopoverContainer" id="balances-panel__negative-balance-popover-container">
            <!--
                this gives the negative balance warning popovers a place to live
                for adjusting styles this is an unfortunate necessity with
                popover capabilities in bootstrap-vue "2.0.0-rc.19"
            -->
        </div>

        <AccountScheduleModal v-if="selectedBankAccount" :bank-account="selectedBankAccount" ref="accountScheduleModal" />
    </div>
</template>

<script src="./account-balances-panel.controller.js"></script>
<style lang="scss" src="./_account-balances-panel.scss" scoped></style>
