<template>
    <b-col
        :cols="logoLeftOnSmallScreen ? 7 : 12"
        class="appHeader"
        lg="12"
    >
        <router-link :to="linkLocation" v-if="linkLocation" class="link">
            <img class="appLogo" :class="[`logo-${logoSize}`, { 'mx-auto': !logoLeftOnSmallScreen }]" src="/fonts/otto_logo.svg"/>
        </router-link>
        <img v-else class="appLogo" :class="[`logo-${logoSize}`, { 'mx-auto': !logoLeftOnSmallScreen }]" src="/fonts/otto_logo.svg"/>
    </b-col>
</template>
<style lang="scss" src="./_app-header.scss" scoped></style>
<script src="./app-header.controller.js"></script>
