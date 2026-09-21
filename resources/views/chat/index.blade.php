@extends(Auth::user()->role === 'student' ? 'layouts.user' : 'layouts.app')

@section('title', 'Chat người dùng')

@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabs chuyển đổi chat cá nhân / chat nhóm --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ ($mode ?? 'direct') === 'direct' ? 'active' : '' }}"
               href="{{ route('chat.index', ['mode' => 'direct']) }}">
                <i class="fas fa-user me-1"></i> Chat cá nhân
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($mode ?? 'direct') === 'group' ? 'active' : '' }}"
               href="{{ route('chat.index', ['mode' => 'group']) }}">
                <i class="fas fa-users me-1"></i> Chat nhóm ({{ $myGroups->count() ?? 0 }})
            </a>
        </li>
    </ul>

    @if(($mode ?? 'direct') === 'group')
        @php
            // Admin KHÔNG là thành viên nhóm nào -> tab này liệt kê TẤT CẢ nhóm để giám
            // sát nội dung và gửi thông báo/cảnh báo mà không cần tham gia nhóm.
            $isAdminViewer = Auth::user()->role === 'admin';
            $gsearch = (string) request('gsearch', '');
        @endphp
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        {{ $isAdminViewer ? 'Tất cả nhóm' : 'Nhóm của tôi' }}
                        ({{ $myGroups->count() ?? 0 }})
                    </div>
                    @if($isAdminViewer)
                        <div class="card-body border-bottom py-2">
                            <form method="GET" action="{{ route('chat.index') }}">
                                <input type="hidden" name="mode" value="group">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="gsearch" value="{{ $gsearch }}" class="form-control"
                                           placeholder="Tìm nhóm theo tên...">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                    <div class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
                        @forelse($myGroups ?? [] as $g)
                            @php $gUnread = (int) ($groupUnread[$g->group_id] ?? 0); @endphp
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <a class="text-truncate flex-grow-1 text-decoration-none"
                                   href="{{ route('groups.chat.show', $g->group_id) }}"
                                   data-chat-group-id="{{ $g->group_id }}">
                                    <i class="fas fa-users me-2 text-primary"></i>{{ $g->group_name }}
                                    @if($isAdminViewer)
                                        <small class="d-block text-muted">
                                            {{ (int) ($g->members_count ?? 0) }} thành viên
                                            @if(!empty($g->chat_messages_max_created_at))
                                                · Tin cuối: {{ \Illuminate\Support\Carbon::parse($g->chat_messages_max_created_at)->format('d/m H:i') }}
                                            @endif
                                        </small>
                                    @endif
                                </a>
                                @if($isAdminViewer)
                                    {{-- Số tin bị gắn cờ trong nhóm -> mở thẳng tab "Chat nhóm" của Giám sát Chat --}}
                                    @if((int) ($g->flagged_count ?? 0) > 0)
                                        <a class="badge bg-danger text-decoration-none ms-2"
                                           href="{{ route('admin.chat.monitor', ['tab' => 'group', 'group_id' => $g->group_id]) }}"
                                           title="Tin nhắn bị gắn cờ trong nhóm này">{{ (int) $g->flagged_count }} cờ</a>
                                    @endif
                                @else
                                    {{-- Badge theo ĐÚNG nhóm: cập nhật realtime qua data-chat-badge-group-id --}}
                                    <span class="badge bg-danger ms-2" data-chat-badge-group-id="{{ $g->group_id }}"
                                          style="{{ $gUnread > 0 ? '' : 'display: none;' }}">{{ $gUnread }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-muted">
                                @if($isAdminViewer)
                                    {{ $gsearch !== '' ? 'Không tìm thấy nhóm phù hợp.' : 'Chưa có nhóm nào trong hệ thống.' }}
                                @else
                                    Bạn chưa tham gia nhóm nào.
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-muted text-center py-5">
                        <i class="fas fa-comments fa-2x mb-3 d-block"></i>
                        @if($isAdminViewer)
                            Chọn một nhóm bên trái để xem nội dung chat và gửi thông báo / cảnh báo
                            (không cần tham gia nhóm).
                        @else
                            Chọn một nhóm bên trái để mở chat nhóm.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="row g-3">
    <div class="col-md-4"><div class="card"><div class="card-header">Người dùng</div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ isset($user) ? route('chat.show', $user->user_id) : route('chat.index') }}" class="d-flex flex-column gap-2">
                <input type="hidden" name="mode" value="direct">
                <input name="search" class="form-control form-control-sm" maxlength="100"
                       placeholder="Tìm theo tên..." value="{{ request('search') }}">
                <select name="role" class="form-select form-select-sm">
                    <option value="all" {{ request('role', 'all') === 'all' ? 'selected' : '' }}>Tất cả vai trò</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Sinh viên</option>
                    <option value="lecturer" {{ request('role') === 'lecturer' ? 'selected' : '' }}>Giảng viên</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                <button class="btn btn-sm btn-outline-primary">Tìm kiếm</button>
            </form>
        </div>
        <div class="list-group list-group-flush" style="max-height: 420px; overflow-y: auto;">
        @foreach($users as $chatUser)
            @php
                $peerUnread = (int) ($chatUser->unread_count ?? 0);
                $isActivePeer = isset($user) && $user->user_id === $chatUser->user_id;
            @endphp
            <div class="list-group-item d-flex justify-content-between align-items-center {{ $isActivePeer ? 'active' : '' }}">
                <a href="{{ route('chat.show', array_merge([$chatUser->user_id], request()->only(['search', 'role']))) }}"
                   data-chat-peer-id="{{ $chatUser->user_id }}"
                   class="text-decoration-none flex-grow-1 {{ $isActivePeer ? 'text-white' : 'text-dark' }}">
                    {{ $chatUser->name }} <small class="d-block {{ $isActivePeer ? 'text-white-50' : 'text-muted' }}">{{ $chatUser->role }}</small>
                </a>
                {{-- Badge theo ĐÚNG người gửi: cập nhật realtime qua data-chat-badge-user-id --}}
                <span class="badge bg-danger ms-2" data-chat-badge-user-id="{{ $chatUser->user_id }}"
                      style="{{ $peerUnread > 0 ? '' : 'display: none;' }}">{{ $peerUnread }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-block-user"
                        data-user-id="{{ $chatUser->user_id }}" data-user-name="{{ $chatUser->name }}" title="Chặn người dùng này">
                    <i class="fas fa-ban"></i>
                </button>
            </div>
        @endforeach
        </div>
        @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="card-footer d-flex justify-content-center">
            {{ $users->links() }}
        </div>
        @endif
    </div></div>
    <div class="col-md-8"><div class="card"><div class="card-header">{{ $user->name ?? 'Chọn người dùng để chat' }}</div>
    @isset($user)<div id="direct-chat-messages" class="card-body" data-direct-chat-user-id="{{ $user->user_id }}" style="height: 420px; overflow-y: auto;">@forelse($messages as $message)<div class="mb-3 {{ $message->sender_id === Auth::id() ? 'text-end' : '' }}"><span class="d-inline-block p-2 rounded {{ $message->sender_id === Auth::id() ? 'bg-primary text-white' : 'bg-light' }}">{{ $message->content }}@if($message->attachment_url)<img src="{{ $message->attachment_url }}" alt="Ảnh đính kèm" class="d-block mt-2 rounded" style="max-width: 240px; max-height: 180px;">@endif</span><small class="d-block text-muted">{{ $message->created_at->format('d/m/Y H:i') }}</small></div>@empty<p class="text-muted">Chưa có tin nhắn.</p>@endforelse</div><form method="POST" action="{{ route('chat.send', $user->user_id) }}" enctype="multipart/form-data" class="card-footer d-flex gap-2">@csrf<input name="content" class="form-control" maxlength="2000" placeholder="Nhập tin nhắn..."><input type="file" name="attachment" accept="image/*" class="form-control" style="max-width: 180px"><button class="btn btn-primary">Gửi</button></form>@endisset
    </div></div>
    @endif
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('direct-chat-messages');
    if (!box || !window.Echo) return;

    const senderId = Number(box.dataset.directChatUserId);
    window.Echo.private(`chat.{{ Auth::id() }}`).listen('.direct-message', function (event) {
        const message = event.message;
        if (Number(message.sender_id) !== senderId) return;

        const row = document.createElement('div');
        row.className = 'mb-3';
        const bubble = document.createElement('span');
        bubble.className = 'd-inline-block p-2 rounded bg-light';
        bubble.textContent = message.content || '';
        if (message.attachment_url) {
            const image = document.createElement('img');
            image.src = message.attachment_url;
            image.alt = 'Ảnh đính kèm';
            image.className = 'd-block mt-2 rounded';
            image.style.maxWidth = '240px';
            image.style.maxHeight = '180px';
            bubble.appendChild(image);
        }
        const time = document.createElement('small');
        time.className = 'd-block text-muted';
        time.textContent = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        row.appendChild(bubble);
        row.appendChild(time);
        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    });

    // Chặn người dùng (có hộp thoại xác nhận)
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value;
    document.querySelectorAll('.btn-block-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const userId = btn.dataset.userId;
            const userName = btn.dataset.userName || 'người dùng này';
            if (!confirm(`Bạn không muốn nhận tin nhắn từ ${userName} nữa?`)) return;
            fetch('{{ url('/block-user') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ user_id: Number(userId) }),
            }).then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            }).then(function () {
                location.reload();
            }).catch(function (err) {
                alert('Chặn người dùng thất bại: ' + err.message);
            });
        });
    });
});
</script>
@endpush
@endsection