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
                        Edit
                    </b-button>
                </div>
                <div v-for="bankAccount in filteredAccounts" :key="bankAccount.id">
                    <div
                        class="bank-account-row"
                        :class="{'bank-account-row--credit': bankAccount.type === 'credit'}"
                    >
                        <div class="bank-account-row__account-name">
                            <span class="mr-2">
                                <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon" />
                            </span>
                            <span class="text-truncate pl-1">
                                {{ bankAccount.name }}
                            </span>
                            <BankConectionErrorIcon :bankAccount="bankAccount" />
                        </div>

                        <div class="bank-account-row__account-balance d-flex justify-content-end align-items-center" :id="`${bankAccount.id}-account-balance`" tabindex="-1">
                            <span v-if="bankAccount.type !== 'credit'" :class="{'text-danger': bankAccount.balance_available < 0}">
                                <span :id="`${bankAccount.id}-negative-balance-warning`">
                                    <i v-if="bankAccount.balance_available < 0"
                                        class="fas fa-exclamation-triangle"
                                    ></i>
                                </span>
                                {{ bankAccount.balance_available | currency }}
                            </span>
                            <span v-else class="credit-balance">{{ bankAccount.balance_available | currency }}</span>
                            <calculator-popover
                                :bank-account="bankAccount"
                                :target-ref="`${bankAccount.id}-account-balance`"
                                :popoverTriggers="popoverTriggers"
                                :show-assignment-adjustment="true"
                                id="balances-panel"
                                hide-icon
                            />
                            <b-popover :target="`${bankAccount.id}-negative-balance-warning`" triggers="hover" placement="left" container="balances-panel__negative-balance-popover-container">
                                Reassign credit card charges or add funds to this account to turn the balance positive.
                            </b-popover>
                        </div>
                    </div>

                    <div class="credit-utilization" v-if="bankAccount.type === 'credit'">
                        <b-progress
                            height="0.5rem"
                            class="credit-utilization__bar"
                        >
                            <b-progress-bar
                                :max="Math.abs(bankAccount.balance_limit)"
                                :value="Math.abs(bankAccount.balance_current)"
                                :variant="getCreditUtilizationColor(bankAccount)"
                            />
                        </b-progress>
                        <div class="credit-utilization__percentage">
                            {{ getLimitUsagePercent(bankAccount) }}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center py-2">
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
    </div>
</template>

<script src="./balances-panel.controller.js"></script>
<style lang="scss" src="./_balances-panel.scss" scoped></style>
