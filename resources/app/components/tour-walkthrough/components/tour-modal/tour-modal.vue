<!--
Usage:  <tour-modal name="tour-query">
            <template slot="title">
                Tour Title goes here
            </template>
            <p>Any content here</p>
        </tour-modal>
-->
<template>
    <div class="tourModalComponent">
        <b-modal v-model="showWalkthrough" no-fade centered lazy hide-header :hide-footer="hideFooter" footer-bg-variant="light" @hide="handleHideEvent" :modal-class="modalClass" :ref="`tour-modal-${name}`" :size="size">
            <b-container class="px-3 px-sm-5 py-md-3">
                <i class="fas fa-times close" @click="closeModal" v-show="!hideCloseButton"></i>
                <b-row>
                    <h1 class="tour-title">
                        <slot name="title"></slot>
                    </h1>
                </b-row>
                <b-row class="tour-content">
                    <slot></slot>
                </b-row>
            </b-container>

            <template v-slot:modal-footer>

                <div class="w-100 py-3 px-4 tour-modal-footer d-flex justify-content-around" v-show="!hideFooter">
                    <router-link :to="previousTourStep" v-if="previousTourStep" class="btn btn-outline-primary btn-md float-left">Previous</router-link>
                    <router-link :to="nextTourStep" v-if="nextTourStep" class="btn btn-primary btn-md float-right">Next</router-link>
                    <router-link :to="{name: 'videos'}" v-if="!nextTourStep" class="btn btn-primary btn-md">Tutorial Videos</router-link>
                    <b-button v-if="!nextTourStep" @click="closeAndCompleteWalthrough" class="float-right" variant="btn btn-outline-primary">Close</b-button>
                </div>
            </template>
        </b-modal>
    </div>
</template>

<style lang="scss" src="vue_root/components/tour-walkthrough/_tour-walkthrough.scss" scoped></style>
<style lang="scss" src="./_tour-modal.scss" scoped></style>
<script src="./tour-modal.controller.js"></script>
