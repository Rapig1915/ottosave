<template>
    <div class="iosLoginOverlay">
        <div class="w-100 mb-5">
            <img src="~vue_root/assets/images/logo.png" class="mt-4 mx-auto w-50 d-block">
        </div>
        <div class="w-100 mt-5">
            <loading-spinner :showSpinner='$store.state.guest.user.login.isRequestingLogin' customClass="text-center" />
        </div>
    </div>
</template>

<style lang="scss" scoped src="./_ios-login-overlay.scss"></style>
