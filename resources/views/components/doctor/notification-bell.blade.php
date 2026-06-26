@php
    $doctorId = session('doctor_id');
@endphp

@if($doctorId)
<div class="relative" x-data="doctorNotifications()" @click.away="open = false">
    <button type="button"
            class="bell-btn"
            @click="togglePanel()"
            aria-label="Notifications"
            :aria-expanded="open">
        <i class="fas fa-bell" style="font-size:13px;"></i>
        <span class="bell-badge"
              x-show="unreadCount > 0"
              x-text="badgeLabel()"
              x-cloak></span>
    </button>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="doctor-notif-panel">
        <div class="doctor-notif-head">
            <span class="doctor-notif-title">Notifications</span>
            <span class="doctor-notif-count" x-show="unreadCount > 0" x-text="`${unreadCount} unread`" x-cloak></span>
        </div>

        <div class="doctor-notif-list">
            <template x-if="loading">
                <div class="doctor-notif-empty">Loading notifications...</div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="doctor-notif-empty">No notifications yet.</div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <button type="button"
                        class="doctor-notif-item"
                        :class="{ 'is-unread': !notification.is_read }"
                        @click="markAsRead(notification)">
                    <div class="doctor-notif-item-top">
                        <span class="doctor-notif-item-title" x-text="notification.title"></span>
                        <span class="doctor-notif-unread-dot" x-show="!notification.is_read"></span>
                    </div>
                    <div class="doctor-notif-item-body" x-text="notification.body"></div>
                    <div class="doctor-notif-item-time" x-text="notification.time_label"></div>
                </button>
            </template>
        </div>
    </div>
</div>
@else
<button type="button" class="bell-btn" aria-label="Notifications">
    <i class="fas fa-bell" style="font-size:13px;"></i>
</button>
@endif
