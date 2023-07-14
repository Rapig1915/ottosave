<template>
    <div class="createCouponComponent bg-white border my-3 px-3 rounded">
        <b-row class="my-3">
            <b-col cols="12">
                <label for="code">Coupon Code:</label>
                <input class="form-control" type="text" name="code" v-model="newCoupon.code" v-b-tooltip.hover title="Leave blank to auto-generate code">
                <span v-if="validationErrors.code" class="smaller-text text-danger">{{ validationErrors.code.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="type_slug">Coupon Type:</label>
                <b-form-select v-model="newCoupon.type_slug" name="type_slug" :options="couponTypeOptions" class="text-capitalize"></b-form-select>
                <span v-if="validationErrors.type_slug" class="smaller-text text-danger">{{ validationErrors.type_slug.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="type_slug">Reward Type:</label>
                <b-form-select v-model="newCoupon.reward_type" name="type_slug" :options="rewardTypeOptions" class="text-capitalize"></b-form-select>
                <span v-if="validationErrors.reward_type" class="smaller-text text-danger">{{ validationErrors.reward_type.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="expiration_date">Expiration Date:</label>
                <date-picker
                    name="expiration_date"
                    format="MM/dd/yyyy"
                    input-class="form-control bg-white"
                    v-model="newCoupon.expiration_date"
                    v-b-tooltip.hover title="Leave blank to never expire coupon."
                />
                <span v-if="validationErrors.expiration_date" class="smaller-text text-danger">{{ validationErrors.expiration_date.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="amount">Reward Amount:</label>
                <input class="form-control" type="number" name="amount" v-model="newCoupon.amount" v-b-tooltip.hover title="e.g. number of free months, or % discount">
                <span v-if="validationErrors.amount" class="smaller-text text-danger">{{ validationErrors.amount.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="reward_duration_in_months">Duration in Months:</label>
                <input class="form-control" type="number" name="reward_duration_in_months" v-model="newCoupon.reward_duration_in_months" v-b-tooltip.hover title="Number of months coupon will be applied after redemption">
                <span v-if="validationErrors.reward_duration_in_months" class="smaller-text text-danger">{{ validationErrors.reward_duration_in_months.join(' ') }}</span>
            </b-col>
            <b-col cols="6" md="4" class="mt-3">
                <label for="number_of_uses">Number of Uses:</label>
                <input class="form-control" type="number" name="number_of_uses" v-model="newCoupon.number_of_uses" v-b-tooltip.hover title="The number of users who can redeem the coupon">
                <span v-if="validationErrors.number_of_uses" class="smaller-text text-danger">{{ validationErrors.number_of_uses.join(' ') }}</span>
            </b-col>
        </b-row>
        <b-row class="my-3">
            <b-col cols="12">
                <div class="float-right">
                    <b-button variant="outline-plain" @click="cancelCreation" class="mr-3" :disabled="isSavingCoupon">Cancel</b-button>
                    <b-button variant="primary" @click="createCoupon" :disabled="isSavingCoupon">
                        <loading-spinner :show-spinner="isSavingCoupon" custom-class="size-auto">
                            Create Coupon
                        </loading-spinner>
                    </b-button>
                </div>
            </b-col>
        </b-row>
    </div>
</template>

<script src="./create-coupon.controller.js"></script>
<style lang="scss" src="./_create-coupon.scss" scoped></style>
