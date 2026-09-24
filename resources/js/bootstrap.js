import _ from 'lodash';
import $ from 'jquery';
import Popper from 'popper.js';
import 'bootstrap';
import axios from 'axios';
import Echo from 'laravel-echo';
import Larasocket from 'larasocket-js';

window._ = _;
window.$ = window.jQuery = $;
window.Popper = Popper;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Echo = new Echo({
    broadcaster: Larasocket,
    token: import.meta.env.VITE_LARASOCKET_TOKEN,
});
