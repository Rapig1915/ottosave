<template>
    <div class="vueUserView mt-4">
        <div class="head-spacer">
        </div>
        <div class="row mt-5">
            <app-header/>
        </div>
        <div class="row justify-content-center">
            <div class="centeredBox col-12 col-sm-10 col-md-8 col-lg-6 centered">
                <router-view></router-view>
            </div>
        </div>
        <div class="row">
            <app-footer />
        </div>
    </div>
</template>
<style lang="scss" src="./_user.scss" scoped></style>
