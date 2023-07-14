<template>
    <div class="refreshAccountsComponent"
        :class="{'mobile': mobile}"
        v-b-tooltip.hover
        title="Refresh Balances"
    >
        <i class="fas fa-sync-alt" :class="dynamicClasses" @click="updateLinkedAccountBalances"></i>
    </div>
</template>

<script src="./refresh-accounts.controller.js"></script>
<style lang="scss" src="./_refresh-accounts.scss" scoped></style>
