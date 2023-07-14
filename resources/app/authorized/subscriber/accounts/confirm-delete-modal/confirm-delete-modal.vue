<template>
    <div class="confirm-delete-modal">
        <b-modal
            ref="confirmDeleteModal"
            header-border-variant="0"
            hide-footer
            static
            title-tag="h2"
            @hidden="cleanupModal"
            centered>
            <template slot="modal-title">
                <div class="mb-2 text-center">
                    <strong class="pr-1" v-if="bankAccount.institution_account && bankAccount.institution_account.institution">
                        {{ bankAccount.institution_account.institution.name }}
                    </strong>
                    <span class="font-weight-normal">{{ bankAccount.name }}</span>
                    <span class="font-weight-normal" v-if="bankAccount.institution_account">x-{{ bankAccount.institution_account.mask }}</span>
                </div>
            </template>
            <div class="px-4">
                <div class="text-center">
                    <p class="font-weight-semibold text-black mb-4">
                        Are you sure you want to delete this <span v-if="bankAccount.parent_bank_account_id">virtual </span>account?
                    </p>
                    <p class="font-weight-semibold text-danger mb-1">
                        This action cannot be undone!
                    </p>
                    <p class="mb-4">
                        All past data associated with this <span v-if="bankAccount.parent_bank_account_id">virtual </span>account will be deleted.
                    </p>
                    <p class="mb-4" v-if="!bankAccount.parent_bank_account_id">
                        If you are having a connection issue, please DO NOT delete your account. We have been notified of the issue and we are working hard to fix it. Most connection issues will be resolved within 24 hours. Thank you for your patience.
                    </p>
                    <p class="mb-4 px-4">
                        If you would still like to delete this <span v-if="bankAccount.parent_bank_account_id">virtual </span>account, please&nbsp;type&nbsp;DELETE in the box below.
                    </p>
                    <div class="mb-4 mx-5">
                        <b-form-input
                            type="text"
                            placeholder="DELETE"
                            v-model="confirmationString"
                            class="confirm-delete-modal__confirmation_input"
                            @keyup.enter="$event.target.blur()"
                            enterkeyhint="done"
                        />
                    </div>
                </div>

                <div class="d-flex justify-content-center py-3 mb-3">
                    <b-button variant="light" block @click="$refs.confirmDeleteModal.hide()" class="mr-2 py-2 mt-0">
                        Cancel
                    </b-button>
                    <b-button variant="danger" block :disabled="!isDeleteButtonEnabled" @click="confirmDelete" class="py-2 mt-0">
                        Delete <span v-if="bankAccount.parent_bank_account_id">virtual </span>account
                    </b-button>
                </div>
            </div>

        </b-modal>
    </div>
</template>

<script src="./confirm-delete-modal.controller.js"></script>
<style lang="scss" src="./_confirm-delete-modal.scss" scoped></style>
