<template>
    <div class="pullToRefreshComponent">
        <slot v-if="!isRefreshing"></slot>
    </div>
</template>

<script src="./pull-to-refresh.controller.js"></script>
<style lang="scss" src="./_pull-to-refresh.scss" scoped></style>
