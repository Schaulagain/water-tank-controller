import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Reverb runs on the same machine as the page, so connect to whatever host
// the browser used (localhost, a LAN address or the Tailscale name).
let meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content
let key = meta('reverb-key')

if (key) {
    window.Pusher = Pusher
    let port = Number(meta('reverb-port'))

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: window.location.protocol === 'https:',
        enabledTransports: ['ws', 'wss'],
    })
}
