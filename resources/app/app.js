import 'core-js/stable';
import Vue from 'vue';
import Axios from 'axios';
import DecimalJs from 'decimal.js';
import VelocityAnimate from 'velocity-animate';
import VueMoment from 'vue-moment';

window.Vue = Vue;
window.axios = Axios;
window.Decimal = DecimalJs;
window.Velocity = VelocityAnimate;
import 'chartjs-plugin-style';

import '@fortawesome/fontawesome-free/css/all.css';
import './assets/fonts/dym-font/css/dym-font.css';
import './app.scss';

import AppApi from './plugins/app-api.plugin.js';
import UtilitiesPlugin from './plugins/utilities.plugin.js';
import IosKeychainPlugin from './plugins/ios-keychain.plugin.js';
import IosUniversalLinksPlugin from './plugins/ios-universal-links.plugin.js';
import IosInAppReviewPlugin from './plugins/ios-in-app-review.plugin.js';
import ClientStorage from './plugins/client-storage/client-storage.plugin.js';
import router from './router';
import store from './app.store';
import appHeader from './components/app-header/app-header.vue';
import appFooter from './components/app-footer/app-footer.vue';
import appMessage from './components/app-message/app-message.vue';
import bankAccountIcon from './components/bank-account-icon/bank-account-icon.vue';
import BalancesPanel from './components/balances-panel/balances-panel';
import validatedInput from './components/validated-input/validated-input.vue';
import LoadingSpinner from './components/loading-spinner/loading-spinner.vue';
import BootstrapVue from 'bootstrap-vue';
import dashboardLayout from './components/dashboard-layout/dashboard-layout.vue';
import { dateFilter } from './filters/date.filter.js';
import { shortDateFilter } from './filters/short-date.filter.js';
import { currencyFilter } from './filters/currency.filter.js';
import { decimalFilter } from './filters/decimal.filter.js';
import currencyInput from './components/currency-input/currency-input';
import CalculatorPopover from './components/calculator-popover/calculator-popover';
import tourModal from './components/tour-walkthrough/components/tour-modal/tour-modal';
import tourPopover from './components/tour-walkthrough/components/tour-popover/tour-popover';
import tourInline from './components/tour-walkthrough/components/tour-inline/tour-inline';
import tourHighlightedModal from './components/tour-walkthrough/components/tour-highlighted-modal/tour-highlighted-modal';
import TourHighlight from './directives/tour-highlight/tour-highlight';
import AccessCheck from './directives/access-check/access-check';
import PurchaseComponent from './components/purchase-component/purchase';
import InfoPopover from './components/info-popover/info-popover';
import PullToRefresh from './components/pull-to-refresh/pull-to-refresh';
import AccountColorPicker from './components/account-color-picker/account-color-picker';
import paginationBar from './components/pagination-bar/pagination-bar.vue';

import Chartist from 'vue-chartist';
import DatePicker from 'vuejs-datepicker';
import { VueSelect } from 'vue-select/dist/vue-select';

Vue.use(AppApi);
Vue.use(UtilitiesPlugin);
Vue.use(ClientStorage);
Vue.use(IosKeychainPlugin);
Vue.use(IosInAppReviewPlugin);
Vue.use(IosUniversalLinksPlugin, { baseUrl: window.appEnv.baseURL });
Vue.use(BootstrapVue);
Vue.use(Chartist);
Vue.use(VueMoment);

Vue.directive('tour-highlight', TourHighlight);
Vue.directive('dym-access', AccessCheck);

Vue.component('v-select', VueSelect);
Vue.component('app-header', appHeader);
Vue.component('app-footer', appFooter);
Vue.component('app-message', appMessage);
Vue.component('bank-account-icon', bankAccountIcon);
Vue.component('balances-panel', BalancesPanel);
Vue.component('validated-input', validatedInput);
Vue.component('loading-spinner', LoadingSpinner);
Vue.component('dashboard-layout', dashboardLayout);
Vue.component('date-picker', DatePicker);
Vue.component('currency-input', currencyInput);
Vue.component('calculator-popover', CalculatorPopover);
Vue.component('tour-modal', tourModal);
Vue.component('tour-highlighted-modal', tourHighlightedModal);
Vue.component('tour-popover', tourPopover);
Vue.component('tour-inline', tourInline);
Vue.component('purchase-component', PurchaseComponent);
Vue.component('info-popover', InfoPopover);
Vue.component('pull-to-refresh', PullToRefresh);
Vue.component('account-color-picker', AccountColorPicker);
Vue.component('pagination-bar', paginationBar);

Vue.filter('date', dateFilter);
Vue.filter('shortDate', shortDateFilter);
Vue.filter('currency', currencyFilter);
Vue.filter('decimal', decimalFilter);

const app = new Vue({
    el: '#vueApp',
    router,
    store,
    components: {}
});
