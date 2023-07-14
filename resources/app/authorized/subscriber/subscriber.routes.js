import SubscriberComponent from './subscriber.vue';
import DefendRoutes from './defend/defend.routes';
import { AccountsRoutes } from './accounts/accounts.routes';
import { SettingsRoutes } from './settings/settings.routes';
import AssignRoutes from './assign/assign.routes';
import DashboardComponent from './dashboard/dashboard.vue';
import ReportsPage from './reports/reports.vue';
import AdminRoutes from './admin/admin.routes';

export default {
    path: '',
    component: SubscriberComponent,
    meta: {
        requiresSubscription: true,
        requiresVerifiedEmail: true,
    },
    children: [
        {
            path: '/',
            name: 'dashboard',
            component: DashboardComponent,
            meta: {
                title: 'Dashboard'
            }
        },
        {
            path: '/reports',
            name: 'reports',
            component: ReportsPage,
            meta: {
                title: 'Reports'
            }
        },
        {
            path: '/videos',
            name: 'videos',
            beforeEnter(){
                window.open('https://www.defendyourmoney.com/tutorial-videos', '_blank');
            }
        },
        DefendRoutes,
        AssignRoutes,
        AdminRoutes,
        ...AccountsRoutes,
        ...SettingsRoutes,
    ]
};
