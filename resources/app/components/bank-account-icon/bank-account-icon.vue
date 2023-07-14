<template>
    <div
        class="bank-account-icon d-inline-flex fa"
        :class="[
            iconClass,
            `color-${color}`
        ]"
        :style="iconStyles"
        ref="bankAccountIcon"
        @click="openColorPicker"
    >
        <account-color-picker
            v-if="isMounted && editable"
            :targetRef="$refs.bankAccountIcon"
            ref="colorPicker"
            :current-color="color"
            @input="$emit('update:color', $event)"
            close-on-select
        />
    </div>
</template>
<script src="./bank-account-icon.controller.js"></script>
