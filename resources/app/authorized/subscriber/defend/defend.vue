<template>
    <b-container class="defend-container">
        <app-message
            type="error"
            :messages="apiErrors"
            @close="apiErrors = []">
        </app-message>

        <b-modal v-model="showSuccessMessage" hide-header hide-footer centered static class="success-message">
            <b-row>
                <b-col>
                    <div class="d-flex align-items-center">
                        <img src="~vue_root/assets/fonts/logo_icon.svg" alt="app logo" class="success-message__appLogo">
                    </div>
                </b-col>
                <b-col cols="8" class="d-flex align-items-center">
                    <div class="text-center">
                        <h2 class="success-message__title mb-0">Nice work!</h2>
                        <p class="success-message__subTitle mb-0">Your money is organized.</p>
                    </div>
                </b-col>
            </b-row>
        </b-modal>

        <b-row class="alert alert-danger no-gutters mb-3" v-if="showCCPayoffPrompt">
            <div class="col-md-11">
                You have credit card charges that need to be assigned. <router-link :to="{ name: 'assign' }">Assign Charges.</router-link>
            </div>
        </b-row>

        <b-row>
            <b-col cols="12" xl="6" class="left-panel">
                <section>
                    <loading-spinner :showSpinner="initializingBankAccounts" :class="{'text-center py-4': initializingBankAccounts}">
                        <b-card :no-body="transferView">
                            <div class="account-list pb-3 transferPanelHeader otto-card-body-px" v-if="transferView">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <h1 class="text-nowrap">
                                        <span>Transfer your money</span>
                                        <info-popover id="sync-header-info-popover">
                                            <template slot="title">
                                                Transfer your money
                                            </template>
                                            <template slot="content">
                                                <p>
                                                    Based on your current assigned charges and your plan for organizing your income, Otto will calculate any transfers you need to make between your Primary Checking, your Primary Savings and any other individual accounts you have.
                                                </p>
                                                <p>
                                                    Check the box(es) once you have made the transfer(s) at your bank or credit union and Otto will organize the money within your virtual accounts.
                                                </p>
                                                <p>
                                                    If you do not have any transfers to make, click the “Organize money” button and Otto will organize the money within your virtual accounts.
                                                </p>
                                            </template>
                                        </info-popover>
                                    </h1>
                                    <bank-account-link
                                        v-if="income_account && allocations.length"
                                        :bankAccount="income_account"
                                        @updated="income_account.online_banking_url = $event.online_banking_url"
                                    />
                                </div>

                                <div v-if="allocations.length" class="account-list-title-col">
                                    <b-row class="pt-2">
                                        <b-col>
                                            <p>
                                                Make the following transfer(s) at your bank or credit union and Otto will organize the money within your virtual accounts. Check the box(es) when completed.
                                            </p>
                                        </b-col>
                                        <b-col class="px-0 pt-1 flex-grow-0 flex-shrink-1">
                                            <i class="exampleCheckbox far fa-check-square"></i>
                                        </b-col>
                                    </b-row>
                                </div>
                            </div>
                            <div class="account-list mx-md-4 mx-sm-3" v-if="!transferView">
                                <div class="align-items-center d-flex">
                                    <h1>
                                        Organize your income
                                        <info-popover id="organize-header-info-popover">
                                            <template slot="title">
                                                Organize your income
                                            </template>
                                            <template slot="content">
                                                <p>On this page you will make a plan for organizing your income. Your scheduled amounts appear in the entry boxes in gray. Click in the field to enter these amounts, or to enter a different amount. Click on the <i class="icon-dym-calendar-list" tabindex="-1"></i> icons to edit your Account Schedules.</p>
                                                <p>As you make your plan, your available balances will adjust accordingly. Hover over the <i class="fas fa-calculator" tabindex="-1" aria-hidden="true"></i> icons to see these calculations.</p>
                                            </template>
                                        </info-popover>
                                    </h1>
                                </div>
                                <div class="organizeDirections">
                                    Make a plan for organizing your income into the accounts below.
                                </div>
                            </div>
                            <div class="account-list sticky-balance border-bottom mx-md-4 mx-sm-3" v-if="!transferView">
                                <div class="align-items-center d-flex justify-content-start pt-2 pb-3 mt-1">
                                    <div class="account-list-name-col">
                                        <div class="allocationAccountName">
                                            <bank-account-icon :color="income_account.color" :icon="income_account.icon" class="mr-2"/>
                                            {{income_account.name}}
                                        </div>
                                    </div>
                                    <div class="account-list-balance-col income-deposit-account ml-auto mr-3">
                                        <div class="d-flex justify-content-end xl-text">
                                            <span class="pl-2 align-self-center balance-text text-nowrap">$ {{ incomeLeftToAllocateDisplay }}</span>
                                        </div>
                                    </div>

                                    <div class="account-list-help-col"></div>
                                </div>
                            </div>
                            <div class="account-list" :class="{'transferPage': transferView}" id="account-list-defend">
                                <div v-if="!transferView" class="mx-md-4 mx-sm-3">
                                    <div id="warning-popover-container" class="warning-popover"></div>
                                    <div v-for="(allocation, index) in allocations"
                                        :key="index"
                                        class="allocationLine d-flex align-items-center justify-content-start py-2"
                                    >
                                        <div class="account-list-name-col">
                                            <div class="py-1">
                                                <div class="allocationAccountName">
                                                    <bank-account-icon :color="allocation.bank_account.color" :icon="allocation.bank_account.icon" class="d-inline-block mr-2" />
                                                    <span :title="allocation.bank_account.name">
                                                        {{ allocation.bank_account.name }}
                                                    </span>
                                                </div>
                                                <div class="allocation-account-balance">
                                                    <span class="float-left mt-1">
                                                        <i v-if="allocation.bank_account.defendedCopy.balance_available < 0"
                                                            :id="`defense-dym-balance-${allocation.bank_account.id}`"
                                                            class="fas fa-exclamation-triangle text-danger"
                                                        ></i>
                                                    </span>

                                                    <span class="balance-text"
                                                        :id="`${allocation.bank_account.id}-account-balance`"
                                                        :class="{'text-danger': (allocation.bank_account.defendedCopy.balance_available < 0)}"
                                                        tabindex="-1">
                                                        {{ allocation.bank_account.defendedCopy.balance_available | currency }}
                                                    </span>
                                                    <calculator-popover
                                                        :bank-account="allocation.bank_account.defendedCopy"
                                                        :target-ref="`${allocation.bank_account.id}-account-balance`"
                                                        :popover-triggers="calculatorPopoverTriggers"
                                                        :id="`defense-dym-breakdown-${allocation.bank_account.id}`"
                                                        :show-assignment-adjustment="true"
                                                        :balance-label="allocation.bank_account.parent_bank_account_id ? 'Current balance' : 'Current bank balance'"
                                                        :available-balance-label="allocation.bank_account.parent_bank_account_id ? 'New balance' : 'New bank balance'"
                                                        hide-icon
                                                    />
                                                    <b-popover
                                                        v-if="allocation.bank_account.defendedCopy.balance_available < 0"
                                                        triggers="hover focus blur"
                                                        :target="`defense-dym-balance-${allocation.bank_account.id}`"
                                                        container="#warning-popover-container"
                                                        placement="bottom"
                                                    >
                                                        <span>Reassign credit card charges or add funds to this account to turn the balance positive.</span>
                                                    </b-popover>
                                                </div>
                                            </div>
                                        </div>
                                        <currency-input
                                            :id="'currency-' + allocation.bank_account_id"
                                            :ref="'ref-' + allocation.bank_account_id"
                                            @focus="cachePreviousValue(allocation)"
                                            @blur="validateNewBalance(allocation)"
                                            class="account-list-balance-col ml-auto mr-3"
                                            :class="{'border border-danger': allocation.hasValidationError}"
                                            v-model.number="allocation.amount"
                                            :disabled="!allocation.editable"
                                            enable-autofill
                                            :autofill-value="accountScheduleTotals[allocation.bank_account.id] || ''"
                                        >
                                        </currency-input>
                                        <div class="account-list-help-col" :id="'help-tip-'+allocation.bank_account_id">
                                            <div class="py-2">
                                                <i class="icon-dym-calendar-list clickable"
                                                    :class="[`color-${allocation.bank_account.color}`]"
                                                    @click="$refs[`accountScheduleModal-${allocation.bank_account_id}`][0].show()"
                                                ></i>
                                                <AccountScheduleModal
                                                    @total-updated="updateAccountScheduleTotals(allocation.bank_account, $event)"
                                                    :bank-account="allocation.bank_account"
                                                    :ref="`accountScheduleModal-${allocation.bank_account_id}`"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="allocationErrorMessage" class="allocation-error-message" @click="allocationErrorMessage = ''">
                                        {{ allocationErrorMessage }}
                                        <i class="fas fa-times close-icon"></i>
                                    </div>
                                </div>
                                <div v-if="transferView" id="transfer-allocation-list">
                                    <div v-for="(allocation, index) in allocations"
                                        :key="index"
                                        class="allocation d-flex otto-card-body-px calculated-transfer"
                                    >
                                        <b-container fluid>
                                            <b-row class="calculated-transfer__transfer-amount-row mb-2">
                                                <b-col cols="3" class="pl-0 calculated-transfer__label-column">
                                                    <span class="balance-text">
                                                        Transfer
                                                    </span>
                                                </b-col>
                                                <b-col class="pl-0 calculated-transfer__transfer-amount">
                                                    <strong>
                                                        {{ allocation.amount | currency }}
                                                    </strong>
                                                    <i :id="`allocation-dym-explanation-${allocation.bank_account.id}-${allocation.from_account.id}`" class="fas fa-calculator ml-2" tabindex="-1"></i>
                                                    <b-popover
                                                        :triggers="calculatorPopoverTriggers"
                                                        :target="`allocation-dym-explanation-${allocation.bank_account.id}-${allocation.from_account.id}`"
                                                        container="transfer-allocation-list"
                                                        @shown="$event.target.focus()"
                                                    >
                                                        <div slot="title" class="d-flex justify-content-center align-items-center">
                                                            <span v-if="allocation.metadata.allocationType === 'ida_to_cc_payoff' || allocation.metadata.allocationType === 'savings_to_cc_payoff'">
                                                                <bank-account-icon :color="allocation.from_account.color" :icon="allocation.from_account.icon" class="mr-2" v-if="!allocation.metadata.isFromParentAccount" />
                                                                <span class="ml-1" v-if="allocation.from_account.institution_account">
                                                                    <strong>{{ allocation.from_account.institution_account.institution.name }}</strong>
                                                                    {{ allocation.from_account.institution_account.name }} x-{{ allocation.from_account.institution_account.mask }}
                                                                </span>
                                                                <span class="ml-1" v-else-if="['primary_savings', 'primary_checking'].includes(allocation.from_account.slug)">
                                                                    {{ (allocation.from_account.slug === 'primary_checking' ? 'Primary Checking' : 'Primary Savings') }}
                                                                </span>
                                                                <span class="ml-1" v-else>
                                                                    {{ allocation.from_account.name }}
                                                                </span>
                                                            </span>
                                                            <span v-else-if="allocation.metadata.allocationType === 'ida_to_savings'">
                                                                <bank-account-icon :color="allocation.bank_account.color" :icon="allocation.bank_account.icon" class="mr-2" v-if="!['primary_savings', 'primary_checking'].includes(allocation.bank_account.slug)" />
                                                                <span class="ml-1">{{ allocation.bank_account.name }}</span>
                                                                <span class="ml-1" v-if="allocation.bank_account.institution_account">
                                                                    <strong>{{ allocation.bank_account.institution_account.institution.name }}</strong>
                                                                    {{ allocation.bank_account.institution_account.name }} x-{{ allocation.bank_account.institution_account.mask }}
                                                                </span>
                                                                <span class="ml-1" v-else-if="['primary_savings', 'primary_checking'].includes(allocation.bank_account.slug)">
                                                                    {{ (allocation.bank_account.slug === 'primary_checking' ? 'Primary Checking' : 'Primary Savings') }}
                                                                </span>
                                                                <span class="ml-1" v-else>
                                                                    {{ allocation.bank_account.name }}
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div class="d-flex justify-content-start align-items-center">
                                                            <span v-if="allocation.metadata.allocationType === 'ida_to_cc_payoff'">
                                                                <div class="d-flex w-100 flex-column">
                                                                    <div class="d-flex flex-row justify-content-start mb-1">
                                                                        <div>Total assigned charges</div>
                                                                        <div class="ml-auto pl-2">
                                                                            + {{ allocation.metadata.assignmentTotal | currency }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex flex-row justify-content-start mb-1">
                                                                        <div>Already transferred</div>
                                                                        <div class="ml-auto pl-2">
                                                                            - {{ allocation.metadata.amountOfOtherTransfers | currency }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex flex-row justify-content-start mb-1 font-weight-semibold">
                                                                        <div>
                                                                            Final transfer needed
                                                                        </div>
                                                                        <div class="ml-auto pl-2">
                                                                            = {{ allocation.amount | currency }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </span>
                                                            <span v-else>
                                                                <div class="d-flex w-100 flex-column">
                                                                    <div class="d-flex flex-row justify-content-start mb-1">
                                                                        <div>
                                                                            <span v-if="allocation.metadata.isFromParentAccount">Total organized</span>
                                                                            <span v-else>Organized</span>
                                                                            income</div>
                                                                        <div class="ml-auto pl-2">
                                                                            + {{ allocation.metadata.allocatedAmount | currency }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex flex-row justify-content-start mb-1">
                                                                        <div>
                                                                            <span v-if="allocation.metadata.isFromParentAccount">Total assigned</span>
                                                                            <span v-else>Assigned</span>
                                                                            charges</div>
                                                                        <div class="ml-auto pl-2">
                                                                            - {{ allocation.metadata.assignmentTotal | currency }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex flex-row justify-content-start mb-1 font-weight-semibold">
                                                                        <div>
                                                                            <span v-if="allocation.metadata.isPlaceholderTransfer">No Transfer needed</span>
                                                                            <span v-else-if="allocation.metadata.allocationType === 'ida_to_savings'">Transfer in</span>
                                                                            <span v-else>Transfer out</span>
                                                                        </div>
                                                                        <div class="ml-auto pl-2">
                                                                            = {{ allocation.amount | currency }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </span>
                                                        </div>
                                                    </b-popover>
                                                </b-col>
                                                <b-col class="text-right pr-0">
                                                    <span v-if="(allocation.transferred && !allocation.isTransferring) || allocation.metadata.isPlaceholderTransfer" class="d-inline-flex align-items-center">
                                                        <i class="fas fa-check-circle mr-2 text-primary"></i>
                                                        <strong>
                                                            Transferred
                                                        </strong>
                                                    </span>
                                                    <loading-spinner :showSpinner="allocation.isTransferring" custom-class="size-auto" v-else>
                                                        <b-checkbox class="secondary transfer-checkbox mr-0"
                                                            v-model="allocation.transferred"
                                                            @input="transferAllocation(allocation, true)"
                                                        >
                                                        </b-checkbox>
                                                    </loading-spinner>
                                                </b-col>
                                            </b-row>
                                            <b-row class="calculated-transfer__account-name-row allocationAccountName mb-2">
                                                <b-col cols="3" class="px-0 calculated-transfer__label-column">
                                                    From
                                                </b-col>
                                                <b-col class="pl-0 d-flex align-items-center">
                                                    <bank-account-icon :color="allocation.from_account.color" :icon="allocation.from_account.icon" v-if="!allocation.metadata.isFromParentAccount" />
                                                    <span class="ml-2 allocationAccountName">
                                                        <span v-if="allocation.from_account.institution_account">
                                                            {{ allocation.from_account.institution_account.name }} x-{{ allocation.from_account.institution_account.mask }}
                                                        </span>
                                                        <span v-else-if="['primary_savings', 'primary_checking'].includes(allocation.from_account.slug)">
                                                            {{ (allocation.from_account.slug === 'primary_checking' ? 'Primary Checking' : 'Primary Savings') }}
                                                        </span>
                                                        <span v-else>
                                                            {{ allocation.from_account.name }}
                                                        </span>
                                                    </span>
                                                </b-col>
                                            </b-row>
                                            <b-row class="calculated-transfer__account-name-row allocationAccountName mb-2">
                                                <b-col cols="3" class="px-0 calculated-transfer__label-column">
                                                    To
                                                </b-col>
                                                <b-col class="px-0 d-flex align-items-center">
                                                    <bank-account-icon :color="allocation.bank_account.color" :icon="allocation.bank_account.icon" v-if="!allocation.metadata.isToParentAccount" />
                                                    <span class="ml-2 allocationAccountName">
                                                        <span v-if="allocation.bank_account.institution_account">
                                                            {{ allocation.bank_account.institution_account.name }} x-{{ allocation.bank_account.institution_account.mask }}
                                                        </span>
                                                        <span v-else-if="['primary_savings', 'primary_checking'].includes(allocation.bank_account.slug)">
                                                            {{ (allocation.bank_account.slug === 'primary_checking' ? 'Primary Checking' : 'Primary Savings') }}
                                                        </span>
                                                        <span v-else>
                                                            {{ allocation.bank_account.name }}
                                                        </span>
                                                    </span>
                                                </b-col>
                                            </b-row>
                                        </b-container>
                                    </div>

                                    <div class="otto-card-body-px pt-4">
                                        <p v-if="!allocations.length && !internalAllocations.length" class="mb-4">You do not have any transfers to make at this time.</p>

                                        <p v-if="!allocations.length && !internalAllocations.length" class="mb-4">Your money is organized.</p>

                                        <div v-if="internalAllocations.length && !allocations.length">
                                            <p class="mb-4">You do not have any transfers to make at this time.</p>
                                            <div class="mb-4">
                                                Click the button below and Otto will organize the money within your virtual accounts.
                                            </div>
                                            <div class="d-flex justify-content-center">
                                                <b-button block variant="primary" class="px-5" @click="makeAllInternalTransfers" :disabled="isCompletingInternalTransfers">
                                                    <loading-spinner :show-spinner="isCompletingInternalTransfers" custom-class="size-auto">
                                                        Organize money
                                                    </loading-spinner>
                                                </b-button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="d-print-none warning-popover mt-4 mx-md-4 mx-sm-3" id="createTransferButton">
                                <b-button v-if="!transferView && !calculatingAllocations"
                                    variant="primary"
                                    block
                                    size="md"
                                    @click="navigateToTransferView"
                                    :disabled="hasNegativeBalance"
                                >
                                    Next
                                </b-button>
                                <div v-if="!transferView && calculatingAllocations" class="text-center">
                                    <loading-spinner :showSpinner="calculatingAllocations" customClass="size-auto"></loading-spinner>
                                    Just a moment while we calculate your <br>
                                    DYM Transfer List.
                                </div>
                            </div>
                            <b-popover v-if="hasNegativeBalance"
                                container="#createTransferButton"
                                target="createTransferButton"
                                placement="top"
                                triggers="click"
                                content="See alerts above"
                            />
                        </b-card>
                    </loading-spinner>
                </section>
            </b-col>

            <b-col cols="12" xl="6" class="d-print-none">
                <section>
                    <loading-spinner :showSpinner="initializingBankAccounts" :class="{'text-center py-4': initializingBankAccounts}">
                        <b-card v-if="!transferView">
                            <div id="chart-view-main" class="chart-view">
                                <chartist
                                    ref="defendChart"
                                    class="ct-square"
                                    type="Pie"
                                    :data="chart_data"
                                    :options="chart_options"
                                    :event-handlers="chart_event_handlers"
                                >
                                </chartist>
                                <div class="totals justify-content-center align-items-center d-flex">
                                    <div class="my-auto">
                                        <h2><span class="larger-text">{{getTotalCheckingAndSavings | currency}}</span></h2>
                                    </div>
                                </div>
                            </div>
                            <div v-if="isChartDrawn" class="chart-calculator-popovers">
                                <div v-for="(slice) in chart_data.series" :key="slice.meta.id">
                                    <calculator-popover
                                        :bank-account="slice.meta.defendedBankAccount"
                                        :show-assignment-adjustment="slice.meta.target !== 'ct-payoff'"
                                        :target-ref="slice.meta.target"
                                        :ref="'ct-'+slice.meta.id"
                                        popover-triggers="click blur"
                                        hide-icon
                                        :balance-label="slice.meta.defendedBankAccount.parent_bank_account_id || slice.meta.target === 'ct-payoff' ? 'Current balance' : 'Current bank balance'"
                                        :available-balance-label="slice.meta.defendedBankAccount.parent_bank_account_id ? 'New balance' : 'New bank balance'"
                                    />
                                </div>
                            </div>

                        </b-card>

                        <b-card no-body class="pb-5" v-if="transferView">
                            <div class="otto-card-body-px">
                                <h1 class="mb-0">
                                    Your new balances
                                    <info-popover id="balance-bar-info-popover">
                                        <template slot="title">
                                            Your new balances
                                        </template>
                                        <template slot="content">
                                            <p>
                                                These bars show the difference between your current balances and your prospective new balances. The smaller of the two is darker and the larger is lighter. The amounts are shown in relation to the nearest thousand.
                                            </p>
                                            <p>
                                                The two bars move together as Otto organizes your money.
                                            </p>
                                        </template>
                                    </info-popover>
                                </h1>
                            </div>
                            <div v-for="bankAccount in allBankAccounts" :key="bankAccount.id" class="otto-card-body-px">
                                <balance-bar
                                    :bankAccount="bankAccount"
                                    :allocations="calculatedAllocations"
                                    :userAllocations="userAllocations"
                                    :ref="`balance-bar-${bankAccount.id}`"
                                />
                            </div>
                        </b-card>
                    </loading-spinner>
                </section>
            </b-col>
        </b-row>
        <transfer-warning-modal ref="transferWarningModal"></transfer-warning-modal>
        <leaving-organize-modal ref="leavingOrganizeModal"></leaving-organize-modal>
        <partial-transfer-modal ref="partialTransferModal"></partial-transfer-modal>
        <b-toast id="example-toast" title="Uh oh!" variant="danger" no-auto-hide solid>
            Looks like some of your accounts are in the red. You must allocate funds into the negative accounts or <router-link :to="{ name: 'assign' }">re-assign charges</router-link> before initiating transfers.
        </b-toast>
    </b-container>
</template>
<script src="./defend.controller.js"></script>
<style lang="scss" src="./_defend.scss" scoped></style>
