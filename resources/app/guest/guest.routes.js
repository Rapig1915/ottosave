import GuestComponent from './guest.vue';
import UserRoutes from './user/user.routes';
import UpgradeRequired from './upgrade-required/upgrade-required';
import VerifyEmailComponent from './verify-email/verify-email.vue';

export default {
    path: 'guest',
    name: 'guest',
    component: GuestComponent,
    children: [
        UserRoutes,
        {
            path: 'upgrade-required',
            name: 'upgrade-required',
            component: UpgradeRequired
        },
        {
            path: '/verify',
            name: 'verify-email',
            component: VerifyEmailComponent,
            meta: {
                title: 'Verify Email'
            }
        },
    ]
};
