<template>
    <b-container class="assign-container">
        <app-message
            type="error"
            :messages="apiErrors"
            @close="apiErrors = []">
        </app-message>
        <div id="warning-popover-container"></div>
        <loading-spinner :show-spinner="isInitializingView || isBulkAssigningTransactions" custom-class="overlay fixed"></loading-spinner>
        <b-row>
            <b-col cols="12" xl="6">
                <section>
                    <b-card no-body>
                        <b-card-body class="px-0" :class="{'pb-0': isBulkAssignmentPanelDisplayed}">
                            <div class="assignable-transactions-header mb-2 otto-card-body-px">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h1>
                                        Assign your charges
                                        <info-popover id="assign-view-info-popover">
                                            <template slot="title">
                                                Assign your charges
                                            </template>
                                            <template slot="plus-content">
                                                <p>As you use your credit card(s), your charges will automatically upload onto this page.</p>
                                                <p>Drag and drop, or select to assign each charge to one of your accounts. As you do, your available balances recalculate to reflect how much money you still have available. At the same time, Otto moves the money you’ve spent (assigned for payoff) into your Credit Card Payoff Account. This ensures you always have enough money to pay off your credit card balance(s).</p>
                                                <p>Hover over the <i class="fas fa-calculator"></i> icons to see the balance calculations.</p>
                                            </template>
                                            <template slot="basic-content">
                                                <p>Click the button below to manually enter your credit card charges, or <a href="javascript:void(0)" @click="$store.dispatch('authorized/DISPLAY_UPGRADE_MODAL')">subscribe today</a> to have your credit card charges automatically upload onto this page.</p>
                                                <p>Hover over the <i class="fas fa-calculator"></i> icons to see the balance calculations.</p>
                                            </template>
                                        </info-popover>
                                    </h1>

                                    <b-button variant="link" class="pr-0 py-0" @click="editUnassignedTransactions()" :disabled="fetchingTransactions">
                                        Edit
                                    </b-button>
                                </div>
                                <p class="card-text">
                                    <span v-if="!isMobileScreenSize">
                                        Select, or drag and drop, to assign credit card charges to one of your accounts. Payments are automatically assigned to your Credit Card Payoff Account.
                                    </span>
                                    <span v-else>
                                        Select to assign credit card charges to one of your accounts. Payments are automatically assigned to your Credit Card Payoff Account.
                                    </span>
                                </p>
                                <div v-if="!$store.getters.isInDemoMode">
                                    <b-row v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'basic' }" class="status-panel">
                                        <b-col class="d-flex flex-wrap flex-xl-nowrap align-items-center justify-content-between">
                                            <div class="mr-1">
                                                Subscribe today and your credit card charges will update automatically.
                                            </div>
                                            <b-button variant="outline-primary" class="upgrade-button mx-auto mx-xl-1 my-2" size="md" @click="$store.commit('authorized/TOGGLE_UPGRADE_MODAL', true)">
                                                Subscribe
                                            </b-button>
                                        </b-col>
                                    </b-row>
                                </div>
                            </div>

                            <b-list-group flush>
                                <!-- Unassigned Transactions List -->
                                <div class="transaction-list">
                                    <b-row class="d-flex justify-content-around align-items-center list-group-item no-gutters otto-card-body-px">
                                        <div class="transaction-list__checkbox-column">
                                            <b-form-checkbox
                                                v-if="pendingAssignmentsByDate.length"
                                                :checked="allTransactionsSelected"
                                                @change="allTransactionsSelected = $event"
                                            />
                                        </div>
                                        <div class="transaction-list__icon-column">
                                            <bank-account-icon color="gray" icon="credit-card" class="opacity-0"/>
                                        </div>
                                        <div class="transaction-list__date-column">
                                            <strong>Date</strong>
                                        </div>
                                        <div class="transaction-list__description-column pl-3 text-truncate"><strong>Description</strong></div>
                                        <div class="transaction-list__amount-column text-right"><strong>Amount</strong></div>
                                    </b-row>
                                    <draggable
                                        :list="pendingAssignmentsByDate"
                                        group="accounts"
                                        dragClass="dragging"
                                        :disabled="isDragAssignmentDisabled"
                                        :sort="false"
                                        :move="applyMoveStyles"
                                        @end="resetDropTransactionHere()"
                                        :class="{'draggable-list--disabled': isDragAssignmentDisabled}"
                                    >
                                        <b-row
                                            class="list-group-item no-gutters draggable pendingAssignment otto-card-body-px"
                                            v-for="pendingAssignment in pendingAssignmentsByDate"
                                            :key="`pending-assignment-${pendingAssignment.transaction.id}`"
                                            :data-id="pendingAssignment.transaction.id"
                                        >
                                            <div class="transaction-list__checkbox-column">
                                                <b-form-checkbox :value="pendingAssignment" v-model="selectedTransactions"/>
                                            </div>
                                            <div class="transaction-list__icon-column">
                                                <bank-account-icon
                                                    v-if="creditCardsKeyedById[pendingAssignment.transaction.bank_account_id]"
                                                    :color="creditCardsKeyedById[pendingAssignment.transaction.bank_account_id].color"
                                                    :icon="creditCardsKeyedById[pendingAssignment.transaction.bank_account_id].icon"/>
                                            </div>
                                            <div class="transaction-list__date-column">
                                                {{ pendingAssignment.transaction.remote_transaction_date | formatDate }}
                                            </div>
                                            <div class="transaction-list__description-column">
                                                <div class="d-flex">
                                                    <div class="transactionDescription">
                                                        {{ pendingAssignment.transaction.merchant }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="transaction-list__amount-column text-right">
                                                <i class="fas fa-code-branch fa-rotate-90 text-primary mr-2" v-if="pendingAssignment.transaction.parent_transaction_id"></i>
                                                {{ pendingAssignment.transaction.amount | currency }}
                                            </div>
                                        </b-row>
                                        <div class="text-center p-5" v-if="!pendingAssignments.length">
                                            No charges to assign.
                                        </div>
                                    </draggable>
                                </div>
                            </b-list-group>
                        </b-card-body>

                        <div class="otto-card-body-px bulk-assignment-panel"
                            v-if="isBulkAssignmentPanelDisplayed"
                            :style="[isIOS ? {bottom: 'calc(85px + env(safe-area-inset-bottom, 15px))'} : {}]">
                            <!-- multiple assignment selection panel -->
                            <div class="text-right font-weight-semibold bulk-assignment-panel__total">
                                Total Selected
                                <span class="ml-3">
                                    {{ totalOfSelectedTransactions | currency }}
                                </span>
                            </div>

                            <v-select
                                :options="accountSelectOptions"
                                :clearable="false"
                                :searchable="false"
                                :selectable="option => option && option.value"
                                v-model="selectedAccount"
                                class="bulk-assignment-panel__account-select account-select"
                                :class="{'bulk-assignment-panel__account-select--invalid': !!bulkAssignmentError}"
                            >
                                <template v-slot:selected-option>
                                    <div class="account-select__select-option select-option">
                                        <div class="select-option__account-name">
                                            <bank-account-icon
                                                v-if="selectedAccount.value"
                                                class="mr-2 d-inline-block"
                                                :color="selectedAccount.value.color"
                                                :icon="selectedAccount.value.icon"
                                            />
                                            {{ selectedAccount.label }}
                                        </div>
                                        <div class="select-option__account-balance" v-if="selectedAccount.value">
                                            {{ selectedAccount.value.balance_available | currency }}
                                        </div>
                                    </div>
                                </template>

                                <template v-slot:option="bankAccount">
                                    <div class="account-select__select-option select-option" v-show="bankAccount.value">
                                        <div class="select-option__account-name">
                                            <bank-account-icon
                                                v-if="bankAccount.value"
                                                class="mr-2 d-inline-block"
                                                :color="bankAccount.value.color"
                                                :icon="bankAccount.value.icon"
                                            />
                                            {{ bankAccount.label }}
                                        </div>
                                        <div class="select-option__account-balance" v-if="bankAccount.value">
                                            {{ bankAccount.value.balance_available | currency }}
                                        </div>
                                    </div>
                                </template>
                            </v-select>

                            <div class="bulk-assignment-panel__error-message" v-show="bulkAssignmentError" ref="bulkAssignmentError" @blur="bulkAssignmentError = ''" tabindex="0">
                                <div>
                                    {{ bulkAssignmentError }}
                                </div>
                                <div class="ml-3">
                                    <i class="fas fa-times close-btn" @click="bulkAssignmentError = ''"></i>
                                </div>
                            </div>

                            <b-button variant="primary" class="bulk-assignment-panel__button" block @click="bulkAssignTransactions" :disabled="!isBulkAssignmentAllowed">
                                Assign
                            </b-button>
                        </div>
                    </b-card>
                </section>

                <section>
                    <balances-panel :bank-accounts="[...creditCards, ccPayoffAccount]" v-if="!isInitializingView" hide-edit-button>
                        <template v-slot:title>
                            <h2 class="panelTitle">
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
            </b-col>
            <b-col cols="12" xl="6">
                <section class="assignableAccountList">
                    <div
                        v-for="assignableAccount in sortedAssignableAccounts"
                        :key="assignableAccount.id"
                        :class="{
                            'expanded': assignableAccountVisibility[assignableAccount.id]
                        }"
                    >
                        <draggable
                            :list="assignableAccount.untransferred_assignments"
                            group="accounts"
                            handle='none'
                            class="card custom-card d-flex flex-column"
                            :class="[assignableAccount.color, {'has-error': !!assignableAccount.apiError}]"
                            :data-color="assignableAccount.color"
                            :move="applyMoveStyles"
                            :id="'assignableAccount-' + assignableAccount.id"
                            @add="assignTransaction(assignableAccount, $event)"
                        >
                            <div class="custom-card-content d-flex justify-content-between assigned-header"
                                :id="'assignableAccount-' + assignableAccount.id + '-body'"
                                :data-color="assignableAccount.color"
                            >
                                <div class="pr-1 d-flex align-items-center assignableAccountList__account-name-column" :class="{'has-schedule': assignableAccount.hasAccountSchedule}">
                                    <bank-account-icon :color="assignableAccount.color" class="mr-2" @click="openAccountSchedule(assignableAccount)" />
                                    <span class="account-name" @click="openAccountSchedule(assignableAccount)">{{ assignableAccount.name }}</span>
                                    <info-popover id="cc-payoff-info-popover" v-if="assignableAccount.slug === 'cc_payoff'" class="ml-1">
                                        <template slot="title">
                                            Credit Card Payoff Account
                                        </template>
                                        <template slot="content">
                                            <p>Your credit card payments are automatically assigned to this account.</p>
                                            <p>NOTE: The balance of your Credit Card Payoff Account does not adjust until the payments post at your bank.</p>
                                        </template>
                                    </info-popover>
                                </div>
                                <loading-spinner :show-spinner="assignableAccount.loading" customClass="size-auto" class="d-flex align-items-center">
                                    <i v-if="assignableAccount.balance_available < 0" :id="'assignableAccount-' + assignableAccount.id + '-warning'" class="fas fa-exclamation-triangle text-danger"></i>
                                    <span :class="{'text-danger': (assignableAccount.balance_available < 0)}" class="account-balance" :id="'assignableAccount-' + assignableAccount.id + '-balance'" tabindex="-1">
                                        {{ assignableAccount.balance_available | currency }}
                                    </span>
                                    <b-popover
                                        v-if="assignableAccount.balance_available < 0"
                                        triggers="hover focus blur"
                                        :target="'assignableAccount-' + assignableAccount.id + '-warning'"
                                        container="#warning-popover-container"
                                    >
                                        <span>Reassign credit card charges or add funds to this account to turn the balance positive.</span>
                                    </b-popover>
                                    <calculator-popover
                                        :bank-account="assignableAccount"
                                        :target-ref="'assignableAccount-' + assignableAccount.id + '-balance'"
                                        popover-triggers="click blur"
                                        :show-assignment-adjustment="true"
                                        hide-icon
                                    />

                                    <div class="assignmentActionColumn">
                                        <i
                                            class="fas fa-caret-up collapse-arrow"
                                            aria-hidden="true"
                                            @click="toggle(assignableAccount)"
                                            :class="[{'fa-rotate-180': assignableAccountVisibility[assignableAccount.id], 'hasAssignments': assignableAccount.untransferred_assignments.length}, assignableAccount.color]"
                                        >
                                        </i>
                                    </div>
                                </loading-spinner>
                            </div>
                            <!-- Individual assignments -->
                            <b-collapse
                                :id="`collapse-assignment-list-${assignableAccount.id}`"
                                :visible="assignableAccountVisibility[assignableAccount.id]"
                                class="assignment-collapse">
                                <div v-if="assignableAccount.untransferred_assignments.length >= 1">
                                    <b-row no-gutters
                                        v-for="assignment in assignableAccount.untransferred_assignments"
                                        :key="assignment.transaction.id"
                                        class="d-flex assigned-trans flex-row flex-nowrap custom-card-content justify-content-between align-items-center completedAssignment"
                                    >
                                        <b-col cols="3" class="text-nowrap">
                                            <bank-account-icon class="mr-2"
                                                v-if="creditCardsKeyedById[assignment.transaction.bank_account_id]"
                                                :color="creditCardsKeyedById[assignment.transaction.bank_account_id].color"
                                                :icon="creditCardsKeyedById[assignment.transaction.bank_account_id].icon"/>
                                            {{ assignment.transaction.remote_transaction_date | formatDate }}
                                        </b-col>
                                        <b-col cols="5" class="pl-3 transactionDescription flex-shrink-1">
                                            {{ assignment.transaction.merchant }}
                                        </b-col>
                                        <b-col class="text-right pl-3 pr-0 assigmentAmount text-nowrap">
                                            {{ assignment.transaction.amount | currency }}
                                            <div class="calculatorPlaceholder"></div>
                                        </b-col>
                                        <div class="assignmentActionColumn">
                                            <b-button
                                                v-if="assignment.allocated_amount <= 0"
                                                variant="link"
                                                class="undo-button px-0"
                                                @click="removeAssignment(assignment, assignableAccount)"
                                            >
                                                <span class="fa-stack">
                                                    <i class="fas fa-circle fa-stack-2x"></i>
                                                    <i class="fas fa-undo fa-stack-1x fa-inverse"></i>
                                                </span>
                                            </b-button>
                                        </div>
                                    </b-row>
                                </div>
                            </b-collapse>
                            <!-- Assignment summary -->
                            <b-collapse :id="`collapse-assignment-summary-${assignableAccount.id}`" :visible="assignableAccountVisibility[assignableAccount.id]" class="footer-collapse">
                                <div>
                                    <div
                                        slot="footer"
                                        class="custom-card-footer total-assigned"
                                    >
                                        <div class="align-items-center d-flex">
                                            <b-col class="px-0" offset="4">
                                                <div class="d-flex justify-content-between">
                                                    <span v-if="assignableAccount.slug !== 'cc_payoff'">Total Assigned:</span>
                                                    <span v-else>Total payments:</span>
                                                    <span class="pl-3 assigmentAmount">
                                                        {{ calculateAssignedTotal(assignableAccount.untransferred_assignments) | currency }}
                                                        <div class="calculatorPlaceholder"></div>
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between" v-if="calculatePartialPaymentTotal(assignableAccount.untransferred_assignments)">
                                                    Partial Payment:
                                                    <span class="pl-3 assigmentAmount">
                                                        {{ calculatePartialPaymentTotal(assignableAccount.untransferred_assignments) | currency }}
                                                        <div class="calculatorPlaceholder"></div>
                                                    </span>
                                                </div>
                                            </b-col>
                                            <div class="assignmentActionColumn">
                                                <i
                                                    class="fas fa-caret-up collapse-arrow"
                                                    :class="[{'hasAssignments': assignableAccount.untransferred_assignments.length}, assignableAccount.color]"
                                                    aria-hidden="true"
                                                    @click="toggle(assignableAccount)"
                                                >
                                                </i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </b-collapse>
                        </draggable>

                        <div class="assignable-account-error" v-show="!!assignableAccount.apiError" :ref="`assignable-account-error-${assignableAccount.id}`" @blur="assignableAccount.apiError = ''" tabindex="0">
                            <div class="">
                                {{ assignableAccount.apiError }}
                            </div>
                            <div class="ml-3">
                                <i class="fas fa-times close-btn" @click="assignableAccount.apiError = ''"></i>
                            </div>
                        </div>
                    </div>
                </section>
            </b-col>
        </b-row>
        <div>
            <edit-transactions-modal
                v-if="savingsAccessCC.id"
                :creditCardAccounts="creditCards"
                :bank-account-id="savingsAccessCC.id"
                ref="editTransactionsModal"
                @transactions-updated="loadAssignableTransactions"
                @error="apiErrors = [$event]"
            />

            <AccountScheduleModal v-if="selectedBankAccount" :bank-account="selectedBankAccount" ref="accountScheduleModal" />
        </div>
    </b-container>
</template>
<script type="text/javascript" src="./assign.controller.js"></script>
<style lang="scss" src="./_assign.scss" scoped></style>
