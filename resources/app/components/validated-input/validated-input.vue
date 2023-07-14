<template>
    <div>
        <input v-model="inputValue" v-bind:type="type" v-bind:name="name" class="form-control" v-bind:placeholder="placeholder" :readonly="!!readOnly">
        <div v-if="validationErrors[name]">
            <div v-for="(error, index) in validationErrors[name]" v-bind:key="index">
                <span class="text-danger">{{error}}</span>
            </div>
        </div>
    </div>
</template>
<script src="./validated-input.controller.js"></script>
