<template>
    <span class="accountScheduleModal">
        <b-modal
            header-border-variant="0"
            hide-footer
            static
            ref="bankAccountScheduleModal"
            size="xl"
            title-tag="div"
            @hide="confirmUnsavedChanges"
            @shown="isCloseConfirmed = false; isModalShown = true"
            @hidden="isModalShown = false"
        >
            <div slot="modal-title">
                <div class="d-flex flex-wrap justify-content-between">
                    <h1 class="account-name">
                        {{ bankAccountName }}
                        <info-popover id="everyday-checking-schedule-popover" v-if="bankAccount.slug === 'everyday_checking'">
                            <template slot="title">
                                <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon" class="mr-2"/>
                                {{ bankAccount.name }}
                            </template>
                            <template slot="content">
                                Plan to spend almost all of the money allocated into this account each month.
                            </template>
                        </info-popover>
                        <info-popover id="monthly-bills-schedule-popover" v-if="bankAccount.slug === 'monthly_bills'">
                            <template slot="title">
                                <bank-account-icon :color="bankAccount.color" :icon="bankAccount.icon" class="mr-2"/>
                                {{ bankAccount.name }}
                            </template>
                            <template slot="content">
                                Keep a small cushion in this account to provide for bills that fluctuate.
                            </template>
                        </info-popover>
                    </h1>
                    <div class="schedule-total">
                        <div class="schedule-total__label">
                            Schedule amount
                            <info-popover id="schedule-amount-popover">
                                <template slot="title">
                                    Schedule Amount
                                </template>
                                <template slot="content">
                                    <p>
                                        The schedule amount is calculated based on the total selected amount, and the schedule frequency (how often you plan to organize your income).
                                    </p>
                                    <p>
                                        The monthly amount is calculated based on the amount and frequency of a schedule item.
                                    </p>
                                    <p>
                                        Schedule frequency is the same for all account schedules and can be changed on the Bills Account Schedule.
                                    </p>
                                </template>
                            </info-popover>
                        </div>
                        <div class="schedule-total__amount">
                            {{ scheduledAmount | currency }}
                        </div>
                        <div class="w-50 schedule-frequency">
                            <b-form-select
                                v-if="bankAccount.slug === 'monthly_bills'"
                                v-model="selectedDefensesPerMonth"
                                @change="updateDefensesPerMonth"
                                :disabled="isUpdatingDefenseInterval"
                            >
                                <b-form-select-option :value="1">Once a month</b-form-select-option>
                                <b-form-select-option :value="2">Twice a month</b-form-select-option>
                            </b-form-select>
                            <a href="javascript:void(0)" v-else tabindex="0" v-b-tooltip.focus.v-white title="Schedule frequency can be changed on the Bills Account Schedule.">
                                <b-form-input disabled class="bg-white" :value="(selectedDefensesPerMonth === 1 ? 'Once a month' : 'Twice a month')"/>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <app-message type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>

            <loading-spinner :show-spinner="loadingScheduleItems" :class="{'text-center my-4': loadingScheduleItems}">
                <div class="schedule-items__container">
                    <div class="schedule-items__header-row">
                        <div class="schedule-item__select-box">
                            <b-form-checkbox
                                :checked="isEveryItemSelected"
                                @change="isEveryItemSelected = !isEveryItemSelected"
                                name="checkbox-1"
                                :value="true"
                                :unchecked-value="false"
                            >
                            </b-form-checkbox>
                        </div>
                    </div>
                    <div v-for="(scheduleItem, index) in scheduleItems" :key="index" class="schedule-items__schedule-item">
                        <b-button variant="white" @click="deleteScheduleItem(index)" class="schedule-item__delete-button">
                            <i class="far fa-trash-alt"></i>
                        </b-button>

                        <b-form-input
                            class="schedule-item__description-input"
                            type="text"
                            placeholder="description"
                            v-model="scheduleItem.description"
                            @change="scheduleItem.isDirty = true"
                            @keyup.enter="$event.target.blur()"
                            enterkeyhint="done"
                        />

                        <currency-input
                            class="schedule-item__amount-input"
                            placeholder="amount"
                            v-model="scheduleItem.amount_total"
                            @change="onChangeTotalTypeOrDate(scheduleItem, 'total')"
                        />

                        <b-form-select
                            class="schedule-item__type-input"
                            :class="{'placeholder': !scheduleItem.type}"
                            :options="defaultTypeOptions"
                            :schedule-item-index="index"
                            v-model="scheduleItem.type"
                            @change="onChangeTotalTypeOrDate(scheduleItem, 'type')"
                        />

                        <div class="schedule-item__date-input">
                            <div v-if="!scheduleItem.type || scheduleItem.type === 'monthly' || scheduleItem.type === 'quarterly'">
                                <b-form-select
                                    :class="{'placeholder': !scheduleItem.approximate_due_date}"
                                    :options="daysOptions"
                                    v-model="scheduleItem.approximate_due_date"
                                    @change="scheduleItem.isDirty = true">
                                </b-form-select>
                            </div>
                            <date-picker
                                v-else-if="scheduleItem.type === 'target_date'"
                                clear-button
                                :ref="`date-picker-${index}`"
                                :use-utc="true"
                                format="MM.dd.yyyy"
                                :input-class="`datepicker form-control mr-2 d-inline-block bg-white ${(!scheduleItem.date_end ? 'placeholder' : '')}` "
                                v-model="scheduleItem.date_end"
                                @input="onChangeTotalTypeOrDate(scheduleItem, 'date')"
                                placeholder="~ due date"
                            />
                            <div v-else-if="scheduleItem.type === 'yearly'">
                                <b-form-select
                                    :class="{'placeholder': !scheduleItem.approximate_due_date}"
                                    :options="monthsOptions"
                                    v-model="scheduleItem.approximate_due_date"
                                    @change="scheduleItem.isDirty = true">
                                </b-form-select>
                            </div>
                        </div>

                        <div class="schedule-item__total">
                            <span v-if="scheduleItem.amount_monthly !== ''">
                                {{ scheduleItem.amount_monthly | currency }}
                            </span>
                            <span v-else class="placeholder">
                                monthly amount
                            </span>
                        </div>

                        <div class="schedule-item__select-box">
                            <b-form-checkbox
                                v-model="scheduleItem.is_selected"
                                name="checkbox-1"
                                :value="true"
                                :unchecked-value="false"
                            >
                            </b-form-checkbox>
                        </div>
                    </div>
                    <div class="schedule-items__footer-row">
                        <b-button variant="link" @click="addScheduleItem" class="add-schedule-button">
                            <i class="fas fa-plus-circle"></i> Add schedule item
                        </b-button>

                        <div class="schedule-items__total-selected">
                            <span class="total-selected__label">
                                Total selected
                            </span>
                            <span class="total-selected__amount">
                                {{ totalSelectedItems | currency }}
                            </span>
                        </div>
                    </div>
                </div>
            </loading-spinner>

            <div class="d-flex justify-content-center">
                <div>
                    <b-button
                        v-if="dirtyScheduleItems.length"
                        @click="bulkSave(false)"
                        :disabled="isBulkSaveInProgress"
                        variant="muted-success"
                        class="px-5 mr-3"
                    >
                        <loading-spinner :show-spinner="isBulkSaveInProgress" custom-class="size-auto">
                            Save
                        </loading-spinner>
                    </b-button>
                    <b-button
                        v-if="dirtyScheduleItems.length"
                        @click="resetWorkingCopyOfScheduleItems"
                        variant="light"
                        class="px-5"
                    >
                        Cancel
                    </b-button>
                    <b-button
                        v-else
                        @click="$refs.bankAccountScheduleModal.hide()"
                        variant="light"
                        class="px-5"
                    >
                        Close
                    </b-button>
                </div>
            </div>
        </b-modal>

        <unsaved-changes-modal ref="confirmUnsavedChangesModal" @bulkSaveAndClose="bulkSave()" @closeWithoutSaving="closeWithoutSaving" :is-saving-changes="isBulkSaveInProgress"/>
        <confirm-delete-modal ref="confirmDeleteModal">
            <div class="confirm-delete-message">
                Delete schedule item?
            </div>
        </confirm-delete-modal>
    </span>
</template>

<script src="./account-schedule-modal.controller.js"></script>
<style lang="scss" src="vue_root/assets/scss/_table-input-modal.scss" scoped></style>
<style lang="scss" src="./_account-schedule-modal.scss" scoped></style>
