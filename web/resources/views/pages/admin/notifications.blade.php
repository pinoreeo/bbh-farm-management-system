<x-layouts.admin title="Notifikasi" subtitle="Lihat pengingat berdasarkan catatan peternakan." skeleton="table">
    @php
        $unreadCount = collect($notifications)->where('is_read', false)->count();
    @endphp

    <section class="admin-notification-page" data-notification-feed>
        <div class="admin-notification-page-actions">
            <div class="admin-notification-tabs" role="group" aria-label="Filter notifikasi">
                <button type="button" data-notification-filter="all" aria-pressed="true">Semua</button>
                <button type="button" data-notification-filter="unread" aria-pressed="false">Belum dibaca</button>
                <button type="button" data-notification-filter="read" aria-pressed="false">Sudah dibaca</button>
            </div>

            @if ($unreadCount > 0)
                <form method="post" action="{{ route('admin.notifications.read-all') }}" data-skeleton-target="table">
                    @csrf
                    <button class="ui-btn ui-btn-soft" type="submit">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        <x-panel :padded="false" class="admin-notification-feed-panel">
            <div class="admin-notification-feed-heading">
                <h2>Semua notifikasi</h2>
                @if ($unreadCount > 0)
                    <span>{{ $unreadCount }} baru</span>
                @endif
            </div>

            <div class="admin-notification-feed-list">
                @forelse ($notifications as $item)
                    <a class="admin-notification-feed-item" href="{{ $item['url'] }}" data-notification-item data-notification-state="{{ $item['is_read'] ? 'read' : 'unread' }}">
                        <span class="admin-notification-feed-icon" data-tone="{{ $item['tone'] }}">
                            <x-icons name="bell" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="admin-notification-feed-title">
                                {{ $item['title'] }}
                                @unless ($item['is_read'])
                                    <i aria-label="Belum dibaca"></i>
                                @endunless
                            </span>
                            <span class="admin-notification-feed-body">{{ $item['body'] }}</span>
                            <span class="admin-notification-feed-time">{{ $item['time'] }}</span>
                        </span>
                    </a>
                @empty
                    <div class="admin-notification-feed-empty">Belum ada pengingat berdasarkan catatan saat ini.</div>
                @endforelse
                <div class="admin-notification-feed-empty" data-notification-empty hidden>Tidak ada notifikasi yang sesuai dengan filter.</div>
            </div>
        </x-panel>
    </section>
</x-layouts.admin>
