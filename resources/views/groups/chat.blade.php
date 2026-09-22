@extends(Auth::user()->role === 'admin' ? 'layouts.app' : 'layouts.user')

@section('title', 'Chat Nhóm')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <h2 class="text-primary"> Chat Nhóm: {{ $group->group_name }}</h2>
            <p class="text-muted">Topic: {{ $group->topic->name ?? 'Chưa chọn Topic' }}</p>

            @if($isAdminViewer ?? false)
                {{-- Admin KHÔNG là thành viên nhóm: chỉ xem để giám sát + gửi thông báo/cảnh báo --}}
                <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-user-shield me-1"></i>
                        <strong>Đang xem với quyền Admin</strong> (chế độ giám sát) — bạn không phải thành viên
                        của nhóm này. Tin nhắn gửi từ ô soạn tin bên dưới là
                        <strong>thông báo / cảnh báo từ Admin</strong>.
                    </span>
                    <a href="{{ route('chat.index', ['mode' => 'group']) }}"
                       class="btn btn-sm btn-outline-secondary ms-3 text-nowrap">
                        <i class="fas fa-arrow-left me-1"></i>Danh sách nhóm
                    </a>
                </div>
            @endif

            <div id="chat-box"
                 class="card shadow-sm"
                 data-group-id="{{ $group->group_id }}" {{-- Giữ lại data-group-id cho JS module --}}
                 data-user-id="{{ Auth::id() }}"
                 data-messages-url="{{ route('groups.chat.messages', $group->group_id) }}" {{-- Polling fallback khi Reverb lỗi --}}
                 data-last-message-id="{{ optional($messages->last())->id ?? 0 }}"
                 style="height: 50vh; overflow-y: scroll; padding: 15px;">
                @forelse ($messages as $message)
                    @if($message->isAdminMessage())
                        {{-- Thông báo / cảnh báo do admin gửi thẳng vào khung chat nhóm --}}
                        @php $isAdminWarning = $message->type === \App\Models\ChatMessage::TYPE_WARNING; @endphp
                        <div class="message mb-3 text-center" data-message-id="{{ $message->id }}">
                            <div class="alert {{ $isAdminWarning ? 'alert-warning' : 'alert-info' }} d-inline-block text-start mb-1"
                                 style="max-width: 85%; word-wrap: break-word;">
                                <strong>{{ $isAdminWarning ? '⚠️ Cảnh báo từ Admin' : '📢 Thông báo từ Admin' }}</strong>
                                <small class="text-muted">— {{ optional($message->user)->name ?? 'Admin' }}</small>
                                <div class="mt-1">{{ $message->content }}</div>
                                <small class="text-muted d-block">{{ $message->created_at->format('H:i') }}</small>
                            </div>
                        </div>
                    @else
                    <div class="message mb-2
                        @if($message->user_id === Auth::id())
                            text-end
                        @else
                            text-start
                        @endif" data-message-id="{{ $message->id }}">
                        
                        <small class="text-muted">{{ optional($message->user)->name ?? 'Người dùng đã xóa' }}:</small>
                        <div class="p-2
                            @if($message->user_id === Auth::id())
                                bg-primary text-white rounded-start d-inline-block
                            @else
                                bg-light text-dark rounded-end d-inline-block border
                            @endif"
                            style="max-width: 70%; word-wrap: break-word;">
                            {{ $message->content }}
                            @if(!empty($message->attachment))
                                <img src="{{ Storage::url($message->attachment) }}" alt="Ảnh đính kèm" class="d-block mt-2 rounded" style="max-width: 240px; max-height: 180px;">
                            @endif
                        </div>
                        <small class="text-muted d-block">{{ $message->created_at->format('H:i') }}</small>
                    </div>
                    @endif
                @empty
                    <p class="text-center text-muted">Chưa có tin nhắn nào. Hãy là người bắt đầu!</p>
                @endforelse
            </div>
            
            <div class="mt-3" style="position: relative; z-index: 1000;">
                @if($isAdminViewer ?? false)
                    {{-- Ô soạn tin của ADMIN: chọn loại tin rồi gửi THẲNG vào khung chat nhóm --}}
                    <form id="send-message-form" data-group-id="{{ $group->group_id }}"
                          method="POST" action="{{ route('admin.chat.message.group') }}">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $group->group_id }}">
                        <div class="input-group">
                            <select name="type" class="form-select" style="max-width: 210px;">
                                <option value="announcement">📢 Thông báo</option>
                                <option value="warning">⚠️ Cảnh báo</option>
                            </select>
                            <input type="text"
                                   name="content"
                                   id="message-input"
                                   class="form-control"
                                   placeholder="Nội dung gửi vào khung chat nhóm (tối đa 1000 ký tự)..."
                                   maxlength="1000"
                                   autocomplete="off"
                                   required>
                            <button type="submit" class="btn btn-primary">Gửi</button>
                        </div>
                        <small class="text-muted">
                            Tin nhắn hiện ngay trong khung chat của nhóm và cộng badge chưa đọc cho thành viên.
                        </small>
                    </form>
                @else
                <form id="send-message-form" data-group-id="{{ $group->group_id }}" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group">
                        <input type="text"
                               name="content"
                               id="message-input"
                               class="form-control"
                               placeholder="Nhập tin nhắn..."
                               autocomplete="off">
                        <input type="file" name="attachment" id="message-attachment"
                               class="form-control" accept="image/*" style="max-width: 200px;">
                        <button type="submit" class="btn btn-primary">Gửi</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    /* CSS giữ nguyên */
    body > div[style*="position: fixed"][style*="bottom"] {
        top: 80px !important;
        bottom: auto !important;
        right: 20px !important;
        z-index: 9999 !important;
    }
    
    #send-message-form {
        position: relative;
        z-index: 10;
    }
</style>
@if(!($isAdminViewer ?? false))
<script>
    // LƯU Ý: bản CHÍNH của renderMessage nằm ở resources/js/chat_listener.js (dùng cho cả
    // Echo realtime lẫn polling). Hàm dưới đây chỉ dùng cho nhánh gửi AJAX khi Echo không
    // khả dụng, nhưng vẫn xử lý đủ các loại tin (kể cả thông báo/cảnh báo của admin)
    // để hai bản không lệch hành vi.
    function renderMessage(message) {
        const chatBox = document.getElementById('chat-box');
        const userId = parseInt(chatBox.dataset.userId);

        // Định dạng thời gian
        const date = new Date(message.created_at);
        const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Thông báo / cảnh báo do ADMIN gửi thẳng vào khung chat nhóm -> hiện giữa khung.
        if (message.type === 'announcement' || message.type === 'warning') {
            const isWarning = message.type === 'warning';

            return `
                <div class="message mb-3 text-center" data-message-id="${message.id ?? ''}">
                    <div class="alert ${isWarning ? 'alert-warning' : 'alert-info'} d-inline-block text-start mb-1"
                         style="max-width: 85%; word-wrap: break-word;">
                        <strong>${isWarning ? '⚠️ Cảnh báo từ Admin' : '📢 Thông báo từ Admin'}</strong>
                        <small class="text-muted">— ${message.user?.name ?? 'Admin'}</small>
                        <div class="mt-1">${message.content ?? ''}</div>
                        <small class="text-muted d-block">${time}</small>
                    </div>
                </div>
            `;
        }

        const isSelf = message.user_id === userId;
        const alignClass = isSelf ? 'text-end' : 'text-start';
        const bgClass = isSelf ? 'bg-primary text-white rounded-start' : 'bg-light text-dark rounded-end border';
        const userName = isSelf ? 'Bạn' : (message.user?.name ?? 'Người dùng không xác định');

        return `
            <div class="message mb-2 ${alignClass}" data-message-id="${message.id ?? ''}">
                <small class="text-muted">${userName}:</small>
                <div class="p-2 ${bgClass} d-inline-block" style="max-width: 70%; word-wrap: break-word;">
                    ${message.content}
                    ${message.attachment_url ? `<img src="${message.attachment_url}" alt="Ảnh đính kèm" class="d-block mt-2 rounded" style="max-width: 240px; max-height: 180px;">` : ''}
                </div>
                <small class="text-muted d-block">${time}</small>
            </div>
        `;
    }

    const groupId = document.getElementById('send-message-form').getAttribute('data-group-id');
    const chatBox = document.getElementById('chat-box');

    // CUỘN XUỐNG CUỐI KHI LOAD (Vẫn cần)
    chatBox.scrollTop = chatBox.scrollHeight;

    // 2. Xử lý gửi tin nhắn AJAX (Vẫn giữ lại)
    document.getElementById('send-message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const contentInput = document.getElementById('message-input');
        const attachmentInput = document.getElementById('message-attachment');
        const content = contentInput.value.trim();
        const hasAttachment = attachmentInput && attachmentInput.files && attachmentInput.files.length > 0;

        if (!content && !hasAttachment) { alert('Vui lòng nhập tin nhắn hoặc chọn ảnh!'); return; }

        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            const tokenInput = form.querySelector('input[name="_token"]');
            if (tokenInput) { csrfToken = tokenInput.value; }
        }

        if (!csrfToken) {
            console.error('CSRF token not found!');
            alert('Lỗi: Không tìm thấy CSRF token. Vui lòng reload trang.');
            return;
        }

        // Gửi dạng multipart để kèm ảnh (FormData)
        const formData = new FormData();
        formData.append('content', content);
        if (hasAttachment) {
            formData.append('attachment', attachmentInput.files[0]);
        }

        fetch(`/groups/${groupId}/chat/send`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => {
            if (response.status === 403) { alert('Bạn không có quyền gửi tin nhắn.'); return null; }
            if (response.status === 422) {
                return response.json().then(err => { throw new Error(err.message || 'Dữ liệu không hợp lệ (ảnh quá lớn hoặc sai định dạng).'); });
            }
            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
            return response.json();
        })
        .then(data => {
            if (data && data.data) {
                contentInput.value = '';
                if (attachmentInput) attachmentInput.value = '';
                
                // Nếu Echo không hoạt động (đã được xử lý ở chat_listener.js), 
                // hiển thị tin nhắn thủ công tại đây
                if (!window.Echo) {
                    const newMessageHtml = renderMessage(data.data);
                    chatBox.insertAdjacentHTML('beforeend', newMessageHtml);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi gửi tin nhắn:', error);
            alert('Gửi tin nhắn thất bại: ' + error.message);
        });
    });
</script>
@else
<script>
    // Admin giám sát: form soạn tin gửi bằng POST thường tới admin.chat.message.group
    // (không AJAX), nên ở đây chỉ cần cuộn khung chat xuống tin nhắn mới nhất.
    (function () {
        const adminChatBox = document.getElementById('chat-box');
        if (adminChatBox) { adminChatBox.scrollTop = adminChatBox.scrollHeight; }
    })();
</script>
@endif
@endpush

@endsection