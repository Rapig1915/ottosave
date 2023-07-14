<template>
    <div class="couponsComponent">
        <app-message class="mt-2" type="error" :messages="errorMessages" @close="errorMessages = []"></app-message>

        <b-row class="my-3" align-h="between">
            <b-col cols="6" md="3">
                <div class="searchInput">
                    <i class="fas fa-search"></i>
                    <input type="text" name="Search" v-model="searchString" class="form-control rounded-pill" placeholder="Search" />
                </div>
            </b-col>
            <b-col cols="6">
                <transition name="fade">
                    <b-button @click="isAddingCoupon = true" v-if="!isAddingCoupon" variant="primary" class="float-right">Add Coupon</b-button>
                </transition>
            </b-col>
        </b-row>

        <transition name="fadeHeight">
            <CreateCoupon v-if="isAddingCoupon"
                @creation-cancelled="isAddingCoupon = false"
                @coupon-created="addCouponToList"
                @error="displayErrorMessage"
                :rewardTypes="rewardTypes"
                :couponTypes="couponTypes"/>
        </transition>

        <loading-spinner :show-spinner="isLoadingCoupons || isLoadingOptions" custom-class="overlay">
            <b-table striped hover responsive :items="displayedCoupons" :fields="couponFields"></b-table>
        </loading-spinner>
    </div>
</template>

<script src="./coupons.controller.js"></script>
<style lang="scss" src="./_coupons.scss" scoped></style>
