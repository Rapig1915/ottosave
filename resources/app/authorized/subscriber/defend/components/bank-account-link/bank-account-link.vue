<template>
    <span id="bankAccountLinkComponent">
        <b-link :id="`${bankAccount.id}-link-target`" tabindex="0" ref="popoverTarget">
            Open link
        </b-link>

        <b-popover
            triggers="focus"
            :target="`${bankAccount.id}-link-target`"
            container="bankAccountLinkComponent"
            @show="initializeView"
            @hide="handleHideEvent"
            @hidden="resetView"
        >
            <div v-if="bankAccount.online_banking_url && !isEditing" class="d-flex align-items-center">

                <b-link @click="openWindow(bankAccount.online_banking_url)" class="form-control inputBox text-primary">
                    {{ bankAccount.online_banking_url }}
                </b-link>
                <b-link @click="isEditing = true" class="ml-2 text-nowrap">
                    <i class="fas fa-pencil-alt"></i> Edit
                </b-link>
            </div>
            <div v-else class="d-flex align-items-start">
                <validated-input type="text" name="online_banking_url" v-model="localBankAccount.online_banking_url" :validation-errors="validationErrors" placeholder="Add online banking URL" class="inputBox" />
                <b-button variant="muted-danger" @click="cancel" class="ml-2">
                    Cancel
                </b-button>
                <b-button variant="muted-success" @click="updateBankAccount" :disabled="isSavingBankAccount" class="ml-2">
                    <loading-spinner :show-spinner="isSavingBankAccount" custom-class="size-auto" :class="{'px-2': isSavingBankAccount}">
                        Save
                    </loading-spinner>
                </b-button>
            </div>
        </b-popover>
    </span>
</template>

<script src="./bank-account-link.controller.js"></script>
<style lang="scss" src="./_bank-account-link.scss" scoped></style>
