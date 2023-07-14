<template>
    <div class="reportsPage">
        <loading-spinner :show-spinner="isInitializingView" custom-class="overlay fixed" />
        <b-container>
            <app-message type="error" :messages="errorMessages" @close="errorMessages = []" class="mb-3"/>

            <b-row>
                <b-col cols="12" xl="6">
                    <div class="reportsPage__section">
                        <assignments-panel
                            :bank-accounts="assignableAccounts"
                            :credit-cards="creditCards"
                            :start-date="startDate"
                            :end-date="endDate"
                        />
                    </div>
                </b-col>

                <b-col cols="12" xl="6">
                    <div class="reportsPage__section">
                        <balances-panel :bank-accounts="assignableAccounts" :defenses="defenses" :start-date="startDate" :end-date="endDate"/>
                    </div>

                    <div class="reportsPage__section">
                        <defense-panel
                            :bank-accounts="assignableAccounts"
                            :defenses="defenses"
                            :start-date="startDate"
                            :end-date="endDate"
                        />
                    </div>
                </b-col>
            </b-row>
        </b-container>
    </div>
</template>

<script src="./reports.controller.js"></script>
<style lang="scss" src="./_reports.scss" scoped></style>
