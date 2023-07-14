<template>
    <div class="vueAuthorizedView">
        <loading-spinner :showSpinner="isInitializingView" custom-class="overlay">
            <router-view></router-view>
        </loading-spinner>
        <upgrade-modal/>
        <trial-ending-modal ref="trialEndingModal"/>
        <session-timeout-modal/>
        <userflow-service v-if="user" :user="user"/>
    </div>
</template>

<script src="./authorized.controller.js"></script>
