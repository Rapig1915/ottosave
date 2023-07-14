<template>
    <div class="leavingOrganizeModal">
        <b-modal
            hide-header
            hide-footer
            ref="leavingOrganizeModal"
            centered
        >
            <h3>
                Wait! You still need to transfer your money.
            </h3>
            <p>
                Otto will calculate the transfer(s) you need to make to execute your plan. If you navigate away from this page, the amounts you have entered will be lost. Do you still want to leave this page?
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

<script src="./leaving-organize-modal.controller.js"></script>
