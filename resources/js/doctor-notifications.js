import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function reverbConfig() {
    const server = window.DoctorNotificationsConfig?.reverb ?? {};

    return {
        key: server.key ?? import.meta.env.VITE_REVERB_APP_KEY ?? '',
        host: server.host ?? import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        port: Number(server.port ?? import.meta.env.VITE_REVERB_PORT ?? 8080),
        scheme: server.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? (window.location.protocol === 'https:' ? 'https' : 'http'),
    };
}

function buildEcho() {
    const { key, host, port, scheme } = reverbConfig();
    const forceTLS = scheme === 'https';

    if (!key) {
        console.warn('[DoctorNotifications] Reverb app key is not configured.');
        return null;
    }

    return new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: forceTLS ? ['wss'] : ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });
}

function formatTimeLabel(isoString) {
    if (!isoString) {
        return '—';
    }

    const date = new Date(isoString);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const isYesterday = date.toDateString() === yesterday.toDateString();

    const time = date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

    if (isToday) {
        const diffMs = now - date;
        const mins = Math.max(1, Math.round(diffMs / 60000));
        if (mins < 60) {
            return `${mins} min${mins === 1 ? '' : 's'} ago`;
        }
        return time;
    }

    if (isYesterday) {
        return `Yesterday, ${time}`;
    }

    return date.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + time;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('doctorNotifications', () => ({
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        lastPlayedNotificationId: 0,
        permissionRequested: false,
        sound: null,
        pollTimer: null,
        echoConnected: false,

        init() {
            const config = window.DoctorNotificationsConfig ?? {};

            if (!config.doctorId) {
                return;
            }

            this.sound = new Audio(config.soundUrl ?? '/sounds/notification.mp3');
            this.sound.preload = 'auto';

            this.fetchNotifications();
            this.subscribeToChannel(config.doctorId);
            this.startPolling();
        },

        startPolling() {
            if (this.pollTimer) {
                return;
            }

            this.pollTimer = window.setInterval(() => {
                this.pollForNewNotifications();
            }, 30000);
        },

        async requestDesktopPermission() {
            if (!('Notification' in window)) {
                return;
            }

            if (Notification.permission === 'default') {
                try {
                    await Notification.requestPermission();
                } catch (error) {
                    console.warn('[DoctorNotifications] Notification permission error', error);
                }
            }

            this.permissionRequested = true;
        },

        subscribeToChannel(doctorId) {
            const echo = buildEcho();

            if (!echo) {
                return;
            }

            const connection = echo.connector?.pusher?.connection;

            if (connection) {
                connection.bind('connected', () => {
                    this.echoConnected = true;
                    console.info('[DoctorNotifications] WebSocket connected.');
                });

                connection.bind('error', (error) => {
                    console.error('[DoctorNotifications] WebSocket error', error);
                });

                connection.bind('disconnected', () => {
                    this.echoConnected = false;
                });
            }

            echo.private(`doctor.${doctorId}`)
                .listen('.NewDoctorNotification', (payload) => {
                    this.handleIncomingNotification(payload);
                })
                .error((error) => {
                    console.error('[DoctorNotifications] Channel subscription error', error);
                });
        },

        async fetchNotifications() {
            const config = window.DoctorNotificationsConfig ?? {};
            this.loading = true;

            try {
                const response = await axios.get(config.indexUrl ?? '/doctor/notifications', {
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                });

                if (response.data?.status) {
                    this.notifications = response.data.data ?? [];
                    this.unreadCount = response.data.unread_count ?? 0;

                    const latestId = this.notifications[0]?.id ?? 0;
                    if (latestId > 0 && this.lastPlayedNotificationId === 0) {
                        this.lastPlayedNotificationId = latestId;
                    }
                }
            } catch (error) {
                console.error('[DoctorNotifications] Failed to load notifications', error);
            } finally {
                this.loading = false;
            }
        },

        async pollForNewNotifications() {
            const config = window.DoctorNotificationsConfig ?? {};

            try {
                const response = await axios.get(config.unreadCountUrl ?? '/doctor/notifications/unread-count', {
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                });

                const newCount = response.data?.unread_count ?? 0;

                if (newCount > this.unreadCount) {
                    const previousLatestId = this.lastPlayedNotificationId;
                    await this.fetchNotifications();

                    const latest = this.notifications[0];
                    if (latest?.id && latest.id > previousLatestId) {
                        this.notifyUser(latest);
                    }
                } else {
                    this.unreadCount = newCount;
                }
            } catch (error) {
                console.warn('[DoctorNotifications] Poll failed', error);
            }
        },

        handleIncomingNotification(payload) {
            const id = Number(payload?.id ?? 0);

            if (!id || this.notifications.some((item) => item.id === id)) {
                return;
            }

            const notification = {
                id,
                title: payload.title ?? 'Notification',
                body: payload.body ?? '',
                data: payload.data ?? {},
                is_read: Boolean(payload.is_read),
                created_at: payload.created_at ?? new Date().toISOString(),
                time_label: formatTimeLabel(payload.created_at),
            };

            this.notifications.unshift(notification);

            if (!notification.is_read) {
                this.unreadCount += 1;
            }

            this.notifyUser(notification);
        },

        notifyUser(notification) {
            const id = Number(notification?.id ?? 0);

            if (!id || id <= this.lastPlayedNotificationId) {
                return;
            }

            this.lastPlayedNotificationId = id;
            this.playSound();
            this.showDesktopNotification(notification);
        },

        playSound() {
            if (!this.sound) {
                return;
            }

            this.sound.currentTime = 0;
            this.sound.play().catch(() => {});
        },

        showDesktopNotification(notification) {
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            const config = window.DoctorNotificationsConfig ?? {};
            const desktop = new Notification(config.appName ?? 'HealthInPocket', {
                body: notification.body || notification.title,
                icon: config.iconUrl ?? '/assets/favicon.png',
                tag: `doctor-notification-${notification.id}`,
            });

            desktop.onclick = () => {
                window.focus();
                this.open = true;
                desktop.close();
            };
        },

        togglePanel() {
            this.requestDesktopPermission();
            this.open = !this.open;
        },

        async markAsRead(notification) {
            if (!notification || notification.is_read) {
                return;
            }

            const config = window.DoctorNotificationsConfig ?? {};

            try {
                const response = await axios.post(
                    (config.markReadUrl ?? '/doctor/notifications') + `/${notification.id}/read`,
                    {},
                    { headers: { 'X-CSRF-TOKEN': csrfToken() } }
                );

                if (response.data?.status) {
                    notification.is_read = true;
                    this.unreadCount = response.data.unread_count ?? Math.max(0, this.unreadCount - 1);
                }
            } catch (error) {
                console.error('[DoctorNotifications] Failed to mark notification as read', error);
            }
        },

        badgeLabel() {
            if (this.unreadCount <= 0) {
                return '';
            }

            return this.unreadCount > 99 ? '99+' : String(this.unreadCount);
        },
    }));
});
