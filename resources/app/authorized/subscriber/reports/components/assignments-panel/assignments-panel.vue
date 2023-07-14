<template>
    <div class="assignmentsPanel">
        <b-card body-class="otto-card-body-px">
            <h2>Assigned Charges</h2>
            <v-select
                :options="monthOptions"
                :clearable="false"
                :searchable="false"
                v-model="selectedMonth"
                class="assignmentsPanel__monthInput"
            >
                <template v-slot:selected-option>
                    {{ selectedMonth.label }}
                </template>
            </v-select>

            <loading-spinner v-if="loadingAssignments" :show-spinner="loadingAssignments" custom-class="text-center" />

            <template v-else>
                <div v-for="bankAccount in bankAccounts" :key="bankAccount.id" class="assignmentsPanel__accountRow accountRow"
                    :class="{
                        'accountRow--expanded': assignmentListCollapseStates[bankAccount.id],
                        'accountRow--hasAssignments': assignmentsByBankAccount[bankAccount.id].length
                    }"
                >
                    <div class="accountRow__collapseHeader collapseHeader">
                        <div class="collapseHeader__accountName">
                            <bank-account-icon class="mr-2" :color="bankAccount.color" :icon="bankAccount.icon"/>
                            {{ bankAccount.name }}
                        </div>
                        <div class="collapseHeader__total">
                            {{ totalsByBankAccount[bankAccount.id] | currency }}
                        </div>
                        <div class="collapseHeader__toggle" v-b-toggle="`assignment-list-${bankAccount.id}`">
                            <i
                                class="fas fa-caret-up"
                                aria-hidden="true"
                                :class="[`toggle-color-${bankAccount.color}`]"
                            >
                            </i>
                        </div>
                    </div>
                    <b-collapse :id="`assignment-list-${bankAccount.id}`" v-model="assignmentListCollapseStates[bankAccount.id]" class="accountCollapse">
                        <div
                            v-for="(assignment, index) in assignmentsByBankAccount[bankAccount.id]"
                            :key="assignment.id"
                            class="accountCollapse__assignment assignment"
                        >
                            <div class="assignment__date">
                                <bank-account-icon class="mr-2"
                                    v-if="creditCardsKeyedById[assignment.transaction.bank_account_id]"
                                    :color="creditCardsKeyedById[assignment.transaction.bank_account_id].color"
                                    :icon="creditCardsKeyedById[assignment.transaction.bank_account_id].icon"/>
                                {{ assignment.transaction.remote_transaction_date | moment('MM/DD/YY') }}
                            </div>
                            <div class="assignment__description">
                                {{ assignment.transaction.merchant }}
                            </div>
                            <div class="assignment__amount">
                                {{ assignment.transaction.amount | currency }}
                            </div>
                            <div class="assignment__toggle" :class="{'assignment__toggle--visible': (index === assignmentsByBankAccount[bankAccount.id].length - 1) }">
                                <i
                                    v-b-toggle="`assignment-list-${bankAccount.id}`"
                                    class="fas fa-caret-up"
                                    aria-hidden="true"
                                    :class="[`toggle-color-${bankAccount.color}`]"
                                >
                                </i>
                            </div>
                        </div>
                    </b-collapse>
                    <div class="border-bottom">

                    </div>
                </div>

                <div class="assignmentsPanel__totalRow totalRow">
                    <div class="totalRow__title">
                        All accounts
                    </div>

                    <div class="totalRow__amount">
                        {{ totalOfAssignments | currency }}
                    </div>
                </div>
            </template>
        </b-card>
    </div>
</template>

<script src="./assignments-panel.controller.js"></script>
<style lang="scss" src="./_assignments-panel.scss" scoped></style>
