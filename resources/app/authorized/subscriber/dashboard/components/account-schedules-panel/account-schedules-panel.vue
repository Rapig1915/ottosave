<template>
    <div class="accountSchedulesPanel">
        <b-card no-body>
            <b-card-body class="otto-card-body-px">
                <b-row>
                    <b-col>
                        <h2 class="d-inline-block">
                            Account Schedules
                            <info-popover id="account-schedules-info-popover">
                                <template slot="title">
                                    Account Schedules
                                </template>
                                <template slot="content">
                                    Use these schedules to create lists of bills, estimates, limits or savings goals to help you determine how to organize your income.
                                </template>
                            </info-popover>
                        </h2>
                    </b-col>
                </b-row>

                <div
                    v-for="(bankAccount, index) in allocationAccounts"
                    :key="bankAccount.id"
                    class="bankAccountRow"
                    :class="[(index < allocationAccounts.length - 1) ? 'my-3' : 'mt-3']"
                >
                    <div class="btn btn-block btn-md d-flex justify-content-between acccountScheduleButton" @click="openAccountSchedule(bankAccount)">
                        <div class="">
                            <span class="mr-2">
                                <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon" />
                            </span>
                            {{ bankAccount.name }}
                        </div>
                        <div :class="`color-${bankAccount.color}`">
                            <i class="icon-dym-calendar-list"></i>
                        </div>
                    </div>
                </div>
            </b-card-body>
        </b-card>

        <AccountScheduleModal v-if="selectedBankAccount" :bank-account="selectedBankAccount" ref="accountScheduleModal" />
    </div>
</template>

<script src="./account-schedules-panel.controller.js"></script>
<style lang="scss" src="./_account-schedules-panel.scss" scoped></style>
