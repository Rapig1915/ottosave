<!--
Usage:  <tour-highlighted-modal name="tour-query" v-if="waitForHighlightTarget" vertical-align="bottom" horizontal-align="center">
            <template slot="title">
                Tour Title goes here
            </template>
            <p>Any content here</p>
        </tour-highlighted-modal>
-->
<template>
    <div class="tourModalHighlightedComponent" ref="tourHighlightedModal">
        <transition name="fade">
            <div class="popover" :class="[`vertical-align-${verticalAlign}`, `horizontal-align-${horizontalAlign}`]" v-if="showWalkthrough && highlightElement">
                <b-container class="pt-4">
                    <i class="fas fa-times close" @click="close"></i>
                    <b-row class="px-4 px-sm-5">
                        <h1 class="tour-title">
                            <slot name="title"></slot>
                        </h1>
                    </b-row>
                    <b-row class="px-3 px-sm-5 pb-4 tour-content">
                        <slot></slot>
                    </b-row>
                    <b-row class="tour-footer py-3 px-5">
                        <div class="w-100 mx-auto d-flex justify-content-around">
                            <router-link :to="previousTourStep" v-if="previousTourStep" class="btn btn-outline-primary btn-md float-left w-35">Previous</router-link>
                            <router-link :to="nextTourStep" v-if="previousTourStep && nextTourStep" class="btn btn-primary btn-md float-right">Next</router-link>
                            <router-link :to="nextTourStep" v-else-if="nextTourStep" class="btn btn-primary btn-md">Get Started</router-link>
                            <b-button v-else @click="closeAndCompleteWalthrough" class="w-35 float-right" variant="primary" size="md">Close</b-button>
                        </div>
                    </b-row>
                </b-container>
            </div>
        </transition>
        <div :class="{ shade: showWalkthrough }" @click="close"></div>
    </div>
</template>

<style lang="scss" src="vue_root/components/tour-walkthrough/_tour-walkthrough.scss" scoped></style>
<style lang="scss" src="./_tour-highlighted-modal.scss" scoped></style>
<script src="./tour-highlighted-modal.controller.js"></script>
