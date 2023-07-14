<!--
Usage:  <tour-inline name="tour-query">
            <template slot="title">
                Tour Title goes here
            </template>
            <p>Any content here</p>
        </tour-inline>
-->
<template>
    <div class="inlineTourComponent">
        <div class="modal-content shadow-lg my-3" v-if="showWalkthrough">
            <div class="modal-body">
                <b-container class="py-3 px-4 px-sm-5">
                    <i class="fas fa-times close" @click="close"></i>
                    <b-row>
                        <h1 class="tour-inline-title tour-title">
                            <slot name="title"></slot>
                        </h1>
                    </b-row>
                    <b-row class="tour-content">
                        <slot></slot>
                    </b-row>
                </b-container>
            </div>
            <footer class="modal-footer bg-light">
                <div class="w-100 py-3 px-5 d-flex justify-content-around">
                    <router-link :to="previousTourStep" v-if="previousTourStep" class="btn btn-outline-primary btn-md float-left">Previous</router-link>
                    <router-link :to="nextTourStep" v-if="previousTourStep && nextTourStep" class="btn btn-primary btn-md float-right">Next</router-link>
                    <router-link :to="nextTourStep" v-else-if="nextTourStep" class="btn btn-primary btn-md">Get Started</router-link>
                    <b-button v-else @click="closeAndCompleteWalthrough" class="w-25 float-right" variant="primary">Close</b-button>
                </div>
            </footer>
        </div>
    </div>
</template>

<style lang="scss" src="vue_root/components/tour-walkthrough/_tour-walkthrough.scss" scoped></style>
<style lang="scss" src="./_tour-inline.scss" scoped></style>
<script src="./tour-inline.controller.js"></script>
