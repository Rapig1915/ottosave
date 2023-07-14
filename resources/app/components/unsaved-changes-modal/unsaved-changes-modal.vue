<template>
    <b-modal
        ref="confirmUnsavedChangesModal"
        size="sm"
        hide-header
        centered
        no-close-on-backdrop
        no-close-on-esc>
        You have unsaved changes, are you sure you want to close?
        <template slot="modal-footer">
            <loading-spinner :showSpinner="isSavingChanges" class="text-center w-100" custom-class="size-auto">
                <b-button variant="light" @click="$refs.confirmUnsavedChangesModal.hide()" class="px-3 mr-2">
                    Go back
                </b-button>
                <b-button variant="light" @click="$emit('closeWithoutSaving')" class="px-3 mr-2">
                    Close
                </b-button>
                <b-button variant="muted-success" @click="$emit('bulkSaveAndClose')">
                    Save all
                </b-button>
            </loading-spinner>
        </template>
    </b-modal>
</template>

<script src="./unsaved-changes-modal.controller.js"></script>
