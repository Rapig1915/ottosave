<template>
    <div class="deactivatedAccountComponent">
        <b-container>
            <div class="d-flex justify-content-center px-4">
                <img class="logo" src="~vue_root/assets/images/logo.png"/>
            </div>
            <b-row class="mt-4">
                <b-col>
                    <div class="w-100">
                        <p class="text-center text-dark largest-text">Your account has been deleted. For help contact <a href="mailto:support@ottosave.com">support@ottosave.com</a>.</p>

                        <div class="d-flex justify-content-center mt-5">
                            <b-button @click.prevent="$router.go()" variant="primary" size="md" class="px-5">
                                <span>Close</span>
                            </b-button>
                        </div>
                    </div>
                </b-col>
            </b-row>
            <div class="py-4 text-center border-top copyright">
                &copy; {{ Date.now() | moment("YYYY") }} Defend Your Money, LLC. All rights reserved.
            </div>
        </b-container>
    </div>
</template>

<script src="./deactivated-account.controller.js"></script>
<style lang="scss" src="./_deactivated-account.scss" scoped></style>
