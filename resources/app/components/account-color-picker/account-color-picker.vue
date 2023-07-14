<template>
    <b-popover :target="targetRef" :show="isOpen" class="account-color-picker" @shown="focusColorPicker">
        <div class="account-color-picker__colors" ref="colorPicker" @blur="closePicker($event)" tabindex="0">
            <div
                v-for="color in availableColors"
                :key="color.value"
                class="account-color-picker__color"
                :class="[
                    `background-color-${color.value}`,
                    currentColor === color.value ? 'account-color-picker__color--selected' : `color-${color.value}`
                ]"
                @click="selectColor(color)"
            >
                <i class="fas fa-check"></i>
            </div>
        </div>
    </b-popover>
</template>

<script src="./account-color-picker.controller.js"></script>
<style lang="scss" src="./_account-color-picker.scss" scoped></style>
