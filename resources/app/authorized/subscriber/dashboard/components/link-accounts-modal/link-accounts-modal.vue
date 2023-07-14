<template>
    <div>
        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="link-accounts-modal"
            ref="linkAccountsModalRef"
        >
            <i class="fas fa-times close-icon" @click="$refs.linkAccountsModalRef.hide()"></i>
            <img src="~vue_root/assets/fonts/otto_logo.svg" class="logo"/>

            <h2 class="title">Welcome to Otto</h2>

            <div class="body">
                <h3 class="subtitle">It's time to spend smarter and SAVE MONEY!</h3>
                <p>
                    To get started, click the button below to<br/> link your financial institutions.
                </p>
                <router-link class="btn btn-primary btn-md link-button" :to="{ name: 'accounts', query: { openConnectModal: true } }">Link</router-link>
            </div>
        </b-modal>
    </div>
</template>
<script src="./link-accounts-modal.controller.js"></script>
<style lang="scss" src="./_link-accounts-modal.scss" scoped></style>
