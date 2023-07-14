import DefendComponent from './defend.vue';

export default {
    path: '',
    component: { template: `<keep-alive><router-view></router-view></keep-alive>` },
    meta: {},
    children: [
        {
            path: 'transfer',
            name: 'transfer',
            component: DefendComponent,
            meta: {
                title: 'Transfer'
            },
        },
        {
            path: 'organize',
            name: 'organize',
            component: DefendComponent,
            meta: {
                title: 'Organize'
            },
        }
    ],
};
