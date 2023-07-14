<template>
    <b-modal centered
        ref="cancelModal"
        hide-header
        ok-variant="success"
        cancel-variant="light"
        ok-title="Yes"
        cancel-title="No"
        @ok="$emit('ok')"
        class="cancel-modal"
    >
        <div>
            <h3 class="cancel-modal__title">
                Are you sure you want to cancel your subscription?
            </h3>
            <p class="cancel-modal__body-text">
                If cancelled, your bank balances and credit card charges will no longer update automatically. You will not lose any of your previously uploaded data.
            </p>
            <p class="cancel-modal__body-text mb-0" v-if="currentAccount.status !== 'expired'">
                Your subscription will remain active until it expires on {{ currentAccount.expire_date | moment("MM/D/YYYY") }}
            </p>
        </div>
    </b-modal>
</template>

<script src="./cancel-modal.controller.js"></script>
<style lang="scss" src="./_cancel-modal.scss" scoped></style>
