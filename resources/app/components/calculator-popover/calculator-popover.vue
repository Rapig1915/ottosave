<template>
    <span class="calculatorPopoverComponent" :id="`calculator-popover-container-${bankAccount.id}-${id}`">
        <i :id="popoverTarget" class="fas fa-calculator" tabindex="-1" v-if="!hideIcon"></i>
        <b-popover
            :triggers="popoverTriggers"
            :target="popoverTarget"
            @shown="onPopoverShown"
            :container="`calculator-popover-container-${bankAccount.id}-${id}`"
            ref="popoverElement"
            :offset="offset"
        >
            <div slot="title" class="d-flex justify-content-center align-items-center">
                <bank-account-icon v-if="showBankAccountIcon" :color="bankAccount.color" :icon="bankAccount.icon" class="mr-2" />

                <span class="ml-1 accountName">
                    <strong v-if="institutionName">{{ institutionName }}</strong>
                    {{ bankAccountName }}
                </span>
            </div>
            <div class="d-flex w-100 flex-column">
                <div class="d-flex flex-row justify-content-start mb-1">
                    <div>{{ displayedBalanceLabel }}</div>
                    <div class="ml-auto pl-2">
                        {{ bankAccount.balance_current | currency }}
                    </div>
                </div>
                <div class="d-flex flex-row justify-content-start mb-1" v-if="isAssignmentAdjustmentVisible">
                    <div>
                        <span>{{ assignmentLabel }}</span>
                    </div>
                    <div class="ml-auto pl-2">
                        <span v-if="bankAccount.slug !== 'cc_payoff'">- {{ bankAccount.assignment_balance_adjustment | currency }}</span>
                        <span v-else>+ {{ bankAccount.assignment_balance_adjustment | currency }}</span>
                    </div>
                </div>
                <div class="d-flex flex-row justify-content-start mb-1" v-if="bankAccount.allocation_balance_adjustment !== 0">
                    <div>
                        <span>{{ allocationLabel }}</span>
                    </div>
                    <div class="ml-auto pl-2">
                        <span v-if="bankAccount.allocation_balance_adjustment > 0">+</span>
                        {{ bankAccount.allocation_balance_adjustment | currency }}
                    </div>
                </div>
                <div class="d-flex flex-row justify-content-start mb-1 total" v-if="bankAccount.type !== 'credit'">
                    <div>
                        {{ displayedAvailableBalanceLabel }}
                    </div>
                    <div class="ml-auto pl-2">
                        = {{ bankAccount.balance_available | currency }}
                    </div>
                </div>
                <div v-else>
                    <div class="d-flex flex-row justify-content-start mb-1">
                        <div>
                            Credit limit
                        </div>
                        <div class="ml-auto pl-2">
                            <span><i class="fas fa-divide"></i> -{{ Math.abs(bankAccount.balance_limit) | currency }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-row justify-content-start mb-1 font-weight-semibold">
                        <div>
                            Credit utilization
                        </div>
                        <div class="ml-auto pl-2">
                            = {{ getLimitUsagePercent() }}
                        </div>
                    </div>
                </div>
            </div>
        </b-popover>
    </span>
</template>

<script src="./calculator-popover.controller.js"></script>
<style lang="scss" src="./_calculator-popover.scss" scoped></style>
