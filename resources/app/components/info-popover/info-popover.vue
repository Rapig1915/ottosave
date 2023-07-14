<template>
    <span class="infoPopoverComponent" :class="{'wide': wide}">
        <a tabindex="-1" :id="id" class="pr-1 popoverTarget">
            <i class="far fa-question-circle"></i>
        </a>
        <b-popover
            :target="id"
            :container="id"
            triggers="click blur"
            placement="auto"
            @shown="$event.target.focus()"
        >
            <template slot="title" v-if="$slots.title">
                <div class="text-center">
                    <slot name="title"></slot>
                </div>
            </template>
            <div class="popover-content">
                <slot name="content"></slot>
            </div>
            <div v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'plus' }" class="popover-content">
                <slot name="plus-content"></slot>
            </div>
            <div v-dym-access="{ permission: 'subscriptionPlan', valueToTest: 'basic' }" class="popover-content">
                <slot name="basic-content"></slot>
            </div>
        </b-popover>
    </span>
</template>

<script src="./info-popover.controller.js"></script>
<style lang="scss" src="./_info-popover.scss" scoped></style>
