<template>
    <b-modal centered ref="passwordModal" title="Confirm password"
        ok-variant="success"
        cancel-variant="light"
        @ok="$emit('ok', currentPassword)">
        <label :for="'current_password'">Password</label>
        <form @submit.prevent="hideAndEmitOk">
            <validated-input v-model="currentPassword" type="password"
                name="currentPassword" :validationErrors="validationErrors"
            ></validated-input>
        </form>
    </b-modal>
</template>

<script src="./password-modal.controller.js"></script>
