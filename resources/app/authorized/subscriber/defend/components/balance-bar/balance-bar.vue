<template>
    <div class="balanceBarComponent">
        <div>
            <b-row class="account-info">
                <b-col class="d-flex justify-content-between align-items-center">
                    <span class="account-info__accountName">
                        <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon" class="mr-2"/>
                        {{ bankAccount.name }}
                    </span>
                    <span class="text-nowrap">
                        <span class="account-info__balanceText"
                            :id="`account-info__balanceText-${bankAccount.id}`"
                            :class="{ 'account-info__balanceText--synced': balanceSynced }"
                            tabindex="-1"
                        >
                            <span v-if="bankAccount.type === 'credit'">-</span>
                            {{ projectedBalance | currency }}
                        </span>
                        <calculator-popover
                            v-if="defendedBankAccount"
                            :bank-account="defendedBankAccount"
                            popover-triggers="click blur"
                            :target-ref="`account-info__balanceText-${bankAccount.id}`"
                            :id="`balance-bar-calculator-${bankAccount.id}`"
                            :show-assignment-adjustment="true"
                            :balance-label="bankAccount.parent_bank_account_id ? 'Current balance' : 'Current bank balance'"
                            :available-balance-label="bankAccount.parent_bank_account_id ? 'New balance' : 'New bank balance'"
                            hide-icon
                        />
                    </span>
                </b-col>
            </b-row>
            <b-row>
                <b-col>
                    <div class="balance-bar">
                        <div class="balance-bar__colored balance-bar__colored--dark-balance"
                            :class="[`background-color-${bankAccount.color}`]" ref="darkBalance"
                        >
                        </div>
                        <div class="balance-bar__colored balance-bar__colored--light-balance"
                            :class="[`background-color-${bankAccount.color}`]" ref="lightBalance"
                        >
                        </div>
                    </div>
                </b-col>
            </b-row>
        </div>
    </div>
</template>

<script src="./balance-bar.controller.js"></script>
<style lang="scss" src="./_balance-bar.scss" scoped></style>
