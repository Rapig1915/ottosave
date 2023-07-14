<template>
    <div v-b-tooltip.hover :title="tooltipTitle">
        <div :id="id" class="currency-mask d-flex px-2 py-2" :class="{ 'disabled': disabled }" @click="selectInput">
            <span class="dollarSign">$</span>
            <input :disabled="disabled" class="text-right" :value="value" type="text" ref="ref-input-element" v-on="inputListeners" @keyup.enter="$event.target.blur()" inputmode="decimal" enterkeyhint="done"/>
            <span class="value-display">
                <span v-if="placeholder && !value" class="placeholder">
                    {{placeholder}}
                </span>
                <span v-else-if="isAutofillPlaceholderShown" class="autofillPlaceholder">
                    {{autofillValue}}
                </span>
                <span v-else>
                    {{currentValue}}
                </span>
            </span>
            <slot></slot>
        </div>
    </div>
</template>

<style lang="scss" src="./_currency-input.scss" scoped></style>
<script src="./currency-input.controller.js"></script>
