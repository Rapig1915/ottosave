<template>
    <div class="emailPreviewComponent w-100">
        <b-container>
            <app-message type="error" :messages="apiErrors" @close="apiErrors = []">
            </app-message>
            <loading-spinner :showSpinner="isLoadingEmail">
                <b-row>
                    <b-col>
                        <iframe :srcdoc="emailContent" width="100%" height="500px"></iframe>
                    </b-col>
                </b-row>
            </loading-spinner>
        </b-container>
    </div>
</template>

<script src="./preview-email.controller.js"></script>
<style lang="scss" src="./_preview-email.scss" scoped></style>
