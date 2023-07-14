import SettingsComponent from './settings.vue';
import AccountAccessComponent from './account-access/account-access.vue';
import AcceptInviteComponent from './accept-invite/accept-invite.vue';

export const SettingsRoutes = [
    {
        path: '/settings',
        name: 'settings',
        component: SettingsComponent,
        meta: {
            title: 'Settings'
        }
    },
    {
        path: '/settings/account-access',
        name: 'account-access',
        component: AccountAccessComponent,
        meta: {
            title: 'Account Access',
            permissions: ['invite account-users']
        }
    },
    {
        path: '/settings/accept-invite',
        name: 'accept-invite',
        component: AcceptInviteComponent,
        meta: {
            title: 'Accept Invite',
        }
    },
];
