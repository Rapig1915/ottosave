import AuthorizedComponent from './authorized.vue';
import SubscriberRoutes from './subscriber/subscriber.routes';
import ManageSubscriptionComponent from './manage-subscription/manage-subscription.vue';
import VerificationRequiredComponent from './verification-required/verification-required.vue';
import DeactivatedAccountComponent from './deactivated-account/deactivated-account.vue';

export default {
    path: '',
    component: AuthorizedComponent,
    meta: {
        requiresAuth: true,
    },
    children: [
        SubscriberRoutes,
        {
            path: 'subscription',
            name: 'manage-subscription',
            component: ManageSubscriptionComponent,
            meta: {
                title: 'Manage Subscription',
                requiresVerifiedEmail: true,
            }
        },
        {
            path: 'verification-required',
            name: 'verification-required',
            component: VerificationRequiredComponent,
            meta: {
                title: 'Verification Required',
            }
        },
        {
            path: 'deactivated-account',
            name: 'deactivated-account',
            component: DeactivatedAccountComponent,
            meta: {
                title: 'Account Deactivated',
                requiresVerifiedEmail: true,
            }
        },
    ]
};
