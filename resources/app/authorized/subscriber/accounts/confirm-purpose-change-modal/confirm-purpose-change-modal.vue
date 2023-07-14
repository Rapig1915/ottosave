<template>
    <div class="confirm-purpose-change-modal">
        <b-modal
            ref="confirmPurposeChangeModal"
            header-border-variant="0"
            hide-footer
            static
            title-tag="h2"
            @hidden="cleanupModal"
            centered>
            <template slot="modal-title">
                <div class="mb-2 text-center">
                    {{ title }}
                </div>
            </template>
            <div class="px-4">
                <div class="text-center">
                    <p class="font-weight-semibold text-black">
                        Are you sure you want to change the purpose of this account?
                    </p>
                    <p class="font-weight-semibold text-danger">
                        This action cannot be undone!
                    </p>
                    <p class="">
                        All sub-balances will be reset and additional virtual accounts will be removed.
                    </p>
                    <p class="mb-4 px-4">
                        Would you still like to change the purpose of this account?
                        <br>
                        If yes, type YES in the box below.
                    </p>
                    <div class="mb-4 mx-5">
                        <b-form-input
                            type="text"
                            placeholder="YES"
                            v-model="confirmationString"
                            class="confirm-delete-modal__confirmation_input"
                            @keyup.enter="$event.target.blur()"
                            enterkeyhint="done"
                        />
                    </div>
                </div>

                <div class="d-flex justify-content-center py-3 mb-3">
                    <b-button variant="light" block @click="cancel" class="mr-2 py-2 mt-0">
                        Cancel
                    </b-button>
                    <b-button variant="danger" block :disabled="!isContinueButtonEnabled" @click="confirmDelete" class="py-2 mt-0">
                        Continue
                    </b-button>
                </div>
            </div>

        </b-modal>
    </div>
</template>

<script src="./confirm-purpose-change-modal.controller.js"></script>
<style lang="scss" src="./_confirm-purpose-change-modal.scss" scoped></style>
