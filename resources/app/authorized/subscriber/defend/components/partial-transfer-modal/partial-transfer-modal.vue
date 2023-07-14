<template>
    <div class="partialTransferModal">
        <b-modal
            hide-header
            hide-footer
            ref="partialTransferModal"
            centered
        >
            <h3>
                WARNING: You have not finished making your transfers.
            </h3>
            <p>
                If you navigate away from this page, your remaining transfer(s) will be lost.
                Are you sure you want to leave this page?
            </p>
            <div>
                <div class="d-flex justify-content-center">
                    <b-button variant="primary" class="px-4 mx-3" @click="cancelNavigation()">
                        Cancel
                    </b-button>
                    <b-button variant="light" class="px-4 mx-3" @click="proceedWithNavigation()">
                        Yes, leave this page
                    </b-button>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script src="./partial-transfer-modal.controller.js"></script>
