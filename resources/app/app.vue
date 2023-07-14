<template>
    <div class="vueIndexView" v-bind:key="currentViewKey">
        <pull-to-refresh>
            <router-view></router-view>
        </pull-to-refresh>
        <IosLoginOverlay v-if="$store.state.showIosLoginOverlay"/>
    </div>
</template>
<style lang="scss" src="./_app.scss" scoped></style>
<script src="./app.controller.js"></script>
