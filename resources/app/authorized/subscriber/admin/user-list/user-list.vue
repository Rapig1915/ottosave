<template>
    <b-container class="settings-dym-container">
        <b-row>
            <b-col cols="12">
                <section>
                    <b-card no-body class="settings-form mx-auto mt-3">
                        <b-card-body>
                            <section>
                                <b-row align-h="between">
                                    <b-col cols="12" sm="6">
                                        <h1>User List</h1>
                                    </b-col>
                                    <b-col cols="12" sm="6">
                                        <div class="searchInput">
                                            <i class="fas fa-search"></i>
                                            <input type="text" name="Search" v-model="userQuery.searchString" class="form-control rounded-pill" placeholder="Search" @keyup="debouncedHandlerGetAllUsers" />
                                        </div>
                                    </b-col>
                                </b-row>

                                <b-row>
                                    <b-col cols="12">
                                        <app-message
                                            type="error"
                                            :messages="getAllUsersErrors"
                                            @close="getAllUsersErrors = []">
                                        </app-message>
                                    </b-col>
                                </b-row>

                                <b-row>
                                    <b-col cols="12">
                                        <loading-spinner :showSpinner="loadingSpinner" customClass="overlay"></loading-spinner>
                                        <b-table
                                            class="user-list-table"
                                            striped small hover
                                            :items="users"
                                            :fields="userTableColumns"
                                            responsive
                                            no-local-sorting
                                            @sort-changed="handleChangeSort"
                                        >
                                            <template #cell(institution_details)="row">
                                                <b-button size="sm" @click="row.toggleDetails()" class="mr-2 w-100" variant="primary" :disabled="row.item.account.institutions.length === 0">
                                                    {{ row.detailsShowing ? 'Hide' : 'View'}}
                                                </b-button>
                                            </template>
                                            <template #cell(status)="row">
                                                <div class="d-flex">
                                                    {{row.item.account.status}}
                                                    <info-popover :id="`accounts-page-info-${row.item.id}`" v-if="row.item.account.braintree_customer_id">
                                                        <template slot="title">
                                                            Subscription Status
                                                        </template>
                                                        <template slot="content">
                                                            <p>
                                                                <strong>Braintree ID:</strong>&nbsp;{{ row.item.account.braintree_customer_id }}
                                                            </p>
                                                        </template>
                                                    </info-popover>
                                                </div>
                                            </template>
                                            <template #cell(accounts_linked)="row">
                                                <div class="d-flex">
                                                    {{row.item.account.institutions ? row.item.account.institutions.length : 0}}
                                                    <info-popover :id="`finicity-info-${row.item.id}`" v-if="row.item.account.finicity_customer">
                                                        <template slot="title">
                                                            Finicity Information
                                                        </template>
                                                        <template slot="content">
                                                            <p>
                                                                <strong>Customer Id:</strong>&nbsp;{{ row.item.account.finicity_customer.customer_id }}
                                                            </p>
                                                        </template>
                                                    </info-popover>
                                                </div>
                                            </template>
                                            <template #cell(actions)="{ item }">
                                                <div class="d-flex flex-row">
                                                    <b-button
                                                        size="sm"
                                                        variant="danger"
                                                        class="mr-2"
                                                        @click="grantUserAccess(item)"
                                                        :disabled="item.isGrantingAccess"
                                                    >
                                                        <loading-spinner :show-spinner="item.isGrantingAccess" custom-class="size-auto">
                                                            <i v-if="!item.adminAccessGranted" class="fas fa-plus"></i>
                                                            <i v-else class="fas fa-user"></i>
                                                        </loading-spinner>
                                                    </b-button>
                                                    <b-button-group v-if="item.account.status === 'deactivated'">
                                                        <b-button size="sm" @click="item.account.isConfirmingReactivation = true" class="mr-2" variant="secondary" v-if="!item.account.isConfirmingReactivation">
                                                            Reactivate&nbsp;Account
                                                        </b-button>
                                                        <b-button size="sm" @click="item.account.isConfirmingReactivation = false" variant="outline-plain" v-if="item.account.isConfirmingReactivation" :disabled="item.account.isReactivatingAccount">
                                                            Cancel
                                                        </b-button>
                                                        <b-button size="sm" @click="reactivateAccount(item.account)" variant="primary" v-if="item.account.isConfirmingReactivation" :disabled="item.account.isReactivatingAccount">
                                                            <loading-spinner :show-spinner="item.account.isReactivatingAccount" custom-class="size-auto">
                                                                Confirm
                                                            </loading-spinner>
                                                        </b-button>
                                                    </b-button-group>
                                                    <b-button
                                                        v-else-if="item.account.status !== 'demo'"
                                                        size="sm"
                                                        variant="outline-secondary"
                                                        class="mr-2"
                                                        @click="resetAccountToDemoMode(item.account)"
                                                    >
                                                        <loading-spinner :show-spinner="item.account.isResettingToDemoMode" custom-class="size-auto">
                                                            Demo
                                                        </loading-spinner>
                                                    </b-button>
                                                    <b-button
                                                        size="sm"
                                                        variant="danger"
                                                        class="mr-2"
                                                        @click="deleteUser(item)"
                                                        :disabled="item.isDeleting"
                                                    >
                                                        <loading-spinner :show-spinner="item.isDeleting" custom-class="size-auto">
                                                            Delete
                                                        </loading-spinner>
                                                    </b-button>
                                                    <b-button
                                                        size="sm"
                                                        variant="danger"
                                                        class="mr-2"
                                                        @click="lockUser(item)"
                                                        :disabled="item.isLocking"
                                                    >
                                                        <loading-spinner :show-spinner="item.isLocking" custom-class="size-auto">
                                                            <i v-if="item.is_owner_account_locked" class="fas fa-lock"></i>
                                                            <i v-else class="fas fa-lock-open"></i>
                                                        </loading-spinner>
                                                    </b-button>
                                                </div>
                                            </template>
                                            <template #row-details="row">
                                                <b-table class="table-nostriped" small :items="row.item.account.institutions" :fields="institutionTableColumns"/>
                                            </template>
                                        </b-table>
                                    </b-col>
                                </b-row>

                                <b-row align-h="end">
                                    <b-col cols="12">
                                        <pagination-bar :total-rows="totalUserCount" @change="handleChangePagination"></pagination-bar>
                                    </b-col>
                                </b-row>
                            </section>
                        </b-card-body>
                    </b-card>
                </section>
            </b-col>
        </b-row>
    </b-container>
</template>
<script src="./user-list.controller.js"></script>
<style lang="scss" src="./_user-list.scss" scoped></style>
