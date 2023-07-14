<template>
    <span class="bankConnectionErrorIconComponent">
        <span v-if="errorType" :id="`connection-error-popover-target-${bankAccount.id}`" class="ml-2">
            <router-link v-if="errorType === 'recoverable'"
                :to="{ name: 'accounts', query: { settings: 'open', id: bankAccount.id }}"
            >
                <i class="fas fa-exclamation text-danger" title="Institution Error" :id="`exclamation-popover-${bankAccount.id}`"></i>
            </router-link>
            <i v-else class="fas fa-exclamation-triangle text-warning" title="Institution Error"></i>
            <b-popover
                :target="`connection-error-popover-target-${bankAccount.id}`"
                :container="`connection-error-popover-target-${bankAccount.id}`"
                placement="bottomright"
                triggers="hover"
                @shown="$event.relatedTarget.focus()">
                <div class="text-muted-light">
                    <div class="mr-1 font-weight-normal smaller-text py-1">
                        <span v-if="errorType ==='recoverable'">
                            We are having trouble communicating with your financial institution. Click to re-attempt connection.
                        </span>
                        <span v-else>
                            There appears to be a problem connecting to {{ displayedInstitutionName }}.
                            <br>
                            We have been notified of the issue and we are working hard to fix it. Most connection issues will be resolved within 24 hours.
                            If the problem persists, please <a :href="`mailto:support@ottosave.com?subject=Institution Error Code ${bankAccount.institution_account.remote_status_code}`">contact our support team</a> referencing Error Code {{ bankAccount.institution_account.remote_status_code }}.
                        </span>
                    </div>
                </div>
            </b-popover>
        </span>
    </span>
</template>

<script src="./bank-connection-error-icon.controller.js"></script>
<style lang="scss" src="./_bank-connection-error-icon.scss" scoped></style>
