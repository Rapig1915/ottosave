<template>
    <div class="loadingSpinnerComponent">
        <slot v-if="!showSpinner"></slot>
        <div v-if="showSpinner" :class="customClass + ' loading-spinner'">
            <i class="fas fa-spin fa-circle-notch"></i>
            <slot name="loading-text"></slot>
        </div>
    </div>
</template>

<script src="./loading-spinner.controller.js"></script>
<style lang="scss" src="./_loading-spinner.scss" scoped></style>
