<template>
    <div>
        <b-modal
            centered
            hide-header
            hide-footer
            static
            class="link-success-modal"
            ref="linkSuccessModalRef"
        >
            <i class="fas fa-times close-icon" @click="$refs.linkSuccessModalRef.hide()"></i>

            <h2 class="title">SUCCESS!</h2>

            <div class="body">
                <h3 class="subtitle">Otto will now set up your account.</h3>
                <ul class="list">
                    <li>Create a list of your bills</li>
                    <li>Set money aside to cover your upcoming credit card payments</li>
                    <li>Show you how much money you have available to spend</li>
                    <li>And much more!</li>
                </ul>
                <h3 class="subtitle">You will receive an email once the set up is complete.</h3>

                <img src="~vue_root/assets/fonts/otto_logo.svg" class="logo"/>
            </div>
        </b-modal>
    </div>
</template>
<script src="./link-success-modal.controller.js"></script>
<style lang="scss" src="./_link-success-modal.scss" scoped></style>
