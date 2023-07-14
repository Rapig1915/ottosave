<!--
Usage:  <pagination-bar
            :total-rows="number"
            @change="handler({ currentPage, count })"
        </pagination-bar>
-->
<template>
    <div class="pagination-bar alert row no-gutters">
        <b-pagination
            v-model="currentPage"
            pills
            :total-rows="totalRows"
            :per-page="rowsPerPage"
            @input="handleChangePageNum"
        ></b-pagination>
        <div class="d-flex rows-per-page">
            <p>Per Page:</p>
            <select v-model="rowsPerPage" @change="handleChangeRowsPerPage">
                <option v-for="numRow in numRows" :value="numRow" :key="numRow">{{ numRow }}</option>
            </select>
        </div>
    </div>
</template>
<script src="./pagination-bar.controller.js"></script>
<style lang="scss" src="./_pagination-bar.scss" scoped></style>
