<template>
    <b-container class="dashboard-container">
        <app-message
            class="mt-3"
            v-if="errorMessages"
            type="error"
            :messages="errorMessages"
            @close="errorMessages = []">
        </app-message>
        <loading-spinner :show-spinner="isInitializingView" custom-class="overlay fixed"></loading-spinner>
        <b-row>
            <b-col cols="12" xl="6">
                <section class="spending-tracker-panel">
                    <b-card no-body v-if="!loadingSpendingAccountOverview && spending_account.id">
                        <b-card-body class="otto-card-body-px">
                            <b-row>
                                <b-col>
                                    <h2 class="d-inline-block text-nowrap">
                                        Spending Account Tracker
                                        <info-popover id="spending-bar-info-popover">
                                            <template slot="title">
                                                Spending Account Tracker
                                            </template>
                                            <template slot="content">
                                                This tracker is designed to help you pace your everyday spending. If you plan to organize/transfer your money once a month, it paces your spending for the next 30 days. If you plan to organize/transfer your money twice a month, it paces your spending for the next 15 days.
                                            </template>
                                        </info-popover>
                                    </h2>
                                </b-col>
                            </b-row>
                            <b-row class="overflow-hidden pt-2">
                                <b-col>
                                    <div class="progress my-3" ref="progressBar">
                                        <div class="progress-bar left spent flex-row justify-content-start" role="progressbar"
                                            :aria-valuenow="getSpentWidth" aria-valuemin="0" aria-valuemax="100"
                                            :style="{flex: getSpentWidth }">
                                        </div>
                                        <div class="progress-bar right available flex-row justify-content-end" role="progressbar"
                                            :aria-valuenow="getAvailableWidth" aria-valuemin="0" aria-valuemax="100"
                                            :style="{flex: getAvailableWidth}">
                                        </div>
                                        <div class="today-marker d-flex align-items-end" :style="{left: (97 * getTodayOffset) + '%'}">
                                            <div class="text-wrapper">
                                                <div class="today-marker-text px-1"><strong>Today</strong></div>
                                            </div>
                                        </div>
                                        <div class="current-balance">
                                            {{ spending_account.balance_available | currency }}
                                        </div>
                                    </div>
                                </b-col>
                            </b-row>
                            <b-row>
                                <b-col>
                                    <div class="progress-footer d-flex justify-content-between">
                                        <span>
                                            Beginning balance
                                            <br>
                                            {{ spendingAccountBalanceAtDefense | currency }}
                                        </span>

                                        <span class="everyday-spending-status">
                                            <div class="text-left text-sm-right" v-if="daysUntilYouDefendYourMoney > 0">
                                                You can spend
                                                <br>
                                                about <strong>${{parseInt(dailyAllowedSpending)}}/day</strong>
                                            </div>
                                        </span>
                                    </div>
                                </b-col>
                            </b-row>
                        </b-card-body>
                    </b-card>
                </section>

                <!-- Available Balances -->
                <section>
                    <account-balances-panel
                        ref="accountBalancesPanel"
                        :bank-accounts="balancePanelAccounts"
                        @bank-account-updated="updateBankAccountBalance"
                        @refresh-requested="refreshBankAccountsOverview"
                        @edit="$refs.moneyMoverModal.openModal()"
                        @move-transaction="(transaction, bankAccount) => $refs.transactionMoverModal.openModal(transaction, bankAccount)"
                        show-first-savings-info-popover
                        use-custom-edit-action
                    >
                        <template v-slot:title>
                            <h2 class="text-nowrap mb-0">
                                Available Balances
                                <info-popover id="available-balances-info-popover">
                                    <template slot="title">
                                        Available Balances
                                    </template>
                                    <template slot="plus-content">
                                        Your available balances reflect the amount of money you have available in your accounts (balance - assigned credit card charges.) Hover over the <i class="fas fa-calculator" tabindex="-1"></i> icons to see these calculations.
                                        <br>
                                        Your bank balances automatically update each time you log in. Click the <i class="fas fa-sync" tabindex="-1"></i> icon to manually update your bank balances.
                                    </template>
                                    <template slot="basic-content">
                                        Your available balances reflect the amount of money you have available in your accounts (bank balance - assigned credit card charges.) Hover over the <i class="fas fa-calculator" tabindex="-1"></i> icons to see these calculations.
                                        <br>
                                        Click the &quot;Edit&quot; button to manually enter your bank balances, or <a href="javascript:void(0)" @click="$store.dispatch('authorized/DISPLAY_UPGRADE_MODAL')">subscribe today</a> to have your balances update automatically each time you log in.
                                    </template>
                                </info-popover>
                            </h2>
                        </template>
                    </account-balances-panel>
                </section>
            </b-col>

            <b-col cols="12" xl="6">
                <!-- Credit Tracker -->
                <section>
                    <balances-panel
                        :bank-accounts="ccTrackerAccounts"
                        @bank-account-updated="updateBankAccountBalance"
                        @refresh-requested="refreshBankAccountsOverview"
                        @edit="$refs.moneyMoverModal.openModal()"
                        hide-edit-button
                    >
                        <template v-slot:title>
                            <h2 class="text-nowrap mb-0">
                                Credit Card Tracker
                                <info-popover id="cc-tracker-info-popover">
                                    <template slot="title">
                                        Credit Card Tracker
                                    </template>
                                    <template slot="content">
                                        <div>
                                            <p class="mb-1">
                                                The Credit Card Tracker focuses on the two areas that have the highest impact on your credit score.
                                            </p>
                                            <ol class="pl-3 mb-1">
                                                <li>
                                                    Otto ensures that your credit card balances are covered so you can always make your credit card payments on time.
                                                </li>
                                                <li>
                                                    Otto monitors your credit utilization so you can keep it in the Good - Excellent range.
                                                </li>
                                            </ol>
                                            <div class="pl-5">
                                                0 - 10 % Excellent <br />
                                                11 - 30 % Good <br />
                                                31 - 50 % Average <br />
                                                51 - 100 % Below Average <br />
                                            </div>
                                        </div>
                                    </template>
                                </info-popover>
                            </h2>
                        </template>
                        <template v-slot:total-label>
                            Difference
                            <info-popover id="difference-info-popover">
                                <template slot="title">
                                    Difference
                                </template>
                                <template slot="content">
                                    Once all charges are assigned, you should have enough money in your Credit Card Payoff Account to cover your credit card balances and the difference should = zero.
                                    <br>
                                    Occasionally credit card balances upload faster than the charges. If this happens, please be patient. Your charges will upload soon.
                                </template>
                            </info-popover>
                        </template>

                        <template v-slot:footer>
                            <div class="positive-difference-warning" v-if="dymCCBalance > 0">
                                <p class="mt-1 mb-0">
                                    **If the <span class="text-semi-bold">Difference</span> is a positive amount please be patient.
                                </p>
                                <p class="mt-0">
                                    It can take a few days for your credit card payments to post at your bank or credit union.
                                </p>
                            </div>
                        </template>
                    </balances-panel>
                </section>

                <section class="d-print-none">
                    <loading-spinner :showSpinner="initializingBankAccounts" :class="{'text-center py-4': initializingBankAccounts}">
                        <b-card>
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
                                        tabindex="0"
                                        :class="{ 'd-none': isIOS && slice.meta.id !== activeChartSliceId }"
                                        :bank-account="slice.meta.defendedBankAccount"
                                        :show-assignment-adjustment="slice.meta.target !== 'ct-payoff'"
                                        :target-ref="slice.meta.target"
                                        :ref="'ct-'+slice.meta.id"
                                        popover-triggers="click blur"
                                        hide-icon
                                        :balance-label="slice.meta.defendedBankAccount.parent_bank_account_id || slice.meta.target === 'ct-payoff' ? 'Current balance' : 'Current bank balance'"
                                        :available-balance-label="slice.meta.defendedBankAccount.parent_bank_account_id ? 'New balance' : 'New bank balance'"
                                        @popover-shown="activeChartSliceId = slice.meta.id"
                                    />
                                </div>
                            </div>
                        </b-card>
                    </loading-spinner>
                </section>
            </b-col>
        </b-row>
        <money-mover-modal ref="moneyMoverModal" @money-move-success="initializeBankAccounts"/>
        <transaction-mover-modal ref="transactionMoverModal" @transaction-move-success="refreshAccountBalances"/>
        <link-accounts-modal ref="linkAccountsModal"/>
    </b-container>
</template>
<script src="./dashboard.controller.js"></script>
<style lang="scss" src="./_dashboard.scss" scoped></style>
