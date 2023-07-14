import AdminComponent from './admin.vue';
import UserListComponent from './user-list/user-list.vue';
import NotificationsComponent from './notifications/notifications';
import CouponsComponent from './coupons/coupons';
import FinicityOauthComponent from './finicity-oauth/finicity-oauth';
import FinicityCustomersComponent from './finicity-customers/finicity-customers';
import TestDepositComponent from './test-deposit/test-deposit';

export default {
    path: '/admin',
    name: 'admin',
    component: AdminComponent,
    meta: {
        permissions: ['access super-admin']
    },
    children: [
        {
            path: 'users',
            name: 'admin-user-list',
            component: UserListComponent,
            meta: {
                title: 'Users List'
            }
        },
        {
            path: 'notifications',
            name: 'admin-notifications-preview',
            component: NotificationsComponent,
            meta: {
                title: 'Notification Previews'
            }
        },
        {
            path: 'coupons',
            name: 'admin-coupons',
            component: CouponsComponent,
            meta: {
                title: 'Manage Coupons'
            }
        },
        {
            path: 'finicity-oauth',
            name: 'admin-finicity-oauth',
            component: FinicityOauthComponent,
            meta: {
                title: 'Manage Fincity Migrations'
            }
        },
        {
            path: 'finicity-customers',
            name: 'admin-finicity-customers',
            component: FinicityCustomersComponent,
            meta: {
                title: 'Manage Fincity Customers'
            }
        },
        {
            path: 'test-deposit',
            name: 'admin-test-deposit',
            component: TestDepositComponent,
            meta: {
                title: 'Test Deposit'
            }
        },
    ]
};
