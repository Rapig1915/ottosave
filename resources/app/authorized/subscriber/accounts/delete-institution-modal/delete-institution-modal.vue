<template>
    <div class="deleteInstitutionModal">
        <b-modal
            ref="deleteInstitutionModal"
            centered
            hide-header
            static
            no-close-on-backdrop
            no-close-on-esc
            @hidden="cleanupModal">
            <p>
                Access tokens for your accounts at {{ institutionToDelete.name }} must be managed directly through your financial institution, as such we cannot remove individual accounts.
            </p>
            <p>
                To unlink all {{ institutionToDelete.name }} accounts from Otto, click the "Unlink" button below.
            </p>
            <p class="smaller-text mb-0">
                Note: Prior to unlinking your accounts, we recommend you visit the institution's website and revoke the access token.
            </p>
            <template slot="modal-footer">
                <loading-spinner :showSpinner="isDeletingInstitution" class="text-center w-100" custom-class="size-auto">
                    <b-button variant="light" @click="$refs.deleteInstitutionModal.hide()" class="px-3 mr-2">
                        Cancel
                    </b-button>
                    <b-button variant="danger" @click="deleteInstitution" class="px-3 mr-2">
                        Unlink
                    </b-button>
                </loading-spinner>
            </template>
        </b-modal>
    </div>
</template>

<script src="./delete-institution-modal.controller.js"></script>
<style lang="scss" src="./_delete-institution-modal.scss" scoped></style>
