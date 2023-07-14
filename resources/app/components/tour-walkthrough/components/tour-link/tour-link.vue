<template>
    <router-link :to="currentTourStep" class="tourLinkComponent">
        <span class="tour-link tour-icon-border" :id="id" v-b-tooltip.hover title="Welcome">
            <i class="far fa-compass" :class="tourAnimationClass"></i>
        </span>
    </router-link>
</template>

<script src="./tour-link.controller.js"></script>
<style lang="scss" src="./_tour-link.scss" scoped></style>
