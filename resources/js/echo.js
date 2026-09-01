import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    activityTimeout: 10000,
    pongTimeout: 5000,
    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            window.axios
                .post(route('api.broadcasting.auth'), {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
                .then((response) => callback(false, response.data))
                .catch((error) => callback(true, error))
        }
    })
})