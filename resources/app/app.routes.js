import appComponent from './app.vue';
import guestRoutes from './guest/guest.routes';
import authorizedRoutes from './authorized/authorized.routes';

export default {
    path: '/',
    component: appComponent,
    children: [
        guestRoutes,
        authorizedRoutes
    ]
};
