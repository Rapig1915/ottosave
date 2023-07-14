<template>
    <div class="balances-panel">
        <b-card body-class="otto-card-body-px">
            <h2>Available Balances</h2>
            <v-select
                :options="accountOptions"
                :clearable="false"
                :searchable="false"
                v-model="selectedAccount"
                class="balances-panel__select-input"
            >
                <template v-slot:selected-option>
                    <bank-account-icon
                        v-if="selectedAccount.value !== 'all'"
                        class="mr-2 d-inline-block"
                        :color="selectedAccount.value.color"
                        :icon="selectedAccount.value.icon"
                    />
                    {{ selectedAccount.label }}
                </template>

                <template v-slot:option="bankAccount">
                    <bank-account-icon
                        v-if="bankAccount.value !== 'all'"
                        class="mr-2 d-inline-block"
                        :color="bankAccount.value.color"
                        :icon="bankAccount.value.icon"
                    />
                    {{ bankAccount.label }}
                </template>
            </v-select>

            <balance-chart
                :height="null"
                :width="null"
                :chart-data="chartjsData"
            >
            </balance-chart>
        </b-card>
    </div>
</template>

<script src="./balances-panel.controller.js"></script>
<style lang="scss" src="./_balances-panel.scss" scoped></style>
