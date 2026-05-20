import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Pusher Connected Successfully!');
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Pusher Connection Error:', err);
});
