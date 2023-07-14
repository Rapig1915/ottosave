<template>
    <div class="defense-panel">
        <b-card body-class="otto-card-body-px">
            <h2>Assigned and Organized</h2>

            <v-select
                :options="accountOptions"
                :clearable="false"
                :searchable="false"
                v-model="selectedAccount"
                class="defense-panel__select-input"
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

            <loading-spinner v-if="loadingAssignments" :show-spinner="loadingAssignments" custom-class="text-center" />

            <template v-else>
                <defense-chart
                    :height="null"
                    :width="null"
                    :chart-data="chartjsData"
                >
                </defense-chart>
            </template>
        </b-card>
    </div>
</template>

<script src="./defense-panel.controller.js"></script>
<style lang="scss" src="./_defense-panel.scss" scoped></style>
