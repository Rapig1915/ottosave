<template>
    <div class="transferWarningModal">
        <b-modal
            centered
            hide-header
            ref="transferWarningModal"
            @hidden="handleHiddenEvent"
        >
            <h3>
                The app does NOT make the transfers for you.
                <i class="fas fa-times text-muted float-right" @click="notTransferMadeFunction()"></i>
            </h3>
            <p>
                You must log in to your online banking on this or another device and make each transfer personally.
            </p>
            <div>
                <span class="text-dark mr-4">Have you made this transfer?</span>
                <div class="d-inline-block mt-3 mt-sm-0">
                    <b-button variant="light" class="px-4 mx-3" @click="notTransferMadeFunction()">
                        No
                    </b-button>
                    <b-button variant="primary" class="px-4 mx-3" @click="transferMadeFunction()">
                        Yes
                    </b-button>

                </div>
            </div>
            <template slot='modal-footer'>
                <div class="w-100">
                    <b-form-checkbox v-model="shouldPermanentlyDismiss" class="float-left">
                        Don't show this message again.
                    </b-form-checkbox>
                </div>
            </template>

        </b-modal>
    </div>
</template>

<script src="./transfer-warning-modal.controller.js"></script>
<style lang="scss" src="./_transfer-warning-modal.scss" scoped></style>
