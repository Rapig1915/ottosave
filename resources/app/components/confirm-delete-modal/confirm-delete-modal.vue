<template>
    <div class="confirm-delete-modal">
        <b-modal
            hide-footer
            hide-header
            static
            v-model="isModalShown"
            @hidden="cleanupModal"
            centered>
            <div class="px-4">
                <div class="text-center">
                    <div class="font-weight-semibold text-black mb-4">
                        <slot>
                            Are you sure?
                        </slot>
                    </div>
                    <div class="mb-4 mx-5" v-if="requiredConfirmationString">
                        <b-form-input
                            type="text"
                            :placeholder="requiredConfirmationString"
                            v-model="confirmationString"
                            class="confirm-delete-modal__confirmation_input"
                            @keyup.enter="$event.target.blur()"
                            enterkeyhint="done"
                        />
                    </div>
                </div>

                <div class="confirm-delete-modal__actions">
                    <b-button variant="danger" block :disabled="!isDeleteButtonEnabled" @click="confirmFunction" class="mr-2 py-2 mt-0">
                        {{ confirmButtonText }}
                    </b-button>
                    <b-button variant="light" block @click="cancelFunction" class="py-2 mt-0">
                        {{ cancelButtonText }}
                    </b-button>
                </div>
            </div>

        </b-modal>
    </div>
</template>

<script src="./confirm-delete-modal.controller.js"></script>
<style lang="scss" src="./_confirm-delete-modal.scss" scoped></style>
