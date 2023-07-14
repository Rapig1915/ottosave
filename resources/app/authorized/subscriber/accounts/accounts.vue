<template>
    <b-container class="accounts-page">
        <app-message
            class="mb-3"
            v-if="errorMessages"
            type="error"
            :messages="errorMessages"
            @close="errorMessages = []">
        </app-message>

        <div id="accountPageNegativeBalancePopoverContainer">
            <!--
                this gives the negative balance warning popovers a place to live
                for adjusting styles this is an unfortunate necessity with
                popover capbilities in bootstrap-vue "2.0.0-rc.19"
            -->
        </div>

        <loading-spinner :show-spinner="loadingBankAccounts || isBulkSaving" custom-class="overlay-fixed"></loading-spinner>

        <b-card no-body class="accounts-page__card">
            <b-card-header class="accounts-page__card-header otto-card-body-px">
                <h1 class="card-header__title">
                    Accounts and Credit Cards
                    <info-popover id="accounts-page-info" v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }">
                        <template slot="title">
                            Accounts and Credit Cards
                        </template>
                        <template slot="content">
                            <p>
                                Click the button and Finicity, a division of Matercard, will securely link your bank accounts and credit cards to the app. Your Fincity connection will be for information only. No one, including you, can move or access your money using Otto.
                            </p>
                            <p>
                                You will need to search for and enter the credentials for each institution you wish to link to the app.
                            </p>
                        </template>
                    </info-popover>
                </h1>

                <div v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }" v-if="canManageFinicityAccounts">
                    <b-button
                        variant="primary" size="md"
                        class="card-header__link-button"
                        @click="openFinicityConnect"
                        :disabled="isFinicityActive">
                        <loading-spinner :show-spinner="isFinicityActive" custom-class="size-auto">
                            Link accounts and credit cards
                        </loading-spinner>
                    </b-button>
                </div>
                <div v-if="!$store.getters.isInDemoMode">
                    <div v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'basic' }" class="card-header__upgrade-panel upgrade-panel">
                        <div class="upgrade-panel__upgrade-text">
                            <strong>Subscribe today</strong> to securely link your bank accounts and credit card charges to the app.
                        </div>

                        <b-button
                            variant="outline-primary" size="md"
                            class="upgrade-panel__button"
                            @click="$store.dispatch('authorized/DISPLAY_UPGRADE_MODAL')"
                        >
                            Subscribe
                        </b-button>
                    </div>
                </div>
            </b-card-header>

            <b-card-body class="accounts-page__card-body otto-card-body-px">
                <div>
                    <div v-for="(bankAccount, index) in accountList" :key="index" class="card-body__bank-account-row bank-account-row px-3">
                        <div class="bank-account-row__account-details no-gutters">
                            <b-col class="my-1 my-xl-0 pr-2 pr-xl-0 mx-xl-3 bank-account-row__remote-name-column" order="1" order-xl="2">
                                <span v-if="bankAccount.institution_account">
                                    <strong class="pr-1" v-if="bankAccount.institution_account.institution">
                                        {{ bankAccount.institution_account.institution.name }}
                                    </strong>
                                    <span>{{ bankAccount.institution_account.name }} x-{{ bankAccount.institution_account.mask }}</span>
                                    <BankConectionErrorIcon :bankAccount="bankAccount" />
                                </span>
                                <strong v-else>
                                    Unlinked
                                </strong>
                                <p class="clearfix" style="margin-bottom: 0" v-if="bankAccount.purpose === 'credit'">
                                    Credit limit: {{ bankAccount.balance_limit | currency }}
                                </p>
                            </b-col>
                            <b-col cols="5" xl="2" class="my-1 my-xl-0 mx-xl-3 bank-account-row__purpose-column" order="2" order-xl="3">
                                <div class="purposeWrapper">
                                    <info-popover :id="`account-purpose-info-popover-${bankAccount.id}`" wide>
                                        <template slot="title">
                                            Account Purposes
                                        </template>
                                        <template slot="content">
                                            <p class="mb-1">
                                                Choose from the following purposes for each of your accounts and credit cards:
                                            </p>
                                            <ul>
                                                <li>
                                                    <strong>Primary Checking</strong> - This purpose can only be chosen once. Choosing this purpose creates four virtual accounts for organizing the money in your Primary Checking.
                                                </li>
                                                <li>
                                                    <strong>An Income Account</strong> - for collecting your income. Any money coming into your Primary Checking will start out in this account.
                                                </li>
                                                <li>
                                                    <strong>A Bills Account</strong> - for paying your bills. Any money coming out of your Primary Checking will automatically be deducted from this account.
                                                </li>
                                                <li>
                                                    <strong>A Spending Account</strong> - for general spending.
                                                </li>
                                                <li>
                                                    <strong>A Credit Card Payoff Account</strong> - for holding the money you’ve already spent using your credit card.
                                                </li>
                                                <li>
                                                    <strong>Credit Card</strong> - Choose this purpose for your credit cards. Label each credit card for easy identification.
                                                </li>
                                                <li>
                                                    <strong>Primary Savings</strong> - This purpose can only be chosen once. Choosing this purpose creates two virtual accounts for organizing the money in your Primary Savings. Label each account for easy identification.
                                                </li>
                                                <li>
                                                    <strong>Spending or saving</strong> - This purpose allows you to have virtual or real bank accounts dedicated to a certain spending or savings goal. Label each account for easy identification.
                                                </li>
                                                <li>
                                                    <strong>None (hidden on app)</strong> - Choose this purpose if you have a linked account that you do not want to be visible on the app.
                                                </li>
                                            </ul>
                                        </template>
                                    </info-popover>
                                    <b-form-select
                                        name="purpose"
                                        :value="bankAccount.purpose"
                                        @change="updateBankAccountPurpose(bankAccount, $event)"
                                        :options="accountPurposeOptions"
                                        :disabled="bankAccount.isSaving"
                                        v-if="!bankAccount.isRerenderingPurposeDropdown"
                                        @keyup.enter="$event.target.blur()"
                                        enterkeyhint="done"
                                    >
                                    </b-form-select>
                                </div>
                            </b-col>
                            <b-col class="my-1 my-xl-0 mr-1 ml-1 ml-xl-0 mr-xl-3 bank-account-row__name-column" order="4" order-xl="4">
                                <div class="" v-show="!bankAccount.sub_accounts || !bankAccount.sub_accounts.length">
                                    <div class="d-flex align-items-center">
                                        <bank-account-icon
                                            class="ml-2 mr-2"
                                            editable
                                            :icon="bankAccount.icon"
                                            :color.sync="bankAccount.color"
                                            @update:color="bankAccount.isDirty = true"
                                        />

                                        <b-form-input
                                            class="bg-white"
                                            type="text"
                                            name="account_name"
                                            v-model="bankAccount.name"
                                            placeholder="Name your account"
                                            @change="bankAccount.isDirty = true"
                                            @keyup.enter="$event.target.blur()"
                                            enterkeyhint="done"
                                        />
                                    </div>
                                </div>
                            </b-col>
                            <b-col class="bank-account-row__schedule-column" order="5" order-xl="5">
                                <div
                                    v-if="!bankAccount.sub_accounts.length && bankAccount.id && !accountPurposesWithoutScheduleItems.includes(bankAccount.purpose)"
                                    :class="`color-${bankAccount.color}`"
                                    @click="openAccountSchedule(bankAccount)"
                                >
                                    <i class="icon-dym-calendar-list"></i>
                                </div>
                            </b-col>
                            <b-col class="my-1 my-xl-0 mr-3 bank-account-row__balance-column" order="6" order-xl="6" :id="`bank-account-balance-${bankAccount.id}-${isEditingBalances ? 'editing' : 'disabled'}`" tabindex="-1">
                                <div :id="`${index}-balance-input`">
                                    <currency-input
                                        class="bank-account-balance"
                                        :disabled="!isEditingBalances"
                                        v-model.number="bankAccount.balance_current"
                                        @change="overrideInstitutionBalance(bankAccount)"
                                    />
                                </div>
                                <b-tooltip :target="`${index}-balance-input`" placement="top" v-if="bankAccount.institution_account_id && !bankAccount.is_balance_overridden">
                                    Balance is updated automatically
                                </b-tooltip>
                                <calculator-popover
                                    :bank-account="bankAccount"
                                    :target-ref="`bank-account-balance-${bankAccount.id}-disabled`"
                                    popover-triggers="click blur"
                                    hide-icon
                                />
                            </b-col>
                            <b-col class="bank-account-row__action-column" order="3" order-xl="1">
                                <b-dropdown no-caret variant="white" menu-class="p-0" offset="-60">
                                    <template v-slot:button-content>
                                        <loading-spinner :show-spinner="bankAccount.isSaving" custom-class="size-auto">
                                            <i class="fas fa-ellipsis-v text-black px-1"></i>
                                        </loading-spinner>
                                    </template>
                                    <b-dropdown-item @click="openDownloadTransactionsModal(bankAccount)" class="border-bottom">
                                        <div class="py-2">
                                            Download Transactions
                                        </div>
                                    </b-dropdown-item>
                                    <b-dropdown-item @click="openEditAccountModal(bankAccount)" class="border-bottom">
                                        <div class="py-2">
                                            Edit Information
                                        </div>
                                    </b-dropdown-item>
                                    <b-dropdown-item v-if="bankAccount.canCurrentUserManage" @click="confirmDelete(bankAccount)" :disabled="bankAccount.isDeleting">
                                        <div class="py-2">
                                            <loading-spinner :show-spinner="bankAccount.isDeleting" custom-class="size-auto" />
                                            Delete Account
                                        </div>
                                    </b-dropdown-item>
                                </b-dropdown>
                            </b-col>
                            <b-col class="bank-account-row__action-column align-self-end mb-0 d-none d-xl-block" order="7">
                                <b-button variant="success" class="btn-muted border-0" v-show="bankAccount.isDirty" @click="updateBankAccount(bankAccount)" :disabled="bankAccount.isSaving">
                                    <loading-spinner :show-spinner="bankAccount.isSaving" custom-class="size-auto">
                                        Save
                                    </loading-spinner>
                                </b-button>
                            </b-col>
                        </div>
                        <div v-if="bankAccount.sub_accounts && bankAccount.sub_accounts.length">
                            <div
                                v-for="(subAccount, subAccountIndex) in bankAccount.sub_accounts"
                                :key="subAccountIndex"
                                class="bank-account-row__virtual-account-row virtual-account-row card no-gutters"
                            >
                                <b-col class="virtual-account-row__delete-column">
                                    <b-button variant="white"
                                        v-if="subAccount.sub_account_order > 0"
                                        @click="removeSubAccount(subAccount, bankAccount)"
                                        :disabled="subAccount.isDeleting"
                                    >
                                        <loading-spinner :show-spinner="subAccount.isDeleting" custom-class="size-auto">
                                            <i class="far fa-trash-alt"></i>
                                        </loading-spinner>
                                    </b-button>
                                </b-col>
                                <b-col class="virtual-account-row__purpose-column">
                                    <info-popover :id="`virtual-account-bills-info-${subAccount.id}`" v-if="bankAccount.purpose === 'primary_checking' && subAccount.purpose === 'bills'">
                                        <template slot="title">
                                            Virtual account balances
                                        </template>
                                        <template slot="content">
                                            <p>
                                                The total balances of your virtual accounts are always equal to the balance in your Primary Checking account. This is how the balances of your virtual accounts adjust to ensure this is the case:
                                            </p>
                                            <p>
                                                When money comes into your Primary Checking (the balance increases), Otto automatically increases the balance in your Income Account.
                                                When money comes out of your Primary Checking (the balance decreases), Otto automatically decreases the balance of your Bills Account.
                                            </p>
                                            <p>
                                                The balance of the Bills Account increases or decreases when you edit your other virtual account balances on the Accounts Page.
                                            </p>
                                        </template>
                                    </info-popover>
                                    <info-popover :id="`virtual-account-savings-info-${subAccount.id}`" v-else-if="bankAccount.purpose === 'primary_savings' && subAccountIndex === 0">
                                        <template slot="title">
                                            Virtual account balances
                                        </template>
                                        <template slot="content">
                                            <p>
                                                The total balances of your virtual accounts are always equal to the balance in your Primary Savings account. This is how the balances of your virtual accounts adjust to ensure this is the case:
                                            </p>
                                            <p>
                                                When money comes into your Primary Savings (the balance increases), Otto automatically increases the balance in your top virtual account.
                                                When money comes out of your Primary Savings (the balance decreases), Otto automatically decreases the balance in your top virtual account.
                                            </p>
                                            <p>
                                                The balance in your top virtual account increases or decreases when you edit your other virtual account balances on the Accounts Page.
                                            </p>
                                        </template>
                                    </info-popover>
                                </b-col>
                                <b-col class="virtual-account-row__name-column">
                                    <bank-account-icon
                                        class="mr-2"
                                        editable
                                        :icon="subAccount.icon"
                                        :color.sync="subAccount.color"
                                        @update:color="subAccount.isDirty = true; bankAccount.isDirty = true"
                                    />

                                    <b-form-input
                                        class="bg-white"
                                        type="text"
                                        name="account_name"
                                        v-model="subAccount.name"
                                        placeholder="Name your account"
                                        @change="subAccount.isDirty = true; bankAccount.isDirty = true"
                                        enterkeyhint="done"
                                    />
                                </b-col>
                                <b-col class="virtual-account-row__schedule-column">
                                    <div
                                        v-show="subAccount.id && !accountPurposesWithoutScheduleItems.includes(subAccount.purpose)"
                                        :class="`color-${subAccount.color}`"
                                        @click="openAccountSchedule(subAccount)"
                                    >
                                        <i class="icon-dym-calendar-list"></i>
                                    </div>
                                </b-col>
                                <b-col class="virtual-account-row__balance-column" :id="`sub-account-balance-${subAccount.id}-${bankAccount.isEditingSubAccountBalances ? 'editing' : 'disabled'}`" tabindex="-1">
                                    <div :id="`${subAccount.id}-negative-balance-warning`">
                                        <i v-if="subAccount.balance_current < 0"
                                            class="fas fa-exclamation-triangle text-danger"
                                        ></i>
                                        <b-popover :target="`${subAccount.id}-negative-balance-warning`" triggers="hover" placement="left" container="accountPageNegativeBalancePopoverContainer">
                                            Reduce the amount(s) in the other virtual account(s) to turn this balance positive.
                                        </b-popover>
                                    </div>
                                    <currency-input
                                        :disabled="!bankAccount.isEditingSubAccountBalances || subAccount.sub_account_order === 0"
                                        v-model.number="subAccount.balance_current"
                                        :class="{'border border-danger': subAccount.balanceError, 'virtual-account-row__balance--negative' : subAccount.balance_current < 0}"
                                        @input="subAccount.isDirty = true; bankAccount.isDirty = true"
                                        @blur="updateSubAccountBalances(subAccount, bankAccount)"
                                    />
                                    <calculator-popover
                                        :bank-account="subAccount"
                                        :target-ref="`sub-account-balance-${subAccount.id}-disabled`"
                                        popover-triggers="click blur"
                                        hide-icon
                                    />
                                </b-col>
                                <b-col cols="12" v-if="subAccount.balanceError" class="virtual-account-row__error-message">
                                    {{ subAccount.balanceError }}
                                </b-col>
                            </div>
                        </div>
                        <div class="bank-account-row__virtual-account-actions" v-if="bankAccount.sub_accounts && bankAccount.sub_accounts.length">
                            <div class="order-1">
                                <b-button variant="link" class="accounts-page__text-button" @click="addSubAccount(bankAccount)">
                                    Add virtual account
                                </b-button>
                                <info-popover :id="`${bankAccount.id}-add-virtual-account-info-popover`">
                                    <template slot="title">
                                        Add virtual account
                                    </template>
                                    <template slot="content">
                                        <p>
                                            To further organize the money in your Primary Checking and Primary Savings, you can add additional virtual accounts with the purpose of "Spending or saving." Label each account for easy identification.
                                        </p>
                                    </template>
                                </info-popover>
                            </div>
                            <div v-if="bankAccount.isEditingSubAccountBalances || bankAccount.sub_accounts.some(({ isDirty }) => isDirty)" class="order-3 order-xl-2 virtual-account-actions__save-buttons">
                                <div class="d-flex justify-content-center">
                                    <b-button variant="muted-success" class="mr-2" @click="updateBankAccount(bankAccount)" :disabled="bankAccount.isSaving">
                                        <loading-spinner :show-spinner="bankAccount.isSaving" custom-class="size-auto">
                                            Save
                                        </loading-spinner>
                                    </b-button>
                                    <b-button variant="light" @click="cancelSubAccountEdits(bankAccount)">
                                        Cancel
                                    </b-button>
                                </div>
                            </div>
                            <div class="order-2 order-xl-3">
                                <b-button variant="link" class="accounts-page__text-button" @click="editSubAccountBalances(bankAccount)">
                                    Edit balances
                                </b-button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body__footer-row footer-row">
                        <b-dropdown no-caret variant="gray-f" menu-class="p-0" class="footer-row__dropdown-menu">
                            <template v-slot:button-content>
                                <i class="fas fa-ellipsis-h text-gray-a"></i>
                            </template>
                            <b-dropdown-item @click="addUnlinkedAccount">
                                <div class="py-2">
                                    Add Unlinked Account
                                </div>
                            </b-dropdown-item>
                        </b-dropdown>
                        <div v-show="dirtyBankAccounts.length" class="footer-row__save-all-buttons">
                            <b-button variant="muted-success" size="md" @click="saveAllChanges" :disabled="isBulkSaving" class="mr-2">
                                Save all
                            </b-button>
                            <b-button variant="light" size="md" @click="refreshBankAccountsList(true)">
                                Cancel
                            </b-button>
                        </div>
                        <div class="footer-row__action-links">
                            <div>
                                <b-button variant="link" class="accounts-page__text-button accounts-page__text-button--gray" @click="isEditingBalances = true">
                                    Override balances
                                </b-button>
                                <info-popover id="override-info-popover">
                                    <template slot="title">
                                        Override balances
                                    </template>
                                    <template slot="content">
                                        If needed, click here to temporarily override your bank and credit card balances. Your balances will reset the next time they are refreshed.
                                    </template>
                                </info-popover>
                            </div>
                            <div v-if="hasPendingTransfers" class="mt-2">
                                <span v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }">
                                    <b-button variant="link" class="accounts-page__text-button accounts-page__text-button--gray" @click="clearPendingTransfers" :disabled="isClearingTransfers">
                                        <loading-spinner :show-spinner="isClearingTransfers" custom-class="size-auto" class="d-inline-block" />
                                        Clear Transfers
                                    </b-button>
                                    <info-popover :id="`clear-transfers-info-popover`">
                                        <template slot="title">
                                            Clear Transfers
                                        </template>
                                        <template slot="content">
                                            If needed, you may click this button to clear your transfers.
                                        </template>
                                    </info-popover>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </b-card-body>
        </b-card>

        <FinicityConnect ref="finicityService"
            @finicity-connect-cancelled="isFinicityActive = false"
            @finicity-connect-complete="completeFinicityConnect"
            @finicity-connect-error="displayError"
            @finicity-connect-fix-complete="completeFinicityConnectFix"
        />
        <DeleteInstitutionModal ref="deleteInstitutionModal" @institution-deleted="removeAccountsForInstitution"/>
        <confirm-delete-modal ref="confirmDeleteModal" @delete-confirmed="deleteBankAccount"/>
        <confirm-purpose-change-modal ref="confirmPurposeChangeModal" />

        <b-modal
            header-border-variant="0"
            hide-footer
            static
            centered
            ref="downloadAccountTransactionsModal"
            title-tag="div"
            class="download-transactions-modal"
        >
            <div slot="modal-title">
                <h2>
                    Download Transactions
                </h2>
            </div>

            <form>
                <div class="px-3 px-sm-5 mb-4">
                    <b-row class="mb-3">
                        <b-col cols="6" class="d-block">
                            Start Date:
                            <date-picker
                                :ref="`date-picker-start`"
                                :use-utc="false"
                                format="MM/dd/yyyy"
                                input-class="datepicker form-control pr-0 d-inline-block bg-white placeholder"
                                class="transaction__date transaction__date--picker mt-1"
                                placeholder="date"
                                v-model="downloadTransaction.startDate"
                            />
                        </b-col>
                        <b-col cols="6" class="d-block">
                            End Date:
                            <date-picker
                                :ref="`date-picker-end`"
                                :use-utc="false"
                                format="MM/dd/yyyy"
                                input-class="datepicker form-control pr-0 d-inline-block bg-white placeholder"
                                class="transaction__date transaction__date--picker mt-1"
                                placeholder="date"
                                v-model="downloadTransaction.endDate"
                            />
                        </b-col>
                    </b-row>
                </div>
            </form>

            <div class="d-flex flex-column align-items-center justify-content-center pt-2 px-5">
                <b-button variant="primary" block
                    @click="downloadTransactionWithDateRange"
                    class="px-3 py-2 mr-2 mt-0 mb-1"
                    :disabled="!downloadTransaction.bankAccount || !downloadTransaction.startDate || !downloadTransaction.endDate"
                >

                    <loading-spinner :show-spinner="downloadTransaction.sendingRequest" custom-class="size-auto">
                        Download .CSV
                    </loading-spinner>
                </b-button>
                <b-button variant="light" block @click="$refs.downloadAccountTransactionsModal.hide()" class="px-3 py-2 mr-2 mt-0">
                    Cancel
                </b-button>

                <app-message
                    class="mt-1 mb-1"
                    v-if="downloadTransaction.errors"
                    type="error"
                    :messages="downloadTransaction.errors"
                    @close="downloadTransaction.errors = []">
                </app-message>
            </div>
        </b-modal>

        <b-modal
            ref="editAccountInfoModal"
            header-border-variant="0"
            hide-footer
            title-tag="h2"
            centered
            class="edit-nickname-modal">

            <template slot="modal-title">
                <div class="mb-2">
                    Edit account information
                    <info-popover id="edit-linked-info-popover">
                        <template slot="title">
                            Edit account information
                        </template>
                        <template slot="content">
                            If you change the name of an account at your bank or credit union, you can manually update the name change here. For easy identification, we recommend having the account name on the app be the same as the account name at your bank or credit union.
                        </template>
                    </info-popover>
                </div>
            </template>

            <form @change="bankAccountToEdit.isDirty = true">

                <div v-if="bankAccountToEdit && bankAccountToEdit.institution_account"
                    class="px-3 px-sm-5 mb-4">

                    <b-row class="mb-3">
                        <b-col cols="5" class="d-flex align-items-center">
                            Institution
                        </b-col>
                        <b-col cols="7" class="d-flex align-items-center">
                            <strong class="pr-1">
                                {{ bankAccountToEdit.institution_account.institution.name }}
                            </strong>
                        </b-col>
                    </b-row>
                    <b-row class="mb-3">
                        <b-col cols="5" class="d-flex align-items-center">
                            Nickname
                        </b-col>
                        <b-col cols="7" class="d-flex align-items-center">
                            <b-form-input type="text" name="account_name" v-model="bankAccountToEdit.institution_account.name" placeholder="Name"/>
                        </b-col>
                    </b-row>
                    <b-row class="mb-3" v-if="bankAccountToEdit.institution_account">
                        <b-col cols="5" class="d-flex align-items-center">
                            Account number
                        </b-col>
                        <b-col cols="7" class="d-flex align-items-center">
                            <span>
                                x-{{ bankAccountToEdit.institution_account.mask }}
                            </span>
                        </b-col>
                    </b-row>
                </div>

                <div v-if="bankAccountToEdit && !bankAccountToEdit.institution_account"
                    class="px-3 px-sm-5 mb-4">
                    <b-row class="mb-3">
                        <b-col cols="5" class="d-flex align-items-center">
                            Name
                        </b-col>
                        <b-col cols="7" class="d-flex align-items-center">
                            <b-form-input type="text" name="name" v-model="bankAccountToEdit.name" placeholder="Name"/>
                        </b-col>
                    </b-row>
                </div>

                <div class="px-3 px-sm-5 mb-4" v-if="bankAccountToEdit && bankAccountToEdit.purpose === 'credit'">
                    <b-row class="mb-3">
                        <b-col cols="5" class="d-flex align-items-center">
                            Credit Limit
                        </b-col>
                        <b-col cols="7" class="d-flex align-items-center">
                            <b-form-input type="number" name="balance_limit" v-model="bankAccountToEdit.balance_limit_override" placeholder="Limit"/>
                        </b-col>
                    </b-row>
                </div>
            </form>

            <div class="d-flex justify-content-center pt-2 px-5" v-if="bankAccountToEdit">
                <b-button variant="light" block @click="$refs.editAccountInfoModal.hide()" class="px-3 py-2 mr-2 mt-0" :disabled="bankAccountToEdit.isSaving">
                    Cancel
                </b-button>
                <b-button variant="muted-success" block
                    @click="saveEditAccountInfo"
                    class="px-3 py-2 mr-2 mt-0"
                    v-if="bankAccountToEdit.isDirty"
                    :disabled="bankAccountToEdit.isSaving">

                    <loading-spinner :show-spinner="bankAccountToEdit.isSaving" custom-class="size-auto">
                        Save
                    </loading-spinner>
                </b-button>
            </div>
        </b-modal>

        <AccountScheduleModal v-if="accountScheduleAccount" :bank-account="accountScheduleAccount" ref="accountScheduleModal" />
        <LinkSuccessModal ref="linkSuccessModalRef"/>
    </b-container>
</template>
<script type="text/javascript" src="./accounts.controller.js"></script>
<style lang="scss" src="./_accounts.scss" scoped></style>
