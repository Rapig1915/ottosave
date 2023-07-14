<template>
    <div>
        <div class="text-center mt-4">
            <img class="logo" src="~vue_root/assets/images/logo.png"/>
        </div>
        <settings-component />
        <div class="col-12 mt-5 pt-4 text-center border-top copyright">
            &copy; {{ Date.now() | moment("YYYY") }} Defend Your Money, LLC. All rights reserved.
        </div>
    </div>
</template>
<script src="./manage-subscription.controller.js"></script>
<style lang="scss" src="./_manage-subscription.scss" scoped></style>
