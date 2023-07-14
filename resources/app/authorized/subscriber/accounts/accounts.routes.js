import AccountsComponent from './accounts.vue';

export const AccountsRoutes = [
    {
        path: '/accounts',
        name: 'accounts',
        component: AccountsComponent,
        meta: {
            title: 'Accounts'
        }
    },
];
