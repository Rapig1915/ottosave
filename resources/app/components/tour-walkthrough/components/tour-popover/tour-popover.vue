<!--
Usage:  <tour-popover
            name="tour-query"
            v-if="waitForTarget"
        >
            <template slot="title">
                Tour Title goes here
            </template>
            <p>Any content here</p>
        </tour-popover>
        <div v-tour-highlight="{ name: 'tourName' }"></div> // This links the tour content with an element //
-->
<template>
    <div class="tourPopoverComponent" :id="`${name}-tour-popover-component`" ref="tourPopoverComponent">
        <b-popover v-if="targetElement" :target="targetElement" :disabled="!showWalkthrough" :show.sync="showWalkthrough" @show="onTourShow" @hide="onTourHide" placement="auto" offset="30,30" :container="`${name}-tour-popover-component`">
            <b-container class="pt-4 tourPopoverContainer" :id="`${name}-tour-popover-container`">
                <b-row class="px-4 px-sm-5">
                    <h1 class="tour-title">
                        <slot name="title"></slot>
                    </h1>
                </b-row>
                <b-row class="px-4 px-sm-5 py-3 tour-content">
                    <slot></slot>
                </b-row>
                <b-row class="popover-footer py-3 px-5">
                    <div class="w-100 mx-auto d-flex justify-content-around">
                        <router-link :to="previousTourStep" v-if="previousTourStep" class="btn btn-outline-primary btn-md float-left w-35">Previous</router-link>
                        <router-link :to="nextTourStep" v-if="previousTourStep && nextTourStep" class="btn btn-primary btn-md float-right">Next</router-link>
                        <router-link :to="nextTourStep" v-else-if="nextTourStep" class="btn btn-primary btn-md">Get Started</router-link>
                        <b-button v-else @click="closeAndCompleteWalthrough" class="w-35 float-right" variant="primary" size="md">Close</b-button>
                    </div>
                </b-row>
            </b-container>
        </b-popover>
        <div :class="{ shade: showWalkthrough }" @click="close"></div>
    </div>
</template>

<style lang="scss" src="vue_root/components/tour-walkthrough/_tour-walkthrough.scss" scoped></style>
<style lang="scss" src="./_tour-popover.scss" scoped></style>
<script src="./tour-popover.controller.js"></script>
