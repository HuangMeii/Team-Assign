// resources/js/bootstrap.js (Sử dụng các biến môi trường trực tiếp từ import.meta.env)

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; 

window.Pusher = Pusher;

// Lấy các biến env trực tiếp
const REVERB_KEY = import.meta.env.VITE_REVERB_APP_KEY;
const REVERB_HOST = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const REVERB_PORT = import.meta.env.VITE_REVERB_PORT ?? 8080;
const REVERB_SCHEME = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

window.Echo = new Echo({
    broadcaster: 'reverb', 
    key: REVERB_KEY, 
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT,
    wssPort: REVERB_PORT,
    forceTLS: REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});